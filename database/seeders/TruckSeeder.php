<?php

namespace Database\Seeders;

use App\Models\Truck;
use App\Models\RfidPoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TruckSeeder extends Seeder
{
    /**
     * Generate NOPOL Indonesia tanpa spasi (B1234ABC) UNIQUE
     */
    private function generateNopol(array &$used): string
    {
        do {
            $prefix = collect(['B', 'D', 'F', 'E', 'Z', 'T', 'A'])->random();
            $number = rand(1000, 9999);
            $suffix = Str::upper(Str::random(rand(2, 3)));

            $nopol = $prefix . $number . $suffix;
        } while (in_array($nopol, $used));

        $used[] = $nopol;
        return $nopol;
    }

    public function run(): void
    {
        $usedNopol = [];

        $locations = [
            'Mainhole',
            'Depan Kiri',
            'Depan Kanan',
            'Tengah Kiri',
            'Tengah Kanan',
        ];

        for ($i = 1; $i <= 15; $i++) {

            $truck = Truck::create([
                'truck_id' => $this->generateNopol($usedNopol),
                'capacity' => collect(['4', '5', '8', '16', '24', '32'])->random(),
                'merk' => collect(['Hino', 'Mitsubishi', 'Isuzu'])->random(),
                'status' => collect([
                    'available',
                    'available',
                    'available',
                    'maintenance',
                    'afkir'
                ])->random(),
            ]);

            // ==== Create RFID Points ====
            foreach ($locations as $index => $location) {
                RfidPoint::create([
                    'truck_id' => $truck->id,
                    'rfid_code' => 'RFID-' . $truck->id . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                    'location' => $location,
                    'point_number' => $index + 1,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('15 Trucks + RFID Points seeded successfully!');
    }
}
