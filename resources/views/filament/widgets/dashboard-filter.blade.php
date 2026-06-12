<x-filament-widgets::widget>
    <x-filament::card>
        <div class="space-y-3">

            {{-- Indikator Loading --}}
            <div
                wire:loading.flex
                wire:target="applyFilter"
                class="items-center gap-2 rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm text-primary-700 dark:border-primary-800 dark:bg-primary-950 dark:text-primary-300"
            >
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span>Sedang menerapkan filter...</span>
            </div>

            {{-- Filter Tanggal --}}
            <div class="flex flex-wrap items-end gap-4">

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Awal</label>
                    <input
                        type="date"
                        wire:model.live="filterTanggalAwal"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Akhir</label>
                    <input
                        type="date"
                        wire:model.live="filterTanggalAkhir"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <button
                        wire:click="applyFilter"
                        wire:loading.attr="disabled"
                        wire:target="applyFilter"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-500 disabled:cursor-wait disabled:opacity-70"
                    >
                        <svg wire:loading wire:target="applyFilter" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="applyFilter">Terapkan Filter</span>
                        <span wire:loading wire:target="applyFilter">Menerapkan...</span>
                    </button>

                    <button
                        wire:click="resetFilter"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Hari Ini
                    </button>
                </div>

            </div>

            {{-- ── Info terakhir data masuk (real dari DB) ── --}}
            @php $update = $this->getLastDataUpdate(); @endphp

            <div class="flex items-center gap-2 border-t border-gray-100 pt-3 dark:border-gray-700">

                {{-- Dot live --}}
                <span class="relative flex h-2 w-2 flex-shrink-0">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75
                        {{ $update && $update['is_fresh'] ? 'bg-success-400' : 'bg-warning-400' }}"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full
                        {{ $update && $update['is_fresh'] ? 'bg-success-500' : 'bg-warning-500' }}"></span>
                </span>

                @if($update)
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Data terakhir masuk:
                        <span class="font-semibold text-gray-800 dark:text-gray-100">
                            {{ $update['is_today'] ? $update['time'] . ' WIB' : $update['date'] . ' ' . $update['time'] . ' WIB' }}
                        </span>
                        <span class="text-gray-400">({{ $update['diff'] }})</span>
                    </span>
                @else
                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">
                        Belum ada data masuk hari ini
                    </span>
                @endif

            </div>

        </div>
    </x-filament::card>
</x-filament-widgets::widget>