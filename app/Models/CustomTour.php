<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomTour extends Model
{
    use HasFactory;

    protected $primaryKey = 'custom_tour_id';

    protected $fillable = [
        'user_id',
        'destination',
        'start_date',
        'end_date',
        'num_people',
        'note',
        'status',
        'is_deleted'
    ];

    // Quan hệ với User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Quan hệ với CustomTourDetail
    public function details()
    {
        return $this->hasMany(CustomTourDetail::class, 'custom_tour_id', 'custom_tour_id');
    }

    // Quan hệ với CustomTourSchedule (nếu có)
    public function schedules()
    {
        return $this->hasMany(CustomTourSchedule::class, 'custom_tour_id', 'custom_tour_id');
    }
}