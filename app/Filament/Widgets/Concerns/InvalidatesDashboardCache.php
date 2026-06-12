<?php

namespace App\Filament\Widgets\Concerns;

use Illuminate\Support\Facades\Cache;

trait InvalidatesDashboardCache
{
    protected function invalidateDashboardCache(): void
    {
        $timezone = 'Asia/Jakarta';
        $now      = now($timezone);

        // Format daily: widget_{key}_{date}_{YmdH}
        $dailyKeys = [
            'coal_movement_stats',
        ];

        foreach ($dailyKeys as $widgetKey) {
            $cacheKey = implode('_', [
                'widget',
                $widgetKey,
                $now->toDateString(),
                $now->format('YmdH'),
            ]);

            Cache::forget($cacheKey);
            Cache::forget($cacheKey . ':updated_at');
        }

        // Format monthly: widget_{key}_{Ym}_{YmdH}
        $monthlyKeys = [
            'coal_movement_monthly_stats',
        ];

        foreach ($monthlyKeys as $widgetKey) {
            $cacheKey = implode('_', [
                'widget',
                $widgetKey,
                $now->format('Ym'),
                $now->format('YmdH'),
            ]);

            Cache::forget($cacheKey);
            Cache::forget($cacheKey . ':updated_at');
        }

        // Format period: widget_{key}_{tanggal_awal}_{tanggal_akhir}_{YmdH}
        $filter     = session('dashboard_filter', [
            'tanggal_awal'  => $now->toDateString(),
            'tanggal_akhir' => $now->toDateString(),
        ]);

        $periodKeys = [
            'plan_ob_removal_chart',
        ];

        foreach ($periodKeys as $widgetKey) {
            $cacheKey = implode('_', [
                'widget',
                $widgetKey,
                $filter['tanggal_awal']  ?? $now->toDateString(),
                $filter['tanggal_akhir'] ?? $now->toDateString(),
            ]) . '_' . $now->format('YmdH');

            Cache::forget($cacheKey);
            Cache::forget($cacheKey . ':updated_at');
        }
    }
}