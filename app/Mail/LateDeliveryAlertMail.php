<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LateDeliveryAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function build()
    {
        return $this->subject('Late delivery alert')
            ->view('emails.late_delivery_alert')
            ->with([
                'order' => $this->order,
            ]);
    }
}

