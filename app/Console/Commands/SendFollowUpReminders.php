<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendFollowUpReminders extends Command
{
    protected $signature = 'notifications:follow-ups';

    protected $description = 'Send follow-up reminders for recently completed orders';

    public function handle(NotificationService $notifications): int
    {
        $cutoff = Carbon::now()->subDays(3);

        $orders = Order::where('order_status', 'completed')
            ->whereNull('follow_up_sent_at')
            ->where('updated_at', '<=', $cutoff)
            ->with('customer')
            ->limit(50)
            ->get();

        foreach ($orders as $order) {
            $notifications->sendFollowUpReminder($order);
            $this->info("Follow-up reminder sent for order {$order->id}");
        }

        if ($orders->isEmpty()) {
            $this->info('No follow-ups to send.');
        }

        return self::SUCCESS;
    }
}

