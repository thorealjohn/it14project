<?php
// app/Http/Controllers/OrderController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected NotificationService $notifications;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(NotificationService $notifications)
    {
        $this->middleware('auth');
        $this->notifications = $notifications;
    }
    
    /**
     * Display a listing of orders.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = \App\Models\Order::with(['customer', 'user', 'deliveryPerson']);
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }
        
        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        // Filter by delivery type
        if ($request->filled('delivery_type')) {
            if ($request->delivery_type === 'delivery') {
                $query->where('is_delivery', true);
            } else {
                $query->where('is_delivery', false);
            }
        }
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        $perPage = $request->input('per_page', 20);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
            
        return view('orders.index', compact('orders'));
    }

    /**
     * Show form to create a new order.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        $deliveryPersonnel = \App\Models\User::whereIn('role', ['delivery', 'helper'])->orderBy('name')->get();
        
        // Prepare customer data for JavaScript
        $customersJson = $customers->map(function($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'address' => $c->address
            ];
        })->values();
        
        return view('orders.create', compact('customers', 'deliveryPersonnel', 'customersJson'));
    }

    /**
     * Store a newly created order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Handle customer input - either customer_id or customer_input (text)
        $customerId = null;
        
        if ($request->filled('customer_id') && $request->customer_id !== '') {
            // Use existing customer ID
            $customerId = $request->customer_id;
        } elseif ($request->filled('customer_input')) {
            // Find or create customer by name
            $customerName = trim($request->customer_input);
            if ($customerName) {
                $customer = \App\Models\Customer::firstOrCreate(
                    ['name' => $customerName],
                    ['phone' => null, 'address' => null, 'is_regular' => false]
                );
                $customerId = $customer->id;
            }
        }
        
        if (!$customerId) {
            return redirect()->back()
                ->withErrors(['customer_input' => 'Please select or enter a customer name.'])
                ->withInput();
        }
        
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'water_type' => 'required|in:alkaline,purified,mineral',
            'water_price' => 'required|numeric|min:0',
            'is_delivery' => 'boolean',
            'delivery_fee' => 'nullable|numeric|min:0',
            'delivery_user_id' => 'nullable|exists:users,id',
            'delivery_date' => 'nullable|date',
            'payment_status' => 'required|in:paid,unpaid',
            'payment_method' => 'required|in:cash,gcash,none',
            'payment_reference' => 'nullable|string|max:255',
            'order_status' => 'required|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
            'replace_gallon' => 'boolean',
            'replace_caps' => 'boolean',
        ]);
        
        $validated['customer_id'] = $customerId;
        $validated['is_delivery'] = $request->has('is_delivery');
        $validated['replace_gallon'] = $request->has('replace_gallon');
        $validated['replace_caps'] = $request->has('replace_caps');
        $validated['user_id'] = auth()->id();
        
        // Calculate delivery fee: Free if quantity is 3 or more
        $defaultDeliveryFee = 5.00;
        if ($validated['is_delivery']) {
            if ($validated['quantity'] >= 3) {
                // Free delivery for 3 or more containers
                $validated['delivery_fee'] = 0;
            } else {
                // Charge delivery fee for less than 3 containers
                $validated['delivery_fee'] = $validated['delivery_fee'] ?? $defaultDeliveryFee;
            }
        } else {
            $validated['delivery_fee'] = 0;
        }
        
        // Default price by water type
        $priceByType = [
            'alkaline' => 35,
            'purified' => 25,
            'mineral' => 25,
        ];

        $validated['water_price'] = $priceByType[$validated['water_type']] ?? $validated['water_price'];

        // Calculate replacement costs
        $gallonReplacementPrice = 25.00;
        $capReplacementPrice = 5.00;
        $replacementCost = 0;
        
        if ($validated['replace_gallon']) {
            $replacementCost += $validated['quantity'] * $gallonReplacementPrice;
        }
        
        if ($validated['replace_caps']) {
            $replacementCost += $validated['quantity'] * $capReplacementPrice;
        }
        
        $validated['replacement_cost'] = $replacementCost;

        // Calculate total amount
        $waterTotal = $validated['quantity'] * $validated['water_price'];
        $deliveryTotal = $validated['is_delivery'] ? ($validated['quantity'] * $validated['delivery_fee']) : 0;
        $validated['total_amount'] = $waterTotal + $deliveryTotal + $replacementCost;
        
        DB::beginTransaction();
        try {
            $order = \App\Models\Order::create($validated);
            
            // Create inventory transactions
            $this->createInventoryTransactions($order);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create order: ' . $e->getMessage()])
                ->withInput();
        }

        // Notify customer
        $this->notifications->sendOrderConfirmation($order);
        if ($order->is_delivery && is_null($order->delivery_notified_at)) {
            $this->notifications->sendDeliveryNotification($order);
        }
        
        return redirect()->route('orders.show', $order->id)
            ->with('success', 'Order created successfully!');
    }

    /**
     * Display the order details.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $order = \App\Models\Order::with(['customer', 'user', 'deliveryPerson', 'inventoryTransactions'])->findOrFail($id);
        
        return view('orders.show', compact('order'));
    }

    /**
     * Show form to edit order.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $order = \App\Models\Order::findOrFail($id);
        $customers = \App\Models\Customer::orderBy('name')->get();
        $deliveryPersonnel = \App\Models\User::whereIn('role', ['delivery', 'helper'])->orderBy('name')->get();
        
        // Prepare customer data for JavaScript
        $customersJson = $customers->map(function($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'address' => $c->address
            ];
        })->values();
        
        return view('orders.edit', compact('order', 'customers', 'deliveryPersonnel', 'customersJson'));
    }

    /**
     * Update the order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $order = \App\Models\Order::findOrFail($id);
        
        // Handle customer input - either customer_id or customer_input (text)
        $customerId = null;
        
        if ($request->filled('customer_id') && $request->customer_id !== '') {
            // Use existing customer ID
            $customerId = $request->customer_id;
        } elseif ($request->filled('customer_input')) {
            // Find or create customer by name
            $customerName = trim($request->customer_input);
            if ($customerName) {
                $customer = \App\Models\Customer::firstOrCreate(
                    ['name' => $customerName],
                    ['phone' => null, 'address' => null, 'is_regular' => false]
                );
                $customerId = $customer->id;
            }
        }
        
        if (!$customerId) {
            return redirect()->back()
                ->withErrors(['customer_input' => 'Please select or enter a customer name.'])
                ->withInput();
        }
        
        $validated = $request->validate([
            'email' => 'nullable|email|max:255',
            'quantity' => 'required|integer|min:1',
            'water_type' => 'required|in:alkaline,purified,mineral',
            'water_price' => 'required|numeric|min:0',
            'is_delivery' => 'boolean',
            'delivery_fee' => 'nullable|numeric|min:0',
            'delivery_user_id' => 'nullable|exists:users,id',
            'delivery_date' => 'nullable|date',
            'payment_status' => 'required|in:paid,unpaid',
            'payment_method' => 'required|in:cash,gcash,none',
            'payment_reference' => 'nullable|string|max:255',
            'order_status' => 'required|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
            'replace_gallon' => 'boolean',
            'replace_caps' => 'boolean',
        ]);
        
        $validated['customer_id'] = $customerId;
        $validated['is_delivery'] = $request->has('is_delivery');
        $validated['replace_gallon'] = $request->has('replace_gallon');
        $validated['replace_caps'] = $request->has('replace_caps');
        
        // Default price by water type
        $priceByType = [
            'alkaline' => 35,
            'purified' => 25,
            'mineral' => 25,
        ];
        $validated['water_price'] = $priceByType[$validated['water_type']] ?? $validated['water_price'];

        // Calculate delivery fee: Free if quantity is 3 or more
        $defaultDeliveryFee = 5.00;
        if ($validated['is_delivery']) {
            if ($validated['quantity'] >= 3) {
                // Free delivery for 3 or more containers
                $validated['delivery_fee'] = 0;
            } else {
                // Charge delivery fee for less than 3 containers
                $validated['delivery_fee'] = $validated['delivery_fee'] ?? $defaultDeliveryFee;
            }
        } else {
            $validated['delivery_fee'] = 0;
        }
        
        // Calculate replacement costs
        $gallonReplacementPrice = 25.00;
        $capReplacementPrice = 5.00;
        $replacementCost = 0;
        
        if ($validated['replace_gallon']) {
            $replacementCost += $validated['quantity'] * $gallonReplacementPrice;
        }
        
        if ($validated['replace_caps']) {
            $replacementCost += $validated['quantity'] * $capReplacementPrice;
        }
        
        $validated['replacement_cost'] = $replacementCost;
        
        // Calculate total amount
        $waterTotal = $validated['quantity'] * $validated['water_price'];
        $deliveryTotal = $validated['is_delivery'] ? ($validated['quantity'] * $validated['delivery_fee']) : 0;
        $validated['total_amount'] = $waterTotal + $deliveryTotal + $replacementCost;
        
        $order->update($validated);

        // If delivery details were set/updated, notify customer once
        if ($order->is_delivery && is_null($order->delivery_notified_at)) {
            $this->notifications->sendDeliveryNotification($order);
        }
        
        return redirect()->route('orders.show', $order->id)
            ->with('success', 'Order updated successfully!');
    }

    /**
     * Mark an order as completed.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function complete($id)
    {
        $order = \App\Models\Order::findOrFail($id);
        
        if ($order->order_status === 'completed') {
            return redirect()->back()
                ->with('error', 'Order is already completed.');
        }
        
        $order->update([
            'order_status' => 'completed',
            'delivery_date' => $order->is_delivery ? now() : null,
        ]);

        if ($order->is_delivery) {
            $this->notifications->sendDeliveryNotification($order);
        }
        
        return redirect()->back()
            ->with('success', 'Order marked as completed!');
    }
    
    /**
     * Cancel an order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel($id)
    {
        $order = \App\Models\Order::findOrFail($id);
        
        if ($order->order_status === 'cancelled') {
            return redirect()->back()
                ->with('error', 'Order is already cancelled.');
        }
        
        if ($order->order_status === 'completed') {
            return redirect()->back()
                ->with('error', 'Cannot cancel a completed order.');
        }
        
        $order->update(['order_status' => 'cancelled']);
        
        return redirect()->back()
            ->with('success', 'Order cancelled successfully!');
    }
    
    /**
     * Remove the specified order from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $order = \App\Models\Order::findOrFail($id);
        
        // Only allow deletion of cancelled orders or pending orders
        if ($order->order_status === 'completed') {
            return redirect()->back()
                ->with('error', 'Cannot delete a completed order.');
        }
        
        $order->delete();
        
        return redirect()->route('orders.index')
            ->with('success', 'Order deleted successfully!');
    }
    
    /**
     * Create inventory transactions for an order
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    private function createInventoryTransactions(Order $order)
    {
        $quantity = $order->quantity;
        $userId = auth()->id();
        
        // Find inventory items by type first, then by name if needed
        $sealItem = InventoryItem::where('type', 'seal')->first();
        if (!$sealItem) {
            $sealItem = InventoryItem::where('name', 'like', '%Seal%')->first();
        }
            
        $stickerItem = InventoryItem::where('type', 'other')
            ->where(function($q) {
                $q->where('name', 'like', '%Sticker%')
                  ->orWhere('name', 'like', '%Brand%');
            })
            ->first();
            
        $capItem = InventoryItem::where('type', 'cap')->first();
        if (!$capItem) {
            $capItem = InventoryItem::where('name', 'like', '%Cap%')->first();
        }
            
        $containerItem = InventoryItem::where('type', 'container')->first();
        if (!$containerItem) {
            $containerItem = InventoryItem::where(function($q) {
                $q->where('name', 'like', '%Container%')
                  ->orWhere('name', 'like', '%Gallon%');
            })->first();
        }
        
        // Always deduct seals (automatic for every order)
        if ($sealItem) {
            if ($sealItem->quantity < $quantity) {
                throw new \Exception("Insufficient seals in inventory. Available: {$sealItem->quantity}, Required: {$quantity}");
            }
            
            $sealItem->quantity -= $quantity;
            $sealItem->save();
            
            InventoryTransaction::create([
                'inventory_item_id' => $sealItem->id,
                'user_id' => $userId,
                'quantity_change' => -$quantity,
                'transaction_type' => 'order',
                'order_id' => $order->id,
                'notes' => "Automatic deduction for order #{$order->id}",
            ]);
        }
        
        // Always deduct brand stickers (automatic for every order)
        if ($stickerItem) {
            if ($stickerItem->quantity < $quantity) {
                throw new \Exception("Insufficient brand stickers in inventory. Available: {$stickerItem->quantity}, Required: {$quantity}");
            }
            
            $stickerItem->quantity -= $quantity;
            $stickerItem->save();
            
            InventoryTransaction::create([
                'inventory_item_id' => $stickerItem->id,
                'user_id' => $userId,
                'quantity_change' => -$quantity,
                'transaction_type' => 'order',
                'order_id' => $order->id,
                'notes' => "Automatic deduction for order #{$order->id}",
            ]);
        }
        
        // Deduct caps if replacement is requested
        if ($order->replace_caps && $capItem) {
            if ($capItem->quantity < $quantity) {
                throw new \Exception("Insufficient caps in inventory. Available: {$capItem->quantity}, Required: {$quantity}");
            }
            
            $capItem->quantity -= $quantity;
            $capItem->save();
            
            InventoryTransaction::create([
                'inventory_item_id' => $capItem->id,
                'user_id' => $userId,
                'quantity_change' => -$quantity,
                'transaction_type' => 'order',
                'order_id' => $order->id,
                'notes' => "Cap replacement for order #{$order->id}",
            ]);
        }
        
        // Deduct containers if replacement is requested
        if ($order->replace_gallon && $containerItem) {
            if ($containerItem->quantity < $quantity) {
                throw new \Exception("Insufficient containers in inventory. Available: {$containerItem->quantity}, Required: {$quantity}");
            }
            
            $containerItem->quantity -= $quantity;
            $containerItem->save();
            
            InventoryTransaction::create([
                'inventory_item_id' => $containerItem->id,
                'user_id' => $userId,
                'quantity_change' => -$quantity,
                'transaction_type' => 'order',
                'order_id' => $order->id,
                'notes' => "Container replacement for order #{$order->id}",
            ]);
        }
    }
}
