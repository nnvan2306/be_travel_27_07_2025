<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'user_id',
        'tour_id',
        'guide_id',
        'hotel_id',
        'bus_route_id',
        'motorbike_id',
        'custom_tour_id',
        'quantity',
        'start_date',
        'end_date',
        'total_price',
        'payment_method',
        'status',
        'cancel_reason',
        'is_deleted',
        'promotion_id',
        'discount_amount',
        'final_amount'
    ];

    // Nếu dùng enum casting (Laravel 9+)
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_price' => 'decimal:2',
        'status' => 'string',
        'payment_method' => 'string',
        'is_deleted' => 'string',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class, 'tour_id', 'tour_id');
    }

    public function guide()
    {
        return $this->belongsTo(Guide::class, 'guide_id', 'guide_id');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id', 'hotel_id');
    }

    public function busRoute()
    {
        return $this->belongsTo(BusRoute::class, 'bus_route_id', 'bus_route_id');
    }

    public function motorbike()
    {
        return $this->belongsTo(Motorbike::class, 'motorbike_id', 'motorbike_id');
    }

    public function customTour()
    {
        return $this->belongsTo(CustomTour::class, 'custom_tour_id', 'custom_tour_id');
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    // Scope filter active bookings
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('is_deleted', 'inactive');
    }

    // Cập nhật phương thức tính toán giá cuối cùng
    public function calculateFinalAmount()
    {
        $this->final_amount = $this->total_amount - $this->discount_amount;
        return $this->final_amount;
    }
}
