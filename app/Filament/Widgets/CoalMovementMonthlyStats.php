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
    protected static string $view = 'filament.widgets.coal-movement-monthly-stats';
    protected static ?int $sort = 3; // Di bawah widget harian (sort = 2)
    protected int | string | array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
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

            $coalGetting = $this->getCoalInTotalMonthly('Coal Getting', $startOfMonth, $now);
            $bmssTrading = $this->getCoalInTotalMonthly('Coal In BMSS Trading', $startOfMonth, $now);
            $outsource   = $this->getCoalInTotalMonthly('Out Source', $startOfMonth, $now);

            // Realisasi bulanan berjalan: tanggal 1 sampai saat ini.
            $crushing = CrusherActivity::query()
                ->whereBetween('Tanggal', [$startOfMonth, $now])
                ->sum('Tonase');

            $haulingCY = round(
                HaulingCY::query()
                    ->whereBetween('Tanggal', [$startOfMonth, $now])
                    ->sum('Netto') / 1000,
                2
            );

            $haulingKA = round(
                HaulingKA::query()
                    ->whereBetween('Tanggal', [$startOfMonth, $now])
                    ->sum('Netto') / 1000,
                2
            );

            $wbs = round(
                StockpileWBS::query()
                    ->whereBetween('TimeMasuk', [$startOfMonth, $now])
                    ->sum('Netto') / 1000,
                2
            );

            $obRemoval = $this->getObRemovalMonthly();
            $sr = $coalGetting > 0
                ? round($obRemoval['actual'] / $coalGetting, 2)
                : 0;
            $daysInMonth = now()->daysInMonth;
            $currentYear = now()->year;
            $currentMonth = now()->month;

            // 1. Target Monthly - Coal Getting
            $targetCoalGetting = (float) \App\Models\PlanCoalIn::query()
                ->where('type', 'Coal Getting')
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') <= ?", [now()->toDateString()])
                ->sum('tonase');

            // 2. Target Monthly - BMSS Trading
            $targetBmssTrading = (float) \App\Models\PlanCoalIn::query()
                ->where('type', 'Coal In BMSS Trading')
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') <= ?", [now()->toDateString()])
                ->sum('tonase');

            // 3. Target Monthly - Outsource
            $targetOutsource = (float) \App\Models\PlanCoalIn::query()
                ->where('type', 'Out Source')
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') <= ?", [now()->toDateString()])
                ->sum('tonase');

            // 4. Target Monthly - Crushing (Melibatkan Validasi Crusher + Equipment Sebulan)
            $targetCrushing = (float) \App\Models\PlanCrushing::query()
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->whereExists(function ($sub) use ($currentYear, $currentMonth) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('tblcrusheractivity as t')
                        ->join('tblcrushermaterial as m', 't.Kode_Material', '=', 'm.Material_id')
                        ->whereRaw("YEAR(t.Tanggal) = ? AND MONTH(t.Tanggal) = ?", [$currentYear, $currentMonth])
                        ->where('t.Tonase', '>', 0)
                        ->whereColumn('m.Material_desc', 'tblplanprodcrush.material_desc')
                        ->whereColumn('t.Crusher', 'tblplanprodcrush.equipment'); 
                })
                ->sum('tonase');

            // 5. Target Monthly - Hauling ke CY Merapi
            $targetHaulingCY = (float) \App\Models\PlanHaulingCY::query()
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->whereExists(function ($sub) use ($currentYear, $currentMonth) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('tblkirimcytransaksikirim as t')
                        ->join('tblkirimcymaterial as m', 't.Kode', '=', 'm.Material_id')
                        ->whereRaw("YEAR(t.Tanggal) = ? AND MONTH(t.Tanggal) = ?", [$currentYear, $currentMonth])
                        ->where('t.Netto', '>', 0)
                        ->whereColumn('m.Material_desc', 'tblplanprodhaulcy.material_desc'); 
                })
                ->sum('tonase');

            // 6. Target Monthly - Hauling pengiriman KA ke WBS
            $targetHaulingKA = (float) \App\Models\PlanHaulingKA::query()
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->whereExists(function ($sub) use ($currentYear, $currentMonth) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('tblkirimcytransaksikirim_ka as t')
                        ->join('tblkirimcymaterial as m', 't.Kode', '=', 'm.Material_id')
                        ->whereRaw("YEAR(t.Tanggal) = ? AND MONTH(t.Tanggal) = ?", [$currentYear, $currentMonth])
                        ->where('t.Netto', '>', 0)
                        ->whereColumn('m.Material_desc', 'tblplanprodhaul_ka.material_desc'); 
                })
                ->sum('tonase');

            // 7. Target Monthly - Hauling Penerimaan KA di WBS
            $targetWbs = (float) \App\Models\PlanStockpileWBS::query()
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->whereExists(function ($sub) use ($currentYear, $currentMonth) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('tbltransaksimasuk as t')
                        ->join('tblmaterial as m', 't.Kode', '=', 'm.Material_id')
                        ->whereRaw("YEAR(t.TimeMasuk) = ? AND MONTH(t.TimeMasuk) = ?", [$currentYear, $currentMonth])
                        ->where('t.Netto', '>', 0)
                        ->whereColumn('m.Material_desc', 'tblplanprodstockwbs.material_desc'); 
                })
                ->sum('tonase');
                
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
            $descTeks = $description . ' — target belum diset';
            if ($extraInfo) {
                $descTeks .= " | {$extraInfo}";
            }
            return Stat::make($label, number_format($actual, 2, ',', '.') . ' Ton')
                ->description($descTeks)
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

        $finalDesc = "{$description} | Target: {$targetFmt} Ton | {$pctLabel} | {$statusFmt}";
        if ($extraInfo) {
            $finalDesc .= " | {$extraInfo}";
        }

        return Stat::make($label, number_format($actual, 2, ',', '.') . ' Ton')
            ->description($finalDesc)
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

    private function getCoalInTotalMonthly(string $source, $startOfMonth, $now): float
    {
        $total = CoalGetting::query()
            ->join('tblcoalmaterial as m', 'tblcoaltransaksimasuk.Kode', '=', 'm.Material_id')
            ->where('m.Source', $source)
            ->whereBetween('tblcoaltransaksimasuk.Tanggal', [$startOfMonth, $now])
            ->sum('tblcoaltransaksimasuk.Netto');

        return round($total / 1000, 2);
    }

    private function getObRemovalMonthly(): array
    {
        $today = now()->toDateString();
        $virtualDateRaw = "STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d')";

        // Target: sum plan tanggal 1 sampai hari ini
        $target = (float) \App\Models\PlanObRemoval::query()
            ->where('tahun', now()->year)
            ->where('bulan', now()->month)
            ->whereRaw("$virtualDateRaw <= ?", [$today])
            ->sum('plan');

        // Actual: sum actual > 0, tanggal 1 sampai hari ini
        $actual = (float) \App\Models\PlanObRemoval::query()
            ->where('tahun', now()->year)
            ->where('bulan', now()->month)
            ->whereRaw("$virtualDateRaw <= ?", [$today])
            ->where('actual', '>', 0)
            ->sum('actual');

        return [
            'actual' => round($actual, 2),
            'target' => round($target, 2),
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
            $descTeks = $description . ' — target belum diset';
            if ($extraInfo) {
                $descTeks .= " | {$extraInfo}";
            }
            return Stat::make($label, number_format($actual, 2, ',', '.') . ' BCM')
                ->description($descTeks)
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

        $finalDesc = "{$description} | Target: {$targetFmt} BCM | {$pctLabel} | {$statusFmt}";
        if ($extraInfo) {
            $finalDesc .= " | {$extraInfo}";
        }

        return Stat::make($label, number_format($actual, 2, ',', '.') . ' BCM')
            ->description($finalDesc)
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
}
