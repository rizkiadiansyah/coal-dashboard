<?php

namespace App\Filament\Resources\StockpileWBSResource\Pages;

use App\Filament\Resources\StockpileWBSResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockpileWBS extends ListRecords
{
    protected static string $resource = StockpileWBSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
