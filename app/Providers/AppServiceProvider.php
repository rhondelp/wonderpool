<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SettingService;
use App\Services\PublicContentService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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
        // Bookings: owners and staff (D-029); finer rules in BookingPolicy / PaymentPolicy.
        Gate::define('manage-bookings', fn (User $user): bool => $user->is_active);

        // Public booking endpoints (D-028), keyed by client IP.
        RateLimiter::for('booking-read', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('booking-write', fn (Request $request) => [
            Limit::perMinute(5)->by('min:'.$request->ip()),
            Limit::perDay(20)->by('day:'.$request->ip()),
        ]);
        RateLimiter::for('proof-upload', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('track', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Site name, contact details, socials and SEO defaults for every public page.
        View::composer(['layouts.public', 'partials.head', 'public.*'], function ($view): void {
            $view->with('site', $this->app->make(PublicContentService::class)->site());
        });
    }
}
