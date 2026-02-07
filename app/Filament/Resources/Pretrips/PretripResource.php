<?php

namespace App\Filament\Resources\Pretrips;

use App\Filament\Resources\Pretrips\Pages\ManagePretrips;
use App\Models\Pretrip;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PretripResource extends Resource
{
    protected static ?string $model = Pretrip::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'PreTrip Inspection';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Pilih Truck
                Select::make('truck_id')
                    ->label('Pilih Truck')
                    ->relationship('truck', 'truck_id')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        // Reset taps ketika ganti truck
                        $set('taps_data', []);

                        // Auto-generate field untuk setiap titik RFID
                        if ($state) {
                            $truck = Truck::find($state);
                            if ($truck) {
                                $rfidPoints = $truck->activeRfidPoints()
                                    ->orderBy('point_number')
                                    ->get();

                                $tapsData = [];
                                foreach ($rfidPoints as $index => $point) {
                                    $tapsData[] = [
                                        'rfid_point_id' => $point->id,
                                        'point_label' => "Point {$point->point_number} - {$point->location}",
                                        'tapped_at' => now(),
                                        'tap_sequence' => $index + 1,
                                    ];
                                }
                                $set('taps_data', $tapsData);
                            }
                        }
                    })
                    ->helperText(fn(Get $get) => self::getTruckInfo($get('truck_id'))),

                DatePicker::make('trip_date')
                    ->label('Tanggal Trip')
                    ->required()
                    ->default(today())
                    ->native(false),

                // Select::make('driver_id')
                //     ->label('Driver')
                //     ->relationship('driver', 'name')
                //     ->searchable()
                //     ->preload()
                //     ->nullable()
                //     ->helperText('Opsional'),

                TimePicker::make('start_time')
                    ->label('Waktu Mulai')
                    ->default(now())
                    ->seconds(false),

                // Field Dinamis untuk Tap setiap Titik
                Repeater::make('taps_data')
                    ->label('Tap Setiap Titik RFID')
                    ->schema([
                        Hidden::make('rfid_point_id'),
                        Hidden::make('tap_sequence'),

                        TextInput::make('point_label')
                            ->label('Titik')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        TimePicker::make('tapped_at')
                            ->label('Tapped At')
                            ->required()
                            ->seconds(true)
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->visible(fn(Get $get) => !empty($get('taps_data')))
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->defaultItems(0)
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3)
                    ->columnSpanFull(),


            ]);
    }

    // public static function infolist(Schema $schema): Schema
    // {
    //     return $schema
    //         ->components([
    //             Section::make('Informasi PreTrip')
    //                 ->schema([
    //                     TextEntry::make('truck.truck_id')
    //                         ->label('Truck'),
    //                     TextEntry::make('driver.name')
    //                         ->label('Driver')
    //                         ->placeholder('-'),
    //                     TextEntry::make('trip_date')
    //                         ->label('Tanggal Trip')
    //                         ->date(),
    //                     TextEntry::make('start_time')
    //                         ->label('Waktu Mulai')
    //                         ->time('H:i:s')
    //                         ->placeholder('-'),
    //                     TextEntry::make('end_time')
    //                         ->label('Waktu Selesai')
    //                         ->time('H:i:s')
    //                         ->placeholder('-'),
    //                     TextEntry::make('status')
    //                         ->label('Status')
    //                         ->badge()
    //                         ->color(fn (string $state): string => match ($state) {
    //                             'completed' => 'success',
    //                             'in_progress' => 'warning',
    //                             'incomplete' => 'danger',
    //                             default => 'gray',
    //                         }),
    //                     TextEntry::make('completion_percentage')
    //                         ->label('Progress')
    //                         ->suffix('%')
    //                         ->badge()
    //                         ->color('success'),
    //                 ])
    //                 ->columns(3),

    //             InfoSection::make('Detail Tapping')
    //                 ->schema([
    //                     RepeatableEntry::make('taps')
    //                         ->label('')
    //                         ->schema([
    //                             TextEntry::make('tap_sequence')
    //                                 ->label('Urutan'),
    //                             TextEntry::make('rfidPoint.point_number')
    //                                 ->label('Point #'),
    //                             TextEntry::make('rfidPoint.location')
    //                                 ->label('Lokasi'),
    //                             TextEntry::make('tapped_at')
    //                                 ->label('Tapped At')
    //                                 ->dateTime('H:i:s'),
    //                         ])
    //                         ->columns(4),
    //                 ]),

    //             InfoSection::make('Catatan')
    //                 ->schema([
    //                     TextEntry::make('notes')
    //                         ->label('')
    //                         ->placeholder('Tidak ada catatan'),
    //                 ])
    //                 ->visible(fn (Pretrip $record): bool => !empty($record->notes)),

    //             InfoSection::make('Timestamps')
    //                 ->schema([
    //                     TextEntry::make('created_at')
    //                         ->dateTime()
    //                         ->label('Dibuat'),
    //                     TextEntry::make('updated_at')
    //                         ->dateTime()
    //                         ->label('Diperbarui'),
    //                     TextEntry::make('deleted_at')
    //                         ->dateTime()
    //                         ->label('Dihapus')
    //                         ->visible(fn(Pretrip $record): bool => $record->trashed()),
    //                 ])
    //                 ->columns(3)
    //                 ->collapsed(),
    //         ]);
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('truck.truck_id')
                    ->label('Truck ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('driver.name')
                    ->label('Driver')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('trip_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Mulai')
                    ->time('H:i')
                    ->placeholder('-'),
                TextColumn::make('end_time')
                    ->label('Selesai')
                    ->time('H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'warning',
                        'incomplete' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'incomplete' => 'Incomplete',
                        default => $state,
                    }),
                TextColumn::make('taps_count')
                    ->label('Taps')
                    ->counts('taps')
                    ->badge()
                    ->color('info'),
                TextColumn::make('completion_percentage')
                    ->label('Progress')
                    ->suffix('%')
                    ->badge()
                    ->color(fn(float $state): string => $state >= 100 ? 'success' : 'warning'),
                TextColumn::make('created_at')
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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'incomplete' => 'Incomplete',
                    ])
                    ->native(false),
                SelectFilter::make('truck_id')
                    ->label('Truck')
                    ->relationship('truck', 'truck_id')
                    ->searchable()
                    ->preload(),
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
            ])
            ->defaultSort('trip_date', 'desc');
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
        $registered = $truck->activeRfidPoints()->count();

        return "Kapasitas: {$truck->capacity} KL | Titik Terdaftar: {$registered} / {$required}";
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePretrips::route('/'),
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
