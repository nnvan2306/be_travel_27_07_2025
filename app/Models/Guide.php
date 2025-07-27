<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guide extends Model
{
    use HasFactory;

    protected $primaryKey = 'guide_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $table = 'guides';

    protected $fillable = [
        'name',
        'gender',
        'language',
        'experience_years',
        'album_id',
        'price_per_day',
        'description',
        'phone',
        'email',
        'average_rating',
        'is_available',
        'is_deleted',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'average_rating' => 'float',
        'price_per_day' => 'decimal:2',
    ];

    /**
     * Liên kết đến Album (1 hướng dẫn viên thuộc 1 album)
     */
    public function album()
    {
        return $this->belongsTo(Album::class, 'album_id', 'album_id');
    }

    /**
     * Liên kết đến các lịch trình tour mà hướng dẫn viên tham gia
     */
    public function tourSchedules()
    {
        return $this->hasMany(TourSchedule::class, 'guide_id', 'guide_id');
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