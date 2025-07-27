<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $table = 'promotions';
    protected $primaryKey = 'promotion_id';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'discount',
        'max_uses',
        'used_count',
        'valid_from',
        'valid_to',
        'applies_to',
        'is_deleted'
    ];

    protected $casts = [
        'discount' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'max_uses' => 'integer',
        'used_count' => 'integer'
    ];

    // Scope để lấy chỉ các promotion active
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 'active');
    }

    // Scope để lấy các promotion inactive
    public function scopeInactive($query)
    {
        return $query->where('is_deleted', 'inactive');
    }

    // Kiểm tra promotion còn hiệu lực
    public function isValid()
    {
        $today = now()->toDateString();
        return $this->is_deleted === 'active' 
            && $this->valid_from <= $today 
            && $this->valid_to >= $today
            && $this->used_count < $this->max_uses;
    }
}