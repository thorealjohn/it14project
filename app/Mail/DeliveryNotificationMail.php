<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeliveryNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function build()
    {
        return $this->subject('Your AQUASTAR delivery update')
            ->view('emails.delivery_notification')
            ->with([
                'order' => $this->order,
            ]);
    }
}

