<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    protected $primaryKey = 'favorite_id';
    
    protected $fillable = [
        'user_id',
        'tour_id',
        'is_deleted'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'is_deleted' => 'string'
    ];

    // Không có updated_at trong migration
    public $timestamps = false;

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('is_deleted', 'inactive');
    }
}