<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 
    ];

    public function boot()
    {
        // Admin gates
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });

        // Manager gates
        Gate::define('manage-products', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        Gate::define('manage-categories', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        // Stock worker gates
        Gate::define('adjust-stock', function (User $user) {
            return in_array($user->role, ['admin', 'manager', 'stock_worker']);
        });

        Gate::define('view-products', function (User $user) {
            return true; // All authenticated users can view
        });

        Gate::define('view-reports', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });
    }
}