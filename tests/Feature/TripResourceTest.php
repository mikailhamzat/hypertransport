<?php

use App\Models\Trip;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Filament\Resources\Trips\TripResource;

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Test Company']);
    $this->driver = Driver::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'John Doe'
    ]);
    $this->vehicle = Vehicle::factory()->create([
        'company_id' => $this->company->id,
        'plate_number' => 'ABC-123'
    ]);
});

describe('TripResource Form', function () {
    it('can render form schema', function () {
        $schema = TripResource::form(\Filament\Schemas\Schema::make());

        expect($schema)->toBeInstanceOf(\Filament\Schemas\Schema::class);

        $components = $schema->getComponents();
        expect($components)->toHaveCount(5);

        // Check component names
        $componentNames = collect($components)->map(fn($component) => $component->getName())->toArray();
        expect($componentNames)->toContain('company_id', 'driver_id', 'vehicle_id', 'starts_at', 'ends_at');
    });

    it('has required company_id field', function () {
        $schema = TripResource::form(\Filament\Schemas\Schema::make());
        $components = $schema->getComponents();

        $companyField = collect($components)->first(fn($component) => $component->getName() === 'company_id');

        expect($companyField)->not->toBeNull();
        expect($companyField->isRequired())->toBeTrue();
        expect($companyField->isLive())->toBeTrue();
    });

    it('has datetime pickers for starts_at and ends_at', function () {
        $schema = TripResource::form(\Filament\Schemas\Schema::make());
        $components = $schema->getComponents();

        $startsAtField = collect($components)->first(fn($component) => $component->getName() === 'starts_at');
        $endsAtField = collect($components)->first(fn($component) => $component->getName() === 'ends_at');

        expect($startsAtField)->toBeInstanceOf(\Filament\Forms\Components\DateTimePicker::class);
        expect($endsAtField)->toBeInstanceOf(\Filament\Forms\Components\DateTimePicker::class);
        expect($startsAtField->isRequired())->toBeTrue();
        expect($endsAtField->isRequired())->toBeTrue();
    });
});

describe('TripResource Table', function () {
    it('can render table schema', function () {
        // Create a mock Livewire component that implements HasTable
        $mockLivewire = $this->createMock(\Filament\Tables\Contracts\HasTable::class);
        $table = TripResource::table(\Filament\Tables\Table::make($mockLivewire));

        expect($table)->toBeInstanceOf(\Filament\Tables\Table::class);

        $columns = $table->getColumns();
        expect($columns)->toHaveCount(7);

        // Check column names
        $columnNames = collect($columns)->map(fn($column) => $column->getName())->toArray();
        expect($columnNames)->toContain('#', 'company.name', 'driver.name', 'vehicle.plate_number', 'status', 'starts_at', 'ends_at');
    });

    it('has sortable columns', function () {
        $mockLivewire = $this->createMock(\Filament\Tables\Contracts\HasTable::class);
        $table = TripResource::table(\Filament\Tables\Table::make($mockLivewire));
        $columns = $table->getColumns();

        $sortableColumns = collect($columns)->filter(fn($column) => $column->isSortable())->map(fn($column) => $column->getName())->toArray();

        expect($sortableColumns)->toContain('company.name', 'driver.name', 'vehicle.plate_number', 'starts_at', 'ends_at');
    });

    it('has searchable status column', function () {
        $mockLivewire = $this->createMock(\Filament\Tables\Contracts\HasTable::class);
        $table = TripResource::table(\Filament\Tables\Table::make($mockLivewire));
        $columns = $table->getColumns();

        $statusColumn = collect($columns)->first(fn($column) => $column->getName() === 'status');

        expect($statusColumn->isSearchable())->toBeTrue();
    });

    it('has record actions', function () {
        $mockLivewire = $this->createMock(\Filament\Tables\Contracts\HasTable::class);
        $table = TripResource::table(\Filament\Tables\Table::make($mockLivewire));
        $actions = $table->getRecordActions();

        expect($actions)->toHaveCount(3);

        $actionNames = collect($actions)->map(fn($action) => class_basename($action))->toArray();
        expect($actionNames)->toContain('ViewAction', 'EditAction', 'DeleteAction');
    });
});

describe('TripResource Infolist', function () {
    it('can render infolist schema', function () {
        $schema = TripResource::infolist(\Filament\Schemas\Schema::make());

        expect($schema)->toBeInstanceOf(\Filament\Schemas\Schema::class);

        $components = $schema->getComponents();
        expect($components)->toHaveCount(6);

        // Check component names
        $componentNames = collect($components)->map(fn($component) => $component->getName())->toArray();
        expect($componentNames)->toContain('company.name', 'driver.name', 'vehicle.plate_number', 'status', 'starts_at', 'ends_at');
    });

    it('has datetime entries for starts_at and ends_at', function () {
        $schema = TripResource::infolist(\Filament\Schemas\Schema::make());
        $components = $schema->getComponents();

        $startsAtEntry = collect($components)->first(fn($component) => $component->getName() === 'starts_at');
        $endsAtEntry = collect($components)->first(fn($component) => $component->getName() === 'ends_at');

        expect($startsAtEntry)->toBeInstanceOf(\Filament\Infolists\Components\TextEntry::class);
        expect($endsAtEntry)->toBeInstanceOf(\Filament\Infolists\Components\TextEntry::class);
    });
});

describe('TripResource Pages', function () {
    it('has correct page configuration', function () {
        $pages = TripResource::getPages();

        expect($pages)->toHaveKey('index');
        expect($pages['index'])->toBeInstanceOf(\Filament\Resources\Pages\PageRegistration::class);
    });
});

describe('TripResource Model', function () {
    it('has correct model class', function () {
        expect(TripResource::getModel())->toBe(Trip::class);
    });

    it('has correct navigation icon', function () {
        $icon = TripResource::getNavigationIcon();
        expect($icon)->toBe(\Filament\Support\Icons\Heroicon::OutlinedMap);
    });
});
