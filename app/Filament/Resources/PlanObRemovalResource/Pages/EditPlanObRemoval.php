<?php

namespace App\Filament\Resources\PlanObRemovalResource\Pages;

use App\Filament\Resources\PlanObRemovalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanObRemoval extends EditRecord
{
    protected static string $resource = PlanObRemovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
