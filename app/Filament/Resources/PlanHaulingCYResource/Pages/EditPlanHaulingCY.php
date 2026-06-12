<?php

namespace App\Filament\Resources\PlanHaulingCYResource\Pages;

use App\Filament\Resources\PlanHaulingCYResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanHaulingCY extends EditRecord
{
    protected static string $resource = PlanHaulingCYResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
