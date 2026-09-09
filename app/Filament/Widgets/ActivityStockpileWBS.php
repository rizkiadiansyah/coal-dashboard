<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardPeriod;
use App\Models\StockpileWBS;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ActivityStockpileWBS extends ChartWidget
{
    protected static bool $isLazy = true;
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
            // 1. Ambil master material dari database WBS ke PHP memory
            $matLookup = DB::connection('mysql_wbs')
                ->table('tblmaterial')
                ->pluck('Material_desc', 'Material_id')
                ->toArray();

            // 2. Query transaksi lokal WBS tanpa SQL JOIN
            $wbsQuery = DB::connection('mysql_wbs')
                ->table('tbltransaksimasuk')
                ->selectRaw("Kode, NoKA, NoCT, Netto");

            $rawRows = $this->applyDashboardFilters($wbsQuery, 'TimeMasuk')->get();

            // 3. Agregasi chart dan summary di level PHP runtime
            $byMaterial = [];
            $totalKAOverall = [];
            $totalCTCount = 0;
            $totalTonaseOverall = 0.0;

            foreach ($rawRows as $row) {
                $kode   = (string) $row->Kode;
                $desc   = $matLookup[$kode] ?? $kode;
                $tonase = ((float) $row->Netto) / 1000;
                $totalTonaseOverall += $tonase;

                $noKA = trim((string) ($row->NoKA ?? ''));
                $noCT = trim((string) ($row->NoCT ?? ''));

                if ($noKA !== '') {
                    $totalKAOverall[$noKA] = true;
                }
                if ($noCT !== '') {
                    $totalCTCount++;
                }

                if (!isset($byMaterial[$desc])) {
                    $byMaterial[$desc] = [
                        'Material_desc' => $desc,
                        'tonase'        => 0.0,
                        'ka_list'       => [],
                        'ct_count'      => 0,
                    ];
                }
                $byMaterial[$desc]['tonase'] += $tonase;
                if ($noKA !== '') {
                    $byMaterial[$desc]['ka_list'][$noKA] = true;
                }
                if ($noCT !== '') {
                    $byMaterial[$desc]['ct_count']++;
                }
            }

            $chartRows = [];
            foreach ($byMaterial as $desc => $item) {
                $sortedKAs = array_keys($item['ka_list']);
                sort($sortedKAs);
                $chartRows[] = (object) [
                    'Material_desc' => $desc,
                    'total_tonase'  => round($item['tonase'], 2),
                    'total_ka'      => count($item['ka_list']),
                    'total_ct'      => $item['ct_count'],
                    'no_ka_list'    => implode(', ', $sortedKAs),
                ];
            }
            usort($chartRows, fn($a, $b) => $b->total_tonase <=> $a->total_tonase);
            $chartCollection = collect($chartRows);

            // 4. Ambil data pengiriman CY dari koneksi mysql_cy
            $queryKirim = DB::connection('mysql_cy')
                ->table('tblkirimcytransaksikirim_ka')
                ->selectRaw("ROUND(SUM(Netto) / 1000, 2) AS total_kirim_cy");

            $summaryKirim = $this->applyDashboardFilters($queryKirim, 'WaktuBerangkat')->first();
            $totalKirim = (float) ($summaryKirim->total_kirim_cy ?? 0.0);

            // 5. Perhitungan Hari & Toleransi Selisih
            $filter     = $this->getDashboardFilter();
            $jumlahHari = max(1, (int) \Carbon\Carbon::parse($filter['tanggal_awal'])
                ->diffInDays(\Carbon\Carbon::parse($filter['tanggal_akhir'])) + 1);

            $toleransiDesimal = ($jumlahHari * 0.07) / 100;
            $totalTonase      = round($totalTonaseOverall, 2);
            $totalSelisih     = round($totalTonase - $totalKirim, 2);

            $selisihDesimal  = $totalTonase > 0 ? $totalSelisih / $totalTonase : 0;
            $selisihPersen   = round($selisihDesimal * 100, 4);
            $toleransiPersen = round($toleransiDesimal * 100, 4);
            $statusSelisih   = abs($selisihDesimal) <= $toleransiDesimal ? 'normal' : 'melebihi';

            return [
                'chart' => $chartCollection,
                'total' => $totalTonase,
                'stats' => [
                    'total_tonase'        => $totalTonase,
                    'total_kirim'         => $totalKirim,
                    'total_selisih'       => $totalSelisih,
                    'total_ka'            => count($totalKAOverall),
                    'total_ct'            => $totalCTCount,
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