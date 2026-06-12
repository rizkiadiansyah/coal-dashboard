<?php

namespace App\Filament\Resources\PlanBMSSTradingResource\Pages;

use App\Filament\Resources\PlanBMSSTradingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanBMSSTrading extends EditRecord
{
    protected static string $resource = PlanBMSSTradingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
