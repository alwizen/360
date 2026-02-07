<?php

namespace App\Filament\Resources\RfidPoints;

use App\Filament\Resources\RfidPoints\Pages\ManageRfidPoints;
use App\Models\RfidPoint;
use App\Models\Truck;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RfidPointResource extends Resource
{
    protected static ?string $model = RfidPoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Registrasi RFID';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Step 1: Pilih Truck
                Select::make('truck_id')
                    ->label('Pilih Truck')
                    ->relationship('truck', 'truck_id')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set) {
                        // Reset field ketika truck berubah
                        $set('num_points', null);
                        $set('points', []);
                    })
                    ->helperText(fn(Get $get) => self::getTruckInfo($get('truck_id'))),

                // Step 2: Pilih Jumlah Titik
                Select::make('num_points')
                    ->label('Jumlah Titik yang Akan Didaftarkan')
                    ->options(fn(Get $get) => self::getAvailablePointsOptions($get('truck_id')))
                    ->required()
                    ->live()
                    ->disabled(fn(Get $get) => !$get('truck_id'))
                    ->afterStateUpdated(function ($state, Set $set) {
                        // Generate field sesuai jumlah yang dipilih
                        $points = [];
                        for ($i = 0; $i < (int)$state; $i++) {
                            $points[] = [
                                'rfid_code' => '',
                                'location' => '',
                            ];
                        }
                        $set('points', $points);
                    })
                    ->helperText('Pilih berapa titik RFID yang akan didaftarkan'),

                // Step 3: Field Dinamis untuk Lokasi & RFID Code
                Repeater::make('points')
                    ->label('Detail Titik RFID')
                    ->schema([
                        TextInput::make('location')
                            ->label('Lokasi Titik')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Depan Kiri, Belakang Kanan, Atas')
                            ->columnSpan(1),

                        TextInput::make('rfid_code')
                            ->label('RFID Code')
                            ->required()
                            ->unique('rfid_points', 'rfid_code', ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Scan atau masukkan kode RFID')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->visible(fn(Get $get) => $get('num_points') !== null && $get('num_points') > 0)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('truck.truck_id')
                    ->label('Truck'),
                TextEntry::make('rfid_code'),
                TextEntry::make('location'),
                TextEntry::make('point_number')
                    ->numeric(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn(RfidPoint $record): bool => $record->trashed()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('truck.truck_id')
                    ->label('Truck ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('point_number')
                    ->label('Titik #')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable(),
                TextColumn::make('rfid_code')
                    ->label('RFID Code')
                    ->searchable()
                    ->copyable(),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Get truck info untuk helper text
     */
    protected static function getTruckInfo(?int $truckId): ?string
    {
        if (!$truckId) {
            return null;
        }

        $truck = Truck::find($truckId);
        if (!$truck) {
            return null;
        }

        $required = $truck->getRequiredPointsCount();
        $registered = $truck->rfidPoints()->count();
        $remaining = max(0, $required - $registered);

        return "Kapasitas: {$truck->capacity} KL | Diperlukan: {$required} titik | Terdaftar: {$registered} | Tersisa: {$remaining}";
    }

    /**
     * Get options untuk jumlah titik yang bisa didaftarkan
     */
    protected static function getAvailablePointsOptions(?int $truckId): array
    {
        if (!$truckId) {
            return [];
        }

        $truck = Truck::find($truckId);
        if (!$truck) {
            return [];
        }

        $required = $truck->getRequiredPointsCount();
        $registered = $truck->rfidPoints()->count();
        $remaining = max(0, $required - $registered);

        if ($remaining === 0) {
            return [0 => 'Semua titik sudah terdaftar'];
        }

        $options = [];
        for ($i = 1; $i <= $remaining; $i++) {
            $options[$i] = "{$i} titik";
        }

        return $options;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRfidPoints::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
