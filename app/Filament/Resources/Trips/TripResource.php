<?php

namespace App\Filament\Resources\Trips;

use BackedEnum;
use App\Models\Trip;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Trips\Pages\ManageTrips;
use Laravel\SerializableClosure\Serializers\Native;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->live()
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('driver_id')
                    ->relationship('driver', 'name', fn(Builder $query, Get $get) => empty($get('company_id')) ? $query->whereRaw('1 = 0') : $query->where('company_id', $get('company_id'))),
                Select::make('vehicle_id')
                    ->relationship('vehicle', 'plate_number', fn(Builder $query, Get $get) => empty($get('company_id')) ? $query->whereRaw('1 = 0') : $query->where('company_id', $get('company_id'))),
                DateTimePicker::make('starts_at')
                    ->native(false)
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->native(false)
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->numeric(),
                TextEntry::make('driver.name')
                    ->numeric(),
                TextEntry::make('vehicle.plate_number')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('starts_at')
                    ->dateTime(),
                TextEntry::make('ends_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('#')
                    ->rowIndex(),
                TextColumn::make('company.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('driver.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('vehicle.plate_number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTrips::route('/'),
        ];
    }
}
