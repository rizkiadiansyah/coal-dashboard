<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardPeriod;
use App\Models\HaulingCY;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ActivityHaulingCY extends ChartWidget
{
    use FiltersDashboardPeriod;

    protected static string $view = 'filament.widgets.activity-chart-widget';
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;
    protected $listeners = ['filterUpdated' => 'refreshChart'];

    public string $title = 'Activity Hauling ke CY Merapi';
    public string $badge = 'Hauling ke CY Merapi';

    public function refreshChart(): void
    {
        $this->cachedData = null;
        $this->forgetDashboardCache('activity_hauling_cy');
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
                    'data'            => $data['rows']->pluck('total')->map(fn($value) => (float) $value)->all(),
                    'materialIds'     => $data['rows']->pluck('material_ids')->all(),
                    'backgroundColor' => '#16a34a',
                    'borderColor'     => '#15803d',
                    'borderWidth'     => 1,
                    'borderRadius'    => 6,
                ],
            ],
            'labels' => $data['rows']->pluck('Material_desc')->all(),
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
                            var ids = dataset.materialIds ? dataset.materialIds[dataIndex] : null;

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
                            colorBox.style.backgroundColor = dataPoints[0].dataset.backgroundColor || '#16a34a';
                            colorBox.style.flexShrink = '0';

                            var titleEl = document.createElement('span');
                            titleEl.style.fontWeight = 'bold';
                            titleEl.textContent = label;

                            headerEl.appendChild(colorBox);
                            headerEl.appendChild(titleEl);
                            tooltipEl.appendChild(headerEl);

                            var totalEl = document.createElement('div');
                            totalEl.style.marginBottom = '8px';
                            totalEl.textContent = 'Total: ' + new Intl.NumberFormat('id-ID').format(value) + ' ton';
                            tooltipEl.appendChild(totalEl);

                            if (ids) {
                                var idLabel = document.createElement('div');
                                idLabel.style.fontWeight = 'bold';
                                idLabel.style.marginBottom = '4px';
                                idLabel.textContent = 'Material ID:';
                                tooltipEl.appendChild(idLabel);

                                var idList = ids.split(', ');
                                idList.forEach(function(id) {
                                    var idEl = document.createElement('div');
                                    idEl.textContent = id;
                                    tooltipEl.appendChild(idEl);
                                });
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
                            callback: (value) => new Intl.NumberFormat('id-ID').format(value),
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
                                const label = this.getLabelForValue(value);
                                if (label.length > 14) {
                                    return label.substring(0, 14) + '…';
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

    // Dipanggil dari blade untuk plain-text di bawah chart
    public function getHaulingCYStats(): array
    {
        return $this->getActivityData()['stats'];
    }

    public function getActivityData(): array
    {
        return $this->rememberDashboardDataHourly('activity_hauling_cy', function () {
            // ── Chart rows: group by Material_desc ──────────────────────
            $rows = $this->applyDashboardFilters(
                HaulingCY::query()
                    ->selectRaw(" 
                        IFNULL(m.Material_desc, tblkirimcytransaksikirim.Kode) as Material_desc,
                        ROUND(SUM(tblkirimcytransaksikirim.Netto) / 1000, 2) as total,
                        GROUP_CONCAT(DISTINCT tblkirimcytransaksikirim.Kode ORDER BY tblkirimcytransaksikirim.Kode SEPARATOR ', ') as material_ids
                    ")
                    ->leftJoin('tblkirimcymaterial as m', 'tblkirimcytransaksikirim.Kode', '=', 'm.Material_id'),
                'tblkirimcytransaksikirim.Tanggal',
            )
                ->groupBy('m.Material_desc')
                ->orderByDesc('total')
                ->get();

            // ── Stats dari kolom Status di tabel transaksi ───────────────
            $totalCT = $this->applyDashboardFilters(
                HaulingCY::query(),
                'tblkirimcytransaksikirim.Tanggal',
            )->count();

            $totalBuffer = $this->applyDashboardFilters(
                HaulingCY::query()->where('Status', 'Buffer'),
                'tblkirimcytransaksikirim.Tanggal',
            )->count();

            $totalReturnCargo = $this->applyDashboardFilters(
                HaulingCY::query()->where('Status', 'Return Cargo'),
                'tblkirimcytransaksikirim.Tanggal',
            )->count();

            return [
                'rows'  => $rows,
                'total' => $rows->sum('total') ?? 0,
                'stats' => [
                    'total_tonase'       => $rows->sum('total') ?? 0,
                    'total_ct'           => $totalCT,
                    'total_buffer'       => $totalBuffer,
                    'total_return_cargo' => $totalReturnCargo,
                ],
            ];
        });
    }

    public function getPlanData(): array
    {
        return ['target' => 0.0];
    }
}