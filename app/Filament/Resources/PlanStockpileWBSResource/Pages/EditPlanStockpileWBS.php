<?php

namespace App\Filament\Resources\PlanStockpileWBSResource\Pages;

use App\Filament\Resources\PlanStockpileWBSResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanStockpileWBS extends EditRecord
{
    protected static string $resource = PlanStockpileWBSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
