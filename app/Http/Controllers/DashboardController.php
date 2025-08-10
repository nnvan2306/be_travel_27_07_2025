<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Promotion;
use App\Models\Tour;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Lấy các chỉ số tổng quan
     */
    public function metrics()
    {
        $currentMonth = Carbon::now()->month;
        $previousMonth = Carbon::now()->subMonth()->month;
        $currentYear = Carbon::now()->year;

        // Đếm số tài khoản
        $accountsThisMonth = User::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $accountsLastMonth = User::whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $accountsIncrease = $accountsLastMonth > 0
            ? round(($accountsThisMonth - $accountsLastMonth) / $accountsLastMonth * 100)
            : 0;

        // Đếm số booking
        $bookingsThisMonth = Booking::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $bookingsLastMonth = Booking::whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $bookingsIncrease = $bookingsLastMonth > 0
            ? round(($bookingsThisMonth - $bookingsLastMonth) / $bookingsLastMonth * 100)
            : 0;

        // Tính doanh thu
        $revenueThisMonth = Booking::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('total_price');
        $revenueLastMonth = Booking::whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('total_price');
        $revenueIncrease = $revenueLastMonth > 0
            ? round(($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth * 100)
            : 0;

        // Đếm số tours
        // Sửa: Bỏ is_active vì không tồn tại, đếm tất cả tour
        $toursCount = Tour::count();
        $toursLastMonth = Tour::whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $toursThisMonth = Tour::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $toursIncrease = $toursLastMonth > 0
            ? round(($toursThisMonth - $toursLastMonth) / $toursLastMonth * 100)
            : 0;

        $data = [
            [
                "key" => "account",
                "title" => "Tài khoản",
                "value" => User::count(),
                "increaseLabel" => "Tăng {$accountsIncrease}% so với tháng trước",
            ],
            [
                "key" => "booking",
                "title" => "Đơn đặt",
                "value" => Booking::count(),
                "increaseLabel" => "Tăng {$bookingsIncrease}% so với tháng trước",
            ],
            [
                "key" => "revenue",
                "title" => "Doanh thu",
                "value" => Booking::sum('total_price'),
                "increaseLabel" => "Tăng {$revenueIncrease}% so với tháng trước",
            ],
            [
                "key" => "tour",
                "title" => "Tours",
                "value" => $toursCount,
                "increaseLabel" => "Tăng {$toursIncrease}% so với tháng trước",
            ]
        ];

        return response()->json($data);
    }

    /**
     * Lấy doanh thu theo tuần
     */
    public function weeklyRevenue()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $data = [];

        for ($day = $startOfWeek; $day->lte($endOfWeek); $day->addDay()) {
            $date = $day->format('d/m');

            $revenue = Booking::whereDate('created_at', $day->format('Y-m-d'))
                ->sum('total_price');

            $data[] = [
                'name' => $date,
                'revenue' => $revenue
            ];
        }

        return response()->json($data);
    }

    /**
     * Lấy xu hướng booking theo tháng
     */
    public function bookingTrend()
    {
        $data = [];
        $year = Carbon::now()->year;

        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month, 1)->format('M Y');

            $bookings = Booking::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();

            $data[] = [
                'name' => $monthName,
                'bookings' => $bookings
            ];
        }

        return response()->json($data);
    }

    /**
     * Lấy booking theo loại
     */
    public function bookingByType()
    {
        $data = [
            ['name' => 'Tour', 'value' => Booking::whereNotNull('tour_id')->count()],
            ['name' => 'Custom Tour', 'value' => Booking::whereNotNull('custom_tour_id')->count()],
            ['name' => 'Hotel', 'value' => Booking::whereNotNull('hotel_id')->count()],
            ['name' => 'Guide', 'value' => Booking::whereNotNull('guide_id')->count()],
            ['name' => 'Bus', 'value' => Booking::whereNotNull('bus_route_id')->count()],
            ['name' => 'Motorbike', 'value' => Booking::whereNotNull('motorbike_id')->count()],
        ];

        return response()->json($data);
    }

    /**
     * Lấy hoạt động gần đây
     */
    public function recentActivities()
    {
        $bookings = Booking::select(
            'booking_id as id',
            DB::raw("CONCAT('Booking #', booking_id, ' đã được đặt') as title"),
            DB::raw("'booking' as type"),
            'created_at',
            'status'
        )
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Format created_at to make it readable
        $bookings->transform(function ($item) {
            $item->created_at = Carbon::parse($item->created_at)->format('Y-m-d H:i');
            return $item;
        });

        return response()->json($bookings);
    }

    /**
     * Lấy danh sách khuyến mãi
     */
    public function promotions()
    {
        // Sửa: Bỏ orderBy theo created_at vì cột không tồn tại trong bảng
        $promotions = Promotion::select(
            'code',
            'discount',
            'max_uses',
            'used_count',
            DB::raw('DATE(valid_from) as valid_from'),
            DB::raw('DATE(valid_to) as valid_to'),
            DB::raw("CASE
                    WHEN NOW() < valid_from THEN 'Upcoming'
                    WHEN NOW() > valid_to THEN 'Expired'
                    WHEN used_count >= max_uses THEN 'Exhausted'
                    ELSE 'Active'
                END as status")
        )
            // Sắp xếp theo valid_from thay vì created_at
            ->orderBy('valid_from', 'desc')
            ->limit(10)
            ->get();

        return response()->json($promotions);
    }

    /**
     * Lấy dữ liệu kết hợp (doanh thu và booking theo tháng)
     */
    public function combinedData()
    {
        $data = [];
        $year = Carbon::now()->year;

        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month, 1)->format('M Y');

            $bookings = Booking::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();

            $revenue = Booking::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('total_price');

            $data[] = [
                'name' => $monthName,
                'bookings' => $bookings,
                'revenue' => $revenue
            ];
        }

        return response()->json($data);
    }
}
