<?php

namespace App\Providers;

use App\Models\Delivery;
use App\Models\Tenant;
use App\Observers\DeliveryObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Delivery::observe(DeliveryObserver::class);

        RateLimiter::for('api', function (Request $request) {
            /** @var Tenant|null $tenant */
            $tenant = app()->has('tenant') ? app('tenant') : null;

            if ($tenant) {
                return Limit::perMinute($tenant->rate_limit_per_minute)
                    ->by("tenant:{$tenant->id}");
            }

            // Unauthenticated / no tenant: strict default
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
