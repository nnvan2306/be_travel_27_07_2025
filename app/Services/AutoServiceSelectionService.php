<?php

namespace App\Services;

use App\Models\Guide;
use App\Models\Hotel;
use App\Models\BusRoute;
use App\Models\Motorbike;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AutoServiceSelectionService
{
    /**
     * Tự động chọn các dịch vụ phù hợp cho booking
     */
    public function autoSelectServices($tourId, $startDate, $endDate, $quantity, $guestCount = null)
    {
        $selectedServices = [];
        
        // Nếu không có guestCount, sử dụng quantity
        $guestCount = $guestCount ?? $quantity;
        
        // 1. Tự động chọn hướng dẫn viên nếu số khách >= 10
        if ($guestCount >= 10) {
            $guide = $this->selectBestGuide($startDate, $endDate);
            if ($guide) {
                $selectedServices['guide'] = $guide;
            }
        }
        
        // 2. Tự động chọn khách sạn nếu tour kéo dài > 1 ngày
        $tourDuration = $this->calculateTourDuration($startDate, $endDate);
        if ($tourDuration > 1) {
            $hotel = $this->selectBestHotel($startDate, $endDate, $guestCount);
            if ($hotel) {
                $selectedServices['hotel'] = $hotel;
            }
        }
        
        // 3. Tự động chọn xe khách nếu số khách >= 15
        if ($guestCount >= 15) {
            $busRoute = $this->selectBestBusRoute($startDate, $endDate, $guestCount);
            if ($busRoute) {
                $selectedServices['bus'] = $busRoute;
            }
        }
        
        // 4. Tự động chọn xe máy nếu số khách <= 5 và tour ngắn
        if ($guestCount <= 5 && $tourDuration <= 3) {
            // Chọn nhiều xe máy nếu cần
            $motorbikes = $this->selectBestMotorbikes($startDate, $endDate, $guestCount);
            if (!empty($motorbikes)) {
                $selectedServices['motorbike'] = $motorbikes;
            }
        }
        
        return $selectedServices;
    }
    
    /**
     * Chọn hướng dẫn viên tốt nhất
     */
    private function selectBestGuide($startDate, $endDate)
    {
        return Guide::where('is_deleted', 'active')
            ->where('is_available', true)
            ->where('price_per_day', '>', 0)
            ->whereNotExists(function ($query) use ($startDate, $endDate) {
                $query->select(DB::raw(1))
                    ->from('bookings')
                    ->whereColumn('bookings.guide_id', 'guides.guide_id')
                    ->where('bookings.status', '!=', 'cancelled')
                    ->where(function ($subQ) use ($startDate, $endDate) {
                        $subQ->where('start_date', '<=', $endDate)
                             ->where('end_date', '>=', $startDate);
                    });
            })
            ->orderBy('average_rating', 'desc')
            ->orderBy('price_per_day', 'asc')
            ->first();
    }
    
    /**
     * Chọn khách sạn tốt nhất
     */
    private function selectBestHotel($startDate, $endDate, $guestCount)
    {
        return Hotel::where('is_deleted', 'active')
            ->where('is_available', true)
            ->where('price', '>', 0)
            ->where('max_guests', '>=', $guestCount)
            ->whereNotExists(function ($query) use ($startDate, $endDate) {
                $query->select(DB::raw(1))
                    ->from('bookings')
                    ->whereColumn('bookings.hotel_id', 'hotels.hotel_id')
                    ->where('bookings.status', '!=', 'cancelled')
                    ->where(function ($subQ) use ($startDate, $endDate) {
                        $subQ->where('start_date', '<=', $endDate)
                             ->where('end_date', '>=', $startDate);
                    });
            })
            ->orderBy('average_rating', 'desc')
            ->orderBy('price', 'asc')
            ->first();
    }
    
    /**
     * Chọn tuyến xe khách tốt nhất
     */
    private function selectBestBusRoute($startDate, $endDate, $guestCount)
    {
        return BusRoute::where('is_deleted', 'active')
            ->where('rental_status', 'available')
            ->where('price', '>', 0)
            ->where(function ($query) use ($guestCount) {
                $query->where('total_seats', '>=', $guestCount)
                      ->orWhere('seats', '>=', $guestCount);
            })
            ->whereNotExists(function ($query) use ($startDate, $endDate, $guestCount) {
                $query->select(DB::raw(1))
                    ->from('bookings')
                    ->whereColumn('bookings.bus_route_id', 'bus_routes.bus_route_id')
                    ->where('bookings.status', '!=', 'cancelled')
                    ->where(function ($subQ) use ($startDate, $endDate) {
                        $subQ->where('start_date', '<=', $endDate)
                             ->where('end_date', '>=', $startDate);
                    });
            })
            ->orderBy('price', 'asc')
            ->orderBy('total_seats', 'desc')
            ->orderBy('seats', 'desc')
            ->first();
    }
    
    /**
     * Chọn xe máy tốt nhất
     */
    private function selectBestMotorbike($startDate, $endDate, $guestCount)
    {
        return Motorbike::where('is_deleted', 'active')
            ->where('rental_status', 'available')
            ->where('price_per_day', '>', 0)
            ->whereNotExists(function ($query) use ($startDate, $endDate, $guestCount) {
                $query->select(DB::raw(1))
                    ->from('bookings')
                    ->whereColumn('bookings.motorbike_id', 'motorbikes.motorbike_id')
                    ->where('bookings.status', '!=', 'cancelled')
                    ->where(function ($subQ) use ($startDate, $endDate) {
                        $subQ->where('start_date', '<=', $endDate)
                             ->where('end_date', '>=', $startDate);
                    });
            })
            ->orderBy('price_per_day', 'asc')
            ->orderBy('average_rating', 'desc')
            ->first();
    }

    /**
     * Chọn nhiều xe máy phù hợp với số lượng khách
     */
    private function selectBestMotorbikes($startDate, $endDate, $guestCount)
    {
        return Motorbike::where('is_deleted', 'active')
            ->where('rental_status', 'available')
            ->where('price_per_day', '>', 0)
            ->where('total_quantity', '>=', 1)
            ->whereNotExists(function ($query) use ($startDate, $endDate) {
                $query->select(DB::raw(1))
                    ->from('bookings')
                    ->whereColumn('bookings.motorbike_id', 'motorbikes.motorbike_id')
                    ->where('bookings.status', '!=', 'cancelled')
                    ->where(function ($subQ) use ($startDate, $endDate) {
                        $subQ->where('start_date', '<=', $endDate)
                             ->where('end_date', '>=', $startDate);
                    });
            })
            ->orderBy('price_per_day', 'asc')
            ->orderBy('average_rating', 'desc')
            ->limit($guestCount)
            ->get();
    }
    
    /**
     * Tính toán thời gian tour
     */
    private function calculateTourDuration($startDate, $endDate)
    {
        if (!$endDate) {
            return 1;
        }
        
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        return $start->diffInDays($end) + 1;
    }
    
    /**
     * Kiểm tra xem dịch vụ có khả dụng không
     */
    public function checkServiceAvailability($serviceType, $serviceId, $startDate, $endDate, $quantity)
    {
        switch ($serviceType) {
            case 'guide':
                return $this->checkGuideAvailability($serviceId, $startDate, $endDate);
            case 'hotel':
                return $this->checkHotelAvailability($serviceId, $startDate, $endDate, $quantity);
            case 'bus':
                return $this->checkBusRouteAvailability($serviceId, $startDate, $endDate, $quantity);
            case 'motorbike':
                return $this->checkMotorbikeAvailability($serviceId, $startDate, $endDate, $quantity);
            default:
                return false;
        }
    }
    
    private function checkGuideAvailability($guideId, $startDate, $endDate)
    {
        return Guide::where('guide_id', $guideId)
            ->where('is_deleted', 'active')
            ->where('is_available', true)
            ->whereNotExists(function ($query) use ($startDate, $endDate) {
                $query->select(DB::raw(1))
                    ->from('bookings')
                    ->whereColumn('bookings.guide_id', 'guides.guide_id')
                    ->where('bookings.status', '!=', 'cancelled')
                    ->where(function ($subQ) use ($startDate, $endDate) {
                        $subQ->where('start_date', '<=', $endDate)
                             ->where('end_date', '>=', $startDate);
                    });
            })
            ->exists();
    }
    
    private function checkHotelAvailability($hotelId, $startDate, $endDate, $quantity)
    {
        return Hotel::where('hotel_id', $hotelId)
            ->where('is_deleted', 'active')
            ->where('is_available', true)
            ->where('max_guests', '>=', $quantity)
            ->whereNotExists(function ($query) use ($startDate, $endDate) {
                $query->select(DB::raw(1))
                    ->from('bookings')
                    ->whereColumn('bookings.hotel_id', 'hotels.hotel_id')
                    ->where('bookings.status', '!=', 'cancelled')
                    ->where(function ($subQ) use ($startDate, $endDate) {
                        $subQ->where('start_date', '<=', $endDate)
                             ->where('end_date', '>=', $startDate);
                    });
            })
            ->exists();
    }
    
    private function checkBusRouteAvailability($busRouteId, $startDate, $endDate, $quantity)
    {
        $busRoute = BusRoute::find($busRouteId);
        if (!$busRoute || $busRoute->is_deleted !== 'active') {
            return false;
        }
        
        $totalSeats = $busRoute->seats > 0 ? $busRoute->seats : 45; // Assuming default seats if not set
        if ($totalSeats <= 0) {
            $totalSeats = 45;
        }
        
        $bookedSeats = $this->getBookedBusSeats($busRouteId, $startDate, $endDate);
        $availableSeats = $totalSeats - $bookedSeats;
        
        return $quantity <= $availableSeats;
    }
    
    private function checkMotorbikeAvailability($motorbikeId, $startDate, $endDate, $quantity)
    {
        $motorbike = Motorbike::find($motorbikeId);
        if (!$motorbike || $motorbike->is_deleted !== 'active') {
            return false;
        }
        
        // Kiểm tra xem xe máy có bị conflict lịch không
        $bookedQuantity = $this->getBookedMotorbikeQuantity($motorbikeId, $startDate, $endDate);
        
        // Sử dụng total_quantity từ database
        $availableQuantity = $motorbike->total_quantity - $bookedQuantity;
        
        return $quantity <= $availableQuantity;
    }
    
    private function getBookedBusSeats($busRouteId, $startDate, $endDate)
    {
        return DB::table('bookings')
            ->where('bus_route_id', $busRouteId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                     ->where('end_date', '>=', $startDate);
            })
            ->sum('quantity');
    }
    
    private function getBookedMotorbikeQuantity($motorbikeId, $startDate, $endDate)
    {
        return DB::table('bookings')
            ->where('motorbike_id', $motorbikeId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                     ->where('end_date', '>=', $startDate);
            })
            ->sum('quantity');
    }
}
