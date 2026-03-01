<?php

namespace App\Filament\Resources\Trucks\Pages;

use App\Filament\Resources\Trucks\TruckResource;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageTrucks extends ManageRecords
{
    protected static string $resource = TruckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->after(function ($record) {
                    Notification::make()
                        ->title('Data baru dibuat')
                        ->body('Truck baru berhasil ditambahkan')
                        ->sendToDatabase(auth()->user());
                }),
        ];
    }
}
