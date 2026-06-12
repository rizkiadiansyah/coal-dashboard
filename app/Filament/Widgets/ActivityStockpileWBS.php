<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardPeriod;
use App\Models\StockpileWBS;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ActivityStockpileWBS extends ChartWidget
{
    use FiltersDashboardPeriod;

    protected static string $view = 'filament.widgets.activity-chart-widget';
    protected static ?int $sort = 9;
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;
    protected $listeners = ['filterUpdated' => 'refreshChart'];

    public string $title = 'Activity Hauling Penerimaan KA di WBS';
    public string $badge = 'Penerimaan KA di WBS';

    public function refreshChart(): void
    {
        $this->cachedData = null;
        $this->forgetDashboardCache('activity_stockpile_wbs');
    }

    public function getHeading(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->badge . ' - Total ' . number_format($this->getActivityData()['total'], 2) . ' ton';
    }

    protected function getData(): array
    {
        $data = $this->getActivityData();

        return [
            'datasets' => [
                [
                    'label'           => 'Tonase',
                    'data'            => $data['chart']->pluck('total_tonase')->map(fn($v) => (float) $v)->all(),
                    'noKAList'        => $data['chart']->pluck('no_ka_list')->all(),
                    'backgroundColor' => '#ea580c',
                    'borderColor'     => '#c2410c',
                    'borderWidth'     => 1,
                    'borderRadius'    => 6,
                ],
            ],
            'labels' => $data['chart']->pluck('Material_desc')->all(),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                clip: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: false,
                        external: function(context) {
                            var tooltipEl = document.getElementById('chartjs-custom-tooltip');
                            if (!tooltipEl) return;

                            if (context.tooltip.opacity === 0) {
                                if (!tooltipEl._isHovered) {
                                    tooltipEl.style.display = 'none';
                                }
                                return;
                            }

                            if (!tooltipEl._listenerAdded) {
                                tooltipEl._listenerAdded = true;
                                tooltipEl.addEventListener('mouseenter', function() {
                                    tooltipEl._isHovered = true;
                                });
                                tooltipEl.addEventListener('mouseleave', function() {
                                    tooltipEl._isHovered = false;
                                    tooltipEl.style.display = 'none';
                                });
                            }

                            var dataPoints = context.tooltip.dataPoints;
                            if (!dataPoints || !dataPoints.length) return;

                            var dataIndex = dataPoints[0].dataIndex;
                            var dataset = dataPoints[0].dataset;
                            var label = dataPoints[0].label;
                            var value = dataPoints[0].parsed.x;
                            var noKA = dataset.noKAList ? dataset.noKAList[dataIndex] : null;

                            tooltipEl.innerHTML = '';
                            var headerEl = document.createElement('div');
                            headerEl.style.display = 'flex';
                            headerEl.style.alignItems = 'center';
                            headerEl.style.marginBottom = '6px';
                            headerEl.style.gap = '6px';

                            var colorBox = document.createElement('span');
                            colorBox.style.display = 'inline-block';
                            colorBox.style.width = '12px';
                            colorBox.style.height = '12px';
                            colorBox.style.borderRadius = '2px';
                            colorBox.style.backgroundColor = dataset.backgroundColor || '#2563eb';
                            colorBox.style.flexShrink = '0';

                            var titleEl = document.createElement('span');
                            titleEl.style.fontWeight = 'bold';
                            titleEl.textContent = label;

                            headerEl.appendChild(colorBox);
                            headerEl.appendChild(titleEl);
                            tooltipEl.appendChild(headerEl);
                            
                            var totalEl = document.createElement('div');
                            totalEl.style.marginBottom = '8px';
                            totalEl.textContent = 'Total: ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' ton';
                            tooltipEl.appendChild(totalEl);

                            if (noKA) {
                                var kaLabel = document.createElement('div');
                                kaLabel.style.fontWeight = 'bold';
                                kaLabel.style.marginBottom = '4px';
                                kaLabel.textContent = 'No KA:';
                                tooltipEl.appendChild(kaLabel);

                                var kaList = noKA.split(', ');
                                for (var i = 0; i < kaList.length; i += 3) {
                                    var rowEl = document.createElement('div');
                                    rowEl.textContent = '  ' + kaList.slice(i, i + 3).join(', ');
                                    tooltipEl.appendChild(rowEl);
                                }
                            }

                            tooltipEl.style.display = 'block';

                            var canvasRect = context.chart.canvas.getBoundingClientRect();
                            var x = canvasRect.left + context.tooltip.caretX + 2;
                            var y = canvasRect.top + context.tooltip.caretY - 10;

                            var tooltipWidth = tooltipEl.offsetWidth;
                            var tooltipHeight = tooltipEl.offsetHeight;

                            if (x + tooltipWidth > window.innerWidth - 20) {
                                x = canvasRect.left + context.tooltip.caretX - tooltipWidth - 2;
                            }

                            if (y + tooltipHeight > window.innerHeight - 20) {
                                y = window.innerHeight - tooltipHeight - 20;
                            }

                            if (y < 10) y = 10;
                            if (x < 10) x = 10;

                            tooltipEl.style.left = x + 'px';
                            tooltipEl.style.top = y + 'px';
                            tooltipEl.style.paddingLeft = '14px';
                            tooltipEl.style.paddingRight = '14px';
                        }
                    }
                },
                datasets: {
                    bar: {
                        categoryPercentage: 0.95,
                        barPercentage: 0.85,
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => new Intl.NumberFormat('id-ID').format(value),
                            font: { size: 10 }
                        }
                    },
                    y: {
                        afterFit: function(scaleInstance) {
                            scaleInstance.width = 150;
                        },
                        ticks: {
                            autoSkip: false,
                            callback: function(value, index, values) {
                                const label = this.getLabelForValue(value);
                                if (label.length > 20) {
                                    return label.substring(0, 20) + '…';
                                }
                                return label;
                            },
                            font: { size: 9 }
                        }
                    }
                }
            }
        JS);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public function getStockpileWBSStats(): array
    {
        return $this->getActivityData()['stats'];
    }

    public function getActivityData(): array
    {
        return $this->rememberDashboardDataHourly('activity_stockpile_wbs', function () {

            // ── 1. Data Utama Chart ───────────────────────────────────────
            $chart = $this->applyDashboardFilters(
                StockpileWBS::query()
                    ->selectRaw("
                        IFNULL(m.Material_desc, k.Kode)                                             AS Material_desc,
                        ROUND(SUM(k.Netto) / 1000, 2)                                               AS total_tonase,
                        COUNT(DISTINCT k.NoKA)                                                      AS total_ka,
                        SUM(CASE WHEN k.NoCT IS NOT NULL AND k.NoCT <> '' THEN 1 ELSE 0 END)         AS total_ct,
                        GROUP_CONCAT(DISTINCT k.NoKA ORDER BY k.NoKA SEPARATOR ', ')                 AS no_ka_list
                    ")
                    ->from('tbltransaksimasuk as k')
                    ->leftJoin('tblmaterial as m', 'k.Kode', '=', 'm.Material_id'),
                'k.TimeMasuk',
            )
                ->groupBy('m.Material_desc', 'k.Kode')
                ->orderByDesc('total_tonase')
                ->get();

            // ── 2. Summary Database Lokal (Penerimaan) ───────────────────
            $summary = $this->applyDashboardFilters(
                StockpileWBS::query()
                    ->selectRaw("
                        COUNT(DISTINCT k.NoKA) AS total_ka,
                        ROUND(SUM(k.Netto) / 1000, 2) AS total_tonase,
                        SUM(CASE WHEN k.NoCT IS NOT NULL AND k.NoCT <> '' THEN 1 ELSE 0 END) AS total_ct
                    ")
                    ->from('tbltransaksimasuk as k'),
                'k.TimeMasuk',
            )->first();

            // ── 3. AMBIL DATA DARI DATABASE & TABEL LAIN (Koneksi: mysql_cy) ──
            // Pastikan 'mysql_cy' sudah terdaftar di config/database.php kamu
            $queryKirim = DB::connection('mysql_cy')
                ->table('tblkirimcytransaksikirim_ka')
                ->selectRaw("ROUND(SUM(Netto) / 1000, 2) AS total_kirim_cy");

            // Terapkan filter tanggal yang sama agar sinkron dengan data penerimaan WBS
            // Sesuaikan parameter kedua ('TanggalKirim' / 'Created_at') dengan kolom tanggal di tabel tblkirimcytransaksikirim
            $summaryKirim = $this->applyDashboardFilters($queryKirim, 'WaktuBerangkat')->first();
            
            $totalKirim = $summaryKirim->total_kirim_cy ?? 0;

            // ── 4. Perhitungan Hari & Toleransi Selisih ──────────────────
            $filter     = $this->getDashboardFilter();
            $jumlahHari = max(1, (int) \Carbon\Carbon::parse($filter['tanggal_awal'])
                ->diffInDays(\Carbon\Carbon::parse($filter['tanggal_akhir'])) + 1);

            $toleransiDesimal = ($jumlahHari * 0.07) / 100;

            $totalTonase  = $summary->total_tonase ?? 0;
            
            // Hitung selisih: Penerimaan WBS ($totalTonase) dikurangi Pengiriman CY ($totalKirim)
            $totalSelisih = round($totalTonase - $totalKirim, 2);

            $selisihDesimal  = $totalTonase > 0 ? $totalSelisih / $totalTonase : 0;
            $selisihPersen   = round($selisihDesimal * 100, 4);
            $toleransiPersen = round($toleransiDesimal * 100, 4);
            $statusSelisih   = abs($selisihDesimal) <= $toleransiDesimal ? 'normal' : 'melebihi';

            return [
                'chart' => $chart,
                'total' => $chart->sum('total_tonase') ?? 0,
                'stats' => [
                    'total_tonase'        => $totalTonase,
                    'total_kirim'         => $totalKirim, // Diisi dari database mysql_cy
                    'total_selisih'       => $totalSelisih,
                    'total_ka'            => $summary->total_ka ?? 0,
                    'total_ct'            => $summary->total_ct ?? 0,
                    'jumlah_hari'         => $jumlahHari,
                    'selisih_persen'      => $selisihPersen,
                    'toleransi_persen'    => $toleransiPersen,
                    'status_selisih'      => $statusSelisih,
                    'selisih_per_tanggal' => collect(), 
                    'shift_rows'          => collect(),
                ],
            ];
        });
    }

    public function getPlanData(): array
    {
        return ['target' => 0.0];
    }
}