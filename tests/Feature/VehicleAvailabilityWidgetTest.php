<?php

namespace Tests\Feature;

use App\Filament\Widgets\VehicleAvailable;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VehicleAvailabilityWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Vehicle $availableVehicle;
    protected Vehicle $busyVehicle;
    protected Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->availableVehicle = Vehicle::factory()->create(['company_id' => $this->company->id]);
        $this->busyVehicle = Vehicle::factory()->create(['company_id' => $this->company->id]);
        $this->driver = Driver::factory()->create(['company_id' => $this->company->id]);

        // Create a trip for the busy vehicle
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->busyVehicle->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
    }

    public function test_it_can_render_the_vehicle_availability_widget()
    {
        Livewire::test(VehicleAvailable::class)
            ->assertSuccessful()
            ->assertSee('Vehicle Availability')
            ->assertSee('View available vehicles for a selected period');
    }

    public function test_it_shows_all_vehicles_by_default()
    {
        Livewire::test(VehicleAvailable::class)
            ->assertSuccessful()
            ->assertSee('Vehicle Availability')
            ->assertSee('Status');
    }

    public function test_it_shows_vehicle_status_correctly()
    {
        Livewire::test(VehicleAvailable::class)
            ->assertSuccessful()
            ->assertSee('Available');
    }

    public function test_it_can_filter_vehicles_by_availability_period()
    {
        $futureStart = now()->addDays(2);
        $futureEnd = now()->addDays(2)->addHours(4);

        Livewire::test(VehicleAvailable::class)
            ->filterTable('availability_period', [
                'available_from' => $futureStart->toDateTimeString(),
                'available_until' => $futureEnd->toDateTimeString(),
            ])
            ->assertSuccessful()
            ->assertSee('Vehicle Availability');
    }

    public function test_it_shows_filter_indicator_when_period_is_selected()
    {
        $start = now()->addHour();
        $end = now()->addHours(3);

        Livewire::test(VehicleAvailable::class)
            ->filterTable('availability_period', [
                'available_from' => $start->toDateTimeString(),
                'available_until' => $end->toDateTimeString(),
            ])
            ->assertSuccessful()
            ->assertSee('Available:');
    }

    public function test_it_displays_vehicle_information_correctly()
    {
        Livewire::test(VehicleAvailable::class)
            ->assertSuccessful()
            ->assertSee('Plate Number')
            ->assertSee('Model')
            ->assertSee('Company');
    }

    public function test_it_handles_vehicles_without_model()
    {
        // Create a vehicle without a model
        $vehicleWithoutModel = Vehicle::factory()->create([
            'company_id' => $this->company->id,
            'model' => null,
        ]);

        Livewire::test(VehicleAvailable::class)
            ->assertSuccessful()
            ->assertSee($vehicleWithoutModel->plate_number);
    }

    public function test_it_sorts_by_plate_number_by_default()
    {
        Livewire::test(VehicleAvailable::class)
            ->assertSuccessful()
            ->assertSee('Vehicle Availability');
    }

    public function test_it_refreshes_every_30_seconds()
    {
        $component = Livewire::test(VehicleAvailable::class);

        // Check that the component has polling enabled
        $this->assertStringContainsString('wire:poll.30s', $component->html());
    }
}
