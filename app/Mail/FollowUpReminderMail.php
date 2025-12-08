<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FollowUpReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function build()
    {
        return $this->subject('How was your AQUASTAR delivery?')
            ->view('emails.follow_up_reminder')
            ->with([
                'order' => $this->order,
            ]);
    }
}

