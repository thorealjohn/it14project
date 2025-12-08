<?php

namespace App\Services;

use App\Mail\DeliveryNotificationMail;
use App\Mail\FollowUpReminderMail;
use App\Mail\LateDeliveryAlertMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\PromoNotificationMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class NotificationService
{
    /**
     * Resolve recipient email, defaulting to global from address.
     * Priority: order email > customer email > default
     */
    private function recipient(Order $order): ?string
    {
        return $order->email ?? $order->customer->email ?? null;
    }

    /**
     * Check if we have a valid email address (not the default fallback)
     */
    private function hasValidEmail(Order $order): bool
    {
        $email = $this->recipient($order);
        if (!$email) {
            return false;
        }
        
        // Don't send if it's just the default fallback address
        $defaultEmail = config('mail.from.address');
        return $email !== $defaultEmail && filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Safely send an email with error handling
     */
    private function safeSend(string $to, $mailable, string $type): bool
    {
        try {
            if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                Log::warning("NotificationService: Invalid email address for {$type}: {$to}");
                return false;
            }

            Mail::to($to)->send($mailable);
            return true;
        } catch (Exception $e) {
            Log::error("NotificationService: Failed to send {$type} email to {$to}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function sendOrderConfirmation(Order $order): void
    {
        $to = $this->recipient($order);
        if (!$to || !$this->hasValidEmail($order)) {
            Log::info("NotificationService: Skipping order confirmation email for order #{$order->id} - no valid email address");
            return;
        }

        $this->safeSend($to, new OrderConfirmationMail($order), 'order confirmation');
    }

    public function sendDeliveryNotification(Order $order): void
    {
        $to = $this->recipient($order);
        if (!$to || !$this->hasValidEmail($order)) {
            Log::info("NotificationService: Skipping delivery notification email for order #{$order->id} - no valid email address");
            $order->forceFill(['delivery_notified_at' => now()])->save();
            return;
        }

        if ($this->safeSend($to, new DeliveryNotificationMail($order), 'delivery notification')) {
            $order->forceFill(['delivery_notified_at' => now()])->save();
        }
    }

    public function sendLateDeliveryAlert(Order $order): void
    {
        $to = $this->recipient($order);
        if (!$to || !$this->hasValidEmail($order)) {
            Log::info("NotificationService: Skipping late delivery alert email for order #{$order->id} - no valid email address");
            $order->forceFill(['late_alert_sent_at' => now()])->save();
            return;
        }

        if ($this->safeSend($to, new LateDeliveryAlertMail($order), 'late delivery alert')) {
            $order->forceFill(['late_alert_sent_at' => now()])->save();
        }
    }

    public function sendFollowUpReminder(Order $order): void
    {
        $to = $this->recipient($order);
        if (!$to || !$this->hasValidEmail($order)) {
            Log::info("NotificationService: Skipping follow-up reminder email for order #{$order->id} - no valid email address");
            $order->forceFill(['follow_up_sent_at' => now()])->save();
            return;
        }

        if ($this->safeSend($to, new FollowUpReminderMail($order), 'follow-up reminder')) {
            $order->forceFill(['follow_up_sent_at' => now()])->save();
        }
    }

    public function sendPromo(string $subject, string $message, array $recipients): void
    {
        foreach ($recipients as $email) {
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning("NotificationService: Skipping promo email - invalid email address: {$email}");
                continue;
            }

            $this->safeSend($email, new PromoNotificationMail($subject, $message), 'promo notification');
        }
    }
}

