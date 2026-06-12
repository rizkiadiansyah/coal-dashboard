<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockpileWBSResource\Pages;
use App\Models\StockpileWBS;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;

class StockpileWBSResource extends Resource
{
    protected static ?string $model = StockpileWBS::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Stockpile WBS';
    protected static ?string $navigationGroup = 'Coal Movement';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Tahun')->label('Tahun')->sortable(),
                TextColumn::make('Bulan')->label('Bulan'),
                TextColumn::make('NoKA')->label('No KA'),
                TextColumn::make('NoCT')->label('No CT'),
                TextColumn::make('NoDo')->label('No DO'),
                TextColumn::make('Kode')->label('Kode'),
                TextColumn::make('Stack')->label('Stack'),
                TextColumn::make('Asal')->label('Asal'),
                TextColumn::make('Tujuan')->label('Tujuan'),
                TextColumn::make('Bruto')->numeric(2)->label('Bruto'),
                TextColumn::make('Tara')->numeric(2)->label('Tara'),
                TextColumn::make('Netto')
                    ->numeric(2)
                    ->label('Netto (Ton)')
                    ->color('success')
                    ->weight('bold'),
                TextColumn::make('NettoMerapi')->numeric(2)->label('Netto Merapi')->toggleable(),
                TextColumn::make('TimeMasuk')->dateTime('d/m/Y H:i')->label('Time Masuk')->toggleable(),
                TextColumn::make('TimeKeluar')->dateTime('d/m/Y H:i')->label('Time Keluar')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('Tahun')
                    ->options(fn() => StockpileWBS::select('Tahun')
                        ->distinct()
                        ->orderBy('Tahun', 'desc')
                        ->whereNotNull('Tahun')
                        ->pluck('Tahun', 'Tahun')
                        ->toArray()
                    ),
                SelectFilter::make('Bulan')
                    ->options([
                        '1'=>'Januari','2'=>'Februari','3'=>'Maret',
                        '4'=>'April','5'=>'Mei','6'=>'Juni',
                        '7'=>'Juli','8'=>'Agustus','9'=>'September',
                        '10'=>'Oktober','11'=>'November','12'=>'Desember',
                    ]),
                Filter::make('TimeMasuk')
                    ->form([
                        DatePicker::make('dari')->label('Dari Tanggal'),
                        DatePicker::make('sampai')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['dari'], fn($q) => $q->whereDate('TimeMasuk', '>=', $data['dari']))
                            ->when($data['sampai'], fn($q) => $q->whereDate('TimeMasuk', '<=', $data['sampai']));
                    }),
            ])
            ->defaultSort('TimeMasuk', 'desc')
            ->striped()
            ->defaultPaginationPageOption(50);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockpileWBS::route('/'),
        ];
    }

    public static function canCreate(): bool { return false; }
}