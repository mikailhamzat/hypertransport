<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AppInsight extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';
    protected ?string $heading = 'App Insights';
    protected ?string $description = 'Key performance indicators for the app';


    protected function getStats(): array
    {
        return [
            $this->getActiveTripsStats(),
            $this->getAvailableDriversStats(),
            $this->getAvailableVehiclesStats(),
            $this->getTripsCompletedThisMonthStats(),
        ];
    }

    private function getActiveTripsStats(): Stat
    {
        $activeTripsCount = Cache::remember('stats.active_trips', 60, function () {
            return Trip::ongoing()->count();
        });

        return Stat::make('Active Trips', number_format($activeTripsCount))
            ->description('Currently ongoing trips')
            ->descriptionIcon('heroicon-m-truck')
            ->color('primary');
    }

    private function getAvailableDriversStats(): Stat
    {
        $availableDriversCount = Cache::remember('stats.available_drivers', 60, function () {
            $now = now();
            return Driver::availableBetween($now, $now)->count();
        });

        return Stat::make('Available Drivers', number_format($availableDriversCount))
            ->description('Drivers not on active trips')
            ->descriptionIcon('heroicon-m-user-group')
            ->color('success');
    }

    private function getAvailableVehiclesStats(): Stat
    {
        $availableVehiclesCount = Cache::remember('stats.available_vehicles', 60, function () {
            $now = now();
            return Vehicle::availableBetween($now, $now)->count();
        });

        return Stat::make('Available Vehicles', number_format($availableVehiclesCount))
            ->description('Vehicles not on active trips')
            ->descriptionIcon('heroicon-m-truck')
            ->color('success');
    }

    private function getTripsCompletedThisMonthStats(): Stat
    {
        $completedTripsCount = Cache::remember('stats.completed_trips_month', 60, function () {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            return Trip::whereBetween('completed_at', [$startOfMonth, $endOfMonth])
                ->whereNotNull('completed_at')
                ->count();
        });

        return Stat::make('Trips Completed This Month', number_format($completedTripsCount))
            ->description('Completed in ' . Carbon::now()->format('F Y'))
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('success');
    }
}
