<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hotel extends Model
{
    use HasFactory;

    protected $table = 'hotels';
    protected $primaryKey = 'hotel_id';

    protected $fillable = [
        'name',
        'location',
        'room_type',
        'price',
        'description',
        'image',
        'album_id',
        'contact_phone',
        'contact_email',
        'average_rating',
        'is_available',
        'max_guests',
        'facilities',
        'bed_type',
        'is_deleted',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'is_available' => 'boolean',
        'max_guests' => 'integer',
    ];

    // Mối quan hệ: Hotel thuộc về 1 album
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