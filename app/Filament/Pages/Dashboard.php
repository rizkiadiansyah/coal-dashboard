<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardFilter;
use App\Filament\Widgets\CoalMovementStats;
use App\Filament\Widgets\CoalMovementMonthlyStats;
use App\Filament\Widgets\PlanObRemovalChart;
use App\Filament\Widgets\ActivityCoalGetting;
use App\Filament\Widgets\ActivityCoalInBmssTrading;
use App\Filament\Widgets\ActivityCoalInOutsource;
use App\Filament\Widgets\ActivityCrushing;
use App\Filament\Widgets\ActivityHaulingCY;
use App\Filament\Widgets\ActivityHaulingKA;
use App\Filament\Widgets\ActivityStockpileWBS;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title = 'Coal Movement Dashboard';
    // Ubah teks di dalam tanda kutip ini sesuai nama yang kamu inginkan
    protected static ?string $navigationLabel = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            CoalMovementStats::class,        // sort=2
            CoalMovementMonthlyStats::class, // sort=3
            DashboardFilter::class,
            PlanObRemovalChart::class, // sort=4
            ActivityCoalGetting::class,
            ActivityCoalInBmssTrading::class,
            ActivityCoalInOutsource::class,
            ActivityCrushing::class,
            ActivityHaulingCY::class,
            ActivityHaulingKA::class,
            ActivityStockpileWBS::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 3,
        ];
    }
}