<?php

namespace App\Filament\Resources\Trips\Pages;

use App\Models\Driver;
use App\Models\Company;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use App\Services\TripScheduler;
use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Resources\Trips\TripResource;
use Illuminate\Validation\ValidationException;

class ManageTrips extends ManageRecords
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): Model {
                    try {
                        $scheduler = app(TripScheduler::class);

                        $trip = $scheduler->schedule(
                            company: Company::find($data['company_id']),
                            driver: Driver::find($data['driver_id']),
                            vehicle: Vehicle::find($data['vehicle_id']),
                            start: Carbon::parse($data['starts_at']),
                            end: Carbon::parse($data['ends_at'])
                        );
                        return $trip;
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('Scheduling Failed')
                            ->body($e->getMessage() ?: 'Driver or vehicle already booked in this period.')
                            ->danger()
                            ->send();

                        throw $e;
                    }
                })
                ->successNotification(function () {
                    return Notification::make()
                        ->success()
                        ->title('Trip Created')
                        ->body('The trip has been created successfully');
                }),
        ];
    }
}
