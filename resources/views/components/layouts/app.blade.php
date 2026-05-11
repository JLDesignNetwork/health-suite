@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' · ' : '' }}iHealth</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-neutral-50 text-neutral-900 antialiased">
    <header class="border-b border-neutral-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-tight">iHealth</a>

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
