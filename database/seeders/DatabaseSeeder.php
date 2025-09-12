<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 3 companies with drivers, vehicles, and trips
        Company::factory(3)->create()->each(function ($company) {
            $drivers = Driver::factory(5)->for($company)->create();
            $vehicles = Vehicle::factory(5)->for($company)->create();

            // Completed trips (past)
            for ($i = 0; $i < 5; $i++) {
                $driver = $drivers->random();
                $vehicle = $vehicles->random();

                Trip::factory()
                    ->completed()
                    ->for($company)
                    ->state([
                        'driver_id' => $driver->id,
                        'vehicle_id' => $vehicle->id,
                    ])
                    ->create();
            }

            // Active trips (current)
            for ($i = 0; $i < 5; $i++) {
                $driver = $drivers->random();
                $vehicle = $vehicles->random();

                Trip::factory()
                    ->active()
                    ->for($company)
                    ->state([
                        'driver_id' => $driver->id,
                        'vehicle_id' => $vehicle->id,
                    ])
                    ->create();
            }

            // Scheduled trips (future, no overlap)
            for ($i = 0; $i < 5; $i++) {
                $driver = $drivers->random();
                $vehicle = $vehicles->random();

                Trip::factory()
                    ->noOverlap($driver, $vehicle, $company)
                    ->create();
            }
        });
    }
}
