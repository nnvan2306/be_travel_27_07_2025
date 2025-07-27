<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomTourDetail extends Model
{
    use SoftDeletes;

    protected $table = 'custom_tour_detail';
    protected $primaryKey = 'custom_tour_detail_id';

    protected $fillable = [
        'custom_tour_id',
        'destination_id',
        'hotel_id',
        'transportation_id',
        'motorbike_id',
        'guide_id',
        'bus_route_id',
        'quantity',
        'price',
        'is_deleted',
    ];

    protected $casts = [
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

    // Quan hệ với Hotel
    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id', 'hotel_id');
    }

    // Quan hệ với Transportation
    public function transportation()
    {
        return $this->belongsTo(Transportation::class, 'transportation_id', 'transportation_id');
    }

    // Quan hệ với Motorbike
    public function motorbike()
    {
        return $this->belongsTo(Motorbike::class, 'motorbike_id', 'motorbike_id');
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