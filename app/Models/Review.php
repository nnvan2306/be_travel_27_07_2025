<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $table = 'reviews';
    protected $primaryKey = 'review_id';
    public $timestamps = false; // Vì chỉ có created_at

    protected $fillable = [
        'user_id',
        'tour_id',
        'rating',
        'comment',
        'created_at',
        'is_deleted'
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'user_id' => 'integer',
        'tour_id' => 'integer'
    ];

    // Scope để lấy chỉ các review active
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 'active');
    }

    // Scope để lấy các review inactive
    public function scopeInactive($query)
    {
        return $query->where('is_deleted', 'inactive');
    }

    // Relationship với User - sửa foreign key và owner key
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id'); // user_id trong reviews -> id trong users
    }

    // Relationship với Tour
    public function tour()
    {
        return $this->belongsTo(Tour::class, 'tour_id', 'tour_id');
    }
}