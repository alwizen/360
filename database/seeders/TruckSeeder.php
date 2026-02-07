<?php

namespace Database\Seeders;

use App\Models\Truck;
use App\Models\RfidPoint;
use Illuminate\Database\Seeder;

class TruckSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample Trucks
        $trucks = [
            [
                'truck_id' => 'MT-001',
                'capacity' => '4',
                'merk' => 'Hino',
                'status' => 'available',
                'rfid_points' => [
                    ['location' => 'Depan Kiri', 'rfid_code' => 'RFID-001-01'],
                    ['location' => 'Belakang Kanan', 'rfid_code' => 'RFID-001-02'],
                ]
            ],
            [
                'truck_id' => 'MT-002',
                'capacity' => '8',
                'merk' => 'Mitsubishi',
                'status' => 'available',
                'rfid_points' => [
                    ['location' => 'Depan Kiri', 'rfid_code' => 'RFID-002-01'],
                    ['location' => 'Depan Kanan', 'rfid_code' => 'RFID-002-02'],
                    ['location' => 'Belakang Tengah', 'rfid_code' => 'RFID-002-03'],
                ]
            ],
            [
                'truck_id' => 'MT-003',
                'capacity' => '16',
                'merk' => 'Isuzu',
                'status' => 'available',
                'rfid_points' => [
                    ['location' => 'Depan Kiri', 'rfid_code' => 'RFID-003-01'],
                    ['location' => 'Depan Kanan', 'rfid_code' => 'RFID-003-02'],
                ]
            ],
            [
                'truck_id' => 'MT-004',
                'capacity' => '24',
                'merk' => 'Hino',
                'status' => 'maintenance',
                'rfid_points' => [
                    ['location' => 'Depan Kiri', 'rfid_code' => 'RFID-004-01'],
                    ['location' => 'Depan Kanan', 'rfid_code' => 'RFID-004-02'],
                    ['location' => 'Tengah Kiri', 'rfid_code' => 'RFID-004-03'],
                    ['location' => 'Tengah Kanan', 'rfid_code' => 'RFID-004-04'],
                    ['location' => 'Belakang Atas', 'rfid_code' => 'RFID-004-05'],
                ]
            ],
            [
                'truck_id' => 'MT-005',
                'capacity' => '32',
                'merk' => 'Mitsubishi',
                'status' => 'available',
                'rfid_points' => [
                    ['location' => 'Depan Kiri', 'rfid_code' => 'RFID-005-01'],
                    ['location' => 'Depan Kanan', 'rfid_code' => 'RFID-005-02'],
                    ['location' => 'Tengah Kiri', 'rfid_code' => 'RFID-005-03'],
                ]
            ],
            [
                'truck_id' => 'MT-006',
                'capacity' => '5',
                'merk' => 'Hino',
                'status' => 'afkir',
                'rfid_points' => []
            ],
        ];

        foreach ($trucks as $truckData) {
            $rfidPoints = $truckData['rfid_points'];
            unset($truckData['rfid_points']);

            $truck = Truck::create($truckData);

            foreach ($rfidPoints as $index => $point) {
                RfidPoint::create([
                    'truck_id' => $truck->id,
                    'rfid_code' => $point['rfid_code'],
                    'location' => $point['location'],
                    'point_number' => $index + 1,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Trucks and RFID Points seeded successfully!');
    }
}
