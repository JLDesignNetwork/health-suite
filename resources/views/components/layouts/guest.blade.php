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
<body class="flex min-h-full items-center justify-center bg-neutral-50 px-4 py-12 antialiased">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-neutral-900">iHealth</h1>
            <p class="mt-1 text-sm text-neutral-500">Personalised health monitoring</p>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
