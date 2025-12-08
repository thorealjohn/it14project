<?php
// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Customer;
use App\Models\InventoryItem;

class DashboardController extends Controller
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
     * Show the dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Metrics
        $todaySales = Order::whereDate('created_at', Carbon::today())->sum('total_amount');
        $todayOrders = Order::whereDate('created_at', Carbon::today())->count();
        $weeklySales = Order::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('total_amount');
        $monthlySales = Order::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('total_amount');
        $pendingDeliveries = Order::where('is_delivery', true)->where('order_status', 'pending')->count();
        $totalCustomers = Customer::count();

        // Low stock (not currently displayed on UI but kept for potential use)
        $lowStockItems = InventoryItem::whereColumn('quantity', '<=', 'threshold')
            ->orderBy('quantity')
            ->take(5)
            ->get();
        $lowStockCount = $lowStockItems->count();

        // Recent orders (dynamic)
        $perPage = in_array($request->input('per_page', 10), [10, 25, 50]) ? $request->input('per_page', 10) : 10;
        $orderPeriod = $request->input('order_period', 'today');
        $search = $request->input('search');

        $recentOrdersQuery = Order::with('customer')
            ->when($orderPeriod === 'today', fn($q) => $q->whereDate('created_at', Carbon::today()))
            ->when($orderPeriod === 'week', fn($q) => $q->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]))
            ->when($orderPeriod === 'month', fn($q) => $q->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]))
            ->when($search, function ($q) use ($search) {
                $q->whereHas('customer', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at');

        $recentOrders = $recentOrdersQuery->paginate($perPage)->withQueryString();
        $showSalesData = true;
        
        return view('dashboard', compact(
            'todaySales', 
            'todayOrders', 
            'weeklySales', 
            'monthlySales', 
            'pendingDeliveries', 
            'lowStockItems',
            'lowStockCount',
            'totalCustomers',
            'recentOrders',
            'orderPeriod',
            'perPage',
            'showSalesData'
        ));
    }
}