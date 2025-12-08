<h2>Order Confirmation</h2>
<p>Hi {{ $order->customer->name ?? 'customer' }},</p>
<p>Your order has been confirmed.</p>
<ul>
    <li>Order ID: {{ $order->id }}</li>
    <li>Quantity: {{ $order->quantity }}</li>
    <li>Total: ₱{{ number_format($order->total_amount, 2) }}</li>
    @if($order->is_delivery)
        <li>Delivery: Scheduled {{ optional($order->delivery_date)->format('M d, Y h:i A') ?? 'TBD' }}</li>
    @else
        <li>Pickup: In-store</li>
    @endif
</ul>
<p>Thank you for choosing AQUASTAR.</p>

