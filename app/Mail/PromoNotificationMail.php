<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PromoNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $subjectLine, public string $messageBody)
    {
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.promo_notification')
            ->with([
                'subjectLine' => $this->subjectLine,
                'messageBody' => $this->messageBody,
            ]);
    }
}

