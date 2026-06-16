<?php

namespace App\Providers;

use App\Models\Store;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CartService::class);
        $this->app->singleton(OrderService::class);
    }

    public function boot(): void
    {
        // Share store with all storefront views
        View::composer('storefront.*', function ($view) {
            try {
                $view->with('store', Store::current());
            } catch (\Exception $e) {
                $view->with('store', null);
            }
        });
    }
}
