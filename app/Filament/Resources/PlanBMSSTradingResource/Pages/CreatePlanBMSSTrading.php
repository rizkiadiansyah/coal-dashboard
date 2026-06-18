<?php

namespace App\Filament\Resources\PlanBMSSTradingResource\Pages;

use App\Filament\Resources\PlanBMSSTradingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PlanCoalIn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Filament\Widgets\Concerns\InvalidatesDashboardCache;

class CreatePlanBMSSTrading extends CreateRecord
{
    use InvalidatesDashboardCache;
    protected static string $resource = PlanBMSSTradingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['create_by'] = auth()->user()?->name ?? auth()->user()?->email ?? 'system';
        $data['create_date'] = now();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // PENGECEKAN DUPLIKAT
        $isExists = \App\Models\PlanCoalIn::query()
            ->where('material_desc', $data['material_desc'])
            ->where('tahun', $data['tahun'])
            ->where('bulan', $data['bulan'])
            ->exists();

        if ($isExists) {
            \Filament\Notifications\Notification::make()
                ->title('Data Plan Sudah Ada!')
                ->body('Kombinasi Material, Tahun, dan Bulan ini sudah terdaftar. Silakan edit data yang sudah ada.')
                ->danger()
                ->persistent()
                ->send();

            throw new \Filament\Support\Exceptions\Halt();
        }

        $daysInMonth = Carbon::create($data['tahun'], $data['bulan'], 1)->daysInMonth;
        $baseTonase = floor(($data['tonase'] / $daysInMonth) * 100) / 100;

        // --- DIUBAH: Mengambil 'type' berdasarkan field 'material' dari tabel dashboard baru ---
        $sourceType = DB::table('tblcoalmaterial_dashboard')
            ->where('material', $data['material_desc'])
            ->value('type') ?? 'Coal In BMSS Trading';

        $bulkData = [];
        $createBy = $data['create_by'] ?? auth()->user()?->name ?? auth()->user()?->email ?? 'system';
        $createDate = $data['create_date'] ?? now();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $tonase = $day === $daysInMonth
                ? round($data['tonase'] - ($baseTonase * ($daysInMonth - 1)), 2)
                : $baseTonase;

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

        PlanCoalIn::query()->insert($bulkData);
        $this->invalidateDashboardCache();

        return PlanCoalIn::query()
            ->where('material_desc', $data['material_desc'])
            ->where('tahun', $data['tahun'])
            ->where('bulan', $data['bulan'])
            ->orderBy('id', 'desc')
            ->first() ?? new PlanCoalIn();
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}