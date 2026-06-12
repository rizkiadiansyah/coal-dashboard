<?php

namespace App\Filament\Resources\HaulingKAResource\Pages;

use App\Filament\Resources\HaulingKAResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHaulingKA extends EditRecord
{
    protected static string $resource = HaulingKAResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
