<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\PasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Enums\SettingGroup;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

/*
| Admin panel (M2). Guests → admin.login; disabled users are signed out (active);
| users with a temporary password must change it first (password.changed);
| owner-only modules sit behind role:owner (PLAN.md §2.2, staff = bookings only).
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'active'])->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::middleware('password.changed')->group(function () {
            Route::get('/', DashboardController::class)->name('dashboard');

            Route::middleware('role:owner')->group(function () {
                Route::redirect('settings', '/admin/settings/'.SettingGroup::General->value)->name('settings.index');
                Route::get('settings/{group}', [SettingController::class, 'edit'])->name('settings.edit');
                Route::put('settings/{group}', [SettingController::class, 'update'])->name('settings.update');

                Route::resource('users', UserController::class)->except(['show', 'destroy']);
                Route::patch('users/{user}/active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
                Route::put('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.reset-password');
            });
        });
    });
});

/*
| TEMPORARY (M0): component/theme preview, local environment only. Remove in M9.
*/
if (app()->environment('local')) {
    Route::get('/design-preview', function () {
        session()->now('success', 'Flash messages render here via x-ui.flash.');

        return view('design-preview');
    })->name('design-preview');
}
