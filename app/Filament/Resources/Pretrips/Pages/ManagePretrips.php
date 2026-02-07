<?php

namespace App\Filament\Resources\Pretrips\Pages;

use App\Filament\Resources\Pretrips\PretripResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePretrips extends ManageRecords
{
    protected static string $resource = PretripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
