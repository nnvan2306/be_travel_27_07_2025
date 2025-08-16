<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'full_name',
        'email',
        'phone',
        'message',
        'status',
        'is_deleted'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_deleted', 'active');
    }
}
