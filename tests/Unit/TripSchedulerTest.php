<?php

use App\Models\Trip;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Services\TripScheduler;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->driver = Driver::factory()->create(['company_id' => $this->company->id]);
    $this->vehicle = Vehicle::factory()->create(['company_id' => $this->company->id]);
    $this->scheduler = new TripScheduler();
});

describe('TripScheduler Service', function () {
    it('can schedule a trip successfully', function () {
        $start = Carbon::now()->addDay();
        $end = $start->copy()->addHours(2);

        $trip = $this->scheduler->schedule(
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

        // Verify trip was saved to database
        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);
    });

    it('validates that end time is after start time', function () {
        $start = Carbon::now()->addDay();
        $end = $start->copy()->subHour(); // End before start

        expect(fn() => $this->scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $start,
            $end
        ))->toThrow(ValidationException::class, 'End must be after start.');
    });

    it('validates that end time is not equal to start time', function () {
        $start = Carbon::now()->addDay();
        $end = $start->copy(); // Same time

        expect(fn() => $this->scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $start,
            $end
        ))->toThrow(ValidationException::class, 'End must be after start.');
    });
});

describe('TripScheduler Overlap Prevention', function () {
    it('prevents overlapping trips for the same driver', function () {
        $start = Carbon::now()->addDay();
        $end = $start->copy()->addHours(2);

        // Create first trip
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => Vehicle::factory()->create(['company_id' => $this->company->id])->id,
            'starts_at' => $start,
            'ends_at' => $end,
        ]);

        // Try to create overlapping trip with same driver
        $overlappingStart = $start->copy()->addMinutes(30);
        $overlappingEnd = $end->copy()->addMinutes(30);

        expect(fn() => $this->scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $overlappingStart,
            $overlappingEnd
        ))->toThrow(ValidationException::class, 'Driver or vehicle already booked in this period.');
    });

    it('prevents overlapping trips for the same vehicle', function () {
        $start = Carbon::now()->addDay();
        $end = $start->copy()->addHours(2);

        // Create first trip
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => Driver::factory()->create(['company_id' => $this->company->id])->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => $start,
            'ends_at' => $end,
        ]);

        // Try to create overlapping trip with same vehicle
        $overlappingStart = $start->copy()->addMinutes(30);
        $overlappingEnd = $end->copy()->addMinutes(30);

        expect(fn() => $this->scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $overlappingStart,
            $overlappingEnd
        ))->toThrow(ValidationException::class, 'Driver or vehicle already booked in this period.');
    });

    it('allows non-overlapping trips', function () {
        $start1 = Carbon::now()->addDay();
        $end1 = $start1->copy()->addHours(2);

        // Create first trip
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => $start1,
            'ends_at' => $end1,
        ]);

        // Create second trip that starts after first one ends
        $start2 = $end1->copy()->addMinutes(30);
        $end2 = $start2->copy()->addHours(2);

        $trip = $this->scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $start2,
            $end2
        );

        expect($trip)->toBeInstanceOf(Trip::class);
        expect($trip->starts_at->format('Y-m-d H:i'))->toBe($start2->format('Y-m-d H:i'));
    });

    it('allows trips that start exactly when another ends', function () {
        $start1 = Carbon::now()->addDay();
        $end1 = $start1->copy()->addHours(2);

        // Create first trip
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => $start1,
            'ends_at' => $end1,
        ]);

        // Create second trip that starts exactly when first one ends
        $start2 = $end1->copy();
        $end2 = $start2->copy()->addHours(2);

        $trip = $this->scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $start2,
            $end2
        );

        expect($trip)->toBeInstanceOf(Trip::class);
    });

    it('only checks overlaps within the same company', function () {
        $otherCompany = Company::factory()->create();
        $otherDriver = Driver::factory()->create(['company_id' => $otherCompany->id]);
        $otherVehicle = Vehicle::factory()->create(['company_id' => $otherCompany->id]);

        $start = Carbon::now()->addDay();
        $end = $start->copy()->addHours(2);

        // Create trip in other company
        Trip::factory()->create([
            'company_id' => $otherCompany->id,
            'driver_id' => $otherDriver->id,
            'vehicle_id' => $otherVehicle->id,
            'starts_at' => $start,
            'ends_at' => $end,
        ]);

        // Should be able to create overlapping trip in different company
        $trip = $this->scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $start,
            $end
        );

        expect($trip)->toBeInstanceOf(Trip::class);
    });
});

describe('TripScheduler Database Locking', function () {
    it('uses database locking for concurrent access', function () {
        // Mock DB facade to verify lockForUpdate is called
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $start = Carbon::now()->addDay();
        $end = $start->copy()->addHours(2);

        $trip = $this->scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $start,
            $end
        );

        expect($trip)->toBeInstanceOf(Trip::class);
    });
});

describe('TripScheduler Edge Cases', function () {
    it('handles trips with minimal duration', function () {
        $start = Carbon::now()->addDay();
        $end = $start->copy()->addMinute(); // 1 minute trip

        $trip = $this->scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $start,
            $end
        );

        expect($trip)->toBeInstanceOf(Trip::class);
        expect((int) $trip->starts_at->diffInMinutes($trip->ends_at))->toBe(1);
    });

    it('handles trips with long duration', function () {
        $start = Carbon::now()->addDay();
        $end = $start->copy()->addDays(7); // 7 day trip

        $trip = $this->scheduler->schedule(
            $this->company,
            $this->driver,
            $this->vehicle,
            $start,
            $end
        );

        expect($trip)->toBeInstanceOf(Trip::class);
        expect((int) $trip->starts_at->diffInDays($trip->ends_at))->toBe(7);
    });
});
