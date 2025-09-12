<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Trip;
use App\Models\Driver;
use App\Models\Company;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    protected $model = Trip::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Default: scheduled trip in the near future
        $start = Carbon::now()->addDays(rand(1, 5))->setTime(rand(6, 12), 0);
        $end = (clone $start)->addHours(rand(1, 8));

        return [
            'company_id' => null, // assign in seeder
            'driver_id' => null,  // assign in seeder
            'vehicle_id' => null, // assign in seeder
            'starts_at' => $start,
            'ends_at' => $end,
            'status' => Status::SCHEDULED->value,
        ];
    }

    /**
     * Create a completed (past) trip
     */
    public function completed(): static
    {
        $start = Carbon::now()->subDays(rand(2, 10))->setTime(rand(6, 12), 0);
        $end = (clone $start)->addHours(rand(1, 8));

        return $this->state([
            'starts_at' => $start,
            'ends_at' => $end,
            'status' => Status::COMPLETED->value,
        ]);
    }

    /**
     * Create an active (ongoing) trip
     */
    public function active(): static
    {
        $start = Carbon::now()->subHours(rand(1, 2));
        $end = Carbon::now()->addHours(rand(1, 3));

        return $this->state([
            'starts_at' => $start,
            'ends_at' => $end,
            'status' => Status::ACTIVE->value,
        ]);
    }

    /**
     * Ensure no overlapping trips for a given driver and vehicle.
     */
    public function noOverlap(Driver $driver, Vehicle $vehicle, Company $company): static
    {
        return $this->state(function () use ($driver, $vehicle, $company) {
            $start = Carbon::now()->addDays(rand(1, 10))->setTime(rand(6, 12), 0);
            $end = (clone $start)->addHours(rand(1, 8));

            // Loop until no overlaps for driver
            while (Trip::where('driver_id', $driver->id)
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('starts_at', [$start, $end])
                        ->orWhereBetween('ends_at', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('starts_at', '<', $start)
                                ->where('ends_at', '>', $end);
                        });
                })->exists()
            ) {
                $start->addHour();
                $end = (clone $start)->addHours(rand(1, 8));
            }

            // Loop until no overlaps for vehicle
            while (Trip::where('vehicle_id', $vehicle->id)
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('starts_at', [$start, $end])
                        ->orWhereBetween('ends_at', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('starts_at', '<', $start)
                                ->where('ends_at', '>', $end);
                        });
                })->exists()
            ) {
                $start->addHour();
                $end = (clone $start)->addHours(rand(1, 8));
            }

            return [
                'company_id' => $company->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => Status::SCHEDULED->value,
            ];
        });
    }
}
