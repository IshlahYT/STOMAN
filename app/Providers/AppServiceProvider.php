<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bindIf('request', function ($app) {
            return $app->bound(Request::class)
                ? $app->make(Request::class)
                : Request::create('/');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-storage', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });
    }
}
