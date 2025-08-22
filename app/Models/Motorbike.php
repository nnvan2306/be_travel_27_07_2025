<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Motorbike extends Model
{
    protected $table = 'motorbikes';
    protected $primaryKey = 'motorbike_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'bike_type',
        'total_quantity',
        'price_per_day',
        'location',
        'license_plate',
        'description',
        'average_rating',
        'total_reviews',
        'rental_status',
        'album_id',
        'is_deleted',
    ];

    protected $casts = [
        'price_per_day' => 'float',
        'average_rating' => 'float',
        'total_reviews' => 'integer',
        'total_quantity' => 'integer',
    ];

    // Quan hệ với Album (nếu có bảng albums)
    public function album()
    {
        return $this->belongsTo(Album::class, 'album_id');
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