<?php

namespace App\Filament\Resources\StockpileWBSResource\Pages;

use App\Filament\Resources\StockpileWBSResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockpileWBS extends EditRecord
{
    protected static string $resource = StockpileWBSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
