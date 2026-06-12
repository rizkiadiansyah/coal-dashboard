{{-- resources/views/filament/widgets/last-update-info.blade.php --}}
<x-filament-widgets::widget>
    @php
        $infos   = $this->getUpdateInfos();
        $oldest  = $this->getOldestUpdate();
        $anyData = collect($infos)->some(fn($i) => !$i['is_pending']);
    @endphp

    <div class="rounded-xl border border-gray-200 dark:border-gray-700
                bg-white dark:bg-gray-800 shadow-sm px-4 py-3">

        {{-- Header baris atas --}}
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">

            <div class="flex items-center gap-2">
                {{-- Dot animasi --}}
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75
                                 {{ $anyData ? 'bg-success-400' : 'bg-gray-300' }}"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5
                                 {{ $anyData ? 'bg-success-500' : 'bg-gray-300' }}"></span>
                </span>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Status Cache Data
                </span>
            </div>

            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                @if($oldest)
                    <span>
                        <x-filament::icon icon="heroicon-o-clock" class="inline w-3.5 h-3.5 -mt-0.5" />
                        Terlama: <strong class="text-gray-700 dark:text-gray-200">{{ $oldest }} WIB</strong>
                    </span>
                @endif
                <span class="italic">Auto-refresh tiap 1 jam</span>
            </div>

        </div>

        {{-- Grid badge per widget --}}
        <div class="flex flex-wrap gap-2">
            @foreach($infos as $info)
                <div
                    title="{{ $info['label'] }}: {{ $info['time'] ? 'Diupdate pukul ' . $info['time'] . ' WIB' : 'Belum ada cache' }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-medium
                           cursor-default select-none transition-colors
                           @if($info['is_pending'])
                               border-gray-200 dark:border-gray-600
                               bg-gray-50 dark:bg-gray-700/50
                               text-gray-400 dark:text-gray-500
                           @elseif($info['is_fresh'])
                               border-success-200 dark:border-success-800
                               bg-success-50 dark:bg-success-900/30
                               text-success-700 dark:text-success-400
                           @else
                               border-warning-200 dark:border-warning-800
                               bg-warning-50 dark:bg-warning-900/30
                               text-warning-700 dark:text-warning-400
                           @endif
                    "
                >
                    {{-- Status dot --}}
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0
                        @if($info['is_pending']) bg-gray-300 dark:bg-gray-500
                        @elseif($info['is_fresh']) bg-success-500
                        @else bg-warning-500
                        @endif
                    "></span>

                    {{-- Label widget --}}
                    <span>{{ $info['label'] }}</span>

                    {{-- Jam update --}}
                    @if($info['time'])
                        <span class="font-normal opacity-75">{{ $info['time'] }}</span>
                    @endif

                    {{-- Berapa menit lalu --}}
                    <span class="font-normal opacity-60">({{ $info['diff'] }})</span>
                </div>
            @endforeach
        </div>

        {{-- Footer hint --}}
        <p class="mt-2.5 text-[11px] text-gray-400 dark:text-gray-500">
            <x-filament::icon icon="heroicon-o-information-circle" class="inline w-3.5 h-3.5 -mt-0.5" />
            Cache otomatis kedaluwarsa setelah 60 menit. Data akan diambil ulang dari database saat kedaluwarsa atau saat filter diubah.
        </p>

    </div>
</x-filament-widgets::widget>