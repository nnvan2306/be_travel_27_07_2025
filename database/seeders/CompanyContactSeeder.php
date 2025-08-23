<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CompanyContact;

class CompanyContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompanyContact::create([
            'address' => 'Tòa nhà VTravel số 1 đường Không tên, Hải Châu, Đà Nẵng',
            'hotline' => '0987654321',
            'email' => 'vtravel@gmail.com',
            'website' => 'www.vtravel.com.vn',
            'is_deleted' => 'active'
        ]);
    }
}
