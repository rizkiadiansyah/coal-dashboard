<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardPeriod;
use App\Models\CoalGetting;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ActivityCoalInOutsource extends ChartWidget
{
    use FiltersDashboardPeriod;

    protected static string $view = 'filament.widgets.activity-chart-widget';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;
    protected $listeners = ['filterUpdated' => 'refreshChart'];

    public string $title = 'Coal In - Outsource';
    public string $badge = 'Outsource';

    public function getMaxHeight(): ?string
    {
        $count = $this->getActivityData()['rows']->count();
        $height = max(150, $count * 28);
        return $height . 'px';
    }

    public function refreshChart(): void
    {
        $this->cachedData = null;
        $this->forgetDashboardCache('activity_coal_in_outsource');
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
                    'backgroundColor' => '#2563eb',
                    'borderColor'     => '#1d4ed8',
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
                            colorBox.style.backgroundColor = dataPoints[0].dataset.backgroundColor || '#2563eb';
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

    // -------------------------------------------------------------------------
    // Public — dipanggil dari blade view
    // -------------------------------------------------------------------------

    public function getActivityData(): array
    {
        return $this->rememberDashboardDataHourly('activity_coal_in_outsource', function () {
            $rows = $this->applyDashboardFilters(
                CoalGetting::query()
                    ->selectRaw(" 
                        IFNULL(m.Material_desc, tblcoaltransaksimasuk.Kode) as Material_desc,
                        ROUND(SUM(tblcoaltransaksimasuk.Netto) / 1000, 2) as total,
                        GROUP_CONCAT(DISTINCT tblcoaltransaksimasuk.Kode ORDER BY tblcoaltransaksimasuk.Kode SEPARATOR ', ') as material_ids
                    ")
                    ->leftJoin('tblcoalmaterial as m', 'tblcoaltransaksimasuk.Kode', '=', 'm.Material_id')
                    ->where('m.Source', 'Out Source'),
                'Tanggal',
                'Kode',
            )
                ->groupBy('m.Material_desc')
                ->orderByDesc('total')
                ->get();

            $filters = $this->getDashboardFilter();

            $ritaseQuery = CoalGetting::query()
                ->leftJoin('tblcoalmaterial as m', 'tblcoaltransaksimasuk.Kode', '=', 'm.Material_id')
                ->where('m.Source', 'Out Source');

            if (!empty($filters['tanggal_awal']) && !empty($filters['tanggal_akhir'])) {
                $ritaseQuery->whereBetween('tblcoaltransaksimasuk.Tanggal', [$filters['tanggal_awal'], $filters['tanggal_akhir']]);
            }

            return [
                'rows'   => $rows,
                'total'  => $rows->sum('total') ?? 0,
                'ritase' => $ritaseQuery->count(),
            ];
        });
    }

    /**
     * TODO: Ganti isi method ini ketika tabel plan sudah tersedia.
     * Contoh query nanti:
     *
     *   $filters = session('dashboard_filter', []);
     *   $target  = \App\Models\CoalPlan::query()
     *       ->when(
     *           !empty($filters['tanggal_awal']),
     *           fn($q) => $q->whereBetween('Tanggal', [$filters['tanggal_awal'], $filters['tanggal_akhir']])
     *       )
     *       ->where('Source', 'Out Source')
     *       ->sum('Target_Netto') / 1000;
     *   return ['target' => (float) $target];
     */
    public function getPlanData(): array
    {
        $dummyTarget = 900.0;

        return [
            'target' => $dummyTarget, // satuan: ton
        ];
    }
}