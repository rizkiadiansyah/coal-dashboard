<?php

namespace App\Filament\Widgets\Concerns;

use Illuminate\Support\Facades\Cache;

trait FiltersDashboardPeriod
{
    protected function getDefaultDashboardFilter(): array
    {
        return [
            'tanggal_awal'  => now()->toDateString(),
            'tanggal_akhir' => now()->toDateString(),
        ];
    }

    protected function getDashboardFilter(): array
    {
        return array_merge(
            $this->getDefaultDashboardFilter(),
            session('dashboard_filter', Cache::get('dashboard_filter', [])),
        );
    }

    protected function applyDashboardFilters(
        $query,
        string  $tanggalCol = 'Tanggal',
        string  $kodeCol    = 'Kode',
        ?string $shiftCol   = null,
    ): mixed {
        $f = $this->getDashboardFilter();

        $query
            ->when($f['tanggal_awal'],  fn($q) => $q->whereDate($tanggalCol, '>=', $f['tanggal_awal']))
            ->when($f['tanggal_akhir'], fn($q) => $q->whereDate($tanggalCol, '<=', $f['tanggal_akhir']));

        return $query;
    }

    // ──────────────────────────────────────────────────────────────
    // Cache helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Cache key unik: widget + tanggal filter aktif.
     * Contoh: "widget_coal_getting_2025-01-13_2025-01-13"
     */
    protected function getDashboardCacheKey(string $widgetKey): string
    {
        $f = $this->getDashboardFilter();

        return implode('_', [
            'widget',
            $widgetKey,
            $f['tanggal_awal']  ?? 'null',
            $f['tanggal_akhir'] ?? 'null',
        ]);
    }

    /**
     * Ambil data dari cache. Kalau cache kosong/expired → query DB,
     * simpan ke cache $ttlMinutes menit, catat timestamp update-nya.
     *
     * Cara pakai di widget:
     *   return $this->rememberDashboardData('coal_getting', fn() => [...query...]);
     */
    protected function rememberDashboardData(
        string   $widgetKey,
        callable $callback,
        int      $ttlMinutes = 60,
    ): mixed {
        $cacheKey     = $this->getDashboardCacheKey($widgetKey);
        $timestampKey = $cacheKey . ':updated_at';

        return Cache::remember(
            $cacheKey,
            now()->addMinutes($ttlMinutes),
            function () use ($callback, $timestampKey, $ttlMinutes) {
                $result = $callback();

                // Simpan kapan terakhir kali data diambil dari DB
                Cache::put($timestampKey, now()->toDateTimeString(), now()->addMinutes($ttlMinutes));

                return $result;
            }
        );
    }

    protected function getDashboardHourlyCacheKey(string $widgetKey): string
    {
        $cacheKey = $this->getDashboardCacheKey($widgetKey);
        $timezone = 'Asia/Jakarta';

        return $cacheKey . '_' . now($timezone)->format('YmdH');
    }

    protected function rememberDashboardDataHourly(
        string   $widgetKey,
        callable $callback,
    ): mixed {
        $cacheKey     = $this->getDashboardHourlyCacheKey($widgetKey);
        $timestampKey = $cacheKey . ':updated_at';
        $timezone     = 'Asia/Jakarta';
        $expiresAt    = now($timezone)->copy()->startOfHour()->addHour();

        return Cache::remember(
            $cacheKey,
            $expiresAt,
            function () use ($callback, $timestampKey, $expiresAt) {
                $result = $callback();

                Cache::put($timestampKey, now()->toDateTimeString(), $expiresAt);

                return $result;
            }
        );
    }

    /**
     * Hapus cache widget ini — dipanggil saat filter berubah.
     */
    protected function forgetDashboardCache(string $widgetKey): void
    {
        $cacheKey     = $this->getDashboardHourlyCacheKey($widgetKey);
        $timestampKey = $cacheKey . ':updated_at';

        Cache::forget($cacheKey);
        Cache::forget($timestampKey);
    }

    /**
     * Kapan terakhir data widget ini di-query dari DB.
     * Return null jika belum pernah di-cache.
     */
    protected function getDashboardCacheUpdatedAt(string $widgetKey): ?string
    {
        $timestampKey = $this->getDashboardCacheKey($widgetKey) . ':updated_at';

        return Cache::get($timestampKey);
    }

    protected function summarizeDashboardChartRows($rows)
    {
        $limit = 15;

        if ($rows->count() <= $limit) {
            return $rows;
        }

        $visibleRows = $rows->take($limit);
        $otherRows   = $rows->slice($limit);

        return $visibleRows->push((object) [
            'Kode'   => 'Lainnya',
            'total'  => $otherRows->sum('total'),
            'ritase' => $otherRows->sum('ritase'),
        ]);
    }
}