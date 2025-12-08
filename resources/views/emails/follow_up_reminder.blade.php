<h2>We hope you enjoyed your water delivery</h2>
<p>Hi {{ $order->customer->name ?? 'customer' }},</p>
<p>We recently completed your order #{{ $order->id }}. We would love to hear your feedback and remind you we are ready for your next refill.</p>
<ul>
    <li>Completed: {{ optional($order->updated_at)->format('M d, Y h:i A') }}</li>
    <li>Quantity: {{ $order->quantity }}</li>
</ul>
<p>Reply to this email if you have any concerns or need another delivery.</p>

