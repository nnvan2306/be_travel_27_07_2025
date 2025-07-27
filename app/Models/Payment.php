<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
  protected $table = 'payments';

  protected $primaryKey = 'id';

  protected $fillable = [
    'booking_id',
    'payment_method_id',
    'amount',
    'status',
    'transaction_code',
    'paid_at',
    'is_deleted',
  ];

  protected $casts = [
    'paid_at' => 'datetime',
  ];

  /**
   * Booking liên kết với thanh toán.
   */
  public function booking(): BelongsTo
  {
    return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
  }

  /**
   * Phương thức thanh toán liên kết.
   */
  public function paymentMethod(): BelongsTo
  {
    return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
  }

  /**
   * Kiểm tra thanh toán đã hoàn thành chưa.
   */
  public function isCompleted(): bool
  {
    return $this->status === 'completed';
  }
}