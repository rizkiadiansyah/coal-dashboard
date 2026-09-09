<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\CoalGetting;
use App\Models\CrusherActivity;
use App\Models\HaulingCY;
use App\Models\HaulingKA;
use App\Models\StockpileWBS;
use Illuminate\Support\Facades\Cache;

class CoalMovementStats extends BaseWidget
{
    protected static bool $isLazy = true;
    protected static string $view = 'filament.widgets.coal-movement-stats';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected $listeners = ['filterUpdated' => 'refreshStats'];
    protected function getColumns(): int
    {
        return 4;
    }

    // Judul & deskripsi widget
    protected function getHeading(): ?string
    {
        return 'Capaian per Hari Ini (' . now()->translatedFormat('l, d F Y') . ')';
    }

    protected function getDescription(): ?string
    {
        return 'Perbandingan realisasi hari ini terhadap target harian masing-masing aktivitas.';
    }

    public function refreshStats(): void
    {
        $this->cachedStats = null;
        $this->forgetDailyCache('coal_movement_stats');
    }

    protected function getStats(): array
    {
        return $this->rememberDailyDataHourly('coal_movement_stats', function () {
            $today = today();
            $now = now();
            $year = (int) $now->year;
            $month = (int) $now->month;
            $day = (int) $now->day;

            // 1. Coal In (Coal Getting, BMSS Trading, Out Source) dalam 1 query
            $coalInTotals = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblcoaltransaksimasuk')
                ->whereDate('Tanggal', $today)
                ->groupBy('type')
                ->selectRaw("type, ROUND(SUM(Netto) / 1000, 2) as total")
                ->pluck('total', 'type')
                ->toArray();

            $coalGetting = (float) ($coalInTotals['Coal Getting'] ?? 0);
            $bmssTrading = (float) ($coalInTotals['Coal In BMSS Trading'] ?? 0);
            $outsource   = (float) ($coalInTotals['Out Source'] ?? 0);

            // 2. Crushing
            $crushing = (float) \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblcrusheractivity')
                ->whereIn('Crusher', ['FB08001', 'CP08001'])
                ->where('Activity', 'Coal Crushing')
                ->whereDate('Tanggal', $today)
                ->sum('Tonase');

            // 3. Hauling CY
            $haulingCY = round(
                ((float) \Illuminate\Support\Facades\DB::connection('mysql_cy')
                    ->table('tblkirimcytransaksikirim')
                    ->whereDate('Tanggal', $today)
                    ->sum('Netto')) / 1000, 2
            );

            // 4. Hauling KA
            $haulingKA = round(
                ((float) \Illuminate\Support\Facades\DB::connection('mysql_cy')
                    ->table('tblkirimcytransaksikirim_ka')
                    ->whereDate('Tanggal', $today)
                    ->sum('Netto')) / 1000, 2
            );

            // 5. Stockpile WBS
            $wbs = round(
                ((float) \Illuminate\Support\Facades\DB::connection('mysql_wbs')
                    ->table('tbltransaksimasuk')
                    ->whereDate('TimeMasuk', $today)
                    ->sum('Netto')) / 1000, 2
            );

            // 6. Pengiriman CY Hari Ini
            $cyKirim = round(
                ((float) \Illuminate\Support\Facades\DB::connection('mysql_cy')
                    ->table('tblkirimcytransaksikirim_ka')
                    ->whereDate('WaktuBerangkat', $today)
                    ->sum('Netto')) / 1000, 2
            );

            $obRemoval = $this->getObRemovalTotal();
            $sr = $coalGetting > 0
                ? round($obRemoval['actual'] / $coalGetting, 2)
                : 0;

            $totalSelisihWBS = round($wbs - $cyKirim, 2);

            // 7. Target Coal In Plans (Coal Getting, BMSS Trading, Out Source) dalam 1 query
            $planCoalInTotals = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblplanprodcoalin')
                ->where('tahun', $year)
                ->where('bulan', $month)
                ->where('hari_ke', $day)
                ->groupBy('type')
                ->pluck(\Illuminate\Support\Facades\DB::raw('SUM(tonase)'), 'type')
                ->toArray();

            $targetCoalGetting = (float) ($planCoalInTotals['Coal Getting'] ?? 0);
            $targetBmssTrading = (float) ($planCoalInTotals['Coal In BMSS Trading'] ?? 0);
            $targetOutsource   = (float) ($planCoalInTotals['Out Source'] ?? 0);

            // 8. Target Crushing (PHP Matching dari material aktif hari ini)
            $activeCrushMaterials = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblcrusheractivity as t')
                ->join('tblcrushermaterial as m', 't.Kode_Material', '=', 'm.Material_id')
                ->whereDate('t.Tanggal', $today)
                ->where('t.Tonase', '>', 0)
                ->selectRaw("CONCAT(t.Crusher, '|', m.Material_desc) as pair")
                ->pluck('pair')
                ->flip()
                ->toArray();

            $crushPlans = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblplanprodcrush')
                ->where('tahun', $year)
                ->where('bulan', $month)
                ->where('hari_ke', $day)
                ->get();

            $targetCrushing = 0.0;
            foreach ($crushPlans as $cp) {
                if (isset($activeCrushMaterials[$cp->equipment . '|' . $cp->material_desc])) {
                    $targetCrushing += (float) $cp->tonase;
                }
            }

            // 9. Target Hauling CY (PHP Matching dari material aktif hari ini)
            $activeHaulCyDesc = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblkirimcytransaksikirim as t')
                ->join('tblkirimcymaterial as m', 't.Kode', '=', 'm.Material_id')
                ->whereDate('t.Tanggal', $today)
                ->where('t.Netto', '>', 0)
                ->pluck('m.Material_desc')
                ->flip()
                ->toArray();

            $haulCyPlans = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblplanprodhaulcy')
                ->where('tahun', $year)
                ->where('bulan', $month)
                ->where('hari_ke', $day)
                ->get();

            $targetHaulingCY = 0.0;
            foreach ($haulCyPlans as $hp) {
                if (isset($activeHaulCyDesc[$hp->material_desc])) {
                    $targetHaulingCY += (float) $hp->tonase;
                }
            }

            // 10. Target Hauling KA (PHP Matching dari material aktif hari ini)
            $activeHaulKaDesc = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblkirimcytransaksikirim_ka as t')
                ->join('tblkirimcymaterial as m', 't.Kode', '=', 'm.Material_id')
                ->whereDate('t.Tanggal', $today)
                ->where('t.Netto', '>', 0)
                ->pluck('m.Material_desc')
                ->flip()
                ->toArray();

            $haulKaPlans = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblplanprodhaul_ka')
                ->where('tahun', $year)
                ->where('bulan', $month)
                ->where('hari_ke', $day)
                ->get();

            $targetHaulingKA = 0.0;
            foreach ($haulKaPlans as $kp) {
                if (isset($activeHaulKaDesc[$kp->material_desc])) {
                    $targetHaulingKA += (float) $kp->tonase;
                }
            }

            // 11. Target WBS (PHP Matching dari material aktif hari ini)
            $activeWbsDesc = \Illuminate\Support\Facades\DB::connection('mysql_wbs')
                ->table('tbltransaksimasuk as t')
                ->join('tblmaterial as m', 't.Kode', '=', 'm.Material_id')
                ->whereDate('t.TimeMasuk', $today)
                ->where('t.Netto', '>', 0)
                ->pluck('m.Material_desc')
                ->flip()
                ->toArray();

            $wbsPlans = \Illuminate\Support\Facades\DB::connection('mysql_wbs')
                ->table('tblplanprodstockwbs')
                ->where('tahun', $year)
                ->where('bulan', $month)
                ->where('hari_ke', $day)
                ->get();

            $targetWbs = 0.0;
            foreach ($wbsPlans as $wp) {
                if (isset($activeWbsDesc[$wp->material_desc])) {
                    $targetWbs += (float) $wp->tonase;
                }
            }

            return [
                $this->buildStatBcm(
                    label       : 'OB Removal',
                    actual      : $obRemoval['actual'],
                    target      : $obRemoval['target'],
                    description : 'OB Removal',
                    color       : 'warning',
                ),
                $this->buildStat(
                    label       : 'Coal In - Coal Getting',
                    actual      : $coalGetting,
                    target      : $targetCoalGetting,
                    description : 'Coal Getting',
                    color       : 'primary',
                    extraInfo   : "SR: {$sr} BCM/Ton",
                ),

                $this->buildStat(
                    label       : 'Coal In - BMSS Trading',
                    actual      : $bmssTrading,
                    target      : $targetBmssTrading,
                    description : 'BMSS Trading',
                    color       : 'info',
                    extraInfo   : null,
                ),

                $this->buildStat(
                    label       : 'Coal In - Outsource',
                    actual      : $outsource,
                    target      : $targetOutsource,
                    description : 'Out Source',
                    color       : 'success',
                    extraInfo   : null,
                ),

                $this->buildStat(
                    label       : 'Crushing',
                    actual      : $crushing,
                    target      : $targetCrushing,
                    description : 'Hasil proses crusher',
                    color       : 'warning',
                    extraInfo   : null,
                ),

                $this->buildStat(
                    label       : 'Hauling ke CY Merapi',
                    actual      : $haulingCY,
                    target      : $targetHaulingCY,
                    description : 'Pengiriman ke CY',
                    color       : 'info',
                    extraInfo   : null,
                ),

                $this->buildStat(
                    label       : 'Hauling pengiriman KA ke WBS',
                    actual      : $haulingKA,
                    target      : $targetHaulingKA,
                    description : 'Pengiriman KA',
                    color       : 'success',
                    extraInfo   : null,
                ),

                $this->buildStat(
                    label       : 'Hauling Penerimaan KA di WBS',
                    actual      : $wbs,
                    target      : $targetWbs,
                    description : 'Terima di WBS',
                    color       : 'gray',
                    extraInfo   : "Selisih: {$totalSelisihWBS} Ton",
                ),
            ];
        });
    }

    /**
     * Build stat card dengan indikator target harian.
     *
     * - target = 0  → tampil biasa, tanpa progress
     * - actual >= target → success (✓ Tercapai, tampil kelebihan jika ada)
     * - actual < target  → danger  (tampil % capaian + sisa Ton)
     */
    private function buildStat(
        string  $label,
        float   $actual,
        float   $target,
        string  $description,
        string  $color,
        ?string $extraInfo = null,
    ): Stat {
        if ($target <= 0) {
            $lines = ['Target belum diset'];
            if ($extraInfo) {
                $lines[] = $extraInfo;
            }
            $html = new \Illuminate\Support\HtmlString(
                '<div class="flex flex-col gap-0.5 mt-0.5 text-xs leading-normal text-gray-500 dark:text-gray-400">' .
                implode('', array_map(fn($l) => '<div>' . e($l) . '</div>', $lines)) .
                '</div>'
            );

            return Stat::make($label, number_format($actual, 2, ',', '.') . ' Ton')
                ->description($html)
                ->color($color);
        }

        $pct       = ($actual / $target) * 100;
        $achieved  = $actual >= $target;
        $statColor = $achieved ? 'success' : ($pct >= 70.0 ? 'warning' : 'danger');
        $pctLabel  = number_format($pct, 1) . '%';
        $targetFmt = number_format($target, 2, ',', '.');

        if ($achieved) {
            $lebihFmt  = number_format($actual - $target, 2, ',', '.');
            $statusFmt = $actual > $target
                ? "✓ Tercapai (+{$lebihFmt} Ton)"
                : '✓ Tercapai';
        } else {
            $sisaFmt   = number_format($target - $actual, 2, ',', '.');
            $statusFmt = "↑ Sisa {$sisaFmt} Ton";
        }

        $lines = [
            "Target: {$targetFmt} Ton | {$pctLabel}",
            $statusFmt,
        ];
        if ($extraInfo) {
            $lines[] = $extraInfo;
        }

        $html = new \Illuminate\Support\HtmlString(
            '<div class="flex flex-col gap-0.5 mt-0.5 text-xs leading-normal">' .
            implode('', array_map(fn($l) => '<div>' . e($l) . '</div>', $lines)) .
            '</div>'
        );

        return Stat::make($label, number_format($actual, 2, ',', '.') . ' Ton')
            ->description($html)
            ->color($statColor)
            ->chart($this->buildProgressChart($pct));
    }

    private function buildStatBcm(
        string  $label,
        float   $actual,
        float   $target,
        string  $description,
        string  $color,
        ?string $extraInfo = null,
    ): Stat {
        if ($target <= 0) {
            $lines = ['Target belum diset'];
            if ($extraInfo) {
                $lines[] = $extraInfo;
            }
            $html = new \Illuminate\Support\HtmlString(
                '<div class="flex flex-col gap-0.5 mt-0.5 text-xs leading-normal text-gray-500 dark:text-gray-400">' .
                implode('', array_map(fn($l) => '<div>' . e($l) . '</div>', $lines)) .
                '</div>'
            );

            return Stat::make($label, number_format($actual, 2, ',', '.') . ' BCM')
                ->description($html)
                ->color($color);
        }

        $pct      = ($actual / $target) * 100;
        $achieved = $actual >= $target;
        $statColor = $achieved ? 'success' : ($pct >= 70.0 ? 'warning' : 'danger');

        $pctLabel  = number_format($pct, 1) . '%';
        $targetFmt = number_format($target, 2, ',', '.');

        if ($achieved) {
            $lebihFmt  = number_format($actual - $target, 2, ',', '.');
            $statusFmt = $actual > $target
                ? "✓ Tercapai (+{$lebihFmt} BCM)"
                : '✓ Tercapai';
        } else {
            $sisaFmt   = number_format($target - $actual, 2, ',', '.');
            $statusFmt = "↑ Sisa {$sisaFmt} BCM";
        }

        $lines = [
            "Target: {$targetFmt} BCM | {$pctLabel}",
            $statusFmt,
        ];
        if ($extraInfo) {
            $lines[] = $extraInfo;
        }

        $html = new \Illuminate\Support\HtmlString(
            '<div class="flex flex-col gap-0.5 mt-0.5 text-xs leading-normal">' .
            implode('', array_map(fn($l) => '<div>' . e($l) . '</div>', $lines)) .
            '</div>'
        );

        return Stat::make($label, number_format($actual, 2, ',', '.') . ' BCM')
            ->description($html)
            ->color($statColor)
            ->chart($this->buildProgressChart($pct));
    }

    /**
     * Sparkline untuk visualisasi progress.
     * Jika melebihi 100%, semua bar penuh + bar terakhir extra tinggi
     * sebagai penanda "over target".
     */
    private function buildProgressChart(float $pct): array
    {
        $chart = [];

        if ($pct >= 100) {
            // Semua bar penuh
            for ($i = 1; $i <= 9; $i++) {
                $chart[] = 10;
            }
            // Bar terakhir extra tinggi proporsional kelebihan (max 20)
            $overPct  = $pct - 100;
            $chart[]  = min(10 + (int) round($overPct / 10), 20);
        } else {
            // Bar normal: 10 titik, isi sesuai desil persentase
            $filled = (int) round($pct / 10);
            for ($i = 1; $i <= 10; $i++) {
                $chart[] = $i <= $filled ? 10 : 1;
            }
        }

        return $chart;
    }

    private function getCoalInTotal(string $type): float
    {
        $total = CoalGetting::query()
            ->where('type', $type)
            ->whereDate('Tanggal', today())
            ->sum('Netto');

        return round($total / 1000, 2);
    }
    
    private function getObRemovalTotal(): array
    {
        $now = now();
        $obRows = \Illuminate\Support\Facades\DB::connection('mysql_cy')
            ->table('tblobremoval')
            ->where('tahun', (int) $now->year)
            ->where('bulan', (int) $now->month)
            ->where('hari_ke', (int) $now->day)
            ->selectRaw("SUM(IFNULL(plan, 0)) as total_plan, SUM(CASE WHEN actual > 0 THEN actual ELSE 0 END) as total_actual")
            ->first();

        return [
            'actual' => round((float) ($obRows->total_actual ?? 0), 2),
            'target' => round((float) ($obRows->total_plan ?? 0), 2),
        ];
    }

    private function rememberDailyDataHourly(string $widgetKey, callable $callback): mixed
    {
        $timezone     = 'Asia/Jakarta';
        $cacheKey     = $this->getDailyCacheKey($widgetKey);
        $timestampKey = $cacheKey . ':updated_at';
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

    private function forgetDailyCache(string $widgetKey): void
    {
        $cacheKey = $this->getDailyCacheKey($widgetKey);

        Cache::forget($cacheKey);
        Cache::forget($cacheKey . ':updated_at');
    }

    private function getDailyCacheKey(string $widgetKey): string
    {
        $timezone = 'Asia/Jakarta';

        return implode('_', [
            'widget',
            $widgetKey,
            now($timezone)->toDateString(),
            now($timezone)->format('YmdH'),
        ]);
    }
}
