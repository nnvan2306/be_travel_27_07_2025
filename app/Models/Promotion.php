<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'promotion_id';

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'max_uses',
        'current_uses',
        'is_active',
        'description'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'discount_value' => 'float',
        'max_uses' => 'integer',
        'current_uses' => 'integer',
    ];

    public function isExpired()
    {
        return now()->gt($this->end_date);
    }

    public function isActive()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->max_uses !== null && $this->current_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function calculateDiscount($orderTotal)
    {
        if (!$this->isActive()) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            return ($orderTotal * $this->discount_value) / 100;
        } else {
            return min($this->discount_value, $orderTotal);
        }
    }

    public function incrementUsage()
    {
        $this->current_uses += 1;
        $this->save();
    }
}
