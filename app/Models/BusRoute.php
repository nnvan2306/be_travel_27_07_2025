<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusRoute extends Model
{
    protected $table = 'bus_routes';
    protected $primaryKey = 'bus_route_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'route_name',
        'vehicle_type',
        'price',
        'seats',
        'license_plate',
        'description',
        'rating',
        'rating_count',
        'rental_status',
        'album_id',
        'is_deleted',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'rating_count' => 'integer',
        'seats' => 'integer',
        'album_id' => 'integer',
    ];

    /**
     * Quan hệ: BusRoute thuộc về một Album
     */
    public function album()
    {
        return $this->belongsTo(Album::class, 'album_id', 'album_id');
    }

    /**
     * Scope: chỉ lấy các route đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 'active');
    }
    public function ratings()
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function getAverageRatingAttribute()
    {
        return $this->ratings()->avg('rating') ?? 0;
    }
    public function customTourDetails()
    {
        return $this->hasMany(CustomTourDetail::class, 'hotel_id', 'hotel_id');
    }
}