<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomTourSchedule extends Model
{
    use SoftDeletes;

    protected $table = 'custom_tour_schedules';
    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'custom_tour_id',
        'day',
        'start_time',
        'end_time',
        'title',
        'activity_description',
        'destination_id',
        'guide_id',
        'bus_route_id',
        'is_deleted',
    ];

    protected $casts = [
        'day' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_deleted' => 'string',
    ];

    // Quan hệ với CustomTour
    public function customTour()
    {
        return $this->belongsTo(CustomTour::class, 'custom_tour_id', 'custom_tour_id');
    }

    // Quan hệ với Destination
    public function destination()
    {
        return $this->belongsTo(Destination::class, 'destination_id', 'destination_id');
    }

    // Quan hệ với Guide
    public function guide()
    {
        return $this->belongsTo(Guide::class, 'guide_id', 'guide_id');
    }

    // Quan hệ với BusRoute
    public function busRoute()
    {
        return $this->belongsTo(BusRoute::class, 'bus_route_id', 'bus_route_id');
    }
}