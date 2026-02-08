<?php

namespace App\Filament\Resources\Pretrips\Pages;

use App\Filament\Resources\Pretrips\PretripResource;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePretrips extends ManageRecords
{
    protected static string $resource = PretripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['status'] = $data['status'] ?? 'in_progress';
                    $data['start_time'] = $data['start_time'] ?? now();
                    return $data;
                })
                ->after(function ($record) {
                    // Relationship sudah auto-save via saveRelationshipsUsing
                    // Tinggal update status aja
                    $record->updateStatus();
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            EditAction::make()
                ->after(function ($record) {
                    // Update status setelah edit
                    $record->updateStatus();
                }),
        ];
    }
}
