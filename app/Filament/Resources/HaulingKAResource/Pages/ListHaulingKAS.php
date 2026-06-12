<?php

namespace App\Filament\Resources\HaulingKAResource\Pages;

use App\Filament\Resources\HaulingKAResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHaulingKAS extends ListRecords
{
    protected static string $resource = HaulingKAResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
