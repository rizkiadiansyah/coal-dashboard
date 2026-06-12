<?php

namespace App\Filament\Resources\HaulingCYResource\Pages;

use App\Filament\Resources\HaulingCYResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHaulingCY extends EditRecord
{
    protected static string $resource = HaulingCYResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
