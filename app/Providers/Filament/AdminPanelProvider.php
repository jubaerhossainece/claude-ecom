<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\CustomerAcquisitionChartWidget;
use App\Filament\Widgets\OperationsOverviewWidget;
use App\Filament\Widgets\OrderStatusChartWidget;
use App\Filament\Widgets\PaymentMethodChartWidget;
use App\Filament\Widgets\RecentCustomersWidget;
use App\Filament\Widgets\RecentOrdersWidget;
use App\Filament\Widgets\RevenueByCategoryChartWidget;
use App\Filament\Widgets\SalesChartWidget;
use App\Filament\Widgets\SalesOverviewWidget;
use App\Filament\Widgets\TopSellingProductsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Green,
            ])
            ->brandName('Store Admin')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                SalesOverviewWidget::class,
                OperationsOverviewWidget::class,
                SalesChartWidget::class,
                OrderStatusChartWidget::class,
                PaymentMethodChartWidget::class,
                RevenueByCategoryChartWidget::class,
                CustomerAcquisitionChartWidget::class,
                RecentOrdersWidget::class,
                TopSellingProductsWidget::class,
                RecentCustomersWidget::class,
            ])
            ->navigationGroups([
                'Catalog',
                'Sales',
                'Customers',
                'Configuration',
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
