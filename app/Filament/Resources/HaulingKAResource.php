<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasFilamentRoleAccess;
use App\Filament\Resources\HaulingKAResource\Pages;
use App\Models\HaulingKA;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;

class HaulingKAResource extends Resource
{
    use HasFilamentRoleAccess;

    protected static ?string $model = HaulingKA::class;
    protected static ?string $navigationIcon = 'heroicon-o-forward';
    protected static ?string $navigationLabel = 'Hauling KA ke WBS';
    protected static ?string $navigationGroup = 'Coal Movement';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Tanggal')->date('d/m/Y')->sortable()->label('Tanggal'),
                TextColumn::make('Shift')->badge()->label('Shift'),
                TextColumn::make('NoDO')->label('No DO'),
                TextColumn::make('NoCT')->label('No CT'),
                TextColumn::make('NoKA')->label('No KA'),
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
                TextColumn::make('Status')
                    ->badge()
                    ->color(fn(string $state) => match($state) {
                        'SELESAI' => 'success',
                        'PROSES'  => 'warning',
                        default   => 'gray',
                    }),
                TextColumn::make('WaktuTiba')->dateTime('d/m/Y H:i')->label('Waktu Tiba')->toggleable(),
                TextColumn::make('WaktuBerangkat')->dateTime('d/m/Y H:i')->label('Waktu Berangkat')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('Shift')
                    ->options(['1'=>'Shift 1','2'=>'Shift 2','3'=>'Shift 3']),
                SelectFilter::make('Status')
                    ->options(['SELESAI'=>'Selesai','PROSES'=>'Proses']),
                Filter::make('Tanggal')
                    ->form([
                        DatePicker::make('dari')->label('Dari Tanggal'),
                        DatePicker::make('sampai')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['dari'], fn($q) => $q->whereDate('Tanggal', '>=', $data['dari']))
                            ->when($data['sampai'], fn($q) => $q->whereDate('Tanggal', '<=', $data['sampai']));
                    }),
            ])
            ->defaultSort('Tanggal', 'desc')
            ->striped()
            ->defaultPaginationPageOption(50);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHaulingKAs::route('/'),
        ];
    }

    public static function canCreate(): bool { return false; }

    public static function canAccess(): bool
    {
        return static::isFilamentAdmin();
    }
}