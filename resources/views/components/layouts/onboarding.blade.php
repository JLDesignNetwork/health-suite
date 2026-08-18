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
<body class="min-h-full bg-gray-50 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">iHealth</h1>
            <p class="mt-1 text-sm text-gray-500">Set up your health profile</p>
        </div>
        <div class="w-full max-w-2xl">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
