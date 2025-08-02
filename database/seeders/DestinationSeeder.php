<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Album;
use App\Models\AlbumImage;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo danh mục điểm đến
        $categories = [
            ['category_name' => 'Biển', 'thumbnail' => null],
            ['category_name' => 'Núi', 'thumbnail' => null],
            ['category_name' => 'Thành phố', 'thumbnail' => null],
            ['category_name' => 'Di tích lịch sử', 'thumbnail' => null],
        ];

        foreach ($categories as $categoryData) {
            DestinationCategory::create($categoryData);
        }

        // Tạo album cho điểm đến
        $album = Album::create([
            'title' => 'Album Điểm Đến Mẫu',
            'is_deleted' => 'active'
        ]);

        // Tạo điểm đến mẫu
        $destinations = [
            [
                'name' => 'Vịnh Hạ Long',
                'category_id' => 1, // Biển
                'description' => 'Vịnh Hạ Long là một vịnh nhỏ thuộc phần bờ Tây vịnh Bắc Bộ tại khu vực biển Đông Bắc Việt Nam.',
                'area' => 'Quảng Ninh',
                'img_banner' => null,
                'is_deleted' => 'active',
                'price' => 500000,
                'slug' => 'vinh-ha-long',
                'album_id' => $album->album_id
            ],
            [
                'name' => 'Sapa',
                'category_id' => 2, // Núi
                'description' => 'Sa Pa là một điểm du lịch cách trung tâm thành phố Lào Cai khoảng 38 km.',
                'area' => 'Lào Cai',
                'img_banner' => null,
                'is_deleted' => 'active',
                'price' => 300000,
                'slug' => 'sapa',
                'album_id' => $album->album_id
            ],
            [
                'name' => 'Phố cổ Hội An',
                'category_id' => 3, // Thành phố
                'description' => 'Hội An là một thành phố trực thuộc tỉnh Quảng Nam, Việt Nam.',
                'area' => 'Quảng Nam',
                'img_banner' => null,
                'is_deleted' => 'active',
                'price' => 200000,
                'slug' => 'pho-co-hoi-an',
                'album_id' => $album->album_id
            ]
        ];

        foreach ($destinations as $destinationData) {
            Destination::create($destinationData);
        }

        // Tạo ảnh mẫu cho album
        $albumImages = [
            [
                'album_id' => $album->album_id,
                'image_url' => 'albums/1/sample_image_1.jpg',
                'caption' => 'Ảnh mẫu 1',
                'is_deleted' => 'active'
            ],
            [
                'album_id' => $album->album_id,
                'image_url' => 'albums/1/sample_image_2.jpg',
                'caption' => 'Ảnh mẫu 2',
                'is_deleted' => 'active'
            ]
        ];

        foreach ($albumImages as $imageData) {
            AlbumImage::create($imageData);
        }
    }
}
