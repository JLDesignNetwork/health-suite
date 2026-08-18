@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' · ' : '' }}iHealth</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" defer></script>
</head>
<body class="min-h-full bg-neutral-50 text-neutral-900 antialiased">
    <header class="border-b border-neutral-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-tight">iHealth</a>
                @auth
                    <nav class="flex items-center gap-4 text-sm">
                        <a href="{{ route('dashboard') }}"
                            @class(['font-medium', 'text-indigo-600' => request()->routeIs('dashboard'), 'text-gray-500 hover:text-gray-900' => !request()->routeIs('dashboard')])>
                            Dashboard
                        </a>
                        <a href="{{ route('history') }}"
                            @class(['font-medium', 'text-indigo-600' => request()->routeIs('history'), 'text-gray-500 hover:text-gray-900' => !request()->routeIs('history')])>
                            History
                        </a>
                        <a href="{{ route('profile') }}"
                            @class(['font-medium', 'text-indigo-600' => request()->routeIs('profile'), 'text-gray-500 hover:text-gray-900' => !request()->routeIs('profile')])>
                            Profile
                        </a>
                        <a href="{{ route('health-record') }}"
                            @class(['font-medium', 'text-indigo-600' => request()->routeIs('health-record'), 'text-gray-500 hover:text-gray-900' => !request()->routeIs('health-record')])>
                            Health Record
                        </a>
                        <a href="{{ route('kitchen') }}"
                            @class(['font-medium', 'text-indigo-600' => request()->routeIs('kitchen'), 'text-gray-500 hover:text-gray-900' => !request()->routeIs('kitchen')])>
                            Kitchen
                        </a>
                        <a href="{{ route('ai') }}"
                            @class(['font-medium', 'text-indigo-600' => request()->routeIs('ai'), 'text-gray-500 hover:text-gray-900' => !request()->routeIs('ai')])>
                            AI Assistant
                        </a>
                        <a href="{{ route('settings') }}"
                            @class(['font-medium', 'text-indigo-600' => request()->routeIs('settings'), 'text-gray-500 hover:text-gray-900' => !request()->routeIs('settings')])>
                            Settings
                        </a>
                    </nav>
                @endauth
            </div>

            @auth
                <form method="POST" action="{{ route('logout') }}" class="flex items-center gap-3">
                    @csrf
                    <span class="text-sm text-neutral-600">{{ auth()->user()->name }}</span>
                    <button type="submit" class="rounded-md border border-neutral-300 px-3 py-1.5 text-sm font-medium hover:bg-neutral-100">
                        Log out
                    </button>
                </form>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        {{ $slot }}
    </main>
</body>
</html>
