<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SettingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager;

/**
 * Container bindings and authorization gates.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ActivityLogger::class);
        $this->app->singleton(SettingService::class);
        $this->app->singleton(ImageManager::class, fn (): ImageManager => ImageManager::gd());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Owner-only areas (PLAN.md §2.2: staff = bookings only). Route groups also use `role:owner`.
        Gate::define('manage-settings', fn (User $user): bool => $user->isOwner());
        Gate::define('manage-users', fn (User $user): bool => $user->isOwner());
        Gate::define('manage-content', fn (User $user): bool => $user->isOwner());
        Gate::define('view-financials', fn (User $user): bool => $user->isOwner());
    }
}
