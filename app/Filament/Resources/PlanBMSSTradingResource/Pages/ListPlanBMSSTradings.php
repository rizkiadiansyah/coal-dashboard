<?php

namespace App\Filament\Resources\PlanBMSSTradingResource\Pages;

use App\Filament\Resources\PlanBMSSTradingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanBMSSTradings extends ListRecords
{
    protected static string $resource = PlanBMSSTradingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
