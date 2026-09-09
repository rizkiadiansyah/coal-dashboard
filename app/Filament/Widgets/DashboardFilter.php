<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardPeriod;
use App\Models\CoalGetting;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardFilter extends Widget
{
    protected static bool $isLazy = false;
    use FiltersDashboardPeriod;

    protected static string $view  = 'filament.widgets.dashboard-filter';
    protected static ?int   $sort  = 1;
    protected int | string | array $columnSpan = 'full';
    protected $listeners = ['filterUpdated' => 'refreshDashboardData'];

    // Polling 60 detik agar "X menit lalu" otomatis terupdate
    protected static ?string $pollingInterval = '60s';

    public string $filterTanggalAwal  = '';
    public string $filterTanggalAkhir = '';
    public int $dashboardRefreshKey = 0;

    public function refreshDashboardData(): void
    {
        $this->dashboardRefreshKey++;
    }

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

        if (!session()->has('dashboard_last_refreshed_at') && !Cache::has('dashboard_last_refreshed_at')) {
            $nowStr = now('Asia/Jakarta')->toDateTimeString();
            session(['dashboard_last_refreshed_at' => $nowStr]);
            Cache::put('dashboard_last_refreshed_at', $nowStr, now()->addHours(24));
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

        $nowStr = now('Asia/Jakarta')->toDateTimeString();
        session(['dashboard_last_refreshed_at' => $nowStr]);
        Cache::put('dashboard_last_refreshed_at', $nowStr, now()->addHours(24));
        $this->dashboardRefreshKey++;

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

        $nowStr = now('Asia/Jakarta')->toDateTimeString();
        session(['dashboard_last_refreshed_at' => $nowStr]);
        Cache::put('dashboard_last_refreshed_at', $nowStr, now()->addHours(24));
        $this->dashboardRefreshKey++;

        $this->dispatch('filterUpdated');
    }

    /**
     * Ambil waktu update/sinkronisasi dashboard terakhir.
     */
    public function getLastDataUpdate(): ?array
    {
        $timezone = 'Asia/Jakarta';
        $refreshedAt = session('dashboard_last_refreshed_at')
            ?? Cache::get('dashboard_last_refreshed_at')
            ?? now($timezone)->toDateTimeString();

        $carbon = \Carbon\Carbon::parse($refreshedAt, $timezone);
        $now    = \Carbon\Carbon::now($timezone);

        $diffSeconds = $carbon->diffInSeconds($now);
        if ($diffSeconds < 60) {
            $diffText = 'Baru saja';
        } else {
            $diffText = $carbon->diffForHumans($now, true) . ' yang lalu';
        }

        return [
            'time'     => $carbon->format('H:i'),
            'date'     => $carbon->format('d/m/Y'),
            'diff'     => $diffText,
            'is_today' => $carbon->isToday(),
            'is_fresh' => $diffSeconds < 1800,
        ];
    }
}