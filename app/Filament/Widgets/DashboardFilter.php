<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardPeriod;
use App\Models\CoalGetting;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardFilter extends Widget
{
    use FiltersDashboardPeriod;

    protected static string $view  = 'filament.widgets.dashboard-filter';
    protected static ?int   $sort  = 1;
    protected int | string | array $columnSpan = 'full';

    // Polling 60 detik agar "X menit lalu" otomatis terupdate
    protected static ?string $pollingInterval = '60s';

    public string $filterTanggalAwal  = '';
    public string $filterTanggalAkhir = '';

    public function mount(): void
    {
        $filter = $this->getDashboardFilter();
        $today  = now()->toDateString();

        $this->filterTanggalAwal  = $filter['tanggal_awal']  ?? $today;
        $this->filterTanggalAkhir = $filter['tanggal_akhir'] ?? $today;

        if (empty($filter)) {
            $default = [
                'tanggal'       => $today,
                'tanggal_awal'  => $today,
                'tanggal_akhir' => $today,
            ];
            session(['dashboard_filter' => $default]);
            Cache::put('dashboard_filter', $default, now()->addHours(2));
        }
    }

    public function applyFilter(): void
    {
        $tanggalAwal  = $this->filterTanggalAwal;
        $tanggalAkhir = $this->filterTanggalAkhir;

        if (filled($tanggalAwal) && filled($tanggalAkhir) && $tanggalAwal > $tanggalAkhir) {
            [$tanggalAwal, $tanggalAkhir] = [$tanggalAkhir, $tanggalAwal];
            $this->filterTanggalAwal  = $tanggalAwal;
            $this->filterTanggalAkhir = $tanggalAkhir;
        }

        $filter = [
            'tanggal'       => $tanggalAwal,
            'tanggal_awal'  => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
        ];

        session(['dashboard_filter' => $filter]);
        Cache::put('dashboard_filter', $filter, now()->addHours(2));

        $this->dispatch('filterUpdated');
    }

    public function resetFilter(): void
    {
        $today = now()->toDateString();

        $this->filterTanggalAwal  = $today;
        $this->filterTanggalAkhir = $today;

        $filter = [
            'tanggal'       => $today,
            'tanggal_awal'  => $today,
            'tanggal_akhir' => $today,
        ];

        session(['dashboard_filter' => $filter]);
        Cache::put('dashboard_filter', $filter, now()->addHours(2));

        $this->dispatch('filterUpdated');
    }

    /**
     * Ambil waktu data terakhir masuk dari DB (MAX TimeMasuk).
     * Di-cache per jam agar hanya query sekali setiap jam.
     */
    public function getLastDataUpdate(): ?array
    {
        $timezone = 'Asia/Jakarta';
        $cacheKey  = 'dashboard_last_data_update_' . now($timezone)->format('YmdH');
        $expiresAt = now($timezone)->copy()->startOfHour()->addHour();

        $lastTime = Cache::remember($cacheKey, $expiresAt, function () {
            return DB::connection('mysql_cy')
                ->table('tblcoaltransaksimasuk')
                ->whereNotNull('TimeMasuk')
                ->max('TimeMasuk');
        });

        if (!$lastTime) {
            return null;
        }

        $carbon = \Carbon\Carbon::parse($lastTime, 'Asia/Jakarta');
        $now    = \Carbon\Carbon::now('Asia/Jakarta');

        return [
            'time'     => $carbon->format('H:i'),
            'date'     => $carbon->format('d/m/Y'),
            'diff'     => $carbon->diffForHumans($now, true) . ' yang lalu',
            'is_today' => $carbon->isToday(),
            'is_fresh' => $carbon->diffInMinutes(now()) < 30,
        ];
    }
}