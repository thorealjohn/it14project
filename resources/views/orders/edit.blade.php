@extends('layouts.app')

@section('styles')
<style>
    #customerSuggestions {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        background: white;
        margin-top: 0.25rem;
    }
    #customerSuggestions .list-group-item {
        cursor: pointer;
        border: none;
        border-bottom: 1px solid #dee2e6;
    }
    #customerSuggestions .list-group-item:last-child {
        border-bottom: none;
    }
    #customerSuggestions .list-group-item:hover {
        background-color: #f8f9fa;
    }
    .mb-3 {
        position: relative;
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-6 fw-bold text-primary">
            <i class="bi bi-pencil-square me-2"></i>Edit Order #{{ $order->id }}
        </h1>
        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Order
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex">
                <div class="me-3">
                    <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                </div>
                <div>
                    <h5 class="alert-heading">Please fix the following errors:</h5>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('orders.update', $order->id) }}">
                @csrf
                @method('PUT')
                
                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <h5 class="card-title text-primary mb-3">Order Details</h5>
                        
                        <div class="mb-3">
                            <label for="customer_input" class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('customer_input') is-invalid @enderror @error('customer_id') is-invalid @enderror" 
                                   id="customer_input" 
                                   name="customer_input" 
                                   value="{{ old('customer_input', $order->customer->name ?? '') }}" 
                                   placeholder="Type customer name or search from existing customers"
                                   autocomplete="off"
                                   required>
                            <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id', $order->customer_id) }}">
                            @error('customer_input')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Type to search existing customers or enter a new customer name</small>
                            <div id="customerSuggestions" class="list-group mt-2" style="display: none; max-height: 200px; overflow-y: auto; position: absolute; z-index: 1000; width: 100%;"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address (Optional)</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $order->email) }}" 
                                   placeholder="customer@example.com"
                                   autocomplete="off">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Email address for order confirmation and notifications</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="order_status" class="form-label">Order Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('order_status') is-invalid @enderror" id="order_status" name="order_status" required>
                                <option value="pending" {{ old('order_status', $order->order_status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ old('order_status', $order->order_status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ old('order_status', $order->order_status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('order_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="water_type" class="form-label">Water Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('water_type') is-invalid @enderror" id="water_type" name="water_type" required>
                                <option value="alkaline" data-price="35" {{ old('water_type', $order->water_type) == 'alkaline' ? 'selected' : '' }}>Alkaline - ₱35</option>
                                <option value="purified" data-price="25" {{ old('water_type', $order->water_type) == 'purified' ? 'selected' : '' }}>Purified - ₱25</option>
                                <option value="mineral" data-price="25" {{ old('water_type', $order->water_type) == 'mineral' ? 'selected' : '' }}>Mineral - ₱25</option>
                            </select>
                            @error('water_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <input type="hidden" id="water_price" name="water_price" value="{{ old('water_price', $order->water_price) }}">
                            <small class="text-muted">Price auto-sets based on type.</small>
                        </div>
                        
                        @if($order->is_delivery)
                        <div class="mb-3">
                            <label for="delivery_user_id" class="form-label">Delivery Personnel</label>
                            <select class="form-select @error('delivery_user_id') is-invalid @enderror" id="delivery_user_id" name="delivery_user_id">
                                <option value="">Select delivery personnel</option>
                                @foreach($deliveryPersonnel as $personnel)
                                    <option value="{{ $personnel->id }}" {{ old('delivery_user_id', $order->delivery_user_id) == $personnel->id ? 'selected' : '' }}>
                                        {{ $personnel->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('delivery_user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $order->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <h5 class="card-title text-primary mb-3">Payment Details</h5>
                        
                        <div class="mb-3">
                            <label for="payment_status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('payment_status') is-invalid @enderror" id="payment_status" name="payment_status" required>
                                <option value="paid" {{ old('payment_status', $order->payment_status) == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="unpaid" {{ old('payment_status', $order->payment_status) == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            </select>
                            @error('payment_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div id="paymentMethodSection" class="{{ old('payment_status', $order->payment_status) == 'unpaid' ? 'd-none' : '' }}">
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method">
                                    <option value="">Select payment method</option>
                                    <option value="cash" {{ old('payment_method', $order->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="gcash" {{ old('payment_method', $order->payment_method) == 'gcash' ? 'selected' : '' }}>GCash</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div id="paymentReferenceSection" class="{{ old('payment_method', $order->payment_method) != 'gcash' ? 'd-none' : '' }}">
                                <div class="mb-3">
                                    <label for="payment_reference" class="form-label">Payment Reference Number</label>
                                    <input type="text" class="form-control @error('payment_reference') is-invalid @enderror" id="payment_reference" name="payment_reference" value="{{ old('payment_reference', $order->payment_reference) }}">
                                    @error('payment_reference')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <div class="d-flex">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <div>
                                    <h6 class="mb-1">Order Summary</h6>
                                    <div class="mb-1">Quantity: {{ $order->quantity }} container(s)</div>
                                    <div class="mb-1">Water Price: ₱{{ number_format($order->water_price, 2) }} each</div>
                                    @if($order->is_delivery)
                                    <div class="mb-1">
                                        Delivery Fee: 
                                        @if($order->delivery_fee == 0)
                                            <span class="text-success fw-bold">FREE</span>
                                            @if($order->quantity >= 3)
                                                <small class="text-muted">(3+ containers)</small>
                                            @endif
                                        @else
                                            ₱{{ number_format($order->delivery_fee, 2) }} each
                                        @endif
                                    </div>
                                    @endif
                                    <div class="mb-1">
                                        <strong>Total Amount: ₱{{ number_format($order->total_amount, 2) }}</strong>
                                    </div>
                                    <div class="mt-2 small text-muted">
                                        Note: To modify quantities or prices, please create a new order.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Update Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Customer input with autocomplete
    const customerInput = document.getElementById('customer_input');
    const customerIdInput = document.getElementById('customer_id');
    const customerSuggestions = document.getElementById('customerSuggestions');
    const customers = @json($customersJson);
    
    let selectedCustomerId = customerIdInput.value || null;
    let suggestionTimeout = null;
    
    // Show customer suggestions
    function showSuggestions(query) {
        if (!query || query.length < 1) {
            customerSuggestions.style.display = 'none';
            customerSuggestions.innerHTML = '';
            return;
        }
        
        const filtered = customers.filter(c => 
            c.name.toLowerCase().includes(query.toLowerCase()) ||
            (c.phone && c.phone.includes(query))
        );
        
        if (filtered.length === 0) {
            customerSuggestions.innerHTML = `
                <div class="list-group-item text-muted">
                    No matching customers. Press Enter to create new customer: "${query}"
                </div>
            `;
            customerSuggestions.style.display = 'block';
            selectedCustomerId = null;
            customerIdInput.value = '';
            return;
        }
        
        customerSuggestions.innerHTML = filtered.map(c => `
            <a href="#" class="list-group-item list-group-item-action" data-id="${c.id}" data-phone="${c.phone || ''}" data-address="${c.address || ''}">
                <strong>${c.name}</strong>
                ${c.phone ? `<small class="text-muted d-block">${c.phone}</small>` : ''}
            </a>
        `).join('');
        
        customerSuggestions.style.display = 'block';
        
        // Add click handlers
        customerSuggestions.querySelectorAll('.list-group-item-action').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const customerId = this.dataset.id;
                const customerName = this.querySelector('strong').textContent;
                
                customerInput.value = customerName;
                customerIdInput.value = customerId;
                selectedCustomerId = customerId;
                customerSuggestions.style.display = 'none';
            });
        });
    }
    
    // Handle input
    customerInput.addEventListener('input', function() {
        clearTimeout(suggestionTimeout);
        const query = this.value.trim();
        
        if (query.length === 0) {
            selectedCustomerId = null;
            customerIdInput.value = '';
            return;
        }
        
        suggestionTimeout = setTimeout(() => {
            showSuggestions(query);
        }, 200);
    });
    
    // Handle Enter key - create new customer if no match
    customerInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = this.value.trim();
            
            if (query && !selectedCustomerId) {
                // Will create new customer on form submit
                customerIdInput.value = '';
                customerSuggestions.style.display = 'none';
            } else if (selectedCustomerId) {
                customerSuggestions.style.display = 'none';
            }
        } else if (e.key === 'Escape') {
            customerSuggestions.style.display = 'none';
        }
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!customerInput.contains(e.target) && !customerSuggestions.contains(e.target)) {
            customerSuggestions.style.display = 'none';
        }
    });
    
    const paymentStatus = document.getElementById('payment_status');
    const paymentMethodSection = document.getElementById('paymentMethodSection');
    const paymentMethod = document.getElementById('payment_method');
    const paymentReferenceSection = document.getElementById('paymentReferenceSection');
    const waterTypeSelect = document.getElementById('water_type');
    const waterPriceInput = document.getElementById('water_price');
    
    paymentStatus.addEventListener('change', function() {
        if (this.value === 'paid') {
            paymentMethodSection.classList.remove('d-none');
            paymentMethod.setAttribute('required', 'required');
        } else {
            paymentMethodSection.classList.add('d-none');
            paymentMethod.removeAttribute('required');
            // Add hidden field for payment method 'none'
            if (!document.getElementById('payment_method_hidden')) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.id = 'payment_method_hidden';
                hiddenInput.name = 'payment_method';
                hiddenInput.value = 'none';
                paymentMethodSection.appendChild(hiddenInput);
            }
        }
    });
    
    paymentMethod.addEventListener('change', function() {
        if (this.value === 'gcash') {
            paymentReferenceSection.classList.remove('d-none');
            document.getElementById('payment_reference').setAttribute('required', 'required');
        } else {
            paymentReferenceSection.classList.add('d-none');
            document.getElementById('payment_reference').removeAttribute('required');
        }
    });
    
    function syncWaterPrice() {
        const option = waterTypeSelect.options[waterTypeSelect.selectedIndex];
        const price = parseFloat(option?.dataset.price || waterPriceInput.value || 25);
        waterPriceInput.value = price.toFixed(2);
    }

    waterTypeSelect.addEventListener('change', syncWaterPrice);
    syncWaterPrice();

    // Initialize payment method hidden field if status is unpaid
    if (paymentStatus.value === 'unpaid' && !document.getElementById('payment_method_hidden')) {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.id = 'payment_method_hidden';
        hiddenInput.name = 'payment_method';
        hiddenInput.value = 'none';
        paymentMethodSection.appendChild(hiddenInput);
    }
});
</script>
@endsection