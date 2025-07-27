<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourSchedule extends Model
{
    use HasFactory;

    protected $table = 'tour_schedules';
    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'tour_id',
        'day',
        'start_time',
        'end_time',
        'title',
        'activity_description',
        'destination_id',
        'guide_id',
        'bus_route_id',
    ];

    /**
     * Quan hệ: TourSchedule thuộc về một Tour
     */
    public function tour()
    {
        return $this->belongsTo(Tour::class, 'tour_id', 'tour_id');
    }

    /**
     * Quan hệ: Điểm đến liên quan (nếu có)
     */
    public function destination()
    {
        return $this->belongsTo(Destination::class, 'destination_id', 'destination_id');
    }

    /**
     * Quan hệ: Hướng dẫn viên phụ trách
     */
    public function guide()
    {
        return $this->belongsTo(Guide::class, 'guide_id', 'guide_id');
    }

    /**
     * Quan hệ: Tuyến xe buýt
     */
    public function busRoute()
    {
        return $this->belongsTo(BusRoute::class, 'bus_route_id', 'route_id');
    }
}