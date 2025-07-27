<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    protected $primaryKey = 'tour_id';
    protected $fillable = [
        'category_id',
        'album_id',
        'tour_name',
        'description',
        'itinerary',
        'image',
        'price',
        'discount_price',
        'duration',
        'status',
        'is_deleted',
        'slug',
    ];

    public function category()
    {
        return $this->belongsTo(TourCategory::class, 'category_id');
    }

    public function album()
    {
        return $this->belongsTo(Album::class, 'album_id', 'album_id');
    }
    public function destinations()
    {
        return $this->belongsToMany(Destination::class, 'tour_destinations', 'tour_id', 'destination_id');
    }
    public function schedules()
    {
        return $this->hasMany(TourSchedule::class, 'tour_id', 'tour_id');
    }
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'tour_id');
    }
    public function ratings()
    {
        return $this->morphMany(Rating::class, 'rateable');
    }
    public function getAverageRatingAttribute()
    {
        return $this->ratings()->avg('rating') ?? 0;
    }

}