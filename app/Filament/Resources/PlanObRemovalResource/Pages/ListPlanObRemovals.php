<?php

namespace App\Filament\Resources\PlanObRemovalResource\Pages;

use App\Filament\Resources\PlanObRemovalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\PlanObRemoval;
use Illuminate\Support\Facades\Cache;

class ListPlanObRemovals extends ListRecords
{
    protected static string $resource = PlanObRemovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. ACTION DOWNLOAD TEMPLATE (DENGAN DROPDOWN OPTION MATERIAL)
            Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->color('info')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $spreadsheet = new Spreadsheet();
                    
                    // --- SHEET 1: UTAMA (TEMPAT INPUT DATA) ---
                    $activeWorksheet = $spreadsheet->getActiveSheet();
                    $activeWorksheet->setTitle('Data Plan');
                    $activeWorksheet->setShowGridlines(true);

                    // Header sesuai urutan request Anda
                    $headers = ['material_desc *', 'hari_ke *', 'bulan *', 'tahun *', 'plan *', 'actual'];
                    
                    $columnIndex = 'A';
                    foreach ($headers as $header) {
                        $activeWorksheet->setCellValue($columnIndex . '1', $header);
                        $columnIndex++;
                    }

                    // Contoh data bawaan
                    $activeWorksheet->setCellValue('B2', 1);
                    $activeWorksheet->setCellValue('C2', now()->month);
                    $activeWorksheet->setCellValue('D2', now()->year);
                    $activeWorksheet->setCellValue('E2', 1500.50);
                    $activeWorksheet->setCellValue('F2', 1400.00);

                    // --- SHEET 2: OPTIONS (UNTUK MENYIMPAN DAFTAR MATERIAL) ---
                    $optionsSheet = $spreadsheet->createSheet();
                    $optionsSheet->setTitle('MaterialOptions');
                    $optionsSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN); // Disembunyikan agar user tidak bingung

                    // Ambil data material langsung dari database (seperti fungsi getMaterialOptions)
                    $materials = \Illuminate\Support\Facades\DB::connection('mysql_cy')
                        ->table('tblcoalmaterial_dashboard')
                        ->where('type', 'Coal Getting')
                        ->whereNotNull('material')
                        ->where('material', '!=', '')
                        ->distinct()
                        ->orderBy('material')
                        ->pluck('material')
                        ->toArray();

                    // Tulis daftar material ke Sheet 2 kolom A
                    $rowIdx = 1;
                    foreach ($materials as $material) {
                        $optionsSheet->setCellValue('A' . $rowIdx, $material);
                        $rowIdx++;
                    }
                    $totalMaterials = $rowIdx - 1;

                    // --- MEMBUAT DROPDOWN DI SHEET UTAMA (Baris 2 sampai 100) ---
                    if ($totalMaterials > 0) {
                        for ($i = 2; $i <= 100; $i++) { // Mengaktifkan dropdown dari baris 2 hingga 100
                        $validation = $activeWorksheet->getCell('A' . $i)->getDataValidation();
                        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                        $validation->setAllowBlank(false);
                        $validation->setShowInputMessage(true);
                        $validation->setShowErrorMessage(true);
                        $validation->setShowDropDown(true);

                        // Perbaikan method terbaru PhpSpreadsheet untuk pesan error
                        $validation->setErrorTitle('Input Error');
                        $validation->setError('Material tidak terdaftar! Silakan pilih dari list yang tersedia.');
                            
                            // Rumus Excel mengambil data dari sheet tersembunyi
                            $validation->setFormula1('MaterialOptions!$A$1:$A$' . $totalMaterials);
                        }
                    }

                    // --- STYLING & FORMATTING (Sama seperti sebelumnya) ---
                    $headerStyle = [
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11, 'name' => 'Calibri'],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '10B981']],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                    ];
                    $activeWorksheet->getStyle('A1:F1')->applyFromArray($headerStyle);
                    $activeWorksheet->getRowDimension('1')->setRowHeight(25);

                    $dataStyle = [
                        'borders' => [
                            'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
                        ],
                    ];
                    $activeWorksheet->getStyle('A1:F100')->applyFromArray($dataStyle); // Border diperpanjang sampai baris 100
                    $activeWorksheet->getStyle('B2:D100')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    foreach (range('A', 'F') as $col) {
                        $activeWorksheet->getColumnDimension($col)->setAutoSize(true);
                    }

                    // Proses download langsung ke browser
                    return response()->streamDownload(function () use ($spreadsheet) {
                        $writer = new Xlsx($spreadsheet);
                        $writer->save('php://output');
                    }, 'template_plan_ob_removal.xlsx');
                }),
            // 2. ACTION IMPORT EXCEL
            Actions\Action::make('importExcel')
                ->label('Import Excel')
                ->color('success')
                ->icon('heroicon-o-document-plus')
                ->steps([
                    // STEP 1: UPLOAD FILE (Menggunakan Forms\Components\Wizard\Step)
                    \Filament\Forms\Components\Wizard\Step::make('step_upload')
                        ->label('Upload File')
                        ->schema([
                            FileUpload::make('file')
                                ->label('Pilih File Excel')
                                ->acceptedFileTypes([
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'text/csv'
                                ])
                                ->disk('local')
                                ->directory('temp-imports')
                                ->required() 
                                ->live() 
                                ->loadingIndicatorPosition('left'), 
                        ]),

                    // STEP 2: PREVIEW DATA (Menggunakan Forms\Components\Wizard\Step)
                    \Filament\Forms\Components\Wizard\Step::make('step_preview')
                        ->label('Preview Data Excel')
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('preview_table')
                                ->label('Periksa kembali data Anda sebelum di-import:')
                                ->content(function ($get) {
                                    $fileData = $get('file');
                                    if (!$fileData) return 'Silakan upload file terlebih dahulu pada langkah sebelumnya.';

                                    // Ambil path utama dari data upload
                                    $file = is_array($fileData) ? (\Illuminate\Support\Arr::first($fileData) ?? '') : $fileData;

                                    // Deteksi jika file masih berstatus temporary object (Livewire Upload)
                                    if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                        $filePath = $file->getRealPath();
                                    } elseif (is_string($file) && file_exists(storage_path('app/private/' . $file))) {
                                        $filePath = storage_path('app/private/' . $file);
                                    } elseif (is_string($file) && file_exists(storage_path('app/' . $file))) {
                                        $filePath = storage_path('app/' . $file);
                                    } else {
                                        // Jalur alternatif jika file berupa string path temporary
                                        $filePath = is_string($file) ? $file : '';
                                    }

                                    if (!file_exists($filePath) || !is_readable($filePath)) {
                                        return 'File template preview tidak ditemukan atau tidak dapat dibaca di storage server.';
                                    }

                                    try {
                                        $spreadsheet = IOFactory::load($filePath);
                                        $worksheet = $spreadsheet->getActiveSheet();
                                        $rows = $worksheet->toArray();
                                        
                                        $previewRows = array_slice($rows, 0, 11); 
                                        $header = array_shift($previewRows);
                                        $html = '<div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">';
                                        $html .= '<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm text-left">';

                                        // HEADER: Menggunakan background standar abu-abu Filament agar teks otomatis menyesuaikan kontras
                                        $html .= '<thead class="bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700">';
                                        $html .= '<tr>';
                                        foreach ($header as $h) {
                                            $html .= '<th class="px-4 py-2.5 font-bold text-center">' . e($h) . '</th>';
                                        }
                                        $html .= '</tr></thead>';

                                        $html .= '<tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">';
                                        foreach ($previewRows as $row) {
                                            if (blank($row[0]) && blank($row[1])) continue;
                                            
                                            $materialName = is_array($row[0]) ? implode(', ', $row[0]) : (string)$row[0];
                                            $hariKe       = is_array($row[1]) ? implode('', $row[1]) : (string)$row[1];
                                            $bulan        = is_array($row[2]) ? implode('', $row[2]) : (string)$row[2];
                                            $tahun        = is_array($row[3]) ? implode('', $row[3]) : (string)$row[3];

                                            $html .= '<tr>';
                                            $html .= '<td class="px-4 py-2 font-semibold text-gray-900 dark:text-gray-100">' . e($materialName) . '</td>';
                                            $html .= '<td class="px-4 py-2 text-center font-bold text-blue-600 dark:text-blue-400">' . e($hariKe) . '</td>';
                                            $html .= '<td class="px-4 py-2 text-center font-bold text-blue-600 dark:text-blue-400">' . e($bulan) . '</td>'; 
                                            $html .= '<td class="px-4 py-2 text-center font-bold text-purple-600 dark:text-purple-400">' . e($tahun) . '</td>'; 
                                            $html .= '<td class="px-4 py-2 text-right text-emerald-600 dark:text-emerald-400 font-semibold">' . number_format((float)($row[4] ?? 0), 2) . '</td>';
                                            $html .= '<td class="px-4 py-2 text-right text-red-600 dark:text-red-400">' . (!blank($row[5]) ? number_format((float)$row[5], 2) : '-') . '</td>';
                                            $html .= '</tr>';
                                        }
                                        $html .= '</tbody></table></div>';
                                        if (count($rows) > 11) {
                                            $html .= '<p class="text-xs text-gray-500 mt-2 italic">* Menampilkan 10 baris pertama dari total ' . (count($rows) - 1) . ' data di Excel.</p>';
                                        }

                                        return new \Illuminate\Support\HtmlString($html);

                                    } catch (\Exception $e) {
                                        return 'Gagal membaca file untuk preview: ' . $e->getMessage();
                                    }
                                })
                        ]),
                ])
                ->action(function (array $data) {
                    // PROSES INSERT DATABASE
                    $file = is_array($data['file']) ? (\Illuminate\Support\Arr::first($data['file']) ?? '') : $data['file'];

                    if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        $filePath = $file->getRealPath();
                    } elseif (is_string($file) && file_exists(storage_path('app/private/' . $file))) {
                        $filePath = storage_path('app/private/' . $file);
                    } else {
                        $filePath = storage_path('app/' . (string)$file);
                    }

                    try {
                        $spreadsheet = IOFactory::load($filePath);
                        $worksheet = $spreadsheet->getActiveSheet();
                        $rows = $worksheet->toArray();

                        array_shift($rows); // Buang header

                        $insertedCount = 0;
                        $userIdent = auth()->user()?->name ?? auth()->user()?->email ?? 'system';

                        foreach ($rows as $row) {
                            if (blank($row[0]) || blank($row[1]) || blank($row[2]) || blank($row[3]) || blank($row[4])) {
                                continue;
                            }

                            PlanObRemoval::create([
                                'material_desc' => $row[0],
                                'hari_ke'       => (int) $row[1],
                                'bulan'         => (int) $row[2], 
                                'tahun'         => (int) $row[3], 
                                'plan'          => (float) $row[4],
                                'actual'        => !blank($row[5]) ? (float) $row[5] : null,
                                'create_by'     => $userIdent,
                                'create_date'   => now(),
                            ]);

                            $insertedCount++;
                        }

                        @unlink($filePath);
                        Cache::flush();

                        Notification::make()
                            ->title('Import Berhasil')
                            ->body("Berhasil mengimpor {$insertedCount} data.")
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\CreateAction::make(),
        ];
    }
}