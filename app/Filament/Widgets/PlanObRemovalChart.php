<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardPeriod; // Trait filter rentang tanggal dashboard
use App\Models\PlanObRemoval;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class PlanObRemovalChart extends ChartWidget
{
    use FiltersDashboardPeriod;

    // Aktifkan Lazy Loading agar tidak membebani loading awal halaman dashboard
    protected static bool $isLazy = true;

    protected static string $view = 'filament.widgets.activity-chart-widget'; // Tetap pakai view blade utama
    protected static ?int $sort = 4; // Urutan penempatan di dashboard
    protected int | string | array $columnSpan = 1; // Ukuran 1 kolom (sama seperti Coal Getting)
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;
    protected $listeners = ['filterUpdated' => 'refreshChart'];

    public string $title = 'OB Removal - Plan vs Actual';
    public string $badge = 'OB Removal';

    public function refreshChart(): void
    {
        $this->cachedData = null;
        $this->forgetDashboardCache('plan_ob_removal_chart');
    }

    public function getHeading(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        // Output: OB Removal - Total X,XXX.XX BCM
        return $this->badge . ' - Total ' . number_format($this->getActivityData()['total'], 2, '.', ',') . ' BCM';
    }

    protected function getData(): array
    {
        $data = $this->getActivityData();

        $actuals = $data['rows']->pluck('total_actual')->map(fn($v) => (float) $v)->all();
        $plans   = $data['rows']->pluck('total_plan')->map(fn($v) => (float) $v)->all();

        $sisa = array_map(function ($plan, $actual) {
            return max(0, round($plan - $actual, 2));
        }, $plans, $actuals);

        return [
            'datasets' => [
                [
                    'label'           => 'Actual',
                    'data'            => $actuals,
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
            'labels' => $data['rows']->pluck('material_desc')->all(),
        ];
    }

    public function getActivityData(): array
    {
        return $this->rememberDashboardDataHourly('plan_ob_removal_chart', function () {

            $virtualDateRaw = "STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d')";
            $f = $this->getDashboardFilter();

            $rows = PlanObRemoval::query()
                ->selectRaw("
                    material_desc,
                    ROUND(SUM(IFNULL(plan, 0)), 2) as total_plan,
                    ROUND(SUM(IFNULL(actual, 0)), 2) as total_actual
                ")
                ->when(
                    $f['tanggal_awal'],
                    fn($q) => $q->whereRaw("$virtualDateRaw >= ?", [$f['tanggal_awal']])
                )
                ->when(
                    $f['tanggal_akhir'],
                    fn($q) => $q->whereRaw("$virtualDateRaw <= ?", [$f['tanggal_akhir']])
                )
                ->groupBy('material_desc')
                ->orderByDesc('total_plan')
                ->get();

            return [
                'rows'        => $rows,
                'total'       => $rows->sum('total_actual') ?? 0,
                'target_plan' => $rows->sum('total_plan') ?? 0,
            ];
        });
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

                            var dataIndex    = dataPoints[0].dataIndex;
                            var actualDataset = context.chart.data.datasets[0];
                            var label        = dataPoints[0].label;
                            var actual       = actualDataset.data[dataIndex] || 0;
                            var plan         = actualDataset.planData ? actualDataset.planData[dataIndex] : 0;
                            var pct          = plan > 0 ? Math.round((actual / plan) * 1000) / 10 : 0;
                            var achieved     = actual >= plan && plan > 0;

                            tooltipEl.innerHTML = '';

                            var headerEl = document.createElement('div');
                            headerEl.style.cssText = 'font-weight:bold;margin-bottom:6px;border-bottom:1px solid #374151;padding-bottom:4px';
                            headerEl.textContent = label;
                            tooltipEl.appendChild(headerEl);

                            var actualEl = document.createElement('div');
                            actualEl.style.cssText = 'display:flex;justify-content:space-between;gap:16px;color:#60a5fa';
                            actualEl.innerHTML = '<span>● Actual</span><span>' + new Intl.NumberFormat('id-ID').format(actual) + ' BCM</span>';
                            tooltipEl.appendChild(actualEl);

                            var planEl = document.createElement('div');
                            planEl.style.cssText = 'display:flex;justify-content:space-between;gap:16px;color:#94a3b8';
                            planEl.innerHTML = '<span>● Plan</span><span>' + new Intl.NumberFormat('id-ID').format(plan) + ' BCM</span>';
                            tooltipEl.appendChild(planEl);

                            var pctEl = document.createElement('div');
                            pctEl.style.cssText = 'margin-top:6px;padding-top:4px;border-top:1px solid #374151;font-weight:bold;color:' + (achieved ? '#34d399' : '#f87171');
                            pctEl.textContent = pct + '% ' + (achieved ? '✓ Tercapai' : '↑ Belum tercapai');
                            tooltipEl.appendChild(pctEl);

                            if (!achieved && plan > 0) {
                                var sisaPct = Math.round((100 - pct) * 10) / 10;
                                var sisaTon = new Intl.NumberFormat('id-ID').format(Math.round((plan - actual) * 100) / 100);
                                var sisaEl = document.createElement('div');
                                sisaEl.style.cssText = 'color:#f87171;font-size:11px;margin-top:2px';
                                sisaEl.textContent = 'Kurang ' + sisaPct + '% lagi (' + sisaTon + ' ton)';
                                tooltipEl.appendChild(sisaEl);
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
}