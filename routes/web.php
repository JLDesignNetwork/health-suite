<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\HistoryExportController;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route(
        Setting::get('auth_mode', 'login') === 'household' ? 'household' : 'login'
    );
});

Route::middleware('guest')->group(function (): void {
    Volt::route('/login', 'auth.login')->name('login');
    Volt::route('/register', 'auth.register')->name('register');
    Volt::route('/household', 'household')->name('household');
});

Route::middleware('auth')->group(function (): void {
    Volt::route('/onboarding', 'onboarding')->name('onboarding');
    Volt::route('/dashboard', 'dashboard')->name('dashboard');
    Volt::route('/history', 'history')->name('history');
    Volt::route('/profile', 'profile')->name('profile');
    Volt::route('/health-record', 'health-record')->name('health-record');
    Volt::route('/kitchen', 'kitchen')->name('kitchen');
    Volt::route('/ai', 'ai')->name('ai');
    Volt::route('/settings', 'settings')->name('settings');
    Route::get('/history/export', HistoryExportController::class)->name('history.export');
    Route::get('/backup/export', [BackupController::class, 'export'])->name('backup.export');
    Route::post('/backup/import', [BackupController::class, 'import'])->name('backup.import');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route(
            Setting::get('auth_mode', 'login') === 'household' ? 'household' : 'login'
        );
    })->name('logout');
});
