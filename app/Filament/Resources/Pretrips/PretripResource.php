<?php

namespace App\Filament\Resources\Pretrips;

use App\Filament\Resources\Pretrips\Pages\ManagePretrips;
use App\Models\Pretrip;
use App\Models\PretripTap;
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
                Select::make('truck_id')
                    ->label('Pilih Truck')
                    ->relationship('truck', 'truck_id')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state, Get $get) {
                        if (!$state) {
                            $set('taps', []);
                            return;
                        }

                        $truck = Truck::find($state);
                        if (!$truck) {
                            return;
                        }

                        // Get existing taps count untuk avoid overwrite saat edit
                        $existingTaps = [];

                        $rfidPoints = $truck->activeRfidPoints()
                            ->orderBy('point_number')
                            ->get();

                        $rows = [];
                        foreach ($rfidPoints as $index => $point) {
                            $rows[] = [
                                'rfid_point_id' => $point->id,
                                'tap_sequence' => $index + 1,
                                'tapped_at' => now(),
                                'point_info' => "Point {$point->point_number} - {$point->location}",
                            ];
                        }

                        $set('taps', $rows);
                    })
                    ->helperText(fn(Get $get) => self::getTruckInfo($get('truck_id'))),

                DatePicker::make('trip_date')
                    ->label('Tanggal Trip')
                    ->required()
                    ->default(today())
                    ->native(false),

                TimePicker::make('start_time')
                    ->label('Waktu Mulai')
                    ->default(now())
                    ->seconds(false),

                // REPEATER DENGAN RELATIONSHIP
                Repeater::make('taps')
                    ->label('Tap Setiap Titik RFID')
                    ->relationship('taps')  // <<< INI PENTING
                    ->schema([
                        // JANGAN pakai Hidden! Pakai Select dengan disabled
                        Select::make('rfid_point_id')
                            ->label('Titik RFID')
                            ->required()
                            ->disabled()
                            ->dehydrated(true)  // <<< WAJIB!
                            ->options(function (Get $get, $record) {
                                // Saat create: ambil dari parent truck_id
                                // Saat edit: ambil dari existing record
                                $truckId = $get('../../truck_id');

                                if (!$truckId) {
                                    return [];
                                }

                                return \App\Models\RfidPoint::where('truck_id', $truckId)
                                    ->where('is_active', true)
                                    ->orderBy('point_number')
                                    ->get()
                                    ->mapWithKeys(function ($point) {
                                        return [$point->id => "Point {$point->point_number} - {$point->location}"];
                                    });
                            }),

                        TextInput::make('tap_sequence')
                            ->label('Urutan')
                            ->required()
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),  // <<< WAJIB!

                        TimePicker::make('tapped_at')
                            ->label('Waktu Tap')
                            ->required()
                            ->seconds(true)
                            ->native(false),
                    ])
                    ->columns(3)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->defaultItems(0)
                    ->columnSpanFull()
                    ->saveRelationshipsUsing(function ($component, $state, $record) {
                        // CUSTOM SAVE LOGIC
                        if (!$record || !is_array($state)) {
                            return;
                        }

                        // Hapus taps lama
                        $record->taps()->delete();

                        // Insert taps baru
                        foreach ($state as $item) {
                            if (isset($item['rfid_point_id'], $item['tap_sequence'], $item['tapped_at'])) {
                                PretripTap::create([
                                    'pretrip_id' => $record->id,
                                    'rfid_point_id' => $item['rfid_point_id'],
                                    'tap_sequence' => $item['tap_sequence'],
                                    'tapped_at' => $item['tapped_at'],
                                ]);
                            }
                        }

                        // Update status
                        $record->updateStatus();
                    }),

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
            ->modifyQueryUsing(function ($query) {
                return $query->with(['truck', 'taps.rfidPoint']);
            })
            ->columns([
                TextColumn::make('truck.truck_id')
                    ->label('Nopol')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('trip_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

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

                // TAP 1
                TextColumn::make('tap1_location')
                    ->label('Lokasi Tap 1')
                    ->getStateUsing(fn($record) => $record->taps->where('tap_sequence', 1)->first()?->rfidPoint?->location ?? '-'),

                TextColumn::make('tap1_time')
                    ->label('Jam Tap 1')
                    ->getStateUsing(function ($record) {
                        $tap = $record->taps->where('tap_sequence', 1)->first();
                        return $tap?->tapped_at ? $tap->tapped_at->format('H:i:s') : '-';
                    }),

                // TAP 2
                TextColumn::make('tap2_location')
                    ->label('Lokasi Tap 2')
                    ->getStateUsing(fn($record) => $record->taps->where('tap_sequence', 2)->first()?->rfidPoint?->location ?? '-'),

                TextColumn::make('tap2_time')
                    ->label('Jam Tap 2')
                    ->getStateUsing(function ($record) {
                        $tap = $record->taps->where('tap_sequence', 2)->first();
                        return $tap?->tapped_at ? $tap->tapped_at->format('H:i:s') : '-';
                    }),

                // TAP 3
                TextColumn::make('tap3_location')
                    ->label('Lokasi Tap 3')
                    ->getStateUsing(fn($record) => $record->taps->where('tap_sequence', 3)->first()?->rfidPoint?->location ?? '-')
                    ->toggleable(),

                TextColumn::make('tap3_time')
                    ->label('Jam Tap 3')
                    ->getStateUsing(function ($record) {
                        $tap = $record->taps->where('tap_sequence', 3)->first();
                        return $tap?->tapped_at ? $tap->tapped_at->format('H:i:s') : '-';
                    })
                    ->toggleable(),

                // TAP 4
                TextColumn::make('tap4_location')
                    ->label('Lokasi Tap 4')
                    ->getStateUsing(fn($record) => $record->taps->where('tap_sequence', 4)->first()?->rfidPoint?->location ?? '-'),

                TextColumn::make('tap4_time')
                    ->label('Jam Tap 4')
                    ->getStateUsing(function ($record) {
                        $tap = $record->taps->where('tap_sequence', 4)->first();
                        return $tap?->tapped_at ? $tap->tapped_at->format('H:i:s') : '-';
                    }),

                // TAP 5
                TextColumn::make('tap5_location')
                    ->label('Lokasi Tap 5')
                    ->getStateUsing(fn($record) => $record->taps->where('tap_sequence', 5)->first()?->rfidPoint?->location ?? '-'),

                TextColumn::make('tap5_time')
                    ->label('Jam Tap 5')
                    ->getStateUsing(function ($record) {
                        $tap = $record->taps->where('tap_sequence', 5)->first();
                        return $tap?->tapped_at ? $tap->tapped_at->format('H:i:s') : '-';
                    }),

                TextColumn::make('completion_percentage')
                    ->label('Progress')
                    ->suffix('%')
                    ->badge()
                    ->color(fn(float $state): string => $state >= 100 ? 'success' : 'warning'),

                TextColumn::make('created_at')
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
            ->bulkActions([
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
