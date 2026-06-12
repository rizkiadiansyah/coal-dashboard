<?php

namespace App\Filament\Resources\PlanHaulingKAResource\Pages;

use App\Filament\Resources\PlanHaulingKAResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanHaulingKAS extends ListRecords
{
    protected static string $resource = PlanHaulingKAResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
