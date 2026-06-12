<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardPeriod;
use App\Models\CoalGetting;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ActivityCoalGetting extends ChartWidget
{
    use FiltersDashboardPeriod;

    protected static string $view = 'filament.widgets.activity-chart-widget';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;
    protected $listeners = ['filterUpdated' => 'refreshChart'];

    public string $title = 'Coal In - Coal Getting';
    public string $badge = 'Coal Getting';

    public function refreshChart(): void
    {
        $this->cachedData = null;
        $this->forgetDashboardCache('activity_coal_getting');
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

        $actuals = $data['rows']->pluck('total')->map(fn($v) => (float) $v)->all();
        $plans   = $data['rows']->pluck('plan')->map(fn($v) => (float) $v)->all();

        $sisa = array_map(function ($plan, $actual) {
            return max(0, round($plan - $actual, 2));
        }, $plans, $actuals);

        return [
            'datasets' => [
                [
                    'label'           => 'Actual',
                    'data'            => $actuals,
                    'materialIds'     => $data['rows']->pluck('material_ids')->all(),
                    'planData'        => $plans,
                    'backgroundColor' => '#16a34a',
                    'borderColor'     => '#15803d',
                    'borderWidth'     => 1,
                    'borderRadius'    => 0,
                    'stack'           => 'stack0',
                ],
                [
                    'label'           => 'Sisa Plan',
                    'data'            => $sisa,
                    'backgroundColor' => '#ef4444', 
                    'borderColor'     => '#dc2626',
                    'borderWidth'     => 1,
                    'borderRadius'    => 0,
                    'stack'           => 'stack0', 
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
                    legend: { display: true, position: 'top' },
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
                                tooltipEl.addEventListener('mouseenter', function() { tooltipEl._isHovered = true; });
                                tooltipEl.addEventListener('mouseleave', function() {
                                    tooltipEl._isHovered = false;
                                    tooltipEl.style.display = 'none';
                                });
                            }

                            var dataPoints = context.tooltip.dataPoints;
                            if (!dataPoints || !dataPoints.length) return;

                            var dataIndex = dataPoints[0].dataIndex;
                            var actualDataset = context.chart.data.datasets[0];
                            var label   = dataPoints[0].label;
                            var actual  = actualDataset.data[dataIndex] || 0;
                            var plan    = actualDataset.planData ? actualDataset.planData[dataIndex] : 0;
                            var ids     = actualDataset.materialIds ? actualDataset.materialIds[dataIndex] : null;
                            var pct     = plan > 0 ? Math.round((actual / plan) * 1000) / 10 : 0;
                            var achieved = actual >= plan && plan > 0;

                            tooltipEl.innerHTML = '';

                            // Header material
                            var headerEl = document.createElement('div');
                            headerEl.style.cssText = 'font-weight:bold;margin-bottom:6px;border-bottom:1px solid #374151;padding-bottom:4px';
                            headerEl.textContent = label;
                            tooltipEl.appendChild(headerEl);

                            // Actual
                            var actualEl = document.createElement('div');
                            actualEl.style.cssText = 'display:flex;justify-content:space-between;gap:16px;color:#60a5fa';
                            actualEl.innerHTML = '<span>● Actual</span><span>' + new Intl.NumberFormat('id-ID').format(actual) + ' ton</span>';
                            tooltipEl.appendChild(actualEl);

                            // Plan
                            var planEl = document.createElement('div');
                            planEl.style.cssText = 'display:flex;justify-content:space-between;gap:16px;color:#94a3b8';
                            planEl.innerHTML = '<span>● Plan</span><span>' + new Intl.NumberFormat('id-ID').format(plan) + ' ton</span>';
                            tooltipEl.appendChild(planEl);

                            // Persentase
                            var pctEl = document.createElement('div');
                            pctEl.style.cssText = 'margin-top:6px;padding-top:4px;border-top:1px solid #374151;font-weight:bold;color:' + (achieved ? '#34d399' : '#f87171');
                            pctEl.textContent = pct + '% ' + (achieved ? '✓ Tercapai' : '↑ Belum tercapai');
                            tooltipEl.appendChild(pctEl);

                            // Kurang berapa persen (hanya kalau belum tercapai)
                            if (!achieved && plan > 0) {
                                var sisaPct = Math.round((100 - pct) * 10) / 10;
                                var sisaTon = new Intl.NumberFormat('id-ID').format(Math.round((plan - actual) * 100) / 100);
                                var sisaEl = document.createElement('div');
                                sisaEl.style.cssText = 'color:#f87171;font-size:11px;margin-top:2px';
                                sisaEl.textContent = 'Kurang ' + sisaPct + '% lagi (' + sisaTon + ' ton)';
                                tooltipEl.appendChild(sisaEl);
                            }

                            // Material IDs
                            if (ids) {
                                var idLabel = document.createElement('div');
                                idLabel.style.cssText = 'font-weight:bold;margin-top:6px;margin-bottom:2px;color:#9ca3af;font-size:11px';
                                idLabel.textContent = 'Material ID:';
                                tooltipEl.appendChild(idLabel);
                                ids.split(', ').forEach(function(id) {
                                    var idEl = document.createElement('div');
                                    idEl.style.cssText = 'color:#9ca3af;font-size:11px';
                                    idEl.textContent = id;
                                    tooltipEl.appendChild(idEl);
                                });
                            }

                            tooltipEl.style.display = 'block';

                            var canvasRect = context.chart.canvas.getBoundingClientRect();
                            var x = canvasRect.left + context.tooltip.caretX + 2;
                            var y = canvasRect.top + context.tooltip.caretY - 10;
                            var tooltipWidth  = tooltipEl.offsetWidth;
                            var tooltipHeight = tooltipEl.offsetHeight;

                            if (x + tooltipWidth > window.innerWidth - 20) x = canvasRect.left + context.tooltip.caretX - tooltipWidth - 2;
                            if (y + tooltipHeight > window.innerHeight - 20) y = window.innerHeight - tooltipHeight - 20;
                            if (y < 10) y = 10;
                            if (x < 10) x = 10;

                            tooltipEl.style.left  = x + 'px';
                            tooltipEl.style.top   = y + 'px';
                            tooltipEl.style.marginLeft = '-8px';
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return new Intl.NumberFormat('id-ID').format(value); }
                        }
                    },
                    y: {
                        stacked: true,
                        ticks: {
                            autoSkip: false,
                            callback: function(value, index, values) {
                                var label = this.getLabelForValue(value);
                                if (label.length > 15) {
                                    return label.match(/.{1,15}(\s|$)/g);
                                }
                                return label;
                            },
                            font: { size: 10 }
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

public function getActivityData(): array
{
    return $this->rememberDashboardDataHourly('activity_coal_getting', function () {
        $f = $this->getDashboardFilter();

        $rows = $this->applyDashboardFilters(
            CoalGetting::query()
                ->selectRaw(" 
                    IFNULL(m.Material_desc, tblcoaltransaksimasuk.Kode) as Material_desc,
                    ROUND(SUM(tblcoaltransaksimasuk.Netto) / 1000, 2) as total,
                    GROUP_CONCAT(DISTINCT tblcoaltransaksimasuk.Kode ORDER BY tblcoaltransaksimasuk.Kode SEPARATOR ', ') as material_ids
                ")
                ->leftJoin('tblcoalmaterial as m', 'tblcoaltransaksimasuk.Kode', '=', 'm.Material_id')
                ->where('m.Source', 'Coal Getting'),
            'tblcoaltransaksimasuk.Tanggal',
        )
            ->groupBy('m.Material_desc')
            ->orderByDesc('total')
            ->get();

        // Ambil plan per material_desc sesuai filter
        $planRows = \App\Models\PlanCoalIn::query()
            ->where('type', 'Coal Getting')
            ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') >= ?", [$f['tanggal_awal']])
            ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') <= ?", [$f['tanggal_akhir']])
            ->whereIn('material_desc', $rows->pluck('Material_desc'))
            ->selectRaw('material_desc, ROUND(SUM(tonase), 2) as total_plan')
            ->groupBy('material_desc')
            ->pluck('total_plan', 'material_desc');

        // Gabungkan plan ke setiap row
        $rows = $rows->map(function ($row) use ($planRows) {
            $row->plan = (float) ($planRows[$row->Material_desc] ?? 0);
            return $row;
        });

        $ritase = $this->applyDashboardFilters(
            CoalGetting::query()
                ->leftJoin('tblcoalmaterial as m', 'tblcoaltransaksimasuk.Kode', '=', 'm.Material_id')
                ->where('m.Source', 'Coal Getting'),
            'tblcoaltransaksimasuk.Tanggal',
        )->count();

        return [
            'rows'   => $rows,
            'total'  => $rows->sum('total') ?? 0,
            'ritase' => $ritase,
        ];
    });
}

    public function getPlanData(): array
    {
        $f = $this->getDashboardFilter();

        // Ambil material_desc yang ada realisasi di periode filter
        $activeMaterials = CoalGetting::query()
            ->leftJoin('tblcoalmaterial as m', 'tblcoaltransaksimasuk.Kode', '=', 'm.Material_id')
            ->where('m.Source', 'Coal Getting')
            ->whereDate('tblcoaltransaksimasuk.Tanggal', '>=', $f['tanggal_awal'])
            ->whereDate('tblcoaltransaksimasuk.Tanggal', '<=', $f['tanggal_akhir'])
            ->where('tblcoaltransaksimasuk.Netto', '>', 0)
            ->distinct()
            ->pluck('m.Material_desc');

        $target = (float) \App\Models\PlanCoalIn::query()
            ->where('type', 'Coal Getting')
            ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') >= ?", [$f['tanggal_awal']])
            ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') <= ?", [$f['tanggal_akhir']])
            ->whereIn('material_desc', $activeMaterials)
            ->sum('tonase');

        return ['target' => $target];
    }
}