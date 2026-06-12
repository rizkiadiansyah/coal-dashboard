<?php

namespace App\Filament\Resources\PlanBargingWBSResource\Pages;

use App\Filament\Resources\PlanBargingWBSResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanBargingWBS extends EditRecord
{
    protected static string $resource = PlanBargingWBSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
