<?php

namespace App\Filament\Resources\PlanBargingWBSResource\Pages;

use App\Filament\Resources\PlanBargingWBSResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanBargingWBS extends ListRecords
{
    protected static string $resource = PlanBargingWBSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
