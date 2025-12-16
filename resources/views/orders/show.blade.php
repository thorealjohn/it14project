@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-6 fw-bold text-primary">
            <i class="bi bi-currency-dollar me-2"></i>Order #{{ $order->id }}
        </h1>
        <div>
            <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-primary me-2">
                <i class="bi bi-pencil me-1"></i> Edit Order
            </a>
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Orders
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order Details</h5>
                        <div>
                            <span class="badge {{ $order->order_status === 'completed' ? 'bg-success' : ($order->order_status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ ucfirst($order->order_status) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-12 col-md-6 mb-4 mb-md-0">
                            <h6 class="text-muted mb-3">Customer Information</h6>
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-circle bg-primary me-3">
                                    {{ substr($order->customer->name, 0, 1) }}
                                </div>
                                <div>
                                    <h5 class="mb-1">{{ $order->customer->name }}</h5>
                                    <a href="{{ route('customers.show', $order->customer->id) }}" class="text-decoration-none">
                                        <i class="bi bi-person-circle me-1"></i> View Profile
                                    </a>
                                </div>
                            </div>
                            <p class="mb-1"><i class="bi bi-telephone me-2 text-muted"></i> {{ $order->customer->phone }}</p>
                            <p class="mb-3"><i class="bi bi-geo-alt me-2 text-muted"></i> {{ $order->customer->address }}</p>
                        </div>
                        <div class="col-12 col-md-6">
                            <h6 class="text-muted mb-3">Order Information</h6>
                            <p class="mb-1">
                                <span class="text-muted">Created By:</span>
                                <span class="ms-2">{{ $order->user->name }}</span>
                            </p>
                            <p class="mb-1">
                                <span class="text-muted">Created On:</span>
                                <span class="ms-2">{{ $order->created_at->format('M d, Y h:i A') }}</span>
                            </p>
                            @if($order->is_delivery)
                            <p class="mb-1">
                                <span class="text-muted">Delivery Type:</span>
                                <span class="badge bg-info text-white ms-2">Delivery</span>
                            </p>
                            <p class="mb-1">
                                <span class="text-muted">Delivery Person:</span>
                                <span class="ms-2">{{ $order->deliveryPerson->name ?? 'Not Assigned' }}</span>
                            </p>
                            @if($order->delivery_date)
                            <p class="mb-1">
                                <span class="text-muted">Delivered On:</span>
                                <span class="ms-2">{{ Carbon\Carbon::parse($order->delivery_date)->format('M d, Y h:i A') }}</span>
                            </p>
                            @endif
                            @else
                            <p class="mb-1">
                                <span class="text-muted">Delivery Type:</span>
                                <span class="badge bg-secondary text-white ms-2">Pick-up</span>
                            </p>
                            @endif
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Water Container</td>
                                    <td class="text-center">{{ $order->quantity }}</td>
                                    <td class="text-end">₱{{ number_format($order->water_price, 2) }}</td>
                                    <td class="text-end">₱{{ number_format($order->quantity * $order->water_price, 2) }}</td>
                                </tr>
                                @if($order->is_delivery)
                                <tr>
                                    <td>Delivery Fee</td>
                                    <td class="text-center">{{ $order->quantity }}</td>
                                    <td class="text-end">
                                        @if($order->delivery_fee == 0)
                                            <span class="text-success fw-bold">FREE</span>
                                            @if($order->quantity >= 3)
                                                <small class="text-muted d-block">(3+ containers)</small>
                                            @endif
                                        @else
                                            ₱{{ number_format($order->delivery_fee, 2) }}
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($order->delivery_fee == 0)
                                            <span class="text-success fw-bold">FREE</span>
                                        @else
                                            ₱{{ number_format($order->quantity * $order->delivery_fee, 2) }}
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                @if($order->replacement_cost > 0)
                                <tr>
                                    <td>Replacement Costs</td>
                                    <td class="text-center">{{ $order->quantity }}</td>
                                    <td class="text-end">
                                        @if($order->replace_gallon && $order->replace_caps)
                                            Gallon (₱25.00) + Cap (₱5.00) = ₱30.00
                                        @elseif($order->replace_gallon)
                                            Gallon: ₱25.00
                                        @elseif($order->replace_caps)
                                            Cap: ₱5.00
                                        @endif
                                    </td>
                                    <td class="text-end">₱{{ number_format($order->replacement_cost, 2) }}</td>
                                </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th colspan="3">Total</th>
                                    <th class="text-end">₱{{ number_format($order->total_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted me-2">Payment Status:</span>
                            <span class="badge {{ $order->isFullyPaid() ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $order->isFullyPaid() ? 'Paid' : 'Unpaid' }}
                            </span>
                            
                            @if($order->total_paid > 0)
                            <span class="ms-3">
                                <span class="text-muted">Paid:</span>
                                <span class="ms-1 fw-bold">₱{{ number_format($order->total_paid, 2) }}</span>
                            </span>
                            @endif
                            
                            @if($order->remaining_balance > 0)
                            <span class="ms-3">
                                <span class="text-muted">Balance:</span>
                                <span class="ms-1 fw-bold text-danger">₱{{ number_format($order->remaining_balance, 2) }}</span>
                            </span>
                            @endif
                        </div>
                        
                        @if($order->order_status === 'pending')
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('orders.complete', $order->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-check-circle me-1"></i> Mark as Completed
                                </button>
                            </form>
                            
                            <form method="POST" action="{{ route('orders.cancel', $order->id) }}" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="bi bi-x-circle me-1"></i> Cancel Order
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            
            @if($order->notes)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Order Notes</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $order->notes }}</p>
                </div>
            </div>
            @endif
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Inventory Transactions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity Change</th>
                                    <th>Date</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->inventoryTransactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->inventoryItem->name }}</td>
                                    <td>
                                        <span class="badge bg-danger">{{ $transaction->quantity_change }}</span>
                                    </td>
                                    <td>{{ $transaction->created_at->format('M d, Y h:i A') }}</td>
                                    <td>{{ $transaction->notes }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3">No inventory transactions found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Payment History</h5>
                    @if($order->remaining_balance > 0 && $order->order_status !== 'cancelled')
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Payment
                    </button>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Total Amount</small>
                                <h5 class="mb-0">₱{{ number_format($order->total_amount, 2) }}</h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Total Paid</small>
                                <h5 class="mb-0 text-success">₱{{ number_format($order->total_paid, 2) }}</h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Remaining Balance</small>
                                <h5 class="mb-0 {{ $order->remaining_balance > 0 ? 'text-danger' : 'text-success' }}">
                                    ₱{{ number_format($order->remaining_balance, 2) }}
                                </h5>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Recorded By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                    <td class="fw-bold">₱{{ number_format($payment->amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                                    </td>
                                    <td>{{ $payment->payment_reference ?? '-' }}</td>
                                    <td>{{ $payment->user->name }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('payments.destroy', $payment->id) }}" 
                                              onsubmit="return confirm('Are you sure you want to delete this payment?')" 
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-3">
                                        <p class="text-muted mb-0">No payments recorded yet</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-lg-4 mt-4 mt-lg-0">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Order Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i> Edit Order
                        </a>
                        
                        @if($order->order_status === 'pending' && $order->is_delivery)
                        <a href="{{ route('deliveries.show', $order->id) }}" class="btn btn-outline-info">
                            <i class="bi bi-truck me-1"></i> Manage Delivery
                        </a>
                        @endif
                        
                        @if($order->order_status !== 'cancelled')
                        <button type="button" class="btn btn-outline-success" onclick="printReceipt()">
                            <i class="bi bi-printer me-1"></i> Print Receipt
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Customer's Recent Orders</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($order->customer->orders()->where('id', '!=', $order->id)->latest()->take(5)->get() as $recentOrder)
                        <a href="{{ route('orders.show', $recentOrder->id) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Order #{{ $recentOrder->id }}</div>
                                    <small>{{ $recentOrder->created_at->format('M d, Y') }}</small>
                                </div>
                                <div>
                                    <span class="badge {{ $recentOrder->order_status === 'completed' ? 'bg-success' : ($recentOrder->order_status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                        {{ ucfirst($recentOrder->order_status) }}
                                    </span>
                                    <div>₱{{ number_format($recentOrder->total_amount, 2) }}</div>
                                </div>
                            </div>
                        </a>
                        @endforeach
                        
                        @if($order->customer->orders()->where('id', '!=', $order->id)->count() === 0)
                        <div class="list-group-item text-center py-3">
                            <span class="text-muted">No other orders found</span>
                        </div>
                        @endif
                    </div>
                </div>
                @if($order->customer->orders()->where('id', '!=', $order->id)->count() > 0)
                <div class="card-footer bg-white">
                    <a href="{{ route('customers.show', $order->customer->id) }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-eye me-1"></i> View All Orders
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPaymentModalLabel">Add Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('payments.store', $order->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" 
                                   class="form-control @error('amount') is-invalid @enderror" 
                                   id="amount" 
                                   name="amount" 
                                   step="0.01" 
                                   min="0.01" 
                                   max="{{ $order->remaining_balance }}"
                                   value="{{ old('amount', $order->remaining_balance) }}"
                                   required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="form-text text-muted">Remaining balance: ₱{{ number_format($order->remaining_balance, 2) }}</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method" required>
                            <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="gcash" {{ old('payment_method') == 'gcash' ? 'selected' : '' }}>GCash</option>
                            <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>Check</option>
                            <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_reference" class="form-label">Payment Reference</label>
                        <input type="text" 
                               class="form-control @error('payment_reference') is-invalid @enderror" 
                               id="payment_reference" 
                               name="payment_reference" 
                               value="{{ old('payment_reference') }}"
                               placeholder="GCash reference, check number, etc.">
                        @error('payment_reference')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Required for GCash, bank transfer, or check payments</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" 
                               class="form-control @error('payment_date') is-invalid @enderror" 
                               id="payment_date" 
                               name="payment_date" 
                               value="{{ old('payment_date', now()->toDateString()) }}"
                               required>
                        @error('payment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" 
                                  name="notes" 
                                  rows="2">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Receipt Print Template (Hidden) -->
<div id="receipt-template" class="d-none">
    <div style="max-width: 300px; font-family: 'Courier New', monospace;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h3 style="margin-bottom: 5px;"><span style="color: #01579B;">CLEAR</span><span style="color: #00B8D4;">pro</span> Water Station</h3>
            <p style="margin: 5px 0;">Refilling Station</p>
            <p style="margin: 5px 0;">{{ now()->format('M d, Y h:i A') }}</p>
        </div>
        
        <div style="margin-bottom: 15px;">
            <p style="margin: 2px 0;"><strong>Order #:</strong> {{ $order->id }}</p>
            <p style="margin: 2px 0;"><strong>Customer:</strong> {{ $order->customer->name }}</p>
            <p style="margin: 2px 0;"><strong>Date:</strong> {{ $order->created_at->format('M d, Y') }}</p>
        </div>
        
        <div style="margin-bottom: 15px;">
            <table style="width: 100%; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0;">
                <tr>
                    <th style="text-align: left;">Item</th>
                    <th style="text-align: right;">Qty</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
                <tr>
                    <td style="text-align: left;">Water</td>
                    <td style="text-align: right;">{{ $order->quantity }}</td>
                    <td style="text-align: right;">₱{{ number_format($order->water_price, 2) }}</td>
                    <td style="text-align: right;">₱{{ number_format($order->quantity * $order->water_price, 2) }}</td>
                </tr>
                @if($order->is_delivery)
                <tr>
                    <td style="text-align: left;">Delivery</td>
                    <td style="text-align: right;">{{ $order->quantity }}</td>
                    <td style="text-align: right;">₱{{ number_format($order->delivery_fee, 2) }}</td>
                    <td style="text-align: right;">₱{{ number_format($order->quantity * $order->delivery_fee, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td colspan="3" style="text-align: right; padding-top: 10px;"><strong>Total</strong></td>
                    <td style="text-align: right; padding-top: 10px;"><strong>₱{{ number_format($order->total_amount, 2) }}</strong></td>
                </tr>
            </table>
        </div>
        
        <div style="margin-bottom: 15px;">
            <p style="margin: 2px 0;"><strong>Payment Status:</strong> {{ ucfirst($order->payment_status) }}</p>
            @if($order->payment_status === 'paid')
            <p style="margin: 2px 0;"><strong>Payment Method:</strong> {{ ucfirst($order->payment_method) }}</p>
            @endif
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <p style="margin: 5px 0;">Thank you for your purchase!</p>
            <p style="margin: 5px 0;">Please bring empty containers on your next order.</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function printReceipt() {
    const receiptWindow = window.open('', '_blank');
    receiptWindow.document.write('<html><head><title>Order Receipt</title>');
    receiptWindow.document.write('<style>body { font-family: Arial, sans-serif; }</style>');
    receiptWindow.document.write('</head><body>');
    receiptWindow.document.write(document.getElementById('receipt-template').innerHTML);
    receiptWindow.document.write('</body></html>');
    receiptWindow.document.close();
    
    receiptWindow.onload = function() {
        receiptWindow.print();
        //receiptWindow.close();
    };
}
</script>
@endsection