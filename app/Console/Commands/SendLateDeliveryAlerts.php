<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendLateDeliveryAlerts extends Command
{
    protected $signature = 'notifications:late-deliveries';

    protected $description = 'Send alerts for delivery orders that are running late';

    public function handle(NotificationService $notifications): int
    {
        $now = Carbon::now();

        $lateOrders = Order::where('is_delivery', true)
            ->where('order_status', 'pending')
            ->whereNotNull('delivery_date')
            ->where('delivery_date', '<', $now)
            ->whereNull('late_alert_sent_at')
            ->with('customer')
            ->limit(50)
            ->get();

        foreach ($lateOrders as $order) {
            $notifications->sendLateDeliveryAlert($order);
            $this->info("Late delivery alert sent for order {$order->id}");
        }

        if ($lateOrders->isEmpty()) {
            $this->info('No late deliveries to notify.');
        }

        return self::SUCCESS;
    }
}

