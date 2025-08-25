<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TourDeparture extends Model
{
    use HasFactory;

    protected $primaryKey = 'departure_id';

    protected $fillable = [
        'tour_id',
        'departure_date',
        'price',
        'max_capacity',
        'booked_count',
        'status',
        'notes',
        'is_deleted',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'price' => 'decimal:2',
        'max_capacity' => 'integer',
        'booked_count' => 'integer',
    ];

    /**
     * Quan hệ: TourDeparture thuộc về một Tour
     */
    public function tour()
    {
        return $this->belongsTo(Tour::class, 'tour_id', 'tour_id');
    }

    /**
     * Quan hệ: TourDeparture có nhiều Booking
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'departure_id', 'departure_id');
    }

    /**
     * Scope: Lọc các departure đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 'active');
    }

    /**
     * Scope: Lọc các departure có sẵn
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope: Lọc theo tháng và năm
     */
    public function scopeByMonthYear($query, $month, $year)
    {
        return $query->whereYear('departure_date', $year)
                    ->whereMonth('departure_date', $month);
    }

    /**
     * Scope: Lọc theo khoảng thời gian
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('departure_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Chỉ lấy các departure trong tương lai
     */
    public function scopeFuture($query)
    {
        return $query->where('departure_date', '>=', Carbon::today());
    }

    /**
     * Tính số chỗ còn trống
     */
    public function getAvailableSeatsAttribute()
    {
        return $this->max_capacity - $this->booked_count;
    }

    /**
     * Kiểm tra xem còn chỗ không
     */
    public function hasAvailableSeats($quantity = 1)
    {
        return $this->available_seats >= $quantity;
    }

    /**
     * Cập nhật số lượng đã đặt
     */
    public function updateBookedCount($quantity = 1, $operation = 'add')
    {
        if ($operation === 'add') {
            $this->increment('booked_count', $quantity);
        } else {
            $this->decrement('booked_count', $quantity);
        }

        // Cập nhật status nếu cần
        if ($this->booked_count >= $this->max_capacity) {
            $this->update(['status' => 'full']);
        } elseif ($this->status === 'full' && $this->booked_count < $this->max_capacity) {
            $this->update(['status' => 'available']);
        }
    }

    /**
     * Format ngày khởi hành
     */
    public function getFormattedDepartureDateAttribute()
    {
        return $this->departure_date->format('d/m/Y');
    }

    /**
     * Format giá
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 0, ',', '.') . ' ₫';
    }
}
