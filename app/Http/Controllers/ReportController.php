<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Customer;
use App\Models\User;

class ReportController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Display the sales report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function salesReport(Request $request)
    {
        $reportPeriod = $request->period ?? 'custom';
        $granularity = $request->granularity ?? 'daily';
        $perPage = in_array($request->per_page, [10, 20, 50, 100]) ? $request->per_page : 20;
        $filter = $request->filter ?? 'all';
        
        // Determine date range based on period
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        } elseif ($reportPeriod === 'daily') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($reportPeriod === 'weekly') {
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek();
        } elseif ($reportPeriod === 'monthly') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        } else {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfDay();
        }
        
        // Build base query
        $query = Order::with('customer')
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        // Apply filters
        if ($filter === 'paid') {
            $query->where('payment_status', 'paid');
        } elseif ($filter === 'unpaid') {
            $query->where('payment_status', 'unpaid');
        } elseif ($filter === 'delivery') {
            $query->where('is_delivery', true);
        } elseif ($filter === 'pickup') {
            $query->where('is_delivery', false);
        }
        
        // Get paginated orders
        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
        
        // Calculate statistics from filtered query
        $statsQuery = Order::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($filter === 'paid') {
            $statsQuery->where('payment_status', 'paid');
        } elseif ($filter === 'unpaid') {
            $statsQuery->where('payment_status', 'unpaid');
        } elseif ($filter === 'delivery') {
            $statsQuery->where('is_delivery', true);
        } elseif ($filter === 'pickup') {
            $statsQuery->where('is_delivery', false);
        }
        
        $totalSales = $statsQuery->sum('total_amount');
        $totalQuantity = $statsQuery->sum('quantity');
        $totalOrders = $statsQuery->count();
        
        // Paid/Unpaid stats (only if not filtered)
        if ($filter === 'all' || $filter === 'delivery' || $filter === 'pickup') {
            $paidQuery = clone $statsQuery;
            $paidOrders = $paidQuery->where('payment_status', 'paid')->count();
            $paidSales = $paidQuery->where('payment_status', 'paid')->sum('total_amount');
            
            $unpaidQuery = clone $statsQuery;
            $unpaidOrders = $unpaidQuery->where('payment_status', 'unpaid')->count();
            $unpaidSales = $unpaidQuery->where('payment_status', 'unpaid')->sum('total_amount');
        } else {
            $paidOrders = $filter === 'paid' ? $totalOrders : 0;
            $paidSales = $filter === 'paid' ? $totalSales : 0;
            $unpaidOrders = $filter === 'unpaid' ? $totalOrders : 0;
            $unpaidSales = $filter === 'unpaid' ? $totalSales : 0;
        }
        
        // Delivery/Pickup stats (only if not filtered)
        if ($filter === 'all' || $filter === 'paid' || $filter === 'unpaid') {
            $deliveryOrders = (clone $statsQuery)->where('is_delivery', true)->count();
            $pickupOrders = (clone $statsQuery)->where('is_delivery', false)->count();
        } else {
            $deliveryOrders = $filter === 'delivery' ? $totalOrders : 0;
            $pickupOrders = $filter === 'pickup' ? $totalOrders : 0;
        }
        
        // Generate chart data based on granularity
        $chartData = $this->generateChartData($startDate, $endDate, $granularity, $filter);
        
        return view('reports.sales', compact(
            'orders', 
            'totalSales', 
            'totalQuantity', 
            'totalOrders',
            'paidOrders', 
            'paidSales', 
            'unpaidOrders', 
            'unpaidSales',
            'deliveryOrders',
            'pickupOrders', 
            'chartData',
            'startDate',
            'endDate',
            'reportPeriod',
            'granularity',
            'perPage',
            'filter'
        ));
    }
    
    /**
     * Generate chart data based on date range and granularity
     */
    private function generateChartData($startDate, $endDate, $granularity, $filter = 'all')
    {
        $labels = [];
        $sales = [];
        $quantities = [];
        
        // Safety check: limit daily granularity to 90 days max to prevent performance issues
        if ($granularity === 'daily') {
            $daysDiff = $startDate->diffInDays($endDate);
            if ($daysDiff > 90) {
                $granularity = 'weekly'; // Auto-switch to weekly for large ranges
            }
        }
        
        if ($granularity === 'daily') {
            $current = $startDate->copy();
            $maxDays = 90; // Safety limit
            $dayCount = 0;
            
            while ($current <= $endDate && $dayCount < $maxDays) {
                $dayStart = $current->copy()->startOfDay();
                $dayEnd = $current->copy()->endOfDay();
                
                $query = Order::whereBetween('created_at', [$dayStart, $dayEnd]);
                
                if ($filter === 'paid') {
                    $query->where('payment_status', 'paid');
                } elseif ($filter === 'unpaid') {
                    $query->where('payment_status', 'unpaid');
                } elseif ($filter === 'delivery') {
                    $query->where('is_delivery', true);
                } elseif ($filter === 'pickup') {
                    $query->where('is_delivery', false);
                }
                
                $labels[] = $current->format('M d');
                $sales[] = (float) $query->sum('total_amount');
                $quantities[] = (int) $query->sum('quantity');
                
                $current->addDay();
                $dayCount++;
            }
        } elseif ($granularity === 'weekly') {
            $current = $startDate->copy()->startOfWeek();
            $maxWeeks = 52; // Safety limit
            
            while ($current <= $endDate) {
                $weekStart = $current->copy()->startOfWeek();
                $weekEnd = $current->copy()->endOfWeek();
                
                // Don't extend beyond endDate
                if ($weekStart > $endDate) {
                    break;
                }
                if ($weekEnd > $endDate) {
                    $weekEnd = $endDate->copy();
                }
                
                $query = Order::whereBetween('created_at', [$weekStart, $weekEnd]);
                
                if ($filter === 'paid') {
                    $query->where('payment_status', 'paid');
                } elseif ($filter === 'unpaid') {
                    $query->where('payment_status', 'unpaid');
                } elseif ($filter === 'delivery') {
                    $query->where('is_delivery', true);
                } elseif ($filter === 'pickup') {
                    $query->where('is_delivery', false);
                }
                
                $labels[] = $weekStart->format('M d') . ' - ' . min($weekEnd, $endDate)->format('M d');
                $sales[] = (float) $query->sum('total_amount');
                $quantities[] = (int) $query->sum('quantity');
                
                $current->addWeek();
            }
        } else { // monthly
            $current = $startDate->copy()->startOfMonth();
            
            while ($current <= $endDate) {
                $monthStart = $current->copy()->startOfMonth();
                $monthEnd = $current->copy()->endOfMonth();
                
                // Don't extend beyond endDate
                if ($monthStart > $endDate) {
                    break;
                }
                if ($monthEnd > $endDate) {
                    $monthEnd = $endDate->copy();
                }
                
                $query = Order::whereBetween('created_at', [$monthStart, $monthEnd]);
                
                if ($filter === 'paid') {
                    $query->where('payment_status', 'paid');
                } elseif ($filter === 'unpaid') {
                    $query->where('payment_status', 'unpaid');
                } elseif ($filter === 'delivery') {
                    $query->where('is_delivery', true);
                } elseif ($filter === 'pickup') {
                    $query->where('is_delivery', false);
                }
                
                $labels[] = $current->format('M Y');
                $sales[] = (float) $query->sum('total_amount');
                $quantities[] = (int) $query->sum('quantity');
                
                $current->addMonth();
            }
        }
        
        return [
            'labels' => $labels,
            'sales' => $sales,
            'quantities' => $quantities,
        ];
    }

    /**
     * Display the delivery report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function deliveryReport(Request $request)
    {
        $reportPeriod = $request->period ?? 'custom';
        $granularity = $request->granularity ?? 'daily';
        $perPage = in_array($request->per_page, [10, 20, 50, 100]) ? $request->per_page : 20;
        $filterStatus = $request->status ?? 'all';
        $filterDriver = $request->driver ?? 'all';
        
        // Determine date range based on period
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        } elseif ($reportPeriod === 'daily') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($reportPeriod === 'weekly') {
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek();
        } elseif ($reportPeriod === 'monthly') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        } else {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfDay();
        }
        
        // Build base query for delivery orders only
        $query = Order::with(['customer', 'deliveryPerson'])
            ->where('is_delivery', true)
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        // Apply status filter
        if ($filterStatus === 'completed') {
            $query->where('order_status', 'completed');
        } elseif ($filterStatus === 'pending') {
            $query->where('order_status', 'pending');
        } elseif ($filterStatus === 'cancelled') {
            $query->where('order_status', 'cancelled');
        }
        
        // Apply driver filter
        if ($filterDriver !== 'all' && $filterDriver) {
            $query->where('delivery_user_id', $filterDriver);
        }
        
        // Get paginated deliveries
        $deliveries = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
        
        // Calculate statistics
        $statsQuery = Order::where('is_delivery', true)
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        if ($filterStatus === 'completed') {
            $statsQuery->where('order_status', 'completed');
        } elseif ($filterStatus === 'pending') {
            $statsQuery->where('order_status', 'pending');
        } elseif ($filterStatus === 'cancelled') {
            $statsQuery->where('order_status', 'cancelled');
        }
        
        if ($filterDriver !== 'all' && $filterDriver) {
            $statsQuery->where('delivery_user_id', $filterDriver);
        }
        
        $totalDeliveries = $statsQuery->count();
        $totalDeliveryAmount = $statsQuery->sum('total_amount');
        $totalQuantity = $statsQuery->sum('quantity');
        
        // Status breakdown (only if not filtered)
        if ($filterStatus === 'all') {
            $completedDeliveries = (clone $statsQuery)->where('order_status', 'completed')->count();
            $pendingDeliveries = (clone $statsQuery)->where('order_status', 'pending')->count();
        } else {
            $completedDeliveries = $filterStatus === 'completed' ? $totalDeliveries : 0;
            $pendingDeliveries = $filterStatus === 'pending' ? $totalDeliveries : 0;
        }
        
        // Get delivery personnel stats
        $personnelQuery = Order::where('is_delivery', true)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('delivery_user_id');
        
        if ($filterStatus !== 'all') {
            $personnelQuery->where('order_status', $filterStatus);
        }
        
        $personnelStats = $personnelQuery
            ->selectRaw('delivery_user_id, COUNT(*) as total_deliveries, 
                        SUM(CASE WHEN order_status = "completed" THEN 1 ELSE 0 END) as completed_deliveries,
                        SUM(quantity) as total_quantity')
            ->groupBy('delivery_user_id')
            ->get()
            ->map(function ($stat) {
                $user = User::find($stat->delivery_user_id);
                return (object)[
                    'id' => $stat->delivery_user_id,
                    'name' => $user ? $user->name : 'Unknown',
                    'total_deliveries' => $stat->total_deliveries,
                    'completed_deliveries' => $stat->completed_deliveries,
                    'total_quantity' => $stat->total_quantity,
                ];
            });
        
        // Get list of delivery personnel (drivers)
        $drivers = User::where('role', 'delivery')
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return view('reports.delivery', compact(
            'deliveries',
            'totalDeliveries',
            'totalDeliveryAmount',
            'completedDeliveries',
            'pendingDeliveries',
            'totalQuantity',
            'personnelStats',
            'startDate',
            'endDate',
            'reportPeriod',
            'granularity',
            'perPage',
            'filterStatus',
            'drivers',
            'filterDriver'
        ));
    }

    /**
     * Display the customer report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function customerReport(Request $request)
    {
        $reportPeriod = $request->period ?? 'custom';
        $perPage = in_array($request->per_page, [10, 20, 50, 100]) ? $request->per_page : 20;
        
        // Determine date range based on period
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        } elseif ($reportPeriod === 'daily') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($reportPeriod === 'weekly') {
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek();
        } elseif ($reportPeriod === 'monthly') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        } else {
            $startDate = Carbon::now()->subMonths(3)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }
        
        // Get customers with their order statistics within date range
        $customers = Customer::withCount([
            'orders' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        ])
        ->withSum([
            'orders' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        ], 'total_amount')
        ->with([
            'orders' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                      ->orderBy('created_at', 'desc');
            }
        ])
        ->having('orders_count', '>', 0)
        ->orderBy('orders_sum_total_amount', 'desc')
        ->paginate($perPage)
        ->withQueryString();
        
        // Transform customers to include last_order date and ensure total_spent is set
        $customers->getCollection()->transform(function ($customer) {
            $lastOrder = $customer->orders->first();
            $customer->last_order = $lastOrder ? $lastOrder->created_at : null;
            $customer->total_spent = (float)($customer->orders_sum_total_amount ?? 0);
            return $customer;
        });
        
        // Calculate overall statistics
        $totalCustomers = Customer::count();
        
        // Customers with orders in date range
        $customersWithOrders = Customer::whereHas('orders', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })->count();
        
        // Regular customers (is_regular = true)
        $regularCustomers = Customer::where('is_regular', true)->count();
        
        // Total orders and sales in date range
        $ordersQuery = Order::whereBetween('created_at', [$startDate, $endDate]);
        $totalOrders = $ordersQuery->count();
        $totalSales = $ordersQuery->sum('total_amount');
        
        // Average order value
        $avgOrderValue = $totalOrders > 0 ? ($totalSales / $totalOrders) : 0;
        
        // Repeat customers (customers with more than 1 order in date range)
        $repeatCustomers = Customer::whereHas('orders', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }, '>', 1)->count();
        
        // Generate chart data (top 10 customers by revenue)
        $topCustomers = Customer::withSum([
            'orders' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        ], 'total_amount')
        ->withCount([
            'orders' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        ])
        ->having('orders_sum_total_amount', '>', 0)
        ->orderBy('orders_sum_total_amount', 'desc')
        ->limit(10)
        ->get();
        
        $chartData = [
            'names' => $topCustomers->pluck('name')->toArray(),
            'revenues' => $topCustomers->pluck('orders_sum_total_amount')->map(fn($v) => (float)$v)->toArray(),
            'orders' => $topCustomers->pluck('orders_count')->toArray(),
        ];
        
        return view('reports.customer', compact(
            'customers',
            'totalCustomers',
            'regularCustomers',
            'totalOrders',
            'totalSales',
            'avgOrderValue',
            'repeatCustomers',
            'chartData',
            'startDate',
            'endDate',
            'reportPeriod',
            'perPage',
            'customersWithOrders'
        ));
    }

    /**
     * Display the inventory report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function inventoryReport(Request $request)
    {
        $reportPeriod = $request->period ?? 'custom';
        $perPage = in_array($request->per_page, [10, 20, 50, 100]) ? $request->per_page : 20;
        
        // Static mock inventory
        $currentInventory = collect([
            (object)[
                'id' => 1,
                'name' => '5 Gallon Water',
                'type' => 'water',
                'quantity' => 150,
                'threshold' => 50,
                'updated_at' => Carbon::now()->subDays(1),
            ],
        ]);
        
        // Mock inventory logs
        $mockLogs = collect([
            (object)[
                'id' => 1,
                'inventoryItem' => (object)[
                    'name' => '5 Gallon Water',
                    'type' => 'water'
                ],
                'user' => (object)['name' => 'Admin User'],
                'order' => null,
                'order_id' => null,
                'quantity_change' => -10,
                'transaction_type' => 'order',
                'notes' => 'Order fulfillment',
                'created_at' => Carbon::now()->subDays(2),
            ],
        ]);
        
        $inventoryLogs = new LengthAwarePaginator(
            $mockLogs,
            $mockLogs->count(),
            $perPage,
            1,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        $totalTransactions = 50;
        $totalIncoming = 200;
        $totalOutgoing = 150;
        
        $itemStats = [
            'water' => [
                'current' => 150,
                'incoming' => 200,
                'outgoing' => 150,
                'last_updated' => Carbon::now()->subDays(1),
            ],
        ];
        
        $startDate = Carbon::now()->subMonth();
        $endDate = Carbon::now()->endOfDay();
        
        return view('reports.inventory', compact(
            'currentInventory',
            'inventoryLogs',
            'totalTransactions',
            'totalIncoming',
            'totalOutgoing',
            'itemStats',
            'startDate',
            'endDate',
            'reportPeriod',
            'perPage'
        ));
    }

    /**
     * Export sales report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportSalesReport(Request $request)
    {
        return redirect()->back()
            ->with('info', 'Functionality disabled. This is a UI-only demo.');
    }

    /**
     * Export delivery report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportDeliveryReport(Request $request)
    {
        return redirect()->back()
            ->with('info', 'Functionality disabled. This is a UI-only demo.');
    }

    /**
     * Export customer report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportCustomerReport(Request $request)
    {
        return redirect()->back()
            ->with('info', 'Functionality disabled. This is a UI-only demo.');
    }

    /**
     * Export inventory report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportInventoryReport(Request $request)
    {
        return redirect()->back()
            ->with('info', 'Functionality disabled. This is a UI-only demo.');
    }
}
