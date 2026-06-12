<?php

namespace App\Filament\Resources\PlanCoalGettingResource\Pages;

use App\Filament\Resources\PlanCoalGettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanCoalGettings extends ListRecords
{
    protected static string $resource = PlanCoalGettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
