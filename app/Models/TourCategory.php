<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourCategory extends Model
{
    protected $primaryKey = 'category_id';
    public $incrementing = true;

    protected $fillable = [
        'category_name',
        'thumbnail',
        'is_deleted',
    ];
    public function tours()
    {
        return $this->hasMany(Tour::class, 'category_id');
    }
}