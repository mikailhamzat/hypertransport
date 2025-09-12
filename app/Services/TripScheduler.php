<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Trip;
use App\Models\Driver;
use App\Models\Company;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


final class TripScheduler
{
    public function schedule(Company $company, Driver $driver, Vehicle $vehicle, Carbon $start, Carbon $end): Trip
    {
        throw_if($end->lte($start), ValidationException::withMessages(['ends_at' => 'End must be after start.']));

        DB::transaction(function () use ($driver, $vehicle) {

            Trip::whereIn('driver_id', [$driver->id])
                ->orWhereIn('vehicle_id', [$vehicle->id])
                ->lockForUpdate()
                ->get();
        });

        $overlap = Trip::query()
            ->where('company_id', $company->id)
            ->where(fn($q) => $q->where('driver_id', $driver->id)->orWhere('vehicle_id', $vehicle->id))
            ->overlapping($start, $end)
            ->exists();

        throw_if($overlap, ValidationException::withMessages(['starts_at' => 'Driver or vehicle already booked in this period.']));

        return Trip::create([
            'company_id' => $company->id,
            'driver_id'  => $driver->id,
            'vehicle_id' => $vehicle->id,
            'starts_at'  => $start,
            'ends_at'    => $end,
            'status'     => Status::SCHEDULED->value,
        ]);
    }
}
