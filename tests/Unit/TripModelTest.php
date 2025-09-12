<?php

use App\Models\Trip;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Enums\Status;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->driver = Driver::factory()->create(['company_id' => $this->company->id]);
    $this->vehicle = Vehicle::factory()->create(['company_id' => $this->company->id]);
});

describe('Trip Model', function () {
    it('can be created with factory', function () {
        $trip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);
        
        expect($trip)->toBeInstanceOf(Trip::class);
        expect($trip->company_id)->toBe($this->company->id);
        expect($trip->driver_id)->toBe($this->driver->id);
        expect($trip->vehicle_id)->toBe($this->vehicle->id);
    });

    it('has correct fillable attributes', function () {
        $trip = new Trip();
        
        // Test that we can mass assign these attributes
        $data = [
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now(),
            'ends_at' => Carbon::now()->addHours(2),
        ];
        
        $trip->fill($data);
        
        expect($trip->company_id)->toBe($data['company_id']);
        expect($trip->driver_id)->toBe($data['driver_id']);
        expect($trip->vehicle_id)->toBe($data['vehicle_id']);
    });

    it('casts datetime fields correctly', function () {
        $trip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => '2024-01-01 10:00:00',
            'ends_at' => '2024-01-01 12:00:00',
            'completed_at' => '2024-01-01 11:30:00',
            'cancelled_at' => null,
        ]);
        
        expect($trip->starts_at)->toBeInstanceOf(Carbon::class);
        expect($trip->ends_at)->toBeInstanceOf(Carbon::class);
        expect($trip->completed_at)->toBeInstanceOf(Carbon::class);
        expect($trip->cancelled_at)->toBeNull();
    });
});

describe('Trip Relationships', function () {
    it('belongs to a company', function () {
        $trip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);
        
        expect($trip->company)->toBeInstanceOf(Company::class);
        expect($trip->company->id)->toBe($this->company->id);
    });

    it('belongs to a driver', function () {
        $trip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);
        
        expect($trip->driver)->toBeInstanceOf(Driver::class);
        expect($trip->driver->id)->toBe($this->driver->id);
    });

    it('belongs to a vehicle', function () {
        $trip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);
        
        expect($trip->vehicle)->toBeInstanceOf(Vehicle::class);
        expect($trip->vehicle->id)->toBe($this->vehicle->id);
    });
});

describe('Trip Status Attribute', function () {
    it('returns CANCELLED when cancelled_at is set', function () {
        $trip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'cancelled_at' => Carbon::now(),
        ]);
        
        expect($trip->status)->toBe(Status::CANCELLED);
    });

    it('returns COMPLETED when completed_at is set', function () {
        $trip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'completed_at' => Carbon::now(),
            'cancelled_at' => null,
        ]);
        
        expect($trip->status)->toBe(Status::COMPLETED);
    });

    it('returns SCHEDULED when starts_at is in the future', function () {
        $trip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addDay()->addHours(2),
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
        
        expect($trip->status)->toBe(Status::SCHEDULED);
    });

    it('returns COMPLETED when ends_at is in the past', function () {
        $trip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->subHours(3),
            'ends_at' => Carbon::now()->subHour(),
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
        
        expect($trip->status)->toBe(Status::COMPLETED);
    });

    it('returns ACTIVE when trip is currently ongoing', function () {
        $trip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->subHour(),
            'ends_at' => Carbon::now()->addHour(),
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
        
        expect($trip->status)->toBe(Status::ACTIVE);
    });
});

describe('Trip Scopes', function () {
    beforeEach(function () {
        // Create test trips with different time ranges
        $this->pastTrip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->subDays(2),
            'ends_at' => Carbon::now()->subDay(),
        ]);

        $this->currentTrip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->subHour(),
            'ends_at' => Carbon::now()->addHour(),
        ]);

        $this->futureTrip = Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addDay()->addHours(2),
        ]);
    });

    it('can find overlapping trips', function () {
        $start = Carbon::now()->subMinutes(30);
        $end = Carbon::now()->addMinutes(30);
        
        $overlappingTrips = Trip::overlapping($start, $end)->get();
        
        expect($overlappingTrips)->toHaveCount(1);
        expect($overlappingTrips->first()->id)->toBe($this->currentTrip->id);
    });

    it('can find ongoing trips', function () {
        $ongoingTrips = Trip::ongoing()->get();
        
        expect($ongoingTrips)->toHaveCount(1);
        expect($ongoingTrips->first()->id)->toBe($this->currentTrip->id);
    });

    it('overlapping scope works with exact boundaries', function () {
        // Test trip that starts exactly when another ends
        $start = $this->pastTrip->ends_at;
        $end = $start->copy()->addHours(2);
        
        $overlappingTrips = Trip::overlapping($start, $end)->get();
        
        // Should not include the past trip since it ends exactly when new trip starts
        expect($overlappingTrips)->not->toContain($this->pastTrip);
    });
});
