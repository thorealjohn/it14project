<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendPromoNotifications extends Command
{
    protected $signature = 'notifications:promo {subject} {message}';

    protected $description = 'Send a promo notification to customers with emails';

    public function handle(NotificationService $notifications): int
    {
        $subject = $this->argument('subject');
        $message = $this->argument('message');

        $emails = Customer::whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Fallback to owner email when no customers have email
        if (empty($emails)) {
            $emails = [config('mail.from.address')];
        }

        $notifications->sendPromo($subject, $message, $emails);

        $this->info('Promo notifications dispatched to ' . count($emails) . ' recipients.');

        return self::SUCCESS;
    }
}

