<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AppInsight;
use App\Filament\Widgets\DriverAvailable;
use App\Filament\Widgets\VehicleAvailable;

class DashBoard extends \Filament\Pages\Dashboard
{
    protected static string $routePath = 'dashboard';
    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            AppInsight::class,
            DriverAvailable::class,
            VehicleAvailable::class,
        ];
    }
}
