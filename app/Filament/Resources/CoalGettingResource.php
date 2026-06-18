<?php

namespace App\Filament\Resources;
    
use App\Filament\Resources\Concerns\HasFilamentRoleAccess;
use App\Filament\Resources\CoalGettingResource\Pages;
use App\Models\CoalGetting;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;

class CoalGettingResource extends Resource
{
    use HasFilamentRoleAccess;

    protected static ?string $model = CoalGetting::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Coal Getting';
    protected static ?string $navigationGroup = 'Coal Movement';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Tanggal'),
                TextColumn::make('Shift')
                    ->badge()
                    ->label('Shift'),
                TextColumn::make('Truck')
                    ->searchable()
                    ->label('Truck'),
                TextColumn::make('Asal')
                    ->label('Asal'),
                TextColumn::make('Tujuan')
                    ->label('Tujuan'),
                TextColumn::make('Kode')
                    ->label('Kode Material'),
                TextColumn::make('Stack')
                    ->label('Stack'),
                TextColumn::make('Bruto')
                    ->numeric(2)
                    ->label('Bruto (Ton)'),
                TextColumn::make('Tara')
                    ->numeric(2)
                    ->label('Tara (Ton)'),
                TextColumn::make('Netto')
                    ->numeric(2)
                    ->label('Netto (Ton)')
                    ->color('success')
                    ->weight('bold'),
                TextColumn::make('Operator')
                    ->label('Operator')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('Shift')
                    ->options([
                        'I' => 'Shift 1',
                        'II' => 'Shift 2',
                    ]),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoalGettings::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // read only
    }

    public static function canAccess(): bool
    {
        return static::isFilamentAdmin();
    }
}