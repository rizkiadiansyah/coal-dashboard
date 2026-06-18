<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasFilamentRoleAccess;
use App\Filament\Resources\CrusherActivityResource\Pages;
use App\Models\CrusherActivity;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;

class CrusherActivityResource extends Resource
{
    use HasFilamentRoleAccess;

    protected static ?string $model = CrusherActivity::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Crushing';
    protected static ?string $navigationGroup = 'Coal Movement';
    protected static ?int $navigationSort = 2;

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
                TextColumn::make('Shift')->badge()->label('Shift'),
                TextColumn::make('Crusher')->label('Crusher'),
                TextColumn::make('Activity')->label('Activity')->wrap(),
                TextColumn::make('Kode_Material')->label('Kode Material'),
                TextColumn::make('Stack')->label('Stack'),
                TextColumn::make('material_desc')->label('Material')->toggleable(),
                TextColumn::make('Alat_loading')->label('Alat Loading'),
                TextColumn::make('Qty_bucket')->numeric(0)->label('Qty Bucket'),
                TextColumn::make('Tonase')
                    ->numeric(2)
                    ->label('Tonase (Ton)')
                    ->color('success')
                    ->weight('bold'),
                TextColumn::make('Kendala')
                    ->label('Kendala')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('Shift')
                    ->options(['1'=>'Shift 1','2'=>'Shift 2','3'=>'Shift 3']),
                SelectFilter::make('Crusher')
                    ->relationship('', 'Crusher')
                    ->searchable(),
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
            'index' => Pages\ListCrusherActivities::route('/'),
        ];
    }

    public static function canCreate(): bool { return false; }

    public static function canAccess(): bool
    {
        return static::isFilamentAdmin();
    }
}