<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Illuminate\Support\Facades\Cache;
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

    public static function canAccess(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshDashboard')
                ->label('Refresh dashboard')
                ->icon('heroicon-o-arrow-path')
                ->iconButton()
                ->tooltip('Refresh dashboard')
                ->action(function (): void {
                    $timezone = 'Asia/Jakarta';
                    $now = now($timezone);
                    $filter = session('dashboard_filter', [
                        'tanggal_awal' => $now->toDateString(),
                        'tanggal_akhir' => $now->toDateString(),
                    ]);
                    $period = ($filter['tanggal_awal'] ?? $now->toDateString()) . '_' .
                        ($filter['tanggal_akhir'] ?? $now->toDateString());
                    $hour = $now->format('YmdH');

                    foreach ([
                        'activity_coal_getting',
                        'activity_coal_in_bmss_trading',
                        'activity_coal_in_outsource',
                        'activity_crushing',
                        'activity_hauling_cy',
                        'activity_hauling_ka',
                        'activity_stockpile_wbs',
                    ] as $widgetKey) {
                        Cache::forget("widget_{$widgetKey}_{$period}_{$hour}");
                        Cache::forget("widget_{$widgetKey}_{$period}_{$hour}:updated_at");
                    }

                    Cache::forget("widget_plan_ob_removal_chart_{$period}_{$hour}");
                    Cache::forget("widget_plan_ob_removal_chart_{$period}_{$hour}:updated_at");
                    Cache::forget("widget_coal_movement_stats_{$now->toDateString()}_{$hour}");
                    Cache::forget("widget_coal_movement_stats_{$now->toDateString()}_{$hour}:updated_at");
                    Cache::forget("widget_coal_movement_monthly_stats_{$now->format('Ym')}_{$hour}");
                    Cache::forget("widget_coal_movement_monthly_stats_{$now->format('Ym')}_{$hour}:updated_at");
                    // Simpan waktu refresh terbaru
                    Cache::put('dashboard_last_refreshed_at', $now->toDateTimeString(), now()->addHours(24));
                    session(['dashboard_last_refreshed_at' => $now->toDateTimeString()]);

                    // Broadcast event ke seluruh widget dashboard
                    $this->dispatch('filterUpdated');
                }),
        ];
    }

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