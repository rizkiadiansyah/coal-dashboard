<?php

namespace App\Filament\Resources\PlanObRemovalResource\Pages;

use App\Filament\Resources\PlanObRemovalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PlanObRemoval;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Filament\Widgets\Concerns\InvalidatesDashboardCache;

class CreatePlanObRemoval extends CreateRecord
{
    use InvalidatesDashboardCache;
    protected static string $resource = PlanObRemovalResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['create_by'] = auth()->user()?->name ?? auth()->user()?->email ?? 'system';
        $data['create_date'] = now();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $daysInMonth = Carbon::create($data['tahun'], $data['bulan'], 1)->daysInMonth;
        $baseBcm = floor(($data['plan'] / $daysInMonth) * 100) / 100;
        
        // 1. Siapkan array kosong untuk menampung semua data hari
        $bulkData = [];
        $createBy = $data['create_by'] ?? auth()->user()?->name ?? auth()->user()?->email ?? 'system';
        $createDate = $data['create_date'] ?? now();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $bcm = $day === $daysInMonth
                ? round($data['plan'] - ($baseBcm * ($daysInMonth - 1)), 2)
                : $baseBcm;

            // 2. Masukkan data ke array penampung (belum disimpan ke database)
            $bulkData[] = [
                'material_desc' => $data['material_desc'],
                'tahun' => $data['tahun'],
                'bulan' => $data['bulan'],
                'hari_ke' => $day,
                'plan' => $bcm,
                'create_by' => $createBy,
                'create_date' => $createDate,
            ];
        }

        // 3. Eksekusi 1 query massal ke database (Proses ini yang bikin jadi instan!)
        PlanObRemoval::query()->insert($bulkData);

        $this->invalidateDashboardCache();

        // 4. Filament mewajibkan method ini mengembalikan satu objek Model yang baru dibuat.
        // Kita ambil data hari terakhir sebagai perwakilan objek kembalian.
        return PlanObRemoval::query()
            ->where('material_desc', $data['material_desc'])
            ->where('tahun', $data['tahun'])
            ->where('bulan', $data['bulan'])
            ->orderBy('id', 'desc')
            ->first() ?? new PlanObRemoval();
    }
    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->hidden();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
