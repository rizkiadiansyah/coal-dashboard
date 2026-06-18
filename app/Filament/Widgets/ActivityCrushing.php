<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardPeriod;
use App\Models\CrusherActivity;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ActivityCrushing extends ChartWidget
{
    use FiltersDashboardPeriod;

    protected static string $view = 'filament.widgets.activity-chart-widget';
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;
    protected $listeners = ['filterUpdated' => 'refreshChart'];

    public string $title = 'Activity Crushing';
    public string $badge = 'Crushing';

    protected array $crusherList = ['FB08001', 'CP08001'];

    public function refreshChart(): void
    {
        $this->cachedData = null;
        $this->forgetDashboardCache('activity_crushing');
    }

    public function getHeading(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        $data = $this->getActivityData();
        $totalFormatted = number_format($data['total_tonase'], 2, '.', ',');
        return $this->badge . ' - Total ' . $totalFormatted . ' ton';
    }

    protected function getData(): array
    {
        $data = $this->getActivityData();

        $directs = array_map(
            fn($c) => (float) ($data['per_crusher'][$c]['direct_dumping'] ?? 0),
            $this->crusherList
        );

        $trucks = array_map(
            fn($c) => (float) ($data['per_crusher'][$c]['truck_count'] ?? 0),
            $this->crusherList
        );

        $plans = array_map(
            fn($c) => (float) ($data['plan_per_crusher'][$c] ?? 0),
            $this->crusherList
        );

        // Hitung sisa plan berdasarkan akumulasi total actual (Direct + Truck)
        $sisa = array_map(function ($plan, $dir, $trk) {
            $actual = $dir + $trk;
            return max(0, round($plan - $actual, 2));
        }, $plans, $directs, $trucks);

        $materialsDirect = array_map(
            fn($c) => $data['per_crusher'][$c]['materials_direct'] ?? [],
            $this->crusherList
        );

        $materialsTruck = array_map(
            fn($c) => $data['per_crusher'][$c]['materials_truck'] ?? [],
            $this->crusherList
        );

        return [
            'datasets' => [
                [
                    'label'           => 'Direct Dumping',
                    'data'            => $directs,
                    'planData'        => $plans,
                    'materials'       => $materialsDirect,
                    'backgroundColor' => '#2563eb', // Biru terang
                    'borderColor'     => '#1d4ed8',
                    'borderWidth'     => 1,
                    'borderRadius'    => 0,
                    'stack'           => 'stack0', // Menggunakan stack yang sama
                ],
                [
                    'label'           => 'Truck Count',
                    'data'            => $trucks,
                    'planData'        => $plans,
                    'materials'       => $materialsTruck,
                    'backgroundColor' => '#10b981', // Hijau emerald
                    'borderColor'     => '#059669',
                    'borderWidth'     => 1,
                    'borderRadius'    => 0,
                    'stack'           => 'stack0', // Menggunakan stack yang sama agar menumpuk
                ],
                [
                    'label'           => 'Sisa Plan',
                    'data'            => $sisa,
                    'backgroundColor' => '#ef4444', // Merah
                    'borderColor'     => '#dc2626',
                    'borderWidth'     => 1,
                    'borderRadius'    => 0,
                    'stack'           => 'stack0', // Menggunakan stack yang sama agar ikut menumpuk di ujung akhir
                ],
            ],
            'labels' => $this->crusherList,
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                
                barPercentage: 0.8,      
                categoryPercentage: 0.9, 

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

                            var dataIndex     = dataPoints[0].dataIndex;
                            var label         = dataPoints[0].label;
                            var value         = dataPoints[0].parsed.x;
                            var dataset       = dataPoints[0].dataset;
                            var datasetLabel  = dataset.label;
                            
                            var datasets      = context.chart.data.datasets;
                            var directVal     = datasets[0].data[dataIndex] || 0;
                            var truckVal      = datasets[1].data[dataIndex] || 0;
                            var actual        = directVal + truckVal;
                            
                            var plan          = datasets[0].planData ? datasets[0].planData[dataIndex] : 0;
                            var pct           = plan > 0 ? Math.round((actual / plan) * 1000) / 10 : 0;
                            var achieved      = actual >= plan && plan > 0;
                            var materialsList = dataset.materials ? dataset.materials[dataIndex] : [];

                            tooltipEl.innerHTML = '';

                            // Header
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
                            colorBox.style.backgroundColor = dataset.backgroundColor;

                            var titleEl = document.createElement('span');
                            titleEl.style.fontWeight = 'bold';
                            titleEl.textContent = label + ' (' + datasetLabel + ')';

                            headerEl.appendChild(colorBox);
                            headerEl.appendChild(titleEl);
                            tooltipEl.appendChild(headerEl);

                            // Nilai bagian bar yang di-hover
                            var totalEl = document.createElement('div');
                            totalEl.style.fontWeight = 'bold';
                            totalEl.style.marginBottom = '6px';
                            totalEl.style.borderBottom = '1px solid #4b5563';
                            totalEl.style.paddingBottom = '4px';
                            totalEl.textContent = datasetLabel + ': ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' ton';
                            tooltipEl.appendChild(totalEl);

                            // Informasi total kombinasi actual (Direct + Truck)
                            var actualEl = document.createElement('div');
                            actualEl.style.cssText = 'display:flex;justify-content:space-between;gap:16px;color:#60a5fa';
                            actualEl.innerHTML = '<span>● Total Kombinasi</span><span>' + new Intl.NumberFormat('id-ID').format(actual) + ' ton</span>';
                            tooltipEl.appendChild(actualEl);

                            var planEl = document.createElement('div');
                            planEl.style.cssText = 'display:flex;justify-content:space-between;gap:16px;color:#94a3b8;margin-bottom:4px';
                            planEl.innerHTML = '<span>● Target Plan</span><span>' + new Intl.NumberFormat('id-ID').format(plan) + ' ton</span>';
                            tooltipEl.appendChild(planEl);

                            // Persentase Pencapaian
                            var pctEl = document.createElement('div');
                            pctEl.style.cssText = 'padding-top:4px;border-top:1px solid #374151;font-weight:bold;color:' + (achieved ? '#34d399' : '#f87171');
                            pctEl.textContent = pct + '% ' + (achieved ? '✓ Tercapai' : '↑ Belum tercapai');
                            tooltipEl.appendChild(pctEl);

                            if (!achieved && plan > 0) {
                                var sisaPct = Math.round((100 - pct) * 10) / 10;
                                var sisaTon = new Intl.NumberFormat('id-ID').format(Math.round((plan - actual) * 100) / 100);
                                var sisaEl = document.createElement('div');
                                sisaEl.style.cssText = 'color:#f87171;font-size:11px;margin-top:2px;margin-bottom:4px';
                                sisaEl.textContent = 'Kurang ' + sisaPct + '% lagi (' + sisaTon + ' ton)';
                                tooltipEl.appendChild(sisaEl);
                            }

                            // Rincian material berdasarkan bagian bar yang di-hover
                            if (materialsList && materialsList.length > 0) {
                                var matHeaderEl = document.createElement('div');
                                matHeaderEl.style.cssText = 'font-weight:bold;margin-top:8px;margin-bottom:4px;border-top:1px solid #374151;padding-top:4px;color:#9ca3af;font-size:11px';
                                matHeaderEl.textContent = 'Rincian Material (' + datasetLabel + '):';
                                tooltipEl.appendChild(matHeaderEl);

                                materialsList.forEach(function(mat) {
                                    var matEl = document.createElement('div');
                                    matEl.style.cssText = 'display:flex;justify-content:space-between;gap:12px;font-size:11px;color:#d1d5db';

                                    var nameSpan = document.createElement('span');
                                    nameSpan.textContent = mat.name;

                                    var volSpan = document.createElement('span');
                                    volSpan.style.fontWeight = 'semibold';
                                    volSpan.textContent = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(mat.total) + ' t';

                                    matEl.appendChild(nameSpan);
                                    matEl.appendChild(volSpan);
                                    tooltipEl.appendChild(matEl);
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
                        stacked: true, // Kembalikan ke true agar bar menumpuk menjadi satu kesatuan panjang
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return new Intl.NumberFormat('id-ID').format(value); }
                        }
                    },
                    y: {
                        stacked: true, // Kembalikan ke true agar kategori sumbu Y mengunci bar tumpukan tersebut
                        ticks: { autoSkip: false }
                    }
                }
            }
        JS);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public function getCrusherStats(): array
    {
        return $this->getActivityData()['per_crusher'];
    }

    private function getActivityData(): array
    {
        return $this->rememberDashboardDataHourly('activity_crushing', function () {
            $perCrusher  = [];
            $totalTonase = 0;

            foreach ($this->crusherList as $crusher) {
                $baseQuery = fn() => $this->applyDashboardFilters(
                    CrusherActivity::query()->where('tblcrusheractivity.Crusher', $crusher),
                    'Tanggal',
                    'tblcrusheractivity.Crusher',
                );

                $crushing = $baseQuery()
                    ->where('tblcrusheractivity.Activity', 'Coal Crushing')
                    ->selectRaw("
                        SUM(CASE WHEN tblcrusheractivity.Alat_loading IN ('Direct Dumping', 'Direct Dumping(TP)') THEN Tonase ELSE 0 END) as total_direct,
                        SUM(CASE WHEN tblcrusheractivity.Alat_loading NOT IN ('Direct Dumping', 'Direct Dumping(TP)') THEN Tonase ELSE 0 END) as total_truck,
                        SUM(CASE WHEN tblcrusheractivity.Alat_loading IN ('Direct Dumping', 'Direct Dumping(TP)') THEN Qty_bucket ELSE 0 END) as ritase_direct,
                        SUM(CASE WHEN tblcrusheractivity.Alat_loading NOT IN ('Direct Dumping', 'Direct Dumping(TP)') THEN Qty_bucket ELSE 0 END) as ritase_truck
                    ")
                    ->first();

                $mDirect = $this->applyDashboardFilters(
                    CrusherActivity::query()->where('tblcrusheractivity.Crusher', $crusher),
                    'Tanggal'
                )
                    ->where('tblcrusheractivity.Activity', 'Coal Crushing')
                    ->join('tblcrushermaterial as m', 'tblcrusheractivity.Kode_Material', '=', 'm.Material_id')
                    ->whereIn('tblcrusheractivity.Alat_loading', ['Direct Dumping', 'Direct Dumping(TP)'])
                    ->groupBy('m.Material_desc')
                    ->selectRaw("m.Material_desc as name, ROUND(SUM(tblcrusheractivity.Tonase), 2) as total")
                    ->having('total', '>', 0)
                    ->get()
                    ->toArray();

                $mTruck = $this->applyDashboardFilters(
                    CrusherActivity::query()->where('tblcrusheractivity.Crusher', $crusher),
                    'Tanggal'
                )
                    ->where('tblcrusheractivity.Activity', 'Coal Crushing')
                    ->join('tblcrushermaterial as m', 'tblcrusheractivity.Kode_Material', '=', 'm.Material_id')
                    ->whereNotIn('tblcrusheractivity.Alat_loading', ['Direct Dumping', 'Direct Dumping(TP)'])
                    ->groupBy('m.Material_desc')
                    ->selectRaw("m.Material_desc as name, ROUND(SUM(tblcrusheractivity.Tonase), 2) as total")
                    ->having('total', '>', 0)
                    ->get()
                    ->toArray();

                $directDumping = (float) ($crushing->total_direct ?? 0);
                $truckCount    = (float) ($crushing->total_truck ?? 0);
                $ritaseDirect  = (int)   ($crushing->ritase_direct ?? 0);
                $ritaseTruck   = (int)   ($crushing->ritase_truck ?? 0);

                $perCrusher[$crusher] = [
                    'direct_dumping'   => $directDumping,
                    'truck_count'      => $truckCount,
                    'ritase_direct'    => $ritaseDirect,
                    'ritase_truck'     => $ritaseTruck,
                    'materials_direct' => $mDirect,
                    'materials_truck'  => $mTruck,
                ];

                $totalTonase += ($directDumping + $truckCount);
            }

            $f = $this->getDashboardFilter();

            $planPerCrusher = \App\Models\PlanCrushing::query()
                ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') >= ?", [$f['tanggal_awal']])
                ->whereRaw("STR_TO_DATE(CONCAT(tahun, '-', bulan, '-', hari_ke), '%Y-%m-%d') <= ?", [$f['tanggal_akhir']])
                ->whereIn('equipment', $this->crusherList)
                ->whereExists(function ($sub) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('tblcrusheractivity as t')
                        ->join('tblcrushermaterial as m', 't.Kode_Material', '=', 'm.Material_id')
                        ->whereColumn('t.Crusher', 'tblplanprodcrush.equipment')
                        ->whereColumn('m.Material_desc', 'tblplanprodcrush.material_desc')
                        ->where('t.Tonase', '>', 0);
                })
                ->selectRaw('equipment, ROUND(SUM(tonase), 2) as total_plan')
                ->groupBy('equipment')
                ->pluck('total_plan', 'equipment');

            return [
                'per_crusher'      => $perCrusher,
                'total_tonase'     => $totalTonase,
                'plan_per_crusher' => $planPerCrusher,
            ];
        });
    }
}