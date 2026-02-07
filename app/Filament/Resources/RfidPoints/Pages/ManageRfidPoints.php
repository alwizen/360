<?php

namespace App\Filament\Resources\RfidPoints\Pages;

use App\Filament\Resources\RfidPoints\RfidPointResource;
use App\Models\RfidPoint;
use App\Models\Truck;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRfidPoints extends ManageRecords
{
    protected static string $resource = RfidPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Registrasi RFID')
                ->using(function (array $data): RfidPoint {
                    // Bulk insert logic
                    $truckId = $data['truck_id'];
                    $points = $data['points'] ?? [];

                    $truck = Truck::findOrFail($truckId);
                    $existingPointsCount = $truck->rfidPoints()->count();

                    $createdRecords = [];

                    // Loop untuk create multiple RFID points
                    foreach ($points as $index => $point) {
                        $pointNumber = $existingPointsCount + $index + 1;

                        $createdRecords[] = RfidPoint::create([
                            'truck_id' => $truckId,
                            'rfid_code' => $point['rfid_code'],
                            'location' => $point['location'],
                            'point_number' => $pointNumber,
                            'is_active' => true,
                        ]);
                    }

                    // Return first record (required by Filament)
                    return $createdRecords[0] ?? new RfidPoint();
                })
                ->successNotificationTitle('RFID Point berhasil didaftarkan'),
        ];
    }
}
