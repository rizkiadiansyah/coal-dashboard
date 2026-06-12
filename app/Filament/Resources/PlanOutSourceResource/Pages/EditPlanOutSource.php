<?php

namespace App\Filament\Resources\PlanOutSourceResource\Pages;

use App\Filament\Resources\PlanOutSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanOutSource extends EditRecord
{
    protected static string $resource = PlanOutSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
