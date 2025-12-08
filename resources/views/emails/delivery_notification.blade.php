<h2>Delivery Update</h2>
<p>Hello {{ $order->customer->name ?? 'customer' }},</p>
<p>Your delivery is on the way.</p>
<ul>
    <li>Order ID: {{ $order->id }}</li>
    <li>Scheduled: {{ optional($order->delivery_date)->format('M d, Y h:i A') ?? 'TBD' }}</li>
    <li>Status: {{ ucfirst($order->order_status) }}</li>
</ul>
<p>We appreciate your business.</p>

