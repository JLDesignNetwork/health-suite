<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
        Artisan::call('migrate', ['--force' => true]);

        Window::open()
            ->title('iHealth')
            ->width(1280)
            ->height(800)
            ->minWidth(900)
            ->minHeight(600)
            ->rememberState();
    }

    public function phpIni(): array
    {
        return [];
    }
}
