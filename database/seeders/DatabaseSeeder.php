<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\Driver;
use App\Models\Company;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Company::factory(3)->create()->each(function ($company) {
            $drivers = Driver::factory(5)->for($company)->create();
            $vehicles = Vehicle::factory(5)->for($company)->create();

            $this->createTripsForCompany($company, $drivers, $vehicles);
        });
    }

    private function createTripsForCompany($company, $drivers, $vehicles)
    {
        // Past completed trips (15-25)
        for ($i = 0; $i < rand(15, 25); $i++) {
            $driver = $drivers->random();
            $vehicle = $vehicles->random();

            Trip::factory()
                ->completed()
                ->noOverlap($driver, $vehicle, $company)
                ->create();
        }

        // Current active trips (1-3 max per company)
        for ($i = 0; $i < rand(1, 3); $i++) {
            $driver = $drivers->random();
            $vehicle = $vehicles->random();

            Trip::factory()
                ->active()
                ->noOverlap($driver, $vehicle, $company)
                ->create();
        }

        // Future scheduled trips (10-20)
        for ($i = 0; $i < rand(10, 20); $i++) {
            $driver = $drivers->random();
            $vehicle = $vehicles->random();

            Trip::factory()
                ->scheduled()
                ->noOverlap($driver, $vehicle, $company)
                ->create();
        }

        // Some cancelled trips (3-7)
        for ($i = 0; $i < rand(3, 7); $i++) {
            $driver = $drivers->random();
            $vehicle = $vehicles->random();

            Trip::factory()
                ->cancelled()
                ->create([
                    'company_id' => $company->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                ]);
        }
    }
}
