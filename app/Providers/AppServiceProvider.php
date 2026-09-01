<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;
use App\Models\Order;

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
        // Use Bootstrap 5 pagination to match frontend theme (Tailwind is default in Laravel 12)
        Paginator::useBootstrapFive();
        // Share pending orders with topbar layout
        View::composer('layouts.topbar', function ($view) {
            $pendingOrdersQuery = Order::where('order_status', 'pending');
            $pendingOrdersCountQuery = Order::where('order_status', 'pending');
            $user = auth()->user();

            if ($user
                && $user->user_type === 'staff'
                && ! $user->hasRole('Super Admin')
                && (
                    $user->hasAnyRole(['Moderator', 'Manager'])
                    || $user->canAny([
                        'moderator-order-management.show',
                        'moderator-order-management.create',
                    ])
                )
            ) {
                $pendingOrdersQuery->where('created_by_id', $user->id);
                $pendingOrdersCountQuery->where('created_by_id', $user->id);
            }

            $pendingOrders = $pendingOrdersQuery
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            $pendingOrdersCount = $pendingOrdersCountQuery->count();
            
            $view->with([
                'pendingOrders' => $pendingOrders,
                'pendingOrdersCount' => $pendingOrdersCount
            ]);
        });
    }
}
