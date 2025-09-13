<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class VehicleAvailable extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->heading('Vehicle Availability')
            ->description('View available vehicles for a selected period')
            ->query(fn(): Builder => Vehicle::query()->with(['company', 'trips']))
            ->columns([
                TextColumn::make('#')
                    ->rowIndex(),
                TextColumn::make('plate_number')
                    ->searchable()
                    ->sortable()
                    ->label('Plate Number'),
                TextColumn::make('model')
                    ->searchable()
                    ->sortable()
                    ->placeholder('N/A'),
                TextColumn::make('company.name')
                    ->sortable()
                    ->label('Company'),
                TextColumn::make('availability_status')
                    ->label('Status')
                    ->getStateUsing(function (Vehicle $record): string {
                        $now = now();
                        $hasActiveTrip = $record->trips()
                            ->overlapping($now, $now->copy()->addMinute())
                            ->exists();

                        return $hasActiveTrip ? 'Busy' : 'Available';
                    })
                    ->badge()
                    ->colors([
                        'success' => 'Available',
                        'danger' => 'Busy',
                    ]),
            ])
            ->filters([
                Filter::make('availability_period')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DateTimePicker::make('available_from')
                            ->label('Available From')
                            ->native(false)
                            ->default(now())
                            ->required(),
                        DateTimePicker::make('available_until')
                            ->label('Available Until')
                            ->native(false)
                            ->default(now()->addHours(8))
                            ->required()
                            ->after('available_from'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['available_from'] && $data['available_until'],
                                fn(Builder $query): Builder => $query->availableBetween(
                                    Carbon::parse($data['available_from']),
                                    Carbon::parse($data['available_until'])
                                ),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['available_from'] || !$data['available_until']) {
                            return null;
                        }

                        $from = Carbon::parse($data['available_from'])->format('M j, Y g:i A');
                        $until = Carbon::parse($data['available_until'])->format('M j, Y g:i A');

                        return "Available: {$from} - {$until}";
                    }),
            ])
            ->defaultSort('plate_number')
            ->poll('30s'); // Refresh every 30 seconds to show real-time availability
    }
}
