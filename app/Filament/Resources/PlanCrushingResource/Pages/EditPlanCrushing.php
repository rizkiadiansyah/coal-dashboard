<?php

namespace App\Filament\Resources\PlanCrushingResource\Pages;

use App\Filament\Resources\PlanCrushingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanCrushing extends EditRecord
{
    protected static string $resource = PlanCrushingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
