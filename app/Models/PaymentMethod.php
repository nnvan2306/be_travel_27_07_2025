<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'description',
        'is_deleted'
    ];

    protected $casts = [
        'is_deleted' => 'string'
    ];

    // Không có timestamps trong migration
    public $timestamps = false;

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('is_deleted', 'inactive');
    }

    // Relationships (nếu cần)
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'payment_method_id');
    }
}