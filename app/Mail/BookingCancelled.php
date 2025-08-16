<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $reason;

    public function __construct(Booking $booking, $reason = null)
    {
        $this->booking = $booking;
        $this->reason = $reason;
    }

    public function build()
    {
        return $this->subject('Thông báo hủy đơn đặt tour - VTravel')
            ->view('emails.booking.cancelled');
    }
}
