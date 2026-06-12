<?php

namespace App\Filament\Resources\CoalGettingResource\Pages;

use App\Filament\Resources\CoalGettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCoalGettings extends ListRecords
{
    protected static string $resource = CoalGettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
