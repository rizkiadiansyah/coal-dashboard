<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasFilamentRoleAccess;
use App\Filament\Resources\PlanHaulingCYResource\Pages;
use App\Filament\Resources\PlanHaulingCYResource\RelationManagers;
use App\Models\PlanHaulingCY;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PlanHaulingCYResource extends Resource
{
    use HasFilamentRoleAccess;

    protected static ?string $model = PlanHaulingCY::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Plan Hauling CY';
    protected static ?string $modelLabel = 'Plan Hauling CY';
    protected static ?string $pluralModelLabel = 'Plan Hauling CY';

    protected static ?string $navigationGroup = 'Data Plan';
    //protected static ?string $navigationParentItem = 'Coal In';
    protected static ?int $navigationSort = 6;
 
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Select::make('material_desc')
                    ->label('Material')
                    ->searchable()
                    ->preload()
                    ->options(static::getMaterialOptions())
                    ->disabledOn('edit')
                    ->required(),

                TextInput::make('tahun')
                    ->label('Tahun')
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->default(now()->year)
                    ->disabledOn('edit')
                    ->required(),

                Select::make('bulan')
                    ->label('Bulan')
                    ->options(static::getMonthOptions())
                    ->default(now()->month)
                    ->disabledOn('edit')
                    ->required(),

                TextInput::make('tonase')
                    ->label('Tonase')
                    ->numeric()
                    ->minValue(0)
                    ->step('0.01')
                    ->suffix('Ton')
                    ->required(),

                TextInput::make('total_ct')
                    ->label('Total CT')
                    ->numeric()
                    ->minValue(0)
                    ->step('1')
                    ->suffix('CT')
                    ->required(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                /*TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                    //->toggleable(isToggledHiddenByDefault: true),*/

                TextColumn::make('tahun')
                    ->sortable()
                    ->alignCenter()
                    ->label('Tahun')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bulan')
                    ->label('Bulan')
                    ->alignCenter()
                    ->formatStateUsing(fn (?int $state): string => static::getMonthOptions()[$state] ?? (string) $state)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('hari_ke')
                    ->label('Hari ke')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('material_desc')
                    ->sortable() 
                    ->label('Material'),

                TextColumn::make('total_ct')
                    ->label('Total CT')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('tonase')
                    ->label('Tonase')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn (?float $state): string => $state !== null ? number_format($state, 2) : '')
                    ->color('success')
                    ->weight('bold'),

                TextColumn::make('create_by')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('create_date')
                    ->dateTime('d/m/Y H:i')
                    ->label('Dibuat Pada')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('edit_by')
                    ->label('Diubah Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('edit_date')
                    ->dateTime('d/m/Y H:i')
                    ->label('Diubah Pada')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('periode_material')
                    ->form([
                        \Filament\Forms\Components\Grid::make(3)
                            ->schema([
                                Select::make('material_desc')
                                    ->label('Material')
                                    ->preload()
                                    ->options(static::getMaterialOptions())
                                    ->placeholder('Semua Material')
                                    ->searchable(),

                                Select::make('tahun')
                                    ->label('Tahun')
                                    ->options(static::getYearOptions())
                                    ->placeholder('Semua Tahun')
                                    ->searchable(),

                                Select::make('bulan')
                                    ->label('Bulan')
                                    ->options(static::getMonthOptions())
                                    ->placeholder('Semua Bulan')
                                    ->searchable(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $state): Builder {
                        $tahun = $state['tahun'] ?? null;
                        $bulan = $state['bulan'] ?? null;
                        $material = $state['material_desc'] ?? null;

                        if (blank($tahun) && blank($bulan) && blank($material)) {
                            return $query->whereRaw('1 = 0');
                        }

                        return $query
                            ->when($tahun, fn (Builder $query) => $query->where('tahun', $tahun))
                            ->when($bulan, fn (Builder $query) => $query->where('bulan', $bulan))
                            ->when($material, fn (Builder $query) => $query->where('material_desc', $material));
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->searchable(false)
            ->headerActions([
                Tables\Actions\Action::make('total_tonase')
                    ->label(function ($livewire) {
                        // Menghitung total secara real-time dari query yang sudah terfilter
                        $total = $livewire->getFilteredTableQuery()->sum('tonase');
                        return 'Total Tonase: ' . number_format($total, 2) . ' Ton';
                    })
                    ->color('success')
                    ->icon('heroicon-o-calculator')
                    ->disabled()
                    ->extraAttributes([
                        'class' => 'bg-emerald-500/10 border border-emerald-500/30 px-3 py-1.5 rounded-lg font-bold pointer-events-none'
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('2xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['edit_by'] = auth()->user()?->name ?? auth()->user()?->email ?? 'system';
                        $data['edit_date'] = now();

                        return $data;
                    })
                    ->after(function () {
                        \Illuminate\Support\Facades\Cache::flush();
                    }),
            ])
            ->emptyStateHeading('Silakan pilih filter terlebih dahulu')
            ->emptyStateDescription('Data plan akan muncul setelah kamu memilih Tahun, Bulan, atau Material di atas.')
            ->defaultSort('tahun', 'desc')
            ->defaultSort('bulan', 'desc')
            ->defaultSort('hari_ke', 'asc')
            ->striped()
            ->defaultPaginationPageOption(50);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlanHaulingCYs::route('/'),
            'create' => Pages\CreatePlanHaulingCY::route('/create'),
        ];
    }

    protected static function getYearOptions(): array
    {
        $currentYear = now()->year;

        return array_combine(
            range($currentYear - 1, $currentYear + 1),
            range($currentYear - 1, $currentYear + 1),
        );
    }

    protected static function getMonthOptions(): array
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }

    protected static function getMaterialOptions(): array
    {
        return DB::connection('mysql_cy')
            ->table('tblkirimcymaterial')
            ->whereNotNull('Material_desc')
            ->where('Material_desc', '!=', '')
            ->distinct()
            ->orderBy('Material_desc')
            ->pluck('Material_desc', 'Material_desc')
            ->toArray();
    }

    public static function canAccess(): bool
    {
        return static::canAccessPlan();
    }

    public static function canCreate(): bool
    {
        return static::canAccessPlan();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canAccessPlan();
    }
}
