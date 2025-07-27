<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transportation extends Model
{
  use SoftDeletes;

  protected $table = 'transportations';
  protected $primaryKey = 'transportation_id';

  protected $fillable = [
    'type',
    'name',
    'price',
    'album_id',
    'is_available',
    'capacity',
    'description',
    'is_deleted',
  ];

  protected $casts = [
    'price' => 'decimal:2',
    'is_available' => 'boolean',
    'is_deleted' => 'string',
  ];

  // Quan hệ với Album
  public function album()
  {
    return $this->belongsTo(Album::class, 'album_id', 'album_id');
  }

  // Quan hệ với CustomTourDetail
  public function customTourDetails()
  {
    return $this->hasMany(CustomTourDetail::class, 'transportation_id', 'transportation_id');
  }

}