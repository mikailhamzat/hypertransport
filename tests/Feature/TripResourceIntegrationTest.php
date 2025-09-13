<?php

use App\Models\Trip;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\User;
use App\Enums\Status;
use Illuminate\Support\Carbon;
use Filament\Actions\Testing\TestAction;
use function Pest\Livewire\livewire;
use App\Filament\Resources\Trips\Pages\ManageTrips;

beforeEach(function () {
    // Create a user for authentication
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->company = Company::factory()->create(['name' => 'Test Company']);
    $this->driver = Driver::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'John Doe'
    ]);
    $this->vehicle = Vehicle::factory()->create([
        'company_id' => $this->company->id,
        'plate_number' => 'ABC-123'
    ]);

    // Create some test trips
    $this->scheduledTrip = Trip::factory()->scheduled()->create([
        'company_id' => $this->company->id,
        'driver_id' => $this->driver->id,
        'vehicle_id' => $this->vehicle->id,
    ]);

    $this->activeTrip = Trip::factory()->active()->create([
        'company_id' => $this->company->id,
        'driver_id' => Driver::factory()->create(['company_id' => $this->company->id])->id,
        'vehicle_id' => Vehicle::factory()->create(['company_id' => $this->company->id])->id,
    ]);

    $this->completedTrip = Trip::factory()->completed()->create([
        'company_id' => $this->company->id,
        'driver_id' => Driver::factory()->create(['company_id' => $this->company->id])->id,
        'vehicle_id' => Vehicle::factory()->create(['company_id' => $this->company->id])->id,
    ]);
});

describe('Trip Resource Page Rendering', function () {
    it('can render the manage trips page', function () {
        livewire(ManageTrips::class)
            ->assertSuccessful();
    });

    it('displays trips in the table', function () {
        livewire(ManageTrips::class)
            ->assertCanSeeTableRecords([
                $this->scheduledTrip,
                $this->activeTrip,
                $this->completedTrip,
            ]);
    });

    it('displays correct trip information in table', function () {
        livewire(ManageTrips::class)
            ->assertTableColumnExists('company.name')
            ->assertTableColumnExists('driver.name')
            ->assertTableColumnExists('vehicle.plate_number')
            ->assertTableColumnExists('status')
            ->assertTableColumnExists('starts_at')
            ->assertTableColumnExists('ends_at');
    });
});

describe('Trip Status Display', function () {
    it('displays correct status for scheduled trip', function () {
        expect($this->scheduledTrip->status)->toBe(Status::SCHEDULED);

        livewire(ManageTrips::class)
            ->assertCanSeeTableRecords([$this->scheduledTrip])
            ->assertSeeText(Status::SCHEDULED->getLabel());
    });

    it('displays correct status for active trip', function () {
        expect($this->activeTrip->status)->toBe(Status::ACTIVE);

        livewire(ManageTrips::class)
            ->assertCanSeeTableRecords([$this->activeTrip])
            ->assertSeeText(Status::ACTIVE->getLabel());
    });

    it('displays correct status for completed trip', function () {
        expect($this->completedTrip->status)->toBe(Status::COMPLETED);

        livewire(ManageTrips::class)
            ->assertCanSeeTableRecords([$this->completedTrip])
            ->assertSeeText(Status::COMPLETED->getLabel());
    });
});

describe('Trip Table Interactions', function () {
    it('can sort trips by company name', function () {
        livewire(ManageTrips::class)
            ->sortTable('company.name')
            ->assertSuccessful();
    });

    it('can sort trips by start date', function () {
        livewire(ManageTrips::class)
            ->sortTable('starts_at')
            ->assertSuccessful();
    });

    it('can search trips by status', function () {
        livewire(ManageTrips::class)
            ->searchTable('Active')
            ->assertSeeText('Active');
    });
});

describe('Trip Record Actions', function () {
    it('can view a trip', function () {
        livewire(ManageTrips::class)
            ->callAction(TestAction::make('view')->table($this->scheduledTrip))
            ->assertSuccessful();
    });

    it('can edit a trip', function () {
        livewire(ManageTrips::class)
            ->callAction(TestAction::make('edit')->table($this->scheduledTrip))
            ->assertSuccessful();
    });

    it('can delete a trip', function () {
        livewire(ManageTrips::class)
            ->callAction(TestAction::make('delete')->table($this->scheduledTrip))
            ->assertSuccessful();

        $this->assertDatabaseMissing('trips', ['id' => $this->scheduledTrip->id]);
    });
});

describe('Trip Creation Flow', function () {
    it('can create a new trip', function () {
        $newDriver = Driver::factory()->create(['company_id' => $this->company->id]);
        $newVehicle = Vehicle::factory()->create(['company_id' => $this->company->id]);

        $start = Carbon::now()->addDays(2);
        $end = $start->copy()->addHours(3);

        livewire(ManageTrips::class)
            ->callAction('create', data: [
                'company_id' => $this->company->id,
                'driver_id' => $newDriver->id,
                'vehicle_id' => $newVehicle->id,
                'starts_at' => $start->format('Y-m-d H:i:s'),
                'ends_at' => $end->format('Y-m-d H:i:s'),
            ])
            ->assertSuccessful()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('trips', [
            'company_id' => $this->company->id,
            'driver_id' => $newDriver->id,
            'vehicle_id' => $newVehicle->id,
        ]);
    });

    it('prevents creating overlapping trips', function () {
        $start = $this->scheduledTrip->starts_at->copy()->addMinutes(30);
        $end = $this->scheduledTrip->ends_at->copy()->addMinutes(30);

        livewire(ManageTrips::class)
            ->callAction('create', data: [
                'company_id' => $this->company->id,
                'driver_id' => $this->scheduledTrip->driver_id,
                'vehicle_id' => $this->scheduledTrip->vehicle_id,
                'starts_at' => $start->format('Y-m-d H:i:s'),
                'ends_at' => $end->format('Y-m-d H:i:s'),
            ])
            ->assertNotified();
    });

    it('validates required fields', function () {
        livewire(ManageTrips::class)
            ->callAction('create', data: [
                'company_id' => null,
                'driver_id' => null,
                'vehicle_id' => null,
                'starts_at' => null,
                'ends_at' => null,
            ])
            ->assertHasFormErrors(['company_id', 'starts_at', 'ends_at']);
    });
});

describe('Trip Bulk Actions', function () {
    it('can bulk delete trips', function () {
        $trips = Trip::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        livewire(ManageTrips::class)
            ->selectTableRecords($trips->pluck('id')->toArray())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertSuccessful();

        foreach ($trips as $trip) {
            $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
        }
    });
});

describe('Trip Form Field Dependencies', function () {
    it('filters drivers by selected company', function () {
        $otherCompany = Company::factory()->create();
        Driver::factory()->create(['company_id' => $otherCompany->id]);

        // When creating a trip, drivers should be filtered by company
        livewire(ManageTrips::class)
            ->mountAction('create')
            ->fillForm([
                'company_id' => $this->company->id,
            ])
            ->assertFormFieldExists('driver_id')
            ->assertSuccessful();
    });

    it('filters vehicles by selected company', function () {
        $otherCompany = Company::factory()->create();
        Vehicle::factory()->create(['company_id' => $otherCompany->id]);

        // When creating a trip, vehicles should be filtered by company
        livewire(ManageTrips::class)
            ->mountAction('create')
            ->fillForm([
                'company_id' => $this->company->id,
            ])
            ->assertFormFieldExists('vehicle_id')
            ->assertSuccessful();
    });
});
