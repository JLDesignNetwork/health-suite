<?php

use App\Services\AiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('AI Assistant')]
class extends Component {
    /** @var array<int, array{role: string, content: string}> */
    public array $messages = [];
    public string $input   = '';
    public string $error   = '';

    public function mount(): void
    {
        $svc = app(AiService::class);
        if (! $svc->isConfigured()) {
            $this->error = 'not_configured';
        }
    }

    public function send(): void
    {
        $this->error = '';

        $this->validate(['input' => ['required', 'string', 'max:2000']]);

        $userMessage = trim($this->input);
        $this->input = '';

        $this->messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $svc      = app(AiService::class);
            $response = $svc->chat(auth()->user(), $this->messages);

            $this->messages[] = ['role' => 'assistant', 'content' => $response];
        } catch (\Throwable $e) {
            $this->messages[] = ['role' => 'assistant', 'content' => '⚠️ Error: '.$e->getMessage()];
        }
    }

    public function clearConversation(): void
    {
        $this->messages = [];
        $this->error    = '';
    }
}; ?>

<div class="flex flex-col h-full space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">AI Assistant</h1>
            <p class="text-xs text-gray-400 mt-0.5">Your full health profile is included as context on every message.</p>
        </div>
        @if (count($messages) > 0)
            <button wire:click="clearConversation"
                class="text-xs text-gray-400 hover:text-red-500 border border-gray-200 rounded-lg px-3 py-1.5 transition-colors">
                Clear conversation
            </button>
        @endif
    </div>

    @if ($error === 'not_configured')
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center">
            <p class="text-sm font-medium text-amber-800">AI Assistant is not configured yet.</p>
            <p class="text-xs text-amber-600 mt-1">Go to <a href="{{ route('settings') }}" wire:navigate class="underline">Settings</a> to add your API key and enable it.</p>
        </div>
    @else
        {{-- Conversation --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm flex-1 overflow-y-auto p-6 space-y-4 min-h-96">
            @if (count($messages) === 0)
                <div class="flex flex-col items-center justify-center h-full text-center py-12 space-y-2">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-700">Ask me anything about your health</p>
                    <p class="text-xs text-gray-400 max-w-xs">I have full context of your conditions, medications, allergies, goals, and recent records.</p>
                    <div class="mt-4 flex flex-wrap gap-2 justify-center">
                        @foreach (['What should I eat to reach my weight goal?', 'Review my current medications for interactions', 'Suggest an exercise routine for my conditions', 'How is my blood pressure trending?'] as $suggestion)
                            <button wire:click="$set('input', '{{ $suggestion }}')"
                                class="text-xs border border-gray-200 rounded-full px-3 py-1 text-gray-600 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-colors">
                                {{ $suggestion }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @else
                @foreach ($messages as $message)
                    <div @class(['flex', 'justify-end' => $message['role'] === 'user', 'justify-start' => $message['role'] === 'assistant'])>
                        <div @class([
                            'max-w-2xl rounded-2xl px-4 py-3 text-sm leading-relaxed',
                            'bg-indigo-600 text-white rounded-br-sm' => $message['role'] === 'user',
                            'bg-gray-100 text-gray-800 rounded-bl-sm' => $message['role'] === 'assistant',
                        ])>
                            {!! nl2br(e($message['content'])) !!}
                        </div>
                    </div>
                @endforeach

                <div wire:loading wire:target="send" class="flex justify-start">
                    <div class="bg-gray-100 rounded-2xl rounded-bl-sm px-4 py-3">
                        <span class="flex gap-1 items-center">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                        </span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Input --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
            <form wire:submit="send" class="flex gap-3">
                <input type="text" wire:model="input"
                    placeholder="Ask about nutrition, exercise, medications, your health data…"
                    autocomplete="off"
                    class="flex-1 rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    wire:loading.attr="disabled" wire:target="send">
                @error('input') <span class="text-xs text-red-500 self-center">{{ $message }}</span> @enderror
                <button type="submit"
                    wire:loading.attr="disabled" wire:target="send"
                    class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 transition-colors">
                    <span wire:loading.remove wire:target="send">Send</span>
                    <span wire:loading wire:target="send">…</span>
                </button>
            </form>
            <p class="mt-2 text-xs text-gray-400">AI responses are informational only — always consult a licensed healthcare professional for medical decisions.</p>
        </div>
    @endif
</div>
