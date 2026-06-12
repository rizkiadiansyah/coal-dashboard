<?php

namespace App\Filament\Resources\HaulingCYResource\Pages;

use App\Filament\Resources\HaulingCYResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHaulingCYS extends ListRecords
{
    protected static string $resource = HaulingCYResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
