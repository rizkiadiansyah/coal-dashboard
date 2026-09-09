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
// use App\Models\CoalDailyTarget; // ← Uncomment setelah tabel siap

class CoalMovementMonthlyStats extends BaseWidget
{
    protected static bool $isLazy = true;
    protected static string $view = 'filament.widgets.coal-movement-monthly-stats';
    protected static ?int $sort = 3; // Di bawah widget harian (sort = 2)
    protected int | string | array $columnSpan = 'full';
    protected $listeners = ['filterUpdated' => 'refreshStats'];

    protected function getColumns(): int
    {
        return 4;
    }

    public function refreshStats(): void
    {
        $this->cachedStats = null;
        $this->forgetMonthlyCache('coal_movement_monthly_stats');
    }


    protected function getHeading(): ?string
    {
        return 'Capaian Bulan ' . now()->translatedFormat('F Y');
    }

    protected function getDescription(): ?string
    {
        $hariIni      = now()->day;
        $totalHari    = now()->daysInMonth;
        $sisaHari     = $totalHari - $hariIni;
        return "Hari ke-{$hariIni} dari {$totalHari} hari | Sisa {$sisaHari} hari lagi";
    }

    protected function getStats(): array
    {
        return $this->rememberMonthlyDataHourly('coal_movement_monthly_stats', function () {
            $startOfMonth = now()->startOfMonth();
            $now          = now();
            $currentYear  = (int) $now->year;
            $currentMonth = (int) $now->month;
            $currentDay   = (int) $now->day;

            // 1. Coal In bulanan dalam 1 query
            $coalInTotals = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblcoaltransaksimasuk')
                ->whereBetween('Tanggal', [$startOfMonth, $now])
                ->groupBy('type')
                ->selectRaw("type, ROUND(SUM(Netto) / 1000, 2) as total")
                ->pluck('total', 'type')
                ->toArray();

            $coalGetting = (float) ($coalInTotals['Coal Getting'] ?? 0);
            $bmssTrading = (float) ($coalInTotals['Coal In BMSS Trading'] ?? 0);
            $outsource   = (float) ($coalInTotals['Out Source'] ?? 0);

            // 2. Realisasi bulanan berjalan
            $crushing = (float) \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblcrusheractivity')
                ->whereBetween('Tanggal', [$startOfMonth, $now])
                ->sum('Tonase');

            $haulingCY = round(
                ((float) \Illuminate\Support\Facades\DB::connection('mysql_cy')
                    ->table('tblkirimcytransaksikirim')
                    ->whereBetween('Tanggal', [$startOfMonth, $now])
                    ->sum('Netto')) / 1000,
                2
            );

            $haulingKA = round(
                ((float) \Illuminate\Support\Facades\DB::connection('mysql_cy')
                    ->table('tblkirimcytransaksikirim_ka')
                    ->whereBetween('Tanggal', [$startOfMonth, $now])
                    ->sum('Netto')) / 1000,
                2
            );

            $wbs = round(
                ((float) \Illuminate\Support\Facades\DB::connection('mysql_wbs')
                    ->table('tbltransaksimasuk')
                    ->whereBetween('TimeMasuk', [$startOfMonth, $now])
                    ->sum('Netto')) / 1000,
                2
            );

            $obRemoval = $this->getObRemovalMonthly();
            $sr = $coalGetting > 0
                ? round($obRemoval['actual'] / $coalGetting, 2)
                : 0;

            // 3. Target Monthly Coal In Plans dalam 1 query
            $planCoalInTotals = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblplanprodcoalin')
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->where('hari_ke', '<=', $currentDay)
                ->groupBy('type')
                ->pluck(\Illuminate\Support\Facades\DB::raw('SUM(tonase)'), 'type')
                ->toArray();

            $targetCoalGetting = (float) ($planCoalInTotals['Coal Getting'] ?? 0);
            $targetBmssTrading = (float) ($planCoalInTotals['Coal In BMSS Trading'] ?? 0);
            $targetOutsource   = (float) ($planCoalInTotals['Out Source'] ?? 0);

            // 4. Target Monthly - Crushing (PHP Matching)
            $activeCrushMaterials = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblcrusheractivity as t')
                ->join('tblcrushermaterial as m', 't.Kode_Material', '=', 'm.Material_id')
                ->whereBetween('t.Tanggal', [$startOfMonth, $now])
                ->where('t.Tonase', '>', 0)
                ->selectRaw("CONCAT(t.Crusher, '|', m.Material_desc) as pair")
                ->pluck('pair')
                ->flip()
                ->toArray();

            $crushPlans = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblplanprodcrush')
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->where('hari_ke', '<=', $currentDay)
                ->get();

            $targetCrushing = 0.0;
            foreach ($crushPlans as $cp) {
                if (isset($activeCrushMaterials[$cp->equipment . '|' . $cp->material_desc])) {
                    $targetCrushing += (float) $cp->tonase;
                }
            }

            // 5. Target Monthly - Hauling ke CY Merapi (PHP Matching)
            $activeHaulCyDesc = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblkirimcytransaksikirim as t')
                ->join('tblkirimcymaterial as m', 't.Kode', '=', 'm.Material_id')
                ->whereBetween('t.Tanggal', [$startOfMonth, $now])
                ->where('t.Netto', '>', 0)
                ->pluck('m.Material_desc')
                ->flip()
                ->toArray();

            $haulCyPlans = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblplanprodhaulcy')
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->where('hari_ke', '<=', $currentDay)
                ->get();

            $targetHaulingCY = 0.0;
            foreach ($haulCyPlans as $hp) {
                if (isset($activeHaulCyDesc[$hp->material_desc])) {
                    $targetHaulingCY += (float) $hp->tonase;
                }
            }

            // 6. Target Monthly - Hauling pengiriman KA ke WBS (PHP Matching)
            $activeHaulKaDesc = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblkirimcytransaksikirim_ka as t')
                ->join('tblkirimcymaterial as m', 't.Kode', '=', 'm.Material_id')
                ->whereBetween('t.Tanggal', [$startOfMonth, $now])
                ->where('t.Netto', '>', 0)
                ->pluck('m.Material_desc')
                ->flip()
                ->toArray();

            $haulKaPlans = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                ->table('tblplanprodhaul_ka')
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->where('hari_ke', '<=', $currentDay)
                ->get();

            $targetHaulingKA = 0.0;
            foreach ($haulKaPlans as $kp) {
                if (isset($activeHaulKaDesc[$kp->material_desc])) {
                    $targetHaulingKA += (float) $kp->tonase;
                }
            }

            // 7. Target Monthly - Hauling Penerimaan KA di WBS (PHP Matching)
            $activeWbsDesc = \Illuminate\Support\Facades\DB::connection('mysql_wbs')
                ->table('tbltransaksimasuk as t')
                ->join('tblmaterial as m', 't.Kode', '=', 'm.Material_id')
                ->whereBetween('t.TimeMasuk', [$startOfMonth, $now])
                ->where('t.Netto', '>', 0)
                ->pluck('m.Material_desc')
                ->flip()
                ->toArray();

            $wbsPlans = \Illuminate\Support\Facades\DB::connection('mysql_wbs')
                ->table('tblplanprodstockwbs')
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->where('hari_ke', '<=', $currentDay)
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
                ),

                $this->buildStat(
                    label       : 'Coal In - Outsource',
                    actual      : $outsource,
                    target      : $targetOutsource,
                    description : 'Out Source',
                    color       : 'success',
                ),

                $this->buildStat(
                    label       : 'Crushing',
                    actual      : $crushing,
                    target      : $targetCrushing,
                    description : 'Hasil proses crusher',
                    color       : 'warning',
                ),

                $this->buildStat(
                    label       : 'Hauling ke CY Merapi',
                    actual      : $haulingCY,
                    target      : $targetHaulingCY,
                    description : 'Pengiriman ke CY',
                    color       : 'info',
                ),

                $this->buildStat(
                    label       : 'Hauling pengiriman KA ke WBS',
                    actual      : $haulingKA,
                    target      : $targetHaulingKA,
                    description : 'Pengiriman KA',
                    color       : 'success',
                ),

                $this->buildStat(
                    label       : 'Hauling Penerimaan KA di WBS',
                    actual      : $wbs,
                    target      : $targetWbs,
                    description : 'Terima di WBS',
                    color       : 'gray',
                ),
            ];
        });
    }

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

    private function buildProgressChart(float $pct): array
    {
        $chart = [];

        if ($pct >= 100) {
            for ($i = 1; $i <= 9; $i++) {
                $chart[] = 10;
            }
            $overPct = $pct - 100;
            $chart[] = min(10 + (int) round($overPct / 10), 20);
        } else {
            $filled = (int) round($pct / 10);
            for ($i = 1; $i <= 10; $i++) {
                $chart[] = $i <= $filled ? 10 : 1;
            }
        }

        return $chart;
    }
    private function getCoalInTotalMonthly(string $type, $startOfMonth, $now): float
    {
        // Langsung tembak kolom 'type' bawaan tabel transaksi tanpa JOIN
        $total = CoalGetting::query()
            ->where('type', $type)
            ->whereBetween('Tanggal', [$startOfMonth, $now])
            ->sum('Netto');

        return round($total / 1000, 2);
    }

    private function getObRemovalMonthly(): array
    {
        $now          = now();
        $currentYear  = (int) $now->year;
        $currentMonth = (int) $now->month;
        $currentDay   = (int) $now->day;

        $obRows = \Illuminate\Support\Facades\DB::connection('mysql_cy')
            ->table('tblobremoval')
            ->where('tahun', $currentYear)
            ->where('bulan', $currentMonth)
            ->where('hari_ke', '<=', $currentDay)
            ->selectRaw("SUM(IFNULL(plan, 0)) as total_plan, SUM(CASE WHEN actual > 0 THEN actual ELSE 0 END) as total_actual")
            ->first();

        return [
            'actual' => round((float) ($obRows->total_actual ?? 0), 2),
            'target' => round((float) ($obRows->total_plan ?? 0), 2),
        ];
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

        $pct       = ($actual / $target) * 100;
        $achieved  = $actual >= $target;
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

    private function rememberMonthlyDataHourly(string $widgetKey, callable $callback): mixed
    {
        $timezone     = 'Asia/Jakarta';
        $cacheKey     = implode('_', [
            'widget',
            $widgetKey,
            now($timezone)->format('Ym'),
            now($timezone)->format('YmdH'),
        ]);
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

    private function forgetMonthlyCache(string $widgetKey): void
    {
        $timezone     = 'Asia/Jakarta';
        $cacheKey     = implode('_', [
            'widget',
            $widgetKey,
            now($timezone)->format('Ym'),
            now($timezone)->format('YmdH'),
        ]);

        Cache::forget($cacheKey);
        Cache::forget($cacheKey . ':updated_at');
    }
}
