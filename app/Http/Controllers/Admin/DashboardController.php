<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get current user for greeting
        $user = auth()->user();
        $userName = $user && isset($user->name) ? $user->name : 'Admin';

        if ($user?->user_type === 'staff') {
            return $this->staffDashboard($user);
        }

        // Calculate date ranges
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $last7Days = Carbon::now()->subDays(7);
        $last30Days = Carbon::now()->subDays(30);
        $lastMonth = Carbon::now()->subMonth();
        $lastMonthStart = $lastMonth->copy()->startOfMonth();
        $lastMonthEnd = $lastMonth->copy()->endOfMonth();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastYear = Carbon::now()->subYear();
        $thisYear = Carbon::now()->startOfYear();

        // Total Earnings (sum of active orders; COD orders remain pending until collection)
        $totalEarnings = $this->earningOrdersQuery()->sum('total');
        $lastMonthEarnings = $this->earningOrdersQuery()
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('total');
        $earningsChange = $lastMonthEarnings > 0
            ? (($totalEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100
            : 0;

        // Total Orders
        $totalOrders = Order::count();
        $lastMonthOrders = Order::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
        $ordersChange = $lastMonthOrders > 0
            ? (($totalOrders - $lastMonthOrders) / $lastMonthOrders) * 100
            : 0;

        // Delivered Orders
        $deliveredOrders = Order::where('order_status', 'delivered')->count();
        $lastMonthDeliveredOrders = Order::where('order_status', 'delivered')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();
        $deliveredChange = $lastMonthDeliveredOrders > 0
            ? (($deliveredOrders - $lastMonthDeliveredOrders) / $lastMonthDeliveredOrders) * 100
            : 0;

        // Cancelled Orders
        $cancelledOrders = Order::where('order_status', 'cancelled')->count();
        $lastMonthCancelledOrders = Order::where('order_status', 'cancelled')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();
        $cancelledChange = $lastMonthCancelledOrders > 0
            ? (($cancelledOrders - $lastMonthCancelledOrders) / $lastMonthCancelledOrders) * 100
            : 0;

        // Total Customers
        $totalCustomers = User::where('user_type', 'customer')->count();
        $lastMonthCustomers = User::where('user_type', 'customer')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();
        $customersChange = $lastMonthCustomers > 0
            ? (($totalCustomers - $lastMonthCustomers) / $lastMonthCustomers) * 100
            : 0;

        // My Balance (total earnings - refunds)
        $totalRefunds = Order::where('payment_status', 'refunded')->sum('total');
        $myBalance = $totalEarnings - $totalRefunds;
        $lastMonthBalance = $this->earningOrdersQuery()
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('total') - Order::where('payment_status', 'refunded')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('total');
        $balanceChange = $lastMonthBalance > 0
            ? (($myBalance - $lastMonthBalance) / $lastMonthBalance) * 100
            : 0;

        // Revenue Section Stats (Last 30 days)
        $revenueOrders = Order::whereBetween('created_at', [$last30Days, now()])->count();
        $revenueEarnings = $this->earningOrdersQuery()
            ->whereBetween('created_at', [$last30Days, now()])
            ->sum('total');
        $revenueRefunds = Order::where('payment_status', 'refunded')
            ->whereBetween('created_at', [$last30Days, now()])
            ->count();
        $conversionRatio = $totalCustomers > 0
            ? ($totalOrders / $totalCustomers) * 100
            : 0;

        // Best Selling Products (by num_of_sale or order items)
        $bestSellingProducts = Product::with('category')
            ->leftJoinSub(
                OrderItem::query()
                    ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
                    ->groupBy('product_id'),
                'order_item_totals',
                'products.id',
                '=',
                'order_item_totals.product_id'
            )
            ->select('products.*')
            ->selectRaw('COALESCE(order_item_totals.total_sold, 0) as total_sold')
            ->orderBy('total_sold', 'desc')
            ->orderBy('num_of_sale', 'desc')
            ->limit(5)
            ->get();

        // Recent Orders
        $recentOrders = Order::with(['user', 'items.product', 'reviews'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Top Categories (by product count)
        $topCategories = Category::withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit(10)
            ->get();

        // Product Reviews (approved reviews)
        $productReviews = Review::with(['user', 'product'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        // Customer Reviews Stats
        $approvedReviewQuery = Review::where('status', 'approved');
        $totalReviews = (clone $approvedReviewQuery)->count();
        $avgRating = (float) ((clone $approvedReviewQuery)->avg('rating') ?? 0);
        $ratingCounts = (clone $approvedReviewQuery)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();
        $ratingDistribution = collect(range(1, 5))
            ->mapWithKeys(fn ($rating) => [$rating => (int) ($ratingCounts[$rating] ?? 0)])
            ->toArray();

        // Recent Activity (combine orders, products, reviews)
        $recentActivities = collect();

        // Recent orders
        $recentOrderActivities = Order::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($order) {
                return [
                    'type' => 'order',
                    'icon' => 'ri-shopping-cart-2-line',
                    'icon_color' => 'success',
                    'title' => 'Purchase by '.$order->customer_name,
                    'description' => 'Order #'.$order->order_number,
                    'time' => $order->created_at,
                    'data' => $order,
                ];
            });

        // Recent products
        $recentProductActivities = Product::orderBy('created_at', 'desc')
            ->limit(2)
            ->get()
            ->map(function ($product) {
                return [
                    'type' => 'product',
                    'icon' => 'ri-stack-fill',
                    'icon_color' => 'danger',
                    'title' => 'Added new product',
                    'description' => $product->name,
                    'time' => $product->created_at,
                    'data' => $product,
                ];
            });

        // Recent reviews
        $recentReviewActivities = Review::with(['user', 'product'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get()
            ->map(function ($review) {
                return [
                    'type' => 'review',
                    'icon' => 'ri-star-fill',
                    'icon_color' => 'warning',
                    'title' => $review->user->name.' reviewed a product',
                    'description' => $review->product->name ?? 'Product',
                    'time' => $review->created_at,
                    'data' => $review,
                ];
            });

        $recentActivities = $recentOrderActivities
            ->concat($recentProductActivities)
            ->concat($recentReviewActivities)
            ->sortByDesc('time')
            ->take(8);

        // Sales by Location (based on delivery area districts)
        $salesByLocation = Order::select(
            'delivery_areas.district_name as district',
            DB::raw('COUNT(orders.id) as count'),
            DB::raw('SUM(orders.total) as total_sales')
        )
            ->leftJoin('shipping_addresses', 'orders.shipping_address_id', '=', 'shipping_addresses.id')
            ->leftJoin('delivery_areas', 'shipping_addresses.delivery_area_id', '=', 'delivery_areas.id')
            ->whereNotNull('shipping_addresses.delivery_area_id')
            ->whereNotNull('delivery_areas.district_name')
            ->groupBy('delivery_areas.district_name')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        $salesLocationMarkers = collect();

        // Revenue chart data (last 12 months)
        $revenueChartData = [];
        $ordersData = [];
        $earningsData = [];
        $refundsData = [];
        $monthLabels = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $monthOrders = Order::whereBetween('created_at', [$monthStart, $monthEnd])->count();
            $monthEarnings = $this->earningOrdersQuery()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('total');
            $monthRefunds = Order::where('payment_status', 'refunded')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $ordersData[] = $monthOrders;
            $earningsData[] = $monthEarnings > 0 ? round($monthEarnings / 1000, 2) : 0; // Convert to thousands
            $refundsData[] = $monthRefunds;
            $monthLabels[] = $month->format('M');

            $revenueChartData[] = [
                'month' => $month->format('M Y'),
                'orders' => $monthOrders,
                'earnings' => $monthEarnings,
                'refunds' => $monthRefunds,
            ];
        }

        return view('dashboard', compact(
            'userName',
            'totalEarnings',
            'earningsChange',
            'totalOrders',
            'ordersChange',
            'deliveredOrders',
            'deliveredChange',
            'cancelledOrders',
            'cancelledChange',
            'totalCustomers',
            'customersChange',
            'myBalance',
            'balanceChange',
            'revenueOrders',
            'revenueEarnings',
            'revenueRefunds',
            'conversionRatio',
            'bestSellingProducts',
            'recentOrders',
            'topCategories',
            'productReviews',
            'totalReviews',
            'avgRating',
            'ratingDistribution',
            'recentActivities',
            'salesByLocation',
            'salesLocationMarkers',
            'revenueChartData',
            'ordersData',
            'earningsData',
            'refundsData',
            'monthLabels'
        ));
    }

    private function earningOrdersQuery(): Builder
    {
        return Order::query()
            ->whereIn('payment_status', ['pending', 'paid'])
            ->where('order_status', '!=', 'cancelled');
    }

    /**
     * Show staff a small dashboard containing only the orders they created.
     */
    protected function staffDashboard(User $user)
    {
        $orders = Order::query()->where('created_by_id', $user->id);
        $statusCounts = (clone $orders)
            ->selectRaw('order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        $staffStats = [
            'total' => (clone $orders)->count(),
            'today' => (clone $orders)->whereDate('created_at', Carbon::today())->count(),
            'pending' => (int) $statusCounts->get('pending', 0),
            'in_progress' => collect(['confirmed', 'processing', 'shipped'])
                ->sum(fn (string $status) => (int) $statusCounts->get($status, 0)),
            'delivered' => (int) $statusCounts->get('delivered', 0),
            'cancelled' => (int) $statusCounts->get('cancelled', 0),
            'total_value' => (float) (clone $orders)->sum('total'),
            'delivered_value' => (float) (clone $orders)
                ->where('order_status', 'delivered')
                ->sum('total'),
        ];
        $staffStats['completion_rate'] = $staffStats['total'] > 0
            ? round(($staffStats['delivered'] / $staffStats['total']) * 100, 1)
            : 0;

        $weeklyOrders = (clone $orders)
            ->whereBetween('created_at', [Carbon::today()->subDays(6)->startOfDay(), Carbon::today()->endOfDay()])
            ->get(['created_at', 'total'])
            ->groupBy(fn (Order $order) => $order->created_at->format('Y-m-d'));

        $weeklyChart = collect(range(6, 1))
            ->map(function (int $daysAgo) use ($weeklyOrders) {
                $date = Carbon::today()->subDays($daysAgo);
                $dailyOrders = $weeklyOrders->get($date->format('Y-m-d'), collect());

                return [
                    'label' => $date->format('D'),
                    'orders' => $dailyOrders->count(),
                    'value' => round((float) $dailyOrders->sum('total'), 2),
                ];
            })
            ->push((function () use ($weeklyOrders) {
                $dailyOrders = $weeklyOrders->get(Carbon::today()->format('Y-m-d'), collect());

                return [
                    'label' => 'Today',
                    'orders' => $dailyOrders->count(),
                    'value' => round((float) $dailyOrders->sum('total'), 2),
                ];
            })());

        $recentOrders = (clone $orders)
            ->latest()
            ->limit(8)
            ->get();

        return view('staff.dashboard', [
            'userName' => $user->name ?: 'Staff',
            'staffStats' => $staffStats,
            'weeklyChart' => $weeklyChart,
            'recentOrders' => $recentOrders,
        ]);
    }
}
