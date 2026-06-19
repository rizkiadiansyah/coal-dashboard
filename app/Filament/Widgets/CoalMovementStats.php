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
            $coalGetting = $this->getCoalInTotal('Coal Getting');
            $bmssTrading = $this->getCoalInTotal('Coal In BMSS Trading');
            $outsource   = $this->getCoalInTotal('Out Source');

            // Crushing
            $crushing = CrusherActivity::query()
                ->whereIn('Crusher', ['FB08001', 'CP08001'])
                ->where('Activity', 'Coal Crushing')
                ->whereDate('Tanggal', today())
                ->sum('Tonase');

            // Hauling CY
            $haulingCY = round(
                HaulingCY::query()
                    ->whereDate('Tanggal', today())
                    ->sum('Netto') / 1000, 2
            );

            // Hauling KA
            $haulingKA = round(
                HaulingKA::query()
                    ->whereDate('Tanggal', today())
                    ->sum('Netto') / 1000, 2
            );

            // Stockpile WBS
            $wbs = round(
                StockpileWBS::query()
                    ->whereDate('TimeMasuk', today())
                    ->sum('Netto') / 1000, 2
            );

            // 2. Tambahan: Ambil data Pengiriman CY Hari Ini (dari database mysql_cy)
            $cyKirim = round(
                \Illuminate\Support\Facades\DB::connection('mysql_cy')
                    ->table('tblkirimcytransaksikirim_ka')
                    ->whereDate('WaktuBerangkat', today())
                    ->sum('Netto') / 1000, 2
            );

            $obRemoval = $this->getObRemovalTotal();
            $sr = $coalGetting > 0
                ? round($obRemoval['actual'] / $coalGetting, 2)
                : 0;

            // 3. Tambahan: Hitung Nilai Selisihnya
            $totalSelisihWBS = round($wbs - $cyKirim, 2);

            $targetCoalGetting = (float) \App\Models\PlanCoalIn::query()
                ->where('type', 'Coal Getting')
                ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') = ?", [now()->toDateString()])
                ->sum('tonase');
            $targetBmssTrading = (float) \App\Models\PlanCoalIn::query()
                ->where('type', 'Coal In BMSS Trading')
                ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') = ?", [now()->toDateString()])
                ->sum('tonase');
            $targetOutsource   = (float) \App\Models\PlanCoalIn::query()
                ->where('type', 'Out Source')
                ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') = ?", [now()->toDateString()])
                ->sum('tonase');
            $targetCrushing = (float) \App\Models\PlanCrushing::query()
                ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') = ?", [now()->toDateString()])
                ->whereExists(function ($sub) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('tblcrusheractivity as t')
                        ->join('tblcrushermaterial as m', 't.Kode_Material', '=', 'm.Material_id')
                        ->whereDate('t.Tanggal', now()->toDateString())
                        ->where('t.Tonase', '>', 0)
                        ->whereColumn('m.Material_desc', 'tblplanprodcrush.material_desc')
                        ->whereColumn('t.Crusher', 'tblplanprodcrush.equipment'); 
                })
                ->sum('tonase');
            $targetHaulingCY   = (float) \App\Models\PlanHaulingCY::query()
                ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') = ?", [now()->toDateString()])
                ->whereExists(function ($sub) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('tblkirimcytransaksikirim as t')
                        ->join('tblkirimcymaterial as m', 't.Kode', '=', 'm.Material_id')
                        ->whereDate('t.Tanggal', now()->toDateString())
                        ->where('t.Netto', '>', 0)
                        ->whereColumn('m.Material_desc', 'tblplanprodhaulcy.material_desc'); 
                })
                ->sum('tonase');
            $targetHaulingKA   = (float) \App\Models\PlanHaulingKA::query()
                ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') = ?", [now()->toDateString()])
                ->whereExists(function ($sub) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('tblkirimcytransaksikirim_ka as t')
                        ->join('tblkirimcymaterial as m', 't.Kode', '=', 'm.Material_id')
                        ->whereDate('t.Tanggal', now()->toDateString())
                        ->where('t.Netto', '>', 0)
                        ->whereColumn('m.Material_desc', 'tblplanprodhaul_ka.material_desc'); 
                })
                ->sum('tonase');
            $targetWbs         = (float) \App\Models\PlanStockpileWBS::query()
                ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') = ?", [now()->toDateString()])
                ->whereExists(function ($sub) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('tbltransaksimasuk as t')
                        ->join('tblmaterial as m', 't.Kode', '=', 'm.Material_id')
                        ->whereDate('t.TimeMasuk', now()->toDateString())
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
                    extraInfo   : null, // Tidak perlu extra info untuk ini
                ),

                $this->buildStat(
                    label       : 'Hauling Penerimaan KA di WBS',
                    actual      : $wbs,
                    target      : $targetWbs,
                    description : 'Terima di WBS',
                    color       : 'gray',
                    extraInfo   : "Selisih: {$totalSelisihWBS} Ton", // ← Kirim teks selisih ke sini
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

        $finalDesc = "{$description} | Target: {$targetFmt} BCM | {$pctLabel} | {$statusFmt}";
        if ($extraInfo) {
            $finalDesc .= " | {$extraInfo}";
        }

        return Stat::make($label, number_format($actual, 2, ',', '.') . ' BCM')
            ->description($finalDesc)
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
        $virtualDateRaw = "STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d')";
        $today          = now()->toDateString();

        // Target: sum SEMUA plan hari ini, tanpa filter material
        $target = (float) \App\Models\PlanObRemoval::query()
            ->whereRaw("$virtualDateRaw = ?", [$today])
            ->sum('plan');

        // Actual: sum hanya material yang actual > 0
        $actual = (float) \App\Models\PlanObRemoval::query()
            ->whereRaw("$virtualDateRaw = ?", [$today])
            ->where('actual', '>', 0)
            ->sum('actual');

        return [
            'actual' => round($actual, 2),
            'target' => round($target, 2),
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
