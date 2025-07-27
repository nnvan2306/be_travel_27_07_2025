<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
  protected $table = 'ratings';
  protected $primaryKey = 'rating_id';
  public $incrementing = true;
  public $timestamps = true;

  protected $fillable = [
    'rateable_id',
    'rateable_type',
    'rating',
    'comment',
    'user_id',
  ];

  protected $casts = [
    'rating' => 'decimal:2',
    'user_id' => 'integer',
  ];

  public function rateable()
  {
    return $this->morphTo();
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id', 'id');
  }
}