<?php

namespace App\Providers;

use App\Models\Trip;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            fn(): string => $this->renderActiveTripsStats(),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
    }

    private function renderActiveTripsStats(): string
    {
        $activeTripsCount = Cache::remember('stats.active_trips', 60, function () {
            return Trip::ongoing()->count();
        });

        return view('filament.topbar.active-trips-stats', [
            'activeTripsCount' => $activeTripsCount
        ])->render();
    }
}
