<?php

use App\Models\Trip;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Services\TripScheduler;
use App\Filament\Resources\Trips\Pages\ManageTrips;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;


beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Test Company']);
    $this->driver = Driver::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'John Doe'
    ]);
    $this->vehicle = Vehicle::factory()->create([
        'company_id' => $this->company->id,
        'plate_number' => 'ABC-123'
    ]);

    $this->tripData = [
        'company_id' => $this->company->id,
        'driver_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
        'starts_at' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
        'ends_at' => Carbon::now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
    ];
});

describe('ManageTrips Page', function () {
    it('extends ManageRecords', function () {
        expect(ManageTrips::class)->toExtend(\Filament\Resources\Pages\ManageRecords::class);
    });

    it('has correct resource', function () {
        $reflection = new ReflectionClass(ManageTrips::class);
        $property = $reflection->getProperty('resource');
        $property->setAccessible(true);

        expect($property->getValue())->toBe(\App\Filament\Resources\Trips\TripResource::class);
    });

    it('has create action in header actions', function () {
        $page = new ManageTrips();
        $reflection = new ReflectionClass($page);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);
        $actions = $method->invoke($page);

        expect($actions)->toHaveCount(1);
        expect($actions[0])->toBeInstanceOf(\Filament\Actions\CreateAction::class);
    });
});

describe('Trip Creation via ManageTrips', function () {
    it('can create a trip successfully', function () {
        // Test the actual functionality without mocking the final class
        $page = new ManageTrips();
        $reflection = new ReflectionClass($page);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);
        $actions = $method->invoke($page);
        $createAction = $actions[0];

        // Get the using callback
        $reflection = new ReflectionClass($createAction);
        $property = $reflection->getProperty('using');
        $property->setAccessible(true);
        $usingCallback = $property->getValue($createAction);

        expect($usingCallback)->toBeCallable();

        // Test with actual service - this will create a real trip
        $result = $usingCallback($this->tripData);
        expect($result)->toBeInstanceOf(Trip::class);
        expect($result->company_id)->toBe($this->company->id);
        expect($result->driver_id)->toBe($this->driver->id);
        expect($result->vehicle_id)->toBe($this->vehicle->id);
    });

    it('handles validation exception during trip creation', function () {
        // Create overlapping trip first
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::parse($this->tripData['starts_at']),
            'ends_at' => Carbon::parse($this->tripData['ends_at']),
        ]);

        $page = new ManageTrips();
        $reflection = new ReflectionClass($page);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);
        $actions = $method->invoke($page);
        $createAction = $actions[0];

        // Get the using callback
        $reflection = new ReflectionClass($createAction);
        $property = $reflection->getProperty('using');
        $property->setAccessible(true);
        $usingCallback = $property->getValue($createAction);

        // This should throw validation exception due to overlap
        expect(fn() => $usingCallback($this->tripData))
            ->toThrow(ValidationException::class);
    });

    it('has create action with proper configuration', function () {
        $page = new ManageTrips();
        $reflection = new ReflectionClass($page);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);
        $actions = $method->invoke($page);
        $createAction = $actions[0];

        expect($createAction)->toBeInstanceOf(\Filament\Actions\CreateAction::class);
    });
});

describe('Trip Scheduling Integration', function () {
    it('uses TripScheduler service for creating trips', function () {
        $scheduler = app(TripScheduler::class);

        $start = Carbon::now()->addDay();
        $end = $start->copy()->addHours(2);

        $trip = $scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $start,
            $end
        );

        expect($trip)->toBeInstanceOf(Trip::class);
        expect($trip->company_id)->toBe($this->company->id);
        expect($trip->driver_id)->toBe($this->driver->id);
        expect($trip->vehicle_id)->toBe($this->vehicle->id);
        expect($trip->starts_at->format('Y-m-d H:i'))->toBe($start->format('Y-m-d H:i'));
        expect($trip->ends_at->format('Y-m-d H:i'))->toBe($end->format('Y-m-d H:i'));
    });

    it('prevents overlapping trips', function () {
        $start = Carbon::now()->addDay();
        $end = $start->copy()->addHours(2);

        // Create first trip
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => $start,
            'ends_at' => $end,
        ]);

        $scheduler = app(TripScheduler::class);

        // Try to create overlapping trip
        expect(fn() => $scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $start->copy()->addMinutes(30),
            $end->copy()->addMinutes(30)
        ))->toThrow(ValidationException::class);
    });

    it('validates end time is after start time', function () {
        $scheduler = app(TripScheduler::class);

        $start = Carbon::now()->addDay();
        $end = $start->copy()->subHour(); // End before start

        expect(fn() => $scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $start,
            $end
        ))->toThrow(ValidationException::class);
    });
});

describe('Form Field Dependencies', function () {
    it('driver field depends on company selection', function () {
        $schema = \App\Filament\Resources\Trips\TripResource::form(\Filament\Schemas\Schema::make());
        $components = $schema->getComponents();

        $driverField = collect($components)->first(fn($component) => $component->getName() === 'driver_id');

        expect($driverField)->not->toBeNull();
        expect($driverField->getRelationshipName())->toBe('driver');
        expect($driverField->getRelationshipTitleAttribute())->toBe('name');
    });

    it('vehicle field depends on company selection', function () {
        $schema = \App\Filament\Resources\Trips\TripResource::form(\Filament\Schemas\Schema::make());
        $components = $schema->getComponents();

        $vehicleField = collect($components)->first(fn($component) => $component->getName() === 'vehicle_id');

        expect($vehicleField)->not->toBeNull();
        expect($vehicleField->getRelationshipName())->toBe('vehicle');
        expect($vehicleField->getRelationshipTitleAttribute())->toBe('plate_number');
    });
});
