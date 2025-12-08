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
    .form-group.mb-3 {
        position: relative;
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-6 fw-bold text-primary">
            <i class="bi bi-cart-plus me-2"></i>Create New Order
        </h1>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Orders
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

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="orderForm" method="POST" action="{{ route('orders.store') }}">
                        @csrf
                        
                        <div class="mb-4">
                            <h5 class="card-title text-primary mb-3">Customer Information</h5>
                            
                            <div class="form-group mb-3">
                                <label for="customer_input" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('customer_input') is-invalid @enderror @error('customer_id') is-invalid @enderror" 
                                       id="customer_input" 
                                       name="customer_input" 
                                       value="{{ old('customer_input', old('customer_id') ? $customers->firstWhere('id', old('customer_id'))?->name : '') }}" 
                                       placeholder="Type customer name or search from existing customers"
                                       autocomplete="off"
                                       required>
                                <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id') }}">
                                @error('customer_input')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Type to search existing customers or enter a new customer name</small>
                                <div id="customerSuggestions" class="list-group mt-2" style="display: none; max-height: 200px; overflow-y: auto; position: absolute; z-index: 1000; width: 100%;"></div>
                            </div>
                        
                            <div id="customerDetails" class="bg-light p-3 rounded mb-3" style="display: none;">
                                <div class="row">
                                    <div class="col-12 col-sm-6 mb-2 mb-sm-0">
                                        <p class="mb-1"><strong>Phone:</strong> <span id="customerPhone"></span></p>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <p class="mb-1"><strong>Address:</strong> <span id="customerAddress"></span></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">Email Address (Optional)</label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       placeholder="customer@example.com"
                                       autocomplete="off">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Email address for order confirmation and notifications</small>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="card-title text-primary mb-3">Order Details</h5>
                            
                            <div class="form-group mb-3">
                                <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" min="1" value="{{ old('quantity', 1) }}" required>
                                    <span class="input-group-text">container(s)</span>
                                    @error('quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="water_type" class="form-label">Water Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('water_type') is-invalid @enderror" id="water_type" name="water_type" required>
                                    <option value="">Select water type</option>
                                    <option value="alkaline" data-price="35" {{ old('water_type') == 'alkaline' ? 'selected' : '' }}>Alkaline - ₱35</option>
                                    <option value="purified" data-price="25" {{ old('water_type', 'purified') == 'purified' ? 'selected' : '' }}>Purified - ₱25</option>
                                    <option value="mineral" data-price="25" {{ old('water_type') == 'mineral' ? 'selected' : '' }}>Mineral - ₱25</option>
                                </select>
                                @error('water_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <input type="hidden" id="water_price" name="water_price" value="{{ old('water_price', 25.00) }}">
                                <small class="form-text text-muted">Price auto-sets by water type.</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="order_status" class="form-label">Order Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('order_status') is-invalid @enderror" id="order_status" name="order_status" required>
                                    <option value="pending" {{ old('order_status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="completed" {{ old('order_status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ old('order_status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('order_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label d-block">Delivery Type</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_delivery" name="is_delivery" value="1" {{ old('is_delivery') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_delivery">
                                        Delivery (₱5.00 per container, FREE for 3+ containers)
                                    </label>
                                </div>
                            </div>
                            
                            <div id="deliveryFields" class="mb-3" style="{{ old('is_delivery') ? '' : 'display: none;' }}">
                                <label for="delivery_user_id" class="form-label">Assign Delivery Personnel <span class="text-danger">*</span></label>
                                <select class="form-select @error('delivery_user_id') is-invalid @enderror" id="delivery_user_id" name="delivery_user_id" {{ old('is_delivery') ? 'required' : '' }}>
                                    <option value="">Select personnel</option>
                                    @foreach($deliveryPersonnel as $personnel)
                                        <option value="{{ $personnel->id }}" {{ old('delivery_user_id') == $personnel->id ? 'selected' : '' }}>
                                            {{ $personnel->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('delivery_user_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="payment_status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('payment_status') is-invalid @enderror" id="payment_status" name="payment_status" required>
                                    <option value="paid" {{ old('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="unpaid" {{ old('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                </select>
                                @error('payment_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div id="paymentFields" class="{{ old('payment_status') == 'unpaid' ? 'd-none' : '' }}">
                                <div class="form-group mb-3">
                                    <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                    <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method">
                                        <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="gcash" {{ old('payment_method') == 'gcash' ? 'selected' : '' }}>GCash</option>
                                    </select>
                                    @error('payment_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div id="gcashFields" class="form-group mb-3 {{ old('payment_method') != 'gcash' ? 'd-none' : '' }}">
                                    <label for="payment_reference" class="form-label">GCash Reference Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('payment_reference') is-invalid @enderror" id="payment_reference" name="payment_reference" value="{{ old('payment_reference') }}">
                                    @error('payment_reference')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-primary mb-3">Replacement Options</h6>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="replace_gallon" name="replace_gallon" value="1" {{ old('replace_gallon') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="replace_gallon">
                                        Replace Gallon Container (₱25.00 per container)
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="replace_caps" name="replace_caps" value="1" {{ old('replace_caps') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="replace_caps">
                                        Replace Caps (₱5.00 per cap)
                                    </label>
                                </div>
                                
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Note: Seals and brand stickers are automatically deducted from inventory for each order.
                                </small>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Submit Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-lg-4 mt-4 mt-lg-0">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Water (<span id="summaryQuantity">1</span> x ₱<span id="summaryWaterPrice">25.00</span>)</span>
                        <span id="waterTotal">₱25.00</span>
                    </div>
                    
                    <div id="deliveryFeeRow" class="d-flex justify-content-between mb-2" style="display: none;">
                        <span>Delivery Fee (<span id="deliveryQuantity">1</span> x <span id="deliveryFeePerUnit">₱5.00</span>)</span>
                        <span id="deliveryTotal">₱5.00</span>
                    </div>
                    
                    <div id="replacementCostRow" class="d-flex justify-content-between mb-2" style="display: none;">
                        <span>Replacement Cost</span>
                        <span id="replacementTotal">₱0.00</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total</span>
                        <span id="orderTotal" class="fs-5">₱25.00</span>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm bg-light">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle me-2"></i>Order Tips
                    </h5>
                    <ul class="card-text">
                        <li>For regular customers, ensure their details are up-to-date</li>
                        <li>Unpaid orders will be marked as "pending"</li>
                        <li>Delivery orders will appear in the Deliveries section</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Customer selection handling
    const customerDetails = document.getElementById('customerDetails');
    const customerPhoneEl = document.getElementById('customerPhone');
    const customerAddressEl = document.getElementById('customerAddress');
    
    // Customer input with autocomplete
    const customerInput = document.getElementById('customer_input');
    const customerIdInput = document.getElementById('customer_id');
    const customerSuggestions = document.getElementById('customerSuggestions');
    const customers = @json($customersJson);
    
    let selectedCustomerId = null;
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
                const customerPhone = this.dataset.phone;
                const customerAddress = this.dataset.address;
                
                customerInput.value = customerName;
                customerIdInput.value = customerId;
                selectedCustomerId = customerId;
                customerSuggestions.style.display = 'none';
                
                // Update customer details
                if (customerPhone || customerAddress) {
                    customerPhoneEl.textContent = customerPhone || 'Not provided';
                    customerAddressEl.textContent = customerAddress || 'Not provided';
                    customerDetails.style.display = 'block';
                } else {
                    customerDetails.style.display = 'none';
                }
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
            customerDetails.style.display = 'none';
            customerSuggestions.style.display = 'none';
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
    
    // Order calculation elements
    const quantityInput = document.getElementById('quantity');
    const waterPriceInput = document.getElementById('water_price');
    const waterTypeSelect = document.getElementById('water_type');
    const isDeliveryCheckbox = document.getElementById('is_delivery');
    const deliveryFields = document.getElementById('deliveryFields');
    const deliveryPersonnel = document.getElementById('delivery_user_id');
    const replaceGallonCheckbox = document.getElementById('replace_gallon');
    const replaceCapsCheckbox = document.getElementById('replace_caps');
    
    // Payment fields
    const paymentStatus = document.getElementById('payment_status');
    const paymentFields = document.getElementById('paymentFields');
    const paymentMethod = document.getElementById('payment_method');
    const gcashFields = document.getElementById('gcashFields');
    
    // Summary elements
    const summaryQuantity = document.getElementById('summaryQuantity');
    const deliveryFeeRow = document.getElementById('deliveryFeeRow');
    const deliveryQuantity = document.getElementById('deliveryQuantity');
    const deliveryFeePerUnit = document.getElementById('deliveryFeePerUnit');
    const waterTotal = document.getElementById('waterTotal');
    const deliveryTotal = document.getElementById('deliveryTotal');
    const orderTotal = document.getElementById('orderTotal');
    const summaryWaterPrice = document.getElementById('summaryWaterPrice');
    const replacementCostRow = document.getElementById('replacementCostRow');
    const replacementTotal = document.getElementById('replacementTotal');
    
    // Initialize customer details if already selected
    const initialCustomerId = customerIdInput.value;
    if (initialCustomerId) {
        const customer = customers.find(c => c.id == initialCustomerId);
        if (customer) {
            customerPhoneEl.textContent = customer.phone || 'Not provided';
            customerAddressEl.textContent = customer.address || 'Not provided';
            customerDetails.style.display = 'block';
        }
    }
    
    // Delivery checkbox change
    isDeliveryCheckbox.addEventListener('change', function() {
        deliveryFields.style.display = this.checked ? 'block' : 'none';
        deliveryPersonnel.required = this.checked;
        deliveryFeeRow.style.display = this.checked ? 'flex' : 'none';
        updateOrderSummary();
    });
    
    // Payment status change
    paymentStatus.addEventListener('change', function() {
        paymentFields.classList.toggle('d-none', this.value === 'unpaid');
        paymentMethod.required = this.value === 'paid';
        
        if (this.value === 'unpaid') {
            // Set a hidden default value for payment_method when unpaid
            if (!document.getElementById('payment_method_hidden')) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.id = 'payment_method_hidden';
                hiddenInput.name = 'payment_method';
                hiddenInput.value = 'none';
                document.getElementById('orderForm').appendChild(hiddenInput);
            }
        } else {
            // Remove hidden input if it exists
            const hiddenInput = document.getElementById('payment_method_hidden');
            if (hiddenInput) hiddenInput.remove();
        }
    });
    
    // Payment method change
    paymentMethod.addEventListener('change', function() {
        gcashFields.classList.toggle('d-none', this.value !== 'gcash');
        document.getElementById('payment_reference').required = this.value === 'gcash';
    });
    
    // Quantity and water type change
    quantityInput.addEventListener('input', updateOrderSummary);
    waterTypeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = parseFloat(selectedOption?.dataset.price || waterPriceInput.value || 0);
        waterPriceInput.value = price.toFixed(2);
        updateOrderSummary();
    });
    
    // Replacement options change
    replaceGallonCheckbox.addEventListener('change', updateOrderSummary);
    replaceCapsCheckbox.addEventListener('change', updateOrderSummary);
    
    // Initialize order summary
    function updateOrderSummary() {
        const quantity = parseInt(quantityInput.value) || 1;
        const isDelivery = isDeliveryCheckbox.checked;
        const selectedOption = waterTypeSelect.options[waterTypeSelect.selectedIndex];
        const waterPrice = parseFloat(selectedOption?.dataset.price || waterPriceInput.value || 25.00);
        waterPriceInput.value = waterPrice.toFixed(2);
        const defaultDeliveryFee = 5.00;
        const gallonReplacementPrice = 25.00;
        const capReplacementPrice = 5.00;
        
        // Calculate delivery fee: Free if quantity is 3 or more
        let deliveryFee = 0;
        if (isDelivery) {
            if (quantity >= 3) {
                deliveryFee = 0; // Free delivery for 3 or more containers
            } else {
                deliveryFee = defaultDeliveryFee; // Charge delivery fee for less than 3 containers
            }
        }
        
        // Calculate replacement costs
        let replacementCost = 0;
        if (replaceGallonCheckbox.checked) {
            replacementCost += quantity * gallonReplacementPrice;
        }
        if (replaceCapsCheckbox.checked) {
            replacementCost += quantity * capReplacementPrice;
        }
        
        const waterTotalAmount = quantity * waterPrice;
        const deliveryTotalAmount = isDelivery ? quantity * deliveryFee : 0;
        const totalAmount = waterTotalAmount + deliveryTotalAmount + replacementCost;
        
        // Update summary display
        summaryQuantity.textContent = quantity;
        summaryWaterPrice.textContent = waterPrice.toFixed(2);
        waterTotal.textContent = `₱${waterTotalAmount.toFixed(2)}`;
        
        if (isDelivery) {
            deliveryQuantity.textContent = quantity;
            if (quantity >= 3) {
                deliveryFeePerUnit.textContent = 'FREE';
                deliveryTotal.textContent = 'FREE';
            } else {
                deliveryFeePerUnit.textContent = `₱${defaultDeliveryFee.toFixed(2)}`;
                deliveryTotal.textContent = `₱${deliveryTotalAmount.toFixed(2)}`;
            }
            deliveryFeeRow.style.display = 'flex';
        } else {
            deliveryFeeRow.style.display = 'none';
        }
        
        // Update replacement cost display
        if (replacementCost > 0) {
            replacementTotal.textContent = `₱${replacementCost.toFixed(2)}`;
            replacementCostRow.style.display = 'flex';
        } else {
            replacementCostRow.style.display = 'none';
        }
        
        orderTotal.textContent = `₱${totalAmount.toFixed(2)}`;
    }
    
    // Initialize form state
    updateOrderSummary();
    deliveryFields.style.display = isDeliveryCheckbox.checked ? 'block' : 'none';
    deliveryPersonnel.required = isDeliveryCheckbox.checked;
    gcashFields.classList.toggle('d-none', paymentMethod.value !== 'gcash');
    document.getElementById('payment_reference').required = paymentMethod.value === 'gcash';
    
    // Form validation before submit
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        if (isDeliveryCheckbox.checked && !deliveryPersonnel.value) {
            e.preventDefault();
            alert('Please select delivery personnel');
            deliveryPersonnel.focus();
        }
        
        if (paymentStatus.value === 'paid' && !paymentMethod.value) {
            e.preventDefault();
            alert('Please select a payment method');
            paymentMethod.focus();
        }
        
        if (paymentMethod.value === 'gcash' && !document.getElementById('payment_reference').value) {
            e.preventDefault();
            alert('Please enter GCash reference number');
            document.getElementById('payment_reference').focus();
        }
    });
});
</script>
@endsection