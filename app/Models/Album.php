<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    protected $primaryKey = 'album_id';

    protected $fillable = [
        'title',
        'is_deleted'
    ];

    protected $attributes = [
        'is_deleted' => 'active'
    ];

    public function images()
    {
        return $this->hasMany(AlbumImage::class, 'album_id', 'album_id')
            ->where('is_deleted', 'active');
    }

    public function allImages()
    {
        return $this->hasMany(AlbumImage::class, 'album_id', 'album_id');
    }
}