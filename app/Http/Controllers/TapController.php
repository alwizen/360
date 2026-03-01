<?php

namespace App\Http\Controllers;

use App\Models\Pretrip;
use App\Models\PretripTap;
use App\Models\RfidPoint;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

class TapController extends Controller
{
    /**
     * Tampilkan halaman tap
     */
    public function index()
    {
        return view('tap.index');
    }

    /**
     * Get active pretrip
     */
    public function getActivePretrip()
    {
        $pretrip = Pretrip::whereDate('trip_date', today())
            ->where('status', 'in_progress')
            ->with(['truck', 'taps.rfidPoint'])
            ->first();

        if (!$pretrip) {
            return response()->json([
                'active' => false,
                'message' => 'Belum ada PreTrip aktif'
            ]);
        }

        return response()->json([
            'active' => true,
            'pretrip' => [
                'id' => $pretrip->id,
                'truck_id' => $pretrip->truck->truck_id,
                'trip_date' => $pretrip->trip_date->format('d M Y'),
                'status' => $pretrip->status,
                'progress' => $pretrip->completion_percentage,
                'taps' => $pretrip->taps->map(function ($tap) {
                    return [
                        'sequence' => $tap->tap_sequence,
                        'point_number' => $tap->rfidPoint->point_number,
                        'location' => $tap->rfidPoint->location,
                        'time' => $tap->tapped_at->format('H:i:s'),
                    ];
                })
            ]
        ]);
    }

    /**
     * Process tap
     */
    public function processTap(Request $request)
    {
        $request->validate([
            'rfid_code' => 'required|string',
        ]);

        // Cari RFID Point
        $rfidPoint = RfidPoint::where('rfid_code', $request->rfid_code)
            ->where('is_active', true)
            ->with('truck')
            ->first();

        if (!$rfidPoint) {
            return response()->json([
                'success' => false,
                'message' => '❌ RFID Code tidak ditemukan atau tidak aktif!',
                'type' => 'error'
            ], 404);
        }

        // Cek apakah ada pretrip aktif untuk truck ini hari ini
        $pretrip = Pretrip::where('truck_id', $rfidPoint->truck_id)
            ->whereDate('trip_date', today())
            ->where('status', 'in_progress')
            ->first();

        // Jika belum ada pretrip, buat baru
        if (!$pretrip) {
            $pretrip = Pretrip::create([
                'truck_id' => $rfidPoint->truck_id,
                'trip_date' => today(),
                'start_time' => now(),
                'status' => 'in_progress',
            ]);
        }

        // Cek apakah point ini sudah di-tap
        $existingTap = PretripTap::where('pretrip_id', $pretrip->id)
            ->where('rfid_point_id', $rfidPoint->id)
            ->first();

        if ($existingTap) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Point ini sudah di-tap sebelumnya!',
                'type' => 'warning'
            ], 400);
        }

        // Hitung sequence berikutnya
        $nextSequence = $pretrip->taps()->count() + 1;

        // Buat tap baru
        $tap = PretripTap::create([
            'pretrip_id' => $pretrip->id,
            'rfid_point_id' => $rfidPoint->id,
            'tapped_at' => now(),
            'tap_sequence' => $nextSequence,
        ]);

        // Update status pretrip
        $pretrip->updateStatus();
        $pretrip->refresh();

        Notification::make()
            ->title('Tap berhasil')
            ->body("Truck {$rfidPoint->truck->truck_id} tap di {$rfidPoint->location}")
            // ->sendToDatabase(User::all());
            ->sendToDatabase(auth()->user());

        return response()->json([
            'success' => true,
            'message' => '✅ Tap berhasil dicatat!',
            'type' => 'success',
            'last_tap' => [
                'truck' => $rfidPoint->truck->truck_id,
                'location' => $rfidPoint->location,
                'point_number' => $rfidPoint->point_number,
                'time' => $tap->tapped_at->format('H:i:s'),
                'sequence' => $tap->tap_sequence,
            ],
            'pretrip' => [
                'id' => $pretrip->id,
                'truck_id' => $pretrip->truck->truck_id,
                'trip_date' => $pretrip->trip_date->format('d M Y'),
                'status' => $pretrip->status,
                'progress' => $pretrip->completion_percentage,
                'taps' => $pretrip->taps->map(function ($tap) {
                    return [
                        'sequence' => $tap->tap_sequence,
                        'point_number' => $tap->rfidPoint->point_number,
                        'location' => $tap->rfidPoint->location,
                        'time' => $tap->tapped_at->format('H:i:s'),
                    ];
                })
            ]
        ]);
    }

    /**
     * Reset session
     */
    public function resetSession()
    {
        return response()->json([
            'success' => true,
            'message' => 'Session direset'
        ]);
    }
}
