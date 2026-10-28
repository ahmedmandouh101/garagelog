<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('authentication', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
        RateLimiter::for('account-recovery', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perMinute(3)->by(hash('sha256', strtolower((string) $request->input('email')))),
        ]);
        RateLimiter::for('verification-email', fn (Request $request) => Limit::perMinute(1)->by($request->user()->id));
    }
}
