<?php

namespace Database\Factories;

use App\Models\Trip;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 day', '+10 days');
        $end = (clone $start)->modify('+' . rand(1, 6) . ' hours');

        return [
            'company_id' => null,
            'driver_id' => null,
            'vehicle_id' => null,
            'starts_at' => $start,
            'ends_at' => $end,
            'completed_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function completed(): static
    {
        $start = Carbon::now()->subDays(rand(1, 30))->setTime(rand(6, 20), 0);
        $end = (clone $start)->addHours(rand(1, 6));

        // 70% completed naturally, 30% completed early
        $completedAt = rand(1, 10) <= 7
            ? $end
            : $start->copy()->addHours(rand(1, $end->diffInHours($start)));

        return $this->state([
            'starts_at' => $start,
            'ends_at' => $end,
            'completed_at' => $completedAt,
            'cancelled_at' => null,
        ]);
    }

    public function active(): static
    {
        $now = Carbon::now();
        $start = $now->copy()->subHours(rand(1, 3));
        $end = $now->copy()->addHours(rand(1, 4));

        return $this->state([
            'starts_at' => $start,
            'ends_at' => $end,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        $start = Carbon::now()->addDays(rand(1, 15))->setTime(rand(6, 20), 0);
        $end = (clone $start)->addHours(rand(1, 6));

        return $this->state([
            'starts_at' => $start,
            'ends_at' => $end,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        $start = Carbon::now()
            ->addDays(rand(-15, 15))
            ->setTime(rand(6, 20), 0);
        $end = (clone $start)->addHours(rand(1, 6));

        return $this->state([
            'starts_at' => $start,
            'ends_at' => $end,
            'completed_at' => null,
            'cancelled_at' => now(),
        ]);
    }

    public function noOverlap($driver, $vehicle, $company): static
    {
        return $this->state(function (array $attributes) use ($driver, $vehicle, $company) {
            $maxRetries = 20;

            $baseStart = $attributes['starts_at'] ?? null;
            $baseEnd = $attributes['ends_at'] ?? null;
            $completedAt = $attributes['completed_at'] ?? null;
            $cancelledAt = $attributes['cancelled_at'] ?? null;

            for ($i = 0; $i < $maxRetries; $i++) {

                if ($baseStart && $baseEnd) {
                    $start = $baseStart;
                    $end = $baseEnd;
                } else {
                    [$start, $end] = $this->generateTimeRange();
                }

                if (!$this->hasOverlap($driver, $vehicle, $company, $start, $end)) {
                    return [
                        'company_id' => $company->id,
                        'driver_id' => $driver->id,
                        'vehicle_id' => $vehicle->id,
                        'starts_at' => $start,
                        'ends_at' => $end,
                        'completed_at' => $completedAt,
                        'cancelled_at' => $cancelledAt,
                    ];
                }

                if ($baseStart && $baseEnd) {
                    $baseStart = $baseEnd = null;
                }
            }

            $fallbackStart = Carbon::now()->addDays(rand(30, 45))->setTime(8, 0);
            $fallbackEnd = $fallbackStart->copy()->addHours(2);

            return [
                'company_id' => $company->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'starts_at' => $fallbackStart,
                'ends_at' => $fallbackEnd,
                'completed_at' => null,
                'cancelled_at' => null,
            ];
        });
    }

    private function generateTimeRange(): array
    {
        $start = Carbon::now()
            ->addDays(rand(-30, 30))
            ->setTime(rand(6, 20), 0);

        $end = $start->copy()->addHours(rand(1, 6));

        return [$start, $end];
    }

    private function hasOverlap($driver, $vehicle, $company, $start, $end): bool
    {
        return Trip::where('company_id', $company->id)
            ->whereNull('cancelled_at')
            ->where(function ($query) use ($driver, $vehicle) {
                $query->where('driver_id', $driver->id)
                    ->orWhere('vehicle_id', $vehicle->id);
            })
            ->where(function ($query) use ($start, $end) {
                $query->where('starts_at', '<', $end)
                    ->where('ends_at', '>', $start);
            })
            ->exists();
    }
}
