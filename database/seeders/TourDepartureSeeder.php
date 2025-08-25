<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TourDeparture;
use App\Models\Tour;
use Carbon\Carbon;

class TourDepartureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy tất cả tours
        $tours = Tour::where('is_deleted', 'active')->get();

        if ($tours->isEmpty()) {
            $this->command->info('Không có tour nào để tạo departure. Vui lòng chạy TourSeeder trước.');
            return;
        }

        foreach ($tours as $tour) {
            $this->createDeparturesForTour($tour);
        }

        $this->command->info('Đã tạo thành công dữ liệu mẫu cho tour departures!');
    }

    private function createDeparturesForTour($tour)
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        // Tạo departures cho 3 tháng tiếp theo
        for ($monthOffset = 0; $monthOffset < 3; $monthOffset++) {
            $targetMonth = $currentMonth + $monthOffset;
            $targetYear = $currentYear;
            
            if ($targetMonth > 12) {
                $targetMonth -= 12;
                $targetYear++;
            }

            // Tạo 2-3 departure mỗi tháng
            $departureCount = rand(2, 3);
            
            for ($i = 0; $i < $departureCount; $i++) {
                // Chọn ngày ngẫu nhiên trong tháng (tránh ngày đầu và cuối tháng)
                $day = rand(3, 28);
                $departureDate = Carbon::create($targetYear, $targetMonth, $day);
                
                // Chỉ tạo departure trong tương lai
                if ($departureDate->isPast()) {
                    continue;
                }

                // Giá có thể khác nhau theo tháng (mùa cao điểm)
                $basePrice = (float) $tour->price;
                $seasonalMultiplier = $this->getSeasonalMultiplier($targetMonth);
                $price = $basePrice * $seasonalMultiplier;

                // Sức chứa ngẫu nhiên từ 20-50
                $maxCapacity = rand(20, 50);

                TourDeparture::create([
                    'tour_id' => $tour->tour_id,
                    'departure_date' => $departureDate->format('Y-m-d'),
                    'price' => round($price, -3), // Làm tròn đến nghìn
                    'max_capacity' => $maxCapacity,
                    'booked_count' => rand(0, min(10, $maxCapacity)), // Một số đã được đặt
                    'status' => $this->getRandomStatus(),
                    'notes' => $this->getRandomNotes(),
                    'is_deleted' => 'active',
                ]);
            }
        }
    }

    private function getSeasonalMultiplier($month)
    {
        // Mùa cao điểm: tháng 6-8 (hè), tháng 12-1 (đông)
        if (in_array($month, [6, 7, 8, 12, 1])) {
            return rand(110, 130) / 100; // Tăng 10-30%
        }
        
        // Mùa thấp điểm: tháng 2-5, 9-11
        return rand(90, 110) / 100; // Giảm 0-10% hoặc tăng 0-10%
    }

    private function getRandomStatus()
    {
        $statuses = ['available', 'available', 'available', 'full', 'cancelled'];
        return $statuses[array_rand($statuses)];
    }

    private function getRandomNotes()
    {
        $notes = [
            'Chuyến đi đặc biệt',
            'Có hướng dẫn viên tiếng Anh',
            'Khởi hành sớm',
            'Phù hợp cho gia đình',
            'Có ưu đãi đặc biệt',
            'Chuyến đi cuối tuần',
            'Có thể thay đổi lịch trình',
            'Điểm đến mới',
            'Có chương trình khuyến mãi',
            'Phù hợp cho nhóm lớn'
        ];
        
        return rand(0, 1) ? $notes[array_rand($notes)] : null;
    }
}
