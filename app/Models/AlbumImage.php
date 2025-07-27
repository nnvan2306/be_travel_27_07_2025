<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlbumImage extends Model
{
    protected $table = 'album_images';
    protected $primaryKey = 'image_id';

    public $timestamps = false;

    protected $fillable = [
        'album_id',
        'image_url',
        'caption',
        'is_deleted'
    ];

    protected $attributes = [
        'is_deleted' => 'active'
    ];

    public function album()
    {
        return $this->belongsTo(Album::class, 'album_id', 'album_id');
    }

    public function getImageUrlFullAttribute()
    {
        return $this->image_url ? asset('storage/' . $this->image_url) : null;
    }
}