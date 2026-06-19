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
        // --- TAMBAHAN: Proteksi Duplikat Data ---
        $isExists = PlanObRemoval::query()
            ->where('material_desc', $data['material_desc'])
            ->where('tahun', $data['tahun'])
            ->where('bulan', $data['bulan'])
            ->exists();

        if ($isExists) {
            \Filament\Notifications\Notification::make()
                ->title('Data Plan OB Sudah Ada!')
                ->body('Kombinasi Material, Tahun, dan Bulan ini sudah terdaftar. Silakan edit data yang sudah ada.')
                ->danger()
                ->persistent()
                ->send();

            // Menghentikan proses penyimpanan, form tetap terbuka
            throw new \Filament\Support\Exceptions\Halt();
        }
        // ----------------------------------------

        $daysInMonth = Carbon::create($data['tahun'], $data['bulan'], 1)->daysInMonth;
        $baseBcm = floor(($data['plan'] / $daysInMonth) * 100) / 100;
        
        $bulkData = [];
        $createBy = $data['create_by'] ?? auth()->user()?->name ?? auth()->user()?->email ?? 'system';
        $createDate = $data['create_date'] ?? now();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $bcm = $day === $daysInMonth
                ? round($data['plan'] - ($baseBcm * ($daysInMonth - 1)), 2)
                : $baseBcm;

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

        PlanObRemoval::query()->insert($bulkData);

        $this->invalidateDashboardCache();

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