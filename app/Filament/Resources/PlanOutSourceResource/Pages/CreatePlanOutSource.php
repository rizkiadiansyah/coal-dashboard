<?php

namespace App\Filament\Resources\PlanOutSourceResource\Pages;

use App\Filament\Resources\PlanOutSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PlanCoalIn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Filament\Widgets\Concerns\InvalidatesDashboardCache;

class CreatePlanOutSource extends CreateRecord
{
    use InvalidatesDashboardCache;
    protected static string $resource = PlanOutSourceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['create_by'] = auth()->user()?->name ?? auth()->user()?->email ?? 'system';
        $data['create_date'] = now();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // PENGECEKAN DUPLIKAT: Cek apakah kombinasi material, tahun, dan bulan sudah pernah di-input
        $isExists = \App\Models\PlanCoalIn::query()
            ->where('material_desc', $data['material_desc'])
            ->where('tahun', $data['tahun'])
            ->where('bulan', $data['bulan'])
            ->exists();

        if ($isExists) {
            // Kirim notifikasi error ke pojok kanan atas layar
            \Filament\Notifications\Notification::make()
                ->title('Data Plan Sudah Ada!')
                ->body('Kombinasi Material, Tahun, dan Bulan ini sudah terdaftar. Silakan edit data yang sudah ada.')
                ->danger()
                ->persistent() // Notifikasi tidak akan hilang sampai diclose user
                ->send();

            // Menghentikan proses penyimpanan (Form tetap terbuka dan tidak tersimpan)
            throw new \Filament\Support\Exceptions\Halt();
        }
        
        $daysInMonth = Carbon::create($data['tahun'], $data['bulan'], 1)->daysInMonth;
        $baseTonase = floor(($data['tonase'] / $daysInMonth) * 100) / 100;
        $sourceType = \Illuminate\Support\Facades\DB::connection('mysql_cy')
            ->table('tblcoalmaterial')
            ->where('Material_desc', $data['material_desc'])
            ->value('Source') ?? 'Out Source';
        
        // 1. Siapkan array kosong untuk menampung semua data hari
        $bulkData = [];
        $createBy = $data['create_by'] ?? auth()->user()?->name ?? auth()->user()?->email ?? 'system';
        $createDate = $data['create_date'] ?? now();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $tonase = $day === $daysInMonth
                ? round($data['tonase'] - ($baseTonase * ($daysInMonth - 1)), 2)
                : $baseTonase;

            // 2. Masukkan data ke array penampung (belum disimpan ke database)
            $bulkData[] = [
                'material_desc' => $data['material_desc'],
                'type' => $sourceType,
                'tahun' => $data['tahun'],
                'bulan' => $data['bulan'],
                'hari_ke' => $day,
                'tonase' => $tonase,
                'create_by' => $createBy,
                'create_date' => $createDate,
            ];
        }

        // 3. Eksekusi 1 query massal ke database (Proses ini yang bikin jadi instan!)
        PlanCoalIn::query()->insert($bulkData);

        $this->invalidateDashboardCache();

        // 4. Filament mewajibkan method ini mengembalikan satu objek Model yang baru dibuat.
        // Kita ambil data hari terakhir sebagai perwakilan objek kembalian.
        return PlanCoalIn::query()
            ->where('material_desc', $data['material_desc'])
            ->where('tahun', $data['tahun'])
            ->where('bulan', $data['bulan'])
            ->orderBy('id', 'desc')
            ->first() ?? new PlanCoalIn();
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