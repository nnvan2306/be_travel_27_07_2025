<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Destination extends Model
{
    use HasFactory;

    protected $table = 'destinations';
    protected $primaryKey = 'destination_id';

    protected $fillable = [
        'name',
        'album_id',
        'category_id',
        'description',
        'area',
        'img_banner',
        'is_deleted',
        'price',
        'slug',
    ];

    protected $appends = ['img_banner_url'];

    /**
     * Accessor: Trả về URL ảnh banner đầy đủ
     */
    public function getImgBannerUrlAttribute()
    {
        return $this->img_banner ? asset('storage/' . $this->img_banner) : null;
    }

    /**
     * Scope: Lọc theo active
     */
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 'active');
    }

    /**
     * Quan hệ: Một điểm đến có nhiều section
     */
    public function sections()
    {
        return $this->hasMany(DestinationSection::class, 'destination_id', 'destination_id');
    }
    public function tours()
    {
        return $this->belongsToMany(Tour::class, 'tour_destinations', 'destination_id', 'tour_id');
    }
    public function album()
    {
        return $this->belongsTo(Album::class, 'album_id', 'album_id');
    }
    public function category()
    {
        return $this->belongsTo(DestinationCategory::class, 'category_id', 'category_id');
    }
    public function customTourDetails()
    {
        return $this->hasMany(CustomTourDetail::class, 'destination_id', 'destination_id');
    }

    // Quan hệ với CustomTourSchedule
    public function customTourSchedules()
    {
        return $this->hasMany(CustomTourSchedule::class, 'destination_id', 'destination_id');
    }

}