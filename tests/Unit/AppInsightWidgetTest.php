<?php

use App\Models\Trip;
use App\Models\Driver;
use App\Models\Company;
use App\Models\Vehicle;
use App\Filament\Widgets\AppInsight;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->driver = Driver::factory()->create(['company_id' => $this->company->id]);
    $this->vehicle = Vehicle::factory()->create(['company_id' => $this->company->id]);
    $this->widget = new AppInsight();

    // Clear cache before each test
    Cache::flush();
});

describe('AppInsight Widget', function () {
    it('can be instantiated', function () {
        expect($this->widget)->toBeInstanceOf(AppInsight::class);
    });

    it('has correct polling interval', function () {
        $reflection = new ReflectionClass($this->widget);
        $property = $reflection->getProperty('pollingInterval');
        $property->setAccessible(true);

        expect($property->getValue($this->widget))->toBe('60s');
    });

    it('returns array of stats', function () {
        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($this->widget);

        expect($stats)->toBeArray();
        expect($stats)->toHaveCount(4);
    });
});

describe('Active Trips Stats', function () {
    it('counts active trips correctly', function () {
        // Create active trips
        Trip::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->subHour(),
            'ends_at' => Carbon::now()->addHour(),
        ]);

        // Create non-active trips
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addDay()->addHours(2),
        ]);

        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($this->widget);
        $activeTripsStats = $stats[0];

        expect($activeTripsStats->getLabel())->toBe('Active Trips');
        expect($activeTripsStats->getValue())->toBe('3');
        expect($activeTripsStats->getDescription())->toBe('Currently ongoing trips');
        expect($activeTripsStats->getColor())->toBe('primary');
    });

    it('caches active trips count', function () {
        Trip::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->subHour(),
            'ends_at' => Carbon::now()->addHour(),
        ]);

        // First call should cache the result
        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats1 = $method->invoke($this->widget);

        // Create more active trips
        Trip::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->subHour(),
            'ends_at' => Carbon::now()->addHour(),
        ]);

        // Second call should return cached result (still 2)
        $stats2 = $method->invoke($this->widget);

        expect($stats1[0]->getValue())->toBe('2');
        expect($stats2[0]->getValue())->toBe('2'); // Should be cached

        // Clear cache and check again
        Cache::forget('stats.active_trips');
        $stats3 = $method->invoke($this->widget);
        expect($stats3[0]->getValue())->toBe('5'); // Should reflect new count
    });
});

describe('Available Drivers Stats', function () {
    it('counts available drivers correctly', function () {
        // Create additional drivers (total: 3 drivers)
        Driver::factory()->create(['company_id' => $this->company->id]);
        Driver::factory()->create(['company_id' => $this->company->id]);

        // Create additional vehicles for the trips
        Vehicle::factory()->create(['company_id' => $this->company->id]);
        Vehicle::factory()->create(['company_id' => $this->company->id]);

        // Make one driver busy with an active trip
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->subHour(),
            'ends_at' => Carbon::now()->addHour(),
        ]);

        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($this->widget);
        $availableDriversStats = $stats[1];

        expect($availableDriversStats->getLabel())->toBe('Available Drivers');
        expect($availableDriversStats->getValue())->toBe('2'); // 2 out of 3 drivers available
        expect($availableDriversStats->getDescription())->toBe('Drivers not on active trips');
        expect($availableDriversStats->getColor())->toBe('success');
    });
});

describe('Available Vehicles Stats', function () {
    it('counts available vehicles correctly', function () {
        // Create additional vehicles (total: 3 vehicles)
        Vehicle::factory()->create(['company_id' => $this->company->id]);
        Vehicle::factory()->create(['company_id' => $this->company->id]);

        // Create additional driver for the trip
        $driver2 = Driver::factory()->create(['company_id' => $this->company->id]);

        // Make one vehicle busy with an active trip
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $driver2->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->subHour(),
            'ends_at' => Carbon::now()->addHour(),
        ]);

        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($this->widget);
        $availableVehiclesStats = $stats[2];

        expect($availableVehiclesStats->getLabel())->toBe('Available Vehicles');
        expect($availableVehiclesStats->getValue())->toBe('2'); // 2 out of 3 vehicles available
        expect($availableVehiclesStats->getDescription())->toBe('Vehicles not on active trips');
        expect($availableVehiclesStats->getColor())->toBe('success');
    });
});

describe('Trips Completed This Month Stats', function () {
    it('counts completed trips this month correctly', function () {
        $startOfMonth = Carbon::now()->startOfMonth();

        // Create trips completed this month
        Trip::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => $startOfMonth->copy()->addDays(5),
            'ends_at' => $startOfMonth->copy()->addDays(5)->addHours(2),
            'completed_at' => $startOfMonth->copy()->addDays(5)->addHours(2),
        ]);

        // Create trips completed last month (should not be counted)
        Trip::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => $startOfMonth->copy()->subMonth(),
            'ends_at' => $startOfMonth->copy()->subMonth()->addHours(2),
            'completed_at' => $startOfMonth->copy()->subMonth()->addHours(2),
        ]);

        // Create trips not completed (should not be counted)
        Trip::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => $startOfMonth->copy()->addDays(10),
            'ends_at' => $startOfMonth->copy()->addDays(10)->addHours(2),
            'completed_at' => null,
        ]);

        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($this->widget);
        $completedTripsStats = $stats[3];

        expect($completedTripsStats->getLabel())->toBe('Trips Completed This Month');
        expect($completedTripsStats->getValue())->toBe('5');
        expect($completedTripsStats->getDescription())->toBe('Completed in ' . Carbon::now()->format('F Y'));
        expect($completedTripsStats->getColor())->toBe('success');
    });

    it('handles zero completed trips this month', function () {
        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($this->widget);
        $completedTripsStats = $stats[3];

        expect($completedTripsStats->getValue())->toBe('0');
    });
});

describe('Caching Behavior', function () {
    it('caches all stats for 60 seconds', function () {
        // Create test data
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->subHour(),
            'ends_at' => Carbon::now()->addHour(),
        ]);

        // First call should cache all results
        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $method->invoke($this->widget);

        // Verify cache keys exist
        expect(Cache::has('stats.active_trips'))->toBeTrue();
        expect(Cache::has('stats.available_drivers'))->toBeTrue();
        expect(Cache::has('stats.available_vehicles'))->toBeTrue();
        expect(Cache::has('stats.completed_trips_month'))->toBeTrue();
    });
});
