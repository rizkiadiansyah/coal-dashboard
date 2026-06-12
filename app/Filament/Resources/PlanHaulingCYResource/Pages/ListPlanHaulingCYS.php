<?php

namespace App\Filament\Resources\PlanHaulingCYResource\Pages;

use App\Filament\Resources\PlanHaulingCYResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanHaulingCYS extends ListRecords
{
    protected static string $resource = PlanHaulingCYResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
