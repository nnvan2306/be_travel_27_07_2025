<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BusRoute;
use App\Models\Motorbike;
use App\Models\Tour;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingValidationService
{
    /**
     * Kiểm tra availability của xe khách
     */
    public function checkBusRouteAvailability($busRouteId, $startDate, $endDate, $quantity = 1, $excludeBookingId = null)
    {
        $busRoute = BusRoute::find($busRouteId);
        if (!$busRoute || $busRoute->is_deleted !== 'active') {
            return [
                'available' => false,
                'message' => 'Xe khách không tồn tại hoặc đã bị vô hiệu hóa'
            ];
        }

        // Lấy tổng số ghế đã được đặt trong khoảng thời gian
        $bookedSeats = $this->getBookedBusSeats($busRouteId, $startDate, $endDate, $excludeBookingId);
        
        // Tính số ghế còn trống
        $availableSeats = $busRoute->total_seats - $bookedSeats;
        
        if ($quantity > $availableSeats) {
            return [
                'available' => false,
                'message' => "Chỉ còn {$availableSeats} ghế trống cho xe khách này trong khoảng thời gian đã chọn",
                'available_seats' => $availableSeats,
                'requested_quantity' => $quantity
            ];
        }

        return [
            'available' => true,
            'message' => 'Xe khách có sẵn',
            'available_seats' => $availableSeats,
            'requested_quantity' => $quantity
        ];
    }

    /**
     * Kiểm tra availability của xe máy
     */
    public function checkMotorbikeAvailability($motorbikeId, $startDate, $endDate, $quantity = 1, $excludeBookingId = null)
    {
        $motorbike = Motorbike::find($motorbikeId);
        if (!$motorbike || $motorbike->is_deleted !== 'active') {
            return [
                'available' => false,
                'message' => 'Xe máy không tồn tại hoặc đã bị vô hiệu hóa'
            ];
        }

        // Lấy tổng số xe máy đã được đặt trong khoảng thời gian
        $bookedQuantity = $this->getBookedMotorbikeQuantity($motorbikeId, $startDate, $endDate, $excludeBookingId);
        
        // Tính số xe máy còn trống
        $availableQuantity = $motorbike->total_quantity - $bookedQuantity;
        
        if ($quantity > $availableQuantity) {
            return [
                'available' => false,
                'message' => "Chỉ còn {$availableQuantity} xe máy trống trong khoảng thời gian đã chọn",
                'available_quantity' => $availableQuantity,
                'requested_quantity' => $quantity
            ];
        }

        return [
            'available' => true,
            'message' => 'Xe máy có sẵn',
            'available_quantity' => $availableQuantity,
            'requested_quantity' => $quantity
        ];
    }

    /**
     * Lấy số ghế xe khách đã được đặt trong khoảng thời gian
     */
    private function getBookedBusSeats($busRouteId, $startDate, $endDate, $excludeBookingId = null)
    {
        $query = Booking::where('bus_route_id', $busRouteId)
            ->where('is_deleted', 'active')
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($subQ) use ($startDate, $endDate) {
                    // Booking bắt đầu trong khoảng thời gian
                    $subQ->where('start_date', '>=', $startDate)
                         ->where('start_date', '<=', $endDate);
                })->orWhere(function ($subQ) use ($startDate, $endDate) {
                    // Booking kết thúc trong khoảng thời gian
                    $subQ->where('end_date', '>=', $startDate)
                         ->where('end_date', '<=', $endDate);
                })->orWhere(function ($subQ) use ($startDate, $endDate) {
                    // Booking bao trọn khoảng thời gian
                    $subQ->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                });
            });

        if ($excludeBookingId) {
            $query->where('booking_id', '!=', $excludeBookingId);
        }

        return $query->sum('service_quantity');
    }

    /**
     * Lấy số xe máy đã được đặt trong khoảng thời gian
     */
    private function getBookedMotorbikeQuantity($motorbikeId, $startDate, $endDate, $excludeBookingId = null)
    {
        $query = Booking::where('motorbike_id', $motorbikeId)
            ->where('is_deleted', 'active')
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($subQ) use ($startDate, $endDate) {
                    // Booking bắt đầu trong khoảng thời gian
                    $subQ->where('start_date', '>=', $startDate)
                         ->where('start_date', '<=', $endDate);
                })->orWhere(function ($subQ) use ($startDate, $endDate) {
                    // Booking kết thúc trong khoảng thời gian
                    $subQ->where('end_date', '>=', $startDate)
                         ->where('end_date', '<=', $endDate);
                })->orWhere(function ($subQ) use ($startDate, $endDate) {
                    // Booking bao trọn khoảng thời gian
                    $subQ->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                });
            });

        if ($excludeBookingId) {
            $query->where('booking_id', '!=', $excludeBookingId);
        }

        return $query->sum('service_quantity');
    }

    /**
     * Kiểm tra availability của tất cả dịch vụ trong một booking
     */
    public function validateBookingServices($bookingData)
    {
        $errors = [];
        $startDate = $bookingData['start_date'];
        $endDate = $bookingData['end_date'] ?? $startDate;
        $quantity = $bookingData['quantity'] ?? 1;

        // Kiểm tra xe khách
        if (!empty($bookingData['bus_route_id'])) {
            $busValidation = $this->checkBusRouteAvailability(
                $bookingData['bus_route_id'],
                $startDate,
                $endDate,
                $quantity,
                $bookingData['booking_id'] ?? null
            );
            
            if (!$busValidation['available']) {
                $errors['bus_route'] = $busValidation['message'];
            }
        }

        // Kiểm tra xe máy
        if (!empty($bookingData['motorbike_id'])) {
            $motorbikeValidation = $this->checkMotorbikeAvailability(
                $bookingData['motorbike_id'],
                $startDate,
                $endDate,
                $quantity,
                $bookingData['booking_id'] ?? null
            );
            
            if (!$motorbikeValidation['available']) {
                $errors['motorbike'] = $motorbikeValidation['message'];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Tính toán ngày kết thúc dựa trên tour duration
     */
    public function calculateEndDate($startDate, $tourId = null, $duration = null)
    {
        if ($duration) {
            return Carbon::parse($startDate)->addDays($duration)->format('Y-m-d');
        }

        if ($tourId) {
            $tour = Tour::find($tourId);
            if ($tour && $tour->duration) {
                // Parse duration string like "3 ngày 2 đêm" or "3 days"
                $durationDays = $this->parseDuration($tour->duration);
                return Carbon::parse($startDate)->addDays($durationDays)->format('Y-m-d');
            }
        }

        return $startDate;
    }

    /**
     * Parse duration string để lấy số ngày
     */
    private function parseDuration($duration)
    {
        // Extract number from strings like "3 ngày 2 đêm", "3 days", "3"
        preg_match('/(\d+)/', $duration, $matches);
        return isset($matches[1]) ? (int)$matches[1] : 1;
    }
}
