<?php

use App\Models\Trip;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Providers\AppServiceProvider;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->driver = Driver::factory()->create(['company_id' => $this->company->id]);
    $this->vehicle = Vehicle::factory()->create(['company_id' => $this->company->id]);
});

describe('Topbar Active Trips Stats', function () {
    it('can render active trips stats view', function () {
        $activeTripsCount = 5;

        $html = view('filament.topbar.active-trips-stats', [
            'activeTripsCount' => $activeTripsCount
        ])->render();

        expect($html)->toContain('Active Trips');
        expect($html)->toContain('5');
        expect($html)->toContain('inline-block');
    });

    it('shows correct active trips count', function () {

        Trip::factory()->active()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        Trip::factory()->completed()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $activeCount = Trip::ongoing()->count();
        expect($activeCount)->toBe(1);

        $html = view('filament.topbar.active-trips-stats', [
            'activeTripsCount' => $activeCount
        ])->render();

        expect($html)->toContain('1 Active Trip');
    });

    it('can access render active trips stats method', function () {
        $provider = new AppServiceProvider(app());
        $method = new ReflectionMethod($provider, 'renderActiveTripsStats');
        $method->setAccessible(true);

        $result = $method->invoke($provider);

        expect($result)->toBeString();
        expect($result)->toContain('Active Trip');
    });

    it('handles plural form correctly', function () {

        $html = view('filament.topbar.active-trips-stats', [
            'activeTripsCount' => 1
        ])->render();
        expect($html)->toContain('1 Active Trip');


        $html = view('filament.topbar.active-trips-stats', [
            'activeTripsCount' => 5
        ])->render();
        expect($html)->toContain('5 Active Trips');
    });
});
