<h2>Late Delivery Alert</h2>
<p>Order {{ $order->id }} for {{ $order->customer->name ?? 'customer' }} is running late.</p>
<ul>
    <li>Scheduled: {{ optional($order->delivery_date)->format('M d, Y h:i A') ?? 'TBD' }}</li>
    <li>Status: {{ ucfirst($order->order_status) }}</li>
    <li>Quantity: {{ $order->quantity }}</li>
</ul>
<p>Please reach out to the customer and update the schedule.</p>

