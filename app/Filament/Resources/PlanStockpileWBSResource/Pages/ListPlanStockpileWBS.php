<?php

namespace App\Filament\Resources\PlanStockpileWBSResource\Pages;

use App\Filament\Resources\PlanStockpileWBSResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanStockpileWBS extends ListRecords
{
    protected static string $resource = PlanStockpileWBSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
