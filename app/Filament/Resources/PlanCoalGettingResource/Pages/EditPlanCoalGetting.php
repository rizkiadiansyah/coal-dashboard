<?php

namespace App\Filament\Resources\PlanCoalGettingResource\Pages;

use App\Filament\Resources\PlanCoalGettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanCoalGetting extends EditRecord
{
    protected static string $resource = PlanCoalGettingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['edit_by'] = auth()->user()?->name ?? auth()->user()?->email ?? 'system';
        $data['edit_date'] = now();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
