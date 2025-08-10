<?php

namespace Database\Seeders;

use App\Models\Blog;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $blogs = [
            [
                'title' => 'Khám phá Vịnh Hạ Long - Kỳ quan thiên nhiên thế giới',
                'description' => 'Hành trình khám phá vẻ đẹp hùng vĩ của Vịnh Hạ Long với những hòn đảo đá vôi độc đáo và làng chài truyền thống.',
                'markdown' => '# Khám phá Vịnh Hạ Long

Vịnh Hạ Long là một trong những kỳ quan thiên nhiên đẹp nhất thế giới, được UNESCO công nhận là Di sản thiên nhiên thế giới.

## Những điểm nổi bật

- **Hơn 1.600 hòn đảo đá vôi** với hình dạng độc đáo
- **Làng chài truyền thống** với cuộc sống thủy cư
- **Hang động kỳ bí** như Hang Sửng Sốt, Hang Đầu Gỗ
- **Hoạt động thú vị**: Kayak, leo núi, tắm biển

## Thời gian tốt nhất để tham quan

Mùa xuân (tháng 3-5) và mùa thu (tháng 9-11) là thời điểm lý tưởng nhất để khám phá Vịnh Hạ Long.

## Lưu ý khi du lịch

- Đặt tour sớm trong mùa cao điểm
- Mang theo kem chống nắng và áo khoác
- Tôn trọng môi trường và không xả rác',
                'location' => 'Vịnh Hạ Long, Quảng Ninh',
                'status' => 'published',
                'view_count' => 1250
            ],
            [
                'title' => 'Sapa - Thiên đường mây phủ của Tây Bắc',
                'description' => 'Khám phá vẻ đẹp hoang dã của Sapa với những ruộng bậc thang, núi non hùng vĩ và văn hóa dân tộc độc đáo.',
                'markdown' => '# Sapa - Thiên đường mây phủ

Sapa là điểm đến lý tưởng cho những ai yêu thích khám phá vẻ đẹp hoang dã và văn hóa dân tộc.

## Những địa điểm không thể bỏ qua

### 1. Fansipan - Nóc nhà Đông Dương
- Độ cao 3.143m
- Cáp treo hiện đại nhất thế giới
- View toàn cảnh Sapa từ trên cao

### 2. Ruộng bậc thang
- Lao Chải - Tả Van
- Bản Cát Cát
- Bản Tả Phìn

### 3. Chợ phiên Sapa
- Chợ tình Sapa
- Chợ Bắc Hà
- Chợ Cán Cấu

## Ẩm thực đặc trưng

- Thắng cố
- Cơm lam
- Rượu táo mèo
- Thịt lợn cắp nách',
                'location' => 'Sapa, Lào Cai',
                'status' => 'published',
                'view_count' => 980
            ],
            [
                'title' => 'Phố cổ Hội An - Nét đẹp cổ kính giữa lòng phố thị',
                'description' => 'Hành trình khám phá phố cổ Hội An với kiến trúc cổ kính, ẩm thực đặc trưng và văn hóa truyền thống.',
                'markdown' => '# Phố cổ Hội An

Hội An là một trong những thành phố cổ đẹp nhất Việt Nam, được UNESCO công nhận là Di sản văn hóa thế giới.

## Kiến trúc độc đáo

### Nhà cổ
- Nhà cổ Tấn Ký (200 năm tuổi)
- Nhà cổ Phùng Hưng
- Nhà cổ Quân Thắng

### Chùa Cầu
- Biểu tượng của Hội An
- Kiến trúc Nhật - Việt độc đáo
- Hơn 400 năm tuổi

## Ẩm thực nổi tiếng

- Cao lầu
- Mì Quảng
- Bánh mì Phượng
- Bánh bao, bánh vạc

## Hoạt động thú vị

- Đi thuyền thúng
- Thả đèn hoa đăng
- Học nấu ăn truyền thống
- May áo dài',
                'location' => 'Hội An, Quảng Nam',
                'status' => 'published',
                'view_count' => 756
            ]
        ];

        foreach ($blogs as $blogData) {
            Blog::create($blogData);
        }
    }
}
