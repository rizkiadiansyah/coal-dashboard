<?php

namespace App\Filament\Resources\CrusherActivityResource\Pages;

use App\Filament\Resources\CrusherActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrusherActivity extends EditRecord
{
    protected static string $resource = CrusherActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
