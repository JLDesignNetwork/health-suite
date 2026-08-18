<?php

use App\Models\Setting;
use App\Services\AiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Settings')]
class extends Component {
    // Authentication
    public string $authMode = 'login';

    // AI
    public bool   $aiEnabled         = false;
    public string $aiProvider        = 'anthropic';
    public string $aiModel           = 'claude-3-7-sonnet-20250219';
    public string $aiApiKey          = '';
    public string $aiCustomUrl       = '';
    public string $aiTestResult      = '';
    public bool   $aiTestOk          = false;
    public array  $availableModels   = [];
    public string $modelFetchError   = '';

    public function mount(): void
    {
        $this->authMode = Setting::get('auth_mode', 'login');

        $this->aiEnabled   = Setting::get('ai_enabled', '0') === '1';
        $this->aiProvider  = Setting::get('ai_provider', 'anthropic');
        $this->aiCustomUrl = Setting::get('ai_custom_base_url', '');

        $svc = app(AiService::class);
        $this->aiModel  = Setting::get('ai_model', $svc->defaultModel());
        $this->aiApiKey = ''; // never pre-fill for security
    }

    public function save(): void
    {
        $this->validate(['authMode' => 'required|in:login,household']);
        Setting::set('auth_mode', $this->authMode);
        $this->dispatch('setting-saved');
    }

    public function saveAi(): void
    {
        $this->validate([
            'aiProvider'  => ['required', 'in:anthropic,google,custom'],
            'aiModel'     => ['required', 'string', 'max:100'],
            'aiCustomUrl' => ['nullable', 'url', 'max:500'],
        ]);

        Setting::set('ai_enabled', $this->aiEnabled ? '1' : '0');
        Setting::set('ai_provider', $this->aiProvider);
        Setting::set('ai_model', $this->aiModel);
        Setting::set('ai_custom_base_url', $this->aiCustomUrl ?: '');

        if (filled($this->aiApiKey)) {
            Setting::set('ai_api_key', encrypt($this->aiApiKey));
            $this->aiApiKey = ''; // clear after saving
        }

        $this->dispatch('ai-saved');
    }

    public function updatedAiProvider(): void
    {
        $defaults = [
            'anthropic' => 'claude-3-7-sonnet-20250219',
            'google'    => 'gemini-2.5-flash',
            'custom'    => '',
        ];
        $this->aiModel         = $defaults[$this->aiProvider] ?? '';
        $this->availableModels = [];
        $this->modelFetchError = '';
    }

    public function fetchModels(): void
    {
        $this->availableModels = [];
        $this->modelFetchError = '';

        try {
            $svc = app(AiService::class);
            if (! $svc->apiKey()) {
                $this->modelFetchError = 'Save your API key first, then fetch models.';
                return;
            }
            $models = $svc->fetchAvailableModels();
            if (empty($models)) {
                $this->modelFetchError = 'No models returned. Custom providers are not supported for model listing.';
                return;
            }
            $this->availableModels = $models;
        } catch (\Throwable $e) {
            $this->modelFetchError = 'Error: '.$e->getMessage();
        }
    }

    public function testAiConnection(): void
    {
        $this->aiTestResult = '';
        $this->aiTestOk     = false;

        try {
            $svc = app(AiService::class);
            if (! $svc->isConfigured()) {
                $this->aiTestResult = 'Save your AI settings and API key first.';
                return;
            }
            $result = $svc->testConnection();
            $this->aiTestOk     = true;
            $this->aiTestResult = $result;
        } catch (\Throwable $e) {
            $this->aiTestResult = 'Error: '.$e->getMessage();
        }
    }
}; ?>

<div class="space-y-6">
    <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Settings</h1>

    {{-- Authentication --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-6">
        <div>
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-4">Authentication</h2>
            <fieldset class="space-y-3">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="radio" wire:model="authMode" value="login"
                        class="mt-0.5 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                    <div>
                        <span class="block text-sm font-medium text-gray-900">Login Mode</span>
                        <span class="block text-xs text-gray-500 mt-0.5">Each user signs in with their email and password. (Default)</span>
                    </div>
                </label>
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="radio" wire:model="authMode" value="household"
                        class="mt-0.5 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                    <div>
                        <span class="block text-sm font-medium text-gray-900">Household Mode</span>
                        <span class="block text-xs text-gray-500 mt-0.5">Skip login — pick a profile from the household screen. No password required.</span>
                    </div>
                </label>
            </fieldset>
            @error('authMode') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
            <button wire:click="save"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Save Settings
            </button>
            <span x-data="{ show: false }" x-on:setting-saved.window="show = true; setTimeout(() => show = false, 2500)"
                x-show="show" x-transition.opacity class="text-sm text-green-600">Saved.</span>
        </div>
    </div>

    {{-- AI Assistant --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide">AI Assistant</h2>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model="aiEnabled" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700">Enable</span>
            </label>
        </div>

        <p class="text-xs text-gray-500">The AI assistant has access to your full health profile (conditions, medications, allergies, measurements) as background context on every query. Requires an internet connection.</p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
                <select wire:model.live="aiProvider"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="anthropic">Anthropic (Claude)</option>
                    <option value="google">Google (Gemini)</option>
                    <option value="custom">Custom (OpenAI-compatible)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Model
                    @if ($aiProvider !== 'custom')
                        <button type="button" wire:click="fetchModels"
                            wire:loading.attr="disabled" wire:target="fetchModels"
                            class="ml-2 text-xs font-normal text-indigo-500 hover:text-indigo-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="fetchModels">↻ Fetch available models</span>
                            <span wire:loading wire:target="fetchModels">Fetching…</span>
                        </button>
                    @endif
                </label>
                @if (!empty($availableModels))
                    <select wire:model="aiModel"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach ($availableModels as $id => $name)
                            <option value="{{ $id }}">{{ $name }} ({{ $id }})</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" wire:model="aiModel"
                        placeholder="{{ $aiProvider === 'anthropic' ? 'claude-3-7-sonnet-20250219' : ($aiProvider === 'google' ? 'gemini-2.5-flash' : 'model-name') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @endif
                @if ($modelFetchError)
                    <p class="mt-1 text-xs text-red-600">{{ $modelFetchError }}</p>
                @endif
                @error('aiModel') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                API Key
                <span class="font-normal text-gray-400 ml-1">(leave blank to keep existing)</span>
            </label>
            <input type="password" wire:model="aiApiKey" autocomplete="off"
                placeholder="Paste your API key here…"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        @if ($aiProvider === 'custom')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Base URL</label>
                <input type="url" wire:model="aiCustomUrl" placeholder="https://your-endpoint.com/v1"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('aiCustomUrl') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        @if ($aiTestResult)
            <div @class(['rounded-lg px-3 py-2 text-sm', 'bg-green-50 text-green-700' => $aiTestOk, 'bg-red-50 text-red-700' => !$aiTestOk])>
                {{ $aiTestResult }}
            </div>
        @endif

        <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
            <button wire:click="saveAi"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Save AI Settings
            </button>
            <button wire:click="testAiConnection" wire:loading.attr="disabled"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50">
                <span wire:loading.remove wire:target="testAiConnection">Test Connection</span>
                <span wire:loading wire:target="testAiConnection">Testing…</span>
            </button>
            <span x-data="{ show: false }" x-on:ai-saved.window="show = true; setTimeout(() => show = false, 2500)"
                x-show="show" x-transition.opacity class="text-sm text-green-600">Saved.</span>
        </div>
    </div>

    {{-- Backup & Restore --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide">Backup & Restore</h2>
        <p class="text-xs text-gray-500">Export all household data (users, health records, meals, medications, ingredients, recipes, settings) as a JSON file. Use the same file to restore on this or another device.</p>

        @if (session('backup_ok'))
            <div class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ session('backup_ok') }}
            </div>
        @endif
        @if (session('backup_error'))
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ session('backup_error') }}
            </div>
        @endif

        {{-- Export --}}
        <div>
            <p class="text-sm font-medium text-gray-700 mb-2">Export</p>
            <a href="{{ route('backup.export') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download Backup
            </a>
        </div>

        {{-- Import --}}
        <div>
            <p class="text-sm font-medium text-gray-700 mb-1">Restore from Backup</p>
            <p class="text-xs text-amber-600 mb-3">⚠️ Restoring will overwrite existing data for any matching users. Settings will be merged.</p>
            <form action="{{ route('backup.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                @csrf
                <input type="file" name="backup_file" accept=".json" required
                    class="text-sm text-gray-600 file:mr-3 file:rounded-lg file:border file:border-gray-300 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 file:hover:bg-gray-50 file:cursor-pointer">
                <button type="submit"
                    onclick="return confirm('This will overwrite existing data for all matching users. Continue?')"
                    class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                    Restore Backup
                </button>
            </form>
            @error('backup_file') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
