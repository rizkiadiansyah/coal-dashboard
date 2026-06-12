@php
    use Filament\Support\Facades\FilamentView;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $filters = $this->getFilters();
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section :description="$description" :heading="$heading" class="h-full flex flex-col">
        @if ($filters)
            <x-slot name="headerEnd">
                <x-filament::input.wrapper
                    inline-prefix
                    wire:target="filter"
                    class="w-max sm:-my-2"
                >
                    <x-filament::input.select
                        inline-prefix
                        wire:model.live="filter"
                    >
                        @foreach ($filters as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-slot>
        @endif

        <div
            class="relative flex-1"
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}="updateChartData"
            @endif
        >
            <div
                wire:loading.flex
                class="absolute inset-0 z-10 items-center justify-center rounded-xl bg-white/75 backdrop-blur-[1px] dark:bg-gray-900/70"
            >
                <div class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700">
                    <svg class="h-4 w-4 animate-spin text-primary-600" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span>Memuat chart...</span>
                </div>
            </div>

            <div
                @if (FilamentView::hasSpaMode())
                    x-load="visible"
                @else
                    x-load
                @endif
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            options: @js($this->getOptions()),
                            type: @js($this->getType()),
                        })"
                @class([
                    match ($color) {
                        'gray' => null,
                        default => 'fi-color-custom',
                    },
                    is_string($color) ? "fi-color-{$color}" : null,
                ])
            >
                <canvas
                    x-ref="canvas"
                    @if ($maxHeight = $this->getMaxHeight())
                        style="max-height: {{ $maxHeight }}"
                    @endif
                ></canvas>

                <span x-ref="backgroundColorElement" @class([ match ($color) { 'gray' => 'text-gray-100 dark:text-gray-800', default => 'text-custom-50 dark:text-custom-400/10', }, ]) @style([ \Filament\Support\get_color_css_variables( $color, shades: [50, 400], alias: 'widgets::chart-widget.background', ) => $color !== 'gray', ])></span>
                <span x-ref="borderColorElement" @class([ match ($color) { 'gray' => 'text-gray-400', default => 'text-custom-500 dark:text-custom-400', }, ]) @style([ \Filament\Support\get_color_css_variables( $color, shades: [400, 500], alias: 'widgets::chart-widget.border', ) => $color !== 'gray', ])></span>
                <span x-ref="gridColorElement" class="text-gray-200 dark:text-gray-800"></span>
                <span x-ref="textColorElement" class="text-gray-500 dark:text-gray-400"></span>
            </div>
        </div>

        {{-- ── Crusher Stats per equipment ── --}}
        @if (method_exists($this, 'getCrusherStats'))
            @php $stats = $this->getCrusherStats(); @endphp

            <div class="mt-4 space-y-4 text-xs">
                @php
                    // Menyusun kategori tampilan agar terkelompok rapi antara Direct dan Truck
                    $categories = [
                        'Direct Dumping (Tonase)' => [
                            'key'    => 'direct_dumping',
                            'suffix' => ' ton',
                            'color'  => 'text-blue-600 dark:text-blue-400',
                        ],
                        'Direct Dumping (Ritase)' => [
                            'key'    => 'ritase_direct',
                            'suffix' => ' rit',
                            'color'  => 'text-blue-500 dark:text-blue-300',
                        ],
                        'Truck Count (Tonase)' => [
                            'key'    => 'truck_count',
                            'suffix' => ' ton',
                            'color'  => 'text-amber-600 dark:text-amber-400',
                        ],
                        'Truck Count (Ritase)' => [
                            'key'    => 'ritase_truck',
                            'suffix' => ' rit',
                            'color'  => 'text-amber-500 dark:text-amber-300',
                        ],
                    ];
                @endphp

                @foreach ($categories as $label => $config)
                    <div class="space-y-1">
                        <p class="font-bold text-gray-800 dark:text-gray-200">{{ $label }}</p>

                        @foreach ($stats as $crusher => $d)
                            <div class="flex gap-2 items-center">
                                <span class="text-gray-500 dark:text-gray-400">{{ $crusher }}:</span>
                                <span class="font-semibold {{ $config['color'] }}">
                                    {{-- Jika akhiran 'ton' beri desimal 2 agar presisi, jika 'rit' beri desimal 0 --}}
                                    {{ number_format($d[$config['key']] ?? 0, ($config['suffix'] === ' ton' ? 2 : 0), ',', '.') }}{{ $config['suffix'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
        {{-- ── Hauling CY Stats ── --}}
        @if (method_exists($this, 'getHaulingCYStats'))
            @php $stats = $this->getHaulingCYStats(); @endphp

            <div class="mt-3 space-y-1 text-xs text-gray-900 dark:text-gray-100">
                <p>
                    Total Tonase:
                    <span class="font-semibold">{{ number_format($stats['total_tonase'], 2, ',', '.') }} ton</span>
                </p>
                <p>
                    Total CT:
                    <span class="font-semibold">{{ number_format($stats['total_ct'], 0, ',', '.') }} rit</span>
                </p>
                <p>
                    Total Buffer:
                    <span class="font-semibold">{{ number_format($stats['total_buffer'], 0, ',', '.') }} rit</span>
                </p>
                <p>
                    Total Return Cargo:
                    <span class="font-semibold">{{ number_format($stats['total_return_cargo'], 0, ',', '.') }} rit</span>
                </p>
            </div>
        @endif


{{-- ── KHUSUS OB REMOVAL: Menampilkan Detail Per Material Desc ── --}}
        @php
            $isObRemoval = str_contains(get_class($this), 'PlanObRemovalChart');
            $activityData = method_exists($this, 'getActivityData') ? $this->getActivityData() : null;
        @endphp

        @if ($isObRemoval && isset($activityData['rows']))
            <div class="mt-4 space-y-2 text-xs pt-3">
                @foreach ($activityData['rows'] as $row)
                    @php
                        $materialName = $row->material_desc ?? 'N/A';
                        $tgt          = $row->total_plan ?? 0;
                        $real         = $row->total_actual ?? 0;
                        $pct          = $tgt > 0 ? min(round(($real / $tgt) * 100, 1), 999) : 0;

                        $styleColor = match(true) {
                            $tgt <= 0    => 'color: #6b7280',
                            $pct >= 100  => 'color: #16a34a',
                            $pct >= 75   => 'color: #ca8a04',
                            default      => 'color: #dc2626',
                        };
                    @endphp
                    
                    {{-- Box transparan dengan border tipis tanpa background solid --}}
                    <div class="mt-3 space-y-1 text-xs text-gray-600 dark:text-gray-400">
                        <p>
                            Target:
                            <b class="font-semibold text-gray-900 dark:text-white">
                                {{ number_format($tgt, 2, ',', '.') }} BCM
                            </b>
                        </p>

                        <p style="{{ $styleColor }}">
                            Realisasi:
                            <b class="font-semibold">
                                {{ number_format($real, 2, ',', '.') }} BCM
                            </b>
                            ({{ $pct }}%)
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ── Stats widget lain (target vs realisasi) — KODE LAMA TIDAK DIOTAK-ATIK ── --}}
        @if (
            !$isObRemoval && {{-- Dikunci agar kode bawah ini tidak dieksekusi oleh OB Removal --}}
            method_exists($this, 'getActivityData') &&
            (method_exists($this, 'getPlanData') || isset($this->getActivityData()['target_plan'])) &&
            !method_exists($this, 'getHaulingCYStats') &&
            !method_exists($this, 'getHaulingKAStats') &&
            !method_exists($this, 'getStockpileWBSStats')
        )
            @php
                $realisasi    = $activityData['total'];
                $ritase       = $activityData['ritase'] ?? 0; 
                
                $target       = method_exists($this, 'getPlanData') 
                                    ? $this->getPlanData()['target'] 
                                    : ($activityData['target_plan'] ?? 0);

                $persen       = $target > 0 ? min(round(($realisasi / $target) * 100, 1), 999) : 0;
                $unit         = ' ton';

                $realisasiStyle = match(true) {
                    $target <= 0   => 'color: #6b7280',
                    $persen >= 100 => 'color: #16a34a',
                    $persen >= 75  => 'color: #ca8a04',
                    default        => 'color: #dc2626',
                };
            @endphp

            <div class="mt-3 space-y-1 text-xs text-gray-900 dark:text-gray-100">
                <p>
                    Target Harian:
                    {{ $target > 0 ? number_format($target, 2, ',', '.') . $unit : 'Belum dikonfigurasi' }}
                </p>

                <p style="{{ $realisasiStyle }}">
                    Realisasi Harian:
                    {{ number_format($realisasi, 2, ',', '.') . $unit }}
                    {{ $target > 0 ? ' (' . $persen . '%)' : '' }}
                </p>

                @if ($ritase > 0)
                    <p>
                        Ritase Truck:
                        {{ number_format($ritase, 0, ',', '.') }} rit
                    </p>
                @endif
            </div>
        @endif
{{-- Hauling KA Stats --}}
@if (method_exists($this, 'getHaulingKAStats'))
    @php $ka = $this->getHaulingKAStats(); @endphp
 
    <div class="mt-3 space-y-1 text-xs text-gray-900 dark:text-gray-100">
        <p>Total Tonase       : {{ number_format($ka['total_tonase'], 2, ',', '.') }} Ton</p>
        <p>Total KA           : {{ number_format($ka['total_ka'], 0, ',', '.') }}</p>
        <p>Total CT           : {{ number_format($ka['total_ct'], 0, ',', '.') }}</p>
        <p>Total Buffer       : {{ number_format($ka['total_ct_buffer'], 0, ',', '.') }}</p>
        <p>Total Return Cargo : {{ number_format($ka['total_return_cargo'], 0, ',', '.') }}</p>
    </div>
 
    @if ($ka['shift_rows']->isNotEmpty())
        <div class="mt-2 space-y-1 text-xs text-gray-900 dark:text-gray-100">
            <p class="font-bold text-gray-800 dark:text-gray-200">Loading Rate per Shift</p>
            @foreach ($ka['shift_rows'] as $row)
                <p>Shift {{ $row->Shift }} : {{ number_format($row->tonase_per_shift, 2, ',', '.') }} ton</p>
            @endforeach
        </div>
    @endif
@endif

@if (method_exists($this, 'getStockpileWBSStats'))
    @php $wbs = $this->getStockpileWBSStats(); @endphp

    <div class="mt-3 space-y-1 text-xs text-gray-900 dark:text-gray-100">
        <p>Total Diterima :
            <span class="font-semibold">{{ number_format($wbs['total_tonase'], 2, ',', '.') }} Ton</span>
        </p>
        <p>Total Dikirim :
            <span class="font-semibold">{{ number_format($wbs['total_kirim'], 2, ',', '.') }} Ton</span>
        </p>
        <p>Selisih Muat :
            <span class="font-semibold {{ $wbs['total_selisih'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format($wbs['total_selisih'], 2, ',', '.') }} Ton
            </span>
        </p>
        <p>Deviasi :
            <span class="font-semibold {{ $wbs['status_selisih'] === 'normal' ? 'text-green-600' : 'text-red-600' }}">
                {{ ($wbs['selisih_persen'] >= 0 ? '+' : '') }}{{ number_format($wbs['selisih_persen'], 4, ',', '.') }}%
            </span>
            <span class="text-gray-400">(toleransi {{ number_format($wbs['toleransi_persen'], 2, ',', '.') }}% / {{ $wbs['jumlah_hari'] }} hari)</span>
        </p>
        <p>Status :
            <span class="font-semibold {{ $wbs['status_selisih'] === 'normal' ? 'text-green-600' : 'text-red-600' }}">
                {{ $wbs['status_selisih'] === 'normal' ? 'Normal' : 'Melebihi Toleransi' }}
            </span>
        </p>
    </div>
@endif


<div id="chartjs-custom-tooltip" style="position:fixed;display:none;background:#1f2937;color:#f9fafb;border-radius:8px;padding:10px 14px;font-size:12px;pointer-events:auto;z-index:9999;max-height:300px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,0.4);min-width:180px;"></div>

    </x-filament::section>
</x-filament-widgets::widget>