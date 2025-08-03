<?php

namespace Database\Seeders;

use App\Models\TourCategory;
use Illuminate\Database\Seeder;

class TourCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'category_name' => 'Tour trong nước',
                'thumbnail' => null,
                'is_deleted' => 'active'
            ],
            [
                'category_name' => 'Tour nước ngoài',
                'thumbnail' => null,
                'is_deleted' => 'active'
            ],
            [
                'category_name' => 'Tour khám phá',
                'thumbnail' => null,
                'is_deleted' => 'active'
            ],
            [
                'category_name' => 'Tour nghỉ dưỡng',
                'thumbnail' => null,
                'is_deleted' => 'active'
            ],
            [
                'category_name' => 'Tour mạo hiểm',
                'thumbnail' => null,
                'is_deleted' => 'active'
            ]
        ];

        foreach ($categories as $categoryData) {
            TourCategory::create($categoryData);
        }
    }
}
