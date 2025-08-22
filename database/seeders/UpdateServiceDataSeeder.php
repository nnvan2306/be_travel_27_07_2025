<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateServiceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cập nhật bus_routes với total_seats
        DB::table('bus_routes')->update([
            'total_seats' => DB::raw('COALESCE(seats, 45)'), // Mặc định 45 ghế nếu seats null
        ]);

        // Cập nhật motorbikes với total_quantity
        DB::table('motorbikes')->update([
            'total_quantity' => 5, // Mặc định 5 xe máy cho mỗi loại
        ]);

        // Cập nhật bookings với service_quantity
        DB::table('bookings')->update([
            'service_quantity' => DB::raw('COALESCE(quantity, 1)'), // Sử dụng quantity làm service_quantity
        ]);

        // Cập nhật end_date cho bookings nếu chưa có
        DB::table('bookings')
            ->whereNull('end_date')
            ->update([
                'end_date' => DB::raw('start_date'), // Mặc định bằng start_date
            ]);

        $this->command->info('Service data updated successfully!');
    }
}
