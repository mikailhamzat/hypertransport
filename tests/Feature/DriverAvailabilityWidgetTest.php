<?php

namespace Tests\Feature;

use App\Filament\Widgets\DriverAvailable;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DriverAvailabilityWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Driver $availableDriver;
    protected Driver $busyDriver;
    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->availableDriver = Driver::factory()->create(['company_id' => $this->company->id]);
        $this->busyDriver = Driver::factory()->create(['company_id' => $this->company->id]);
        $this->vehicle = Vehicle::factory()->create(['company_id' => $this->company->id]);

        // Create a trip for the busy driver
        Trip::factory()->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->busyDriver->id,
            'vehicle_id' => $this->vehicle->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
    }

    public function test_it_can_render_the_driver_availability_widget()
    {
        Livewire::test(DriverAvailable::class)
            ->assertSuccessful()
            ->assertSee('Driver Availability')
            ->assertSee('View available drivers for a selected period');
    }

    public function test_it_shows_all_drivers_by_default()
    {
        Livewire::test(DriverAvailable::class)
            ->assertSuccessful()
            ->assertSee('Driver Availability')
            ->assertSee('Status');
    }

    public function test_it_shows_driver_status_correctly()
    {
        Livewire::test(DriverAvailable::class)
            ->assertSuccessful()
            ->assertSee('Available');
    }

    public function test_it_can_filter_drivers_by_availability_period()
    {
        $futureStart = now()->addDays(2);
        $futureEnd = now()->addDays(2)->addHours(4);

        Livewire::test(DriverAvailable::class)
            ->filterTable('availability_period', [
                'available_from' => $futureStart->toDateTimeString(),
                'available_until' => $futureEnd->toDateTimeString(),
            ])
            ->assertSuccessful()
            ->assertSee('Driver Availability');
    }

    public function test_it_shows_filter_indicator_when_period_is_selected()
    {
        $start = now()->addHour();
        $end = now()->addHours(3);

        Livewire::test(DriverAvailable::class)
            ->filterTable('availability_period', [
                'available_from' => $start->toDateTimeString(),
                'available_until' => $end->toDateTimeString(),
            ])
            ->assertSuccessful()
            ->assertSee('Available:');
    }
}
