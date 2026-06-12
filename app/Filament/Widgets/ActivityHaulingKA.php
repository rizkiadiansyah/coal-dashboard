<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardPeriod;
use App\Models\HaulingKA;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ActivityHaulingKA extends ChartWidget
{
    use FiltersDashboardPeriod;

    protected static string $view = 'filament.widgets.activity-chart-widget';
    protected static ?int $sort = 8;
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;
    protected $listeners = ['filterUpdated' => 'refreshChart'];

    public string $title = 'Activity Hauling pengiriman KA ke WBS';
    public string $badge = 'Pengiriman KA ke WBS';

    public function refreshChart(): void
    {
        $this->cachedData = null;
        $this->forgetDashboardCache('activity_hauling_ka');
    }

    public function getHeading(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->badge . ' - Total ' . number_format($this->getActivityData()['total'], 2) . ' ton';
    }

    // ----------------------------------------------------------------
    // Chart
    // ----------------------------------------------------------------
    protected function getData(): array
    {
        $data = $this->getActivityData();

        return [
            'datasets' => [
                [
                    'label'           => 'Tonase',
                    'data'            => $data['chart']->pluck('total_tonase')->map(fn($v) => (float) $v)->all(),
                    'totalKAList'     => $data['chart']->pluck('total_ka')->all(),
                    'totalCTList'     => $data['chart']->pluck('total_ct')->all(),
                    'backgroundColor' => '#9333ea',
                    'borderColor'     => '#7e22ce',
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
                            var totalKA = dataset.totalKAList ? dataset.totalKAList[dataIndex] : null;
                            var totalCT = dataset.totalCTList ? dataset.totalCTList[dataIndex] : null;

                            tooltipEl.innerHTML = '';

                            // Header dengan color box
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

                            // Total tonase
                            var totalEl = document.createElement('div');
                            totalEl.style.marginBottom = '4px';
                            totalEl.textContent = 'Total   : ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' ton';
                            tooltipEl.appendChild(totalEl);

                            // Spacer
                            tooltipEl.appendChild(document.createElement('br'));

                            // Total KA
                            if (totalKA !== null && totalKA !== undefined) {
                                var kaEl = document.createElement('div');
                                kaEl.textContent = 'Total KA : ' + totalKA;
                                tooltipEl.appendChild(kaEl);
                            }

                            // Total CT
                            if (totalCT !== null && totalCT !== undefined) {
                                var ctEl = document.createElement('div');
                                ctEl.textContent = 'Total CT : ' + totalCT;
                                tooltipEl.appendChild(ctEl);
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
                            tooltipEl.style.marginLeft = '-8px';
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
                            callback: function(value) { return new Intl.NumberFormat('id-ID').format(value); },
                            font: { size: 10 }
                        }
                    },
                    y: {
                        afterFit: function(scaleInstance) {
                            scaleInstance.width = 100;
                        },
                        ticks: {
                            autoSkip: false,
                            callback: function(value, index, values) {
                                var label = this.getLabelForValue(value);
                                if (label.length > 14) {
                                    return label.substring(0, 14) + '...';
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

    // ----------------------------------------------------------------
    // Stats (dipanggil dari blade)
    // ----------------------------------------------------------------
    public function getHaulingKAStats(): array
    {
        return $this->getActivityData()['stats'];
    }

    // ----------------------------------------------------------------
    // Query
    // ----------------------------------------------------------------
    public function getActivityData(): array
    {
        return $this->rememberDashboardDataHourly('activity_hauling_ka', function () {
            // ── Chart: group by Material_desc ────────────────────────────
            $chart = $this->applyDashboardFilters(
                HaulingKA::query()
                    ->selectRaw(" 
                        IFNULL(m.Material_desc, k.Kode)                                              AS Material_desc,
                        ROUND(SUM(k.Netto) / 1000, 2)                                                AS total_tonase,
                        COUNT(DISTINCT k.NoKA)                                                        AS total_ka,
                        SUM(CASE WHEN k.NoCT IS NOT NULL AND k.NoCT <> '' THEN 1 ELSE 0 END)         AS total_ct
                    ")
                    ->from('tblkirimcytransaksikirim_ka as k')
                    ->leftJoin('tblkirimcymaterial as m', 'k.Kode', '=', 'm.Material_id'),
                'k.Tanggal',
            )
                ->groupBy('m.Material_desc', 'k.Kode')
                ->orderByDesc('total_tonase')
                ->get();

            // ── Summary: total per status + total KA ─────────────────────
            $summary = $this->applyDashboardFilters(
                HaulingKA::query()
                    ->selectRaw(" 
                        COUNT(DISTINCT k.NoKA)                                                        AS total_ka,
                        ROUND(SUM(k.Netto) / 1000, 2)                                                AS total_tonase,
                        SUM(CASE WHEN k.Status = 'Terkirim'     THEN 1 ELSE 0 END)                   AS total_ct,
                        SUM(CASE WHEN k.Status = 'Buffer'       THEN 1 ELSE 0 END)                   AS total_ct_buffer,
                        SUM(CASE WHEN k.Status = 'Return Cargo' THEN 1 ELSE 0 END)                   AS total_return_cargo
                    ")
                    ->from('tblkirimcytransaksikirim_ka as k'),
                'k.Tanggal',
            )->first();

            // ── Loading rate: tonase per shift ───────────────────────────
            $shiftRows = $this->applyDashboardFilters(
                HaulingKA::query()
                    ->selectRaw(" 
                        k.Shift,
                        ROUND(SUM(k.Netto) / 1000, 2)                                                AS tonase_per_shift
                    ")
                    ->from('tblkirimcytransaksikirim_ka as k'),
                'k.Tanggal',
            )
                ->groupBy('k.Shift')
                ->orderBy('k.Shift')
                ->get();

            return [
                'chart' => $chart,
                'total' => $chart->sum('total_tonase') ?? 0,
                'stats' => [
                    'total_tonase'       => $summary->total_tonase ?? 0,
                    'total_ka'           => $summary->total_ka ?? 0,
                    'total_ct'           => $summary->total_ct ?? 0,
                    'total_ct_buffer'    => $summary->total_ct_buffer ?? 0,
                    'total_return_cargo' => $summary->total_return_cargo ?? 0,
                    'shift_rows'         => $shiftRows,
                ],
            ];
        });
    }

    public function getPlanData(): array
    {
        return ['target' => 0.0];
    }
}