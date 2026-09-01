@extends('layouts.master')
@section('title', 'Dashboard')
@section('css')
    <link href="{{ URL::asset('build/libs/jsvectormap/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/libs/swiper/swiper-bundle.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .commerce-dashboard {
            --dash-ink: #111827;
            --dash-muted: #64748b;
            --dash-line: rgba(148, 163, 184, .22);
            --dash-soft: #f8fafc;
            --dash-primary: #405189;
            --dash-teal: #0ab39c;
            --dash-amber: #f7b84b;
            --dash-red: #f06548;
            --dash-shadow: 0 12px 28px rgba(15, 23, 42, .08);
        }

        .commerce-dashboard .card {
            border: 1px solid var(--dash-line);
            border-radius: 8px;
            box-shadow: var(--dash-shadow);
        }

        .commerce-dashboard .layout-rightside > .card {
            border-radius: 0;
        }

        .commerce-dashboard .card-header {
            border-bottom: 1px solid var(--dash-line);
            background: #fff;
        }

        .commerce-dashboard .dashboard-hero {
            position: relative;
            overflow: hidden;
            border: 0;
            color: #fff;
            background:
                linear-gradient(135deg, rgba(17, 24, 39, .98) 0%, rgba(64, 81, 137, .96) 54%, rgba(10, 179, 156, .94) 100%);
        }

        .commerce-dashboard .dashboard-hero::after {
            position: absolute;
            inset: 0;
            content: "";
            background-image:
                linear-gradient(rgba(255, 255, 255, .08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .08) 1px, transparent 1px);
            background-size: 34px 34px;
            opacity: .22;
            pointer-events: none;
        }

        .commerce-dashboard .dashboard-hero .card-body {
            position: relative;
            z-index: 1;
            padding: 28px;
        }

        .commerce-dashboard .dashboard-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            margin-bottom: 14px;
            color: rgba(255, 255, 255, .88);
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .commerce-dashboard .dashboard-hero-title {
            max-width: 660px;
            margin-bottom: 10px;
            color: #fff;
            font-size: 32px;
            font-weight: 700;
            line-height: 1.18;
        }

        .commerce-dashboard .dashboard-hero-copy {
            max-width: 540px;
            color: rgba(255, 255, 255, .76);
            font-size: 14px;
        }

        .commerce-dashboard .dashboard-hero .btn {
            border-radius: 6px;
        }

        .commerce-dashboard .hero-insights {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .commerce-dashboard .hero-insight,
        .commerce-dashboard .hero-filter {
            padding: 15px;
            background: rgba(255, 255, 255, .13);
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 8px;
            backdrop-filter: blur(6px);
        }

        .commerce-dashboard .hero-insight span {
            display: block;
            color: rgba(255, 255, 255, .68);
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .commerce-dashboard .hero-insight strong {
            display: block;
            margin-top: 8px;
            color: #fff;
            font-size: 20px;
            line-height: 1.1;
        }

        .commerce-dashboard .hero-filter {
            margin-top: 12px;
        }

        .commerce-dashboard .dashboard-stat-card {
            position: relative;
            overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .commerce-dashboard .dashboard-stat-card::before {
            position: absolute;
            inset: 0 0 auto;
            height: 4px;
            content: "";
            background: var(--card-tone, var(--dash-primary));
        }

        .commerce-dashboard .dashboard-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 34px rgba(15, 23, 42, .12);
        }

        .commerce-dashboard .dash-tone-success { --card-tone: var(--dash-teal); }
        .commerce-dashboard .dash-tone-primary { --card-tone: var(--dash-primary); }
        .commerce-dashboard .dash-tone-info { --card-tone: #299cdb; }
        .commerce-dashboard .dash-tone-danger { --card-tone: var(--dash-red); }

        .commerce-dashboard .dashboard-stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            color: var(--card-tone, var(--dash-primary));
            background: rgba(64, 81, 137, .1);
            background: color-mix(in srgb, var(--card-tone, var(--dash-primary)) 12%, #fff);
            border-radius: 8px;
            font-size: 23px;
        }

        .commerce-dashboard .trend-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .commerce-dashboard .trend-up {
            color: #057a55;
            background: rgba(10, 179, 156, .12);
        }

        .commerce-dashboard .trend-down {
            color: #c2410c;
            background: rgba(240, 101, 72, .12);
        }

        .commerce-dashboard .dashboard-stat-label {
            margin: 18px 0 8px;
            color: var(--dash-muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .commerce-dashboard .dashboard-stat-value {
            margin-bottom: 14px;
            color: var(--dash-ink);
            font-size: 25px;
            font-weight: 700;
            line-height: 1.15;
        }

        .commerce-dashboard .dashboard-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--dash-primary);
            font-weight: 600;
        }

        .commerce-dashboard .mini-metric {
            padding: 16px;
            border-right: 1px solid var(--dash-line);
            background: var(--dash-soft);
        }

        .commerce-dashboard .mini-metric:last-child {
            border-right: 0;
        }

        .commerce-dashboard .mini-metric h5 {
            color: var(--dash-ink);
            font-size: 18px;
            font-weight: 700;
        }

        .commerce-dashboard .dashboard-map {
            height: 190px;
            margin-bottom: 16px;
            overflow: hidden;
            background: var(--dash-soft);
            border: 1px solid var(--dash-line);
            border-radius: 8px;
        }

        .commerce-dashboard .location-row {
            padding: 10px 0;
            border-top: 1px solid var(--dash-line);
        }

        .commerce-dashboard .location-row:first-child {
            border-top: 0;
        }

        .commerce-dashboard .progress {
            height: 7px;
            border-radius: 999px;
        }

        .commerce-dashboard .progress-bar {
            border-radius: inherit;
        }

        .commerce-dashboard .table-card {
            margin: 0;
        }

        .commerce-dashboard .table > :not(caption) > * > * {
            padding: 15px 14px;
            border-bottom-color: var(--dash-line);
        }

        .commerce-dashboard .table thead th {
            color: var(--dash-muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .commerce-dashboard .product-thumb {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            overflow: hidden;
            background: var(--dash-soft);
            border: 1px solid var(--dash-line);
            border-radius: 8px;
        }

        .commerce-dashboard .product-thumb img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .commerce-dashboard .seller-progress {
            width: 78px;
            min-width: 78px;
        }

        .commerce-dashboard .side-section {
            padding: 18px;
            border-top: 1px solid var(--dash-line);
        }

        .commerce-dashboard .side-section:first-child {
            border-top: 0;
        }

        .commerce-dashboard .side-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            color: var(--dash-ink);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .commerce-dashboard .category-list {
            display: grid;
            gap: 8px;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .commerce-dashboard .category-list a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            color: var(--dash-ink);
            background: var(--dash-soft);
            border: 1px solid var(--dash-line);
            border-radius: 8px;
        }

        .commerce-dashboard .category-rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            margin-right: 8px;
            color: var(--dash-primary);
            background: rgba(64, 81, 137, .1);
            border-radius: 50%;
            font-size: 12px;
            font-weight: 800;
        }

        .commerce-dashboard .review-tile,
        .commerce-dashboard .rating-summary,
        .commerce-dashboard .seller-invite {
            border: 1px solid var(--dash-line);
            border-radius: 8px;
        }

        .commerce-dashboard .review-tile {
            padding: 14px;
            background: #fff;
        }

        .commerce-dashboard .rating-summary {
            padding: 12px;
            background: var(--dash-soft);
        }

        .commerce-dashboard .seller-invite {
            margin: 4px 18px 18px;
            padding: 22px 18px;
            background: linear-gradient(135deg, rgba(64, 81, 137, .08), rgba(10, 179, 156, .12));
        }

        .commerce-dashboard .empty-state {
            padding: 30px 16px;
            color: var(--dash-muted);
            text-align: center;
        }

        @media (max-width: 991.98px) {
            .commerce-dashboard .hero-insights {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .commerce-dashboard .dashboard-hero .card-body {
                padding: 22px;
            }

            .commerce-dashboard .dashboard-hero-title {
                font-size: 25px;
            }

            .commerce-dashboard .dashboard-stat-value {
                font-size: 22px;
            }
        }
    </style>
@endsection
@section('content')
    @php
        $hour = \Carbon\Carbon::now()->hour;
        $greeting = $hour < 12 ? 'Morning' : ($hour < 18 ? 'Afternoon' : 'Evening');
        $deliveryRate = $totalOrders > 0 ? ($deliveredOrders / $totalOrders) * 100 : 0;
        $metricCards = [
            [
                'label' => 'Total Earnings',
                'value' => '৳'.number_format($totalEarnings, 2),
                'change' => $earningsChange,
                'icon' => 'ri-money-dollar-circle-line',
                'tone' => 'success',
                'url' => route('orders.index'),
                'link' => 'View earnings',
                'trend' => $earningsChange >= 0 ? 'trend-up' : 'trend-down',
            ],
            [
                'label' => 'Orders',
                'value' => number_format($totalOrders),
                'change' => $ordersChange,
                'icon' => 'ri-shopping-bag-3-line',
                'tone' => 'primary',
                'url' => route('orders.index'),
                'link' => 'View orders',
                'trend' => $ordersChange >= 0 ? 'trend-up' : 'trend-down',
            ],
            [
                'label' => 'Delivered',
                'value' => number_format($deliveredOrders),
                'change' => $deliveredChange,
                'icon' => 'ri-checkbox-circle-line',
                'tone' => 'info',
                'url' => route('orders.delivered'),
                'link' => 'See delivered',
                'trend' => $deliveredChange >= 0 ? 'trend-up' : 'trend-down',
            ],
            [
                'label' => 'Cancelled',
                'value' => number_format($cancelledOrders),
                'change' => $cancelledChange,
                'icon' => 'ri-close-circle-line',
                'tone' => 'danger',
                'url' => route('orders.cancelled'),
                'link' => 'Review cancelled',
                'trend' => $cancelledChange <= 0 ? 'trend-up' : 'trend-down',
            ],
        ];
        $spotlightMetrics = [
            ['label' => 'Net Balance', 'value' => '৳'.number_format($myBalance, 2), 'change' => $balanceChange],
            ['label' => 'Customers', 'value' => number_format($totalCustomers), 'change' => $customersChange],
            ['label' => 'Delivery Rate', 'value' => number_format($deliveryRate, 1).'%', 'change' => $deliveredChange],
        ];
    @endphp

    <div class="commerce-dashboard">
    <div class="row">
        <div class="col">

            <div class="h-100">
                <div class="row">
                    <div class="col-12">
                        <div class="card dashboard-hero mb-3">
                            <div class="card-body">
                                <div class="row align-items-center g-4">
                                    <div class="col-xl-6">
                                        <span class="dashboard-eyebrow">
                                            <i class="ri-dashboard-3-line"></i>
                                            Store Dashboard
                                        </span>
                                        <h1 class="dashboard-hero-title">Good {{ $greeting }}, {{ $userName }}!</h1>
                                        <p class="dashboard-hero-copy mb-4">Here's what's happening with your store today.</p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="{{ route('orders.index') }}" class="btn btn-light shadow-none">
                                                <i class="ri-file-list-3-line align-middle me-1"></i> View Orders
                                            </a>
                                            <a href="{{ route('products.create') }}" class="btn btn-success shadow-none">
                                                <i class="ri-add-circle-line align-middle me-1"></i> Add Product
                                            </a>
                                            <button type="button" class="btn btn-outline-light layout-rightside-btn shadow-none">
                                                <i class="ri-pulse-line align-middle me-1"></i> Activity
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <div class="hero-insights">
                                            @foreach($spotlightMetrics as $metric)
                                                <div class="hero-insight">
                                                    <span>{{ $metric['label'] }}</span>
                                                    <strong>{{ $metric['value'] }}</strong>
                                                    <small class="d-inline-flex align-items-center gap-1 mt-2 {{ $metric['change'] >= 0 ? 'text-white' : 'text-warning' }}">
                                                        <i class="ri-arrow-right-{{ $metric['change'] >= 0 ? 'up' : 'down' }}-line"></i>
                                                        {{ $metric['change'] >= 0 ? '+' : '' }}{{ number_format($metric['change'], 2) }}%
                                                    </small>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="hero-filter">
                                            <form action="javascript:void(0);">
                                                <div class="input-group">
                                                    <input type="text"
                                                           class="form-control border-0 dash-filter-picker"
                                                           data-provider="flatpickr" data-range-date="true"
                                                           data-date-format="d M, Y"
                                                           data-deafult-date="01 Jan 2022 to 31 Jan 2022">
                                                    <div class="input-group-text bg-white border-0 text-primary">
                                                        <i class="ri-calendar-2-line"></i>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    @foreach($metricCards as $card)
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-animate dashboard-stat-card dash-tone-{{ $card['tone'] }} h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between gap-3">
                                        <span class="dashboard-stat-icon">
                                            <i class="{{ $card['icon'] }}"></i>
                                        </span>
                                        <span class="trend-pill {{ $card['trend'] }}">
                                            <i class="ri-arrow-right-{{ $card['change'] >= 0 ? 'up' : 'down' }}-line"></i>
                                            {{ $card['change'] >= 0 ? '+' : '' }}{{ number_format($card['change'], 2) }}%
                                        </span>
                                    </div>
                                    <p class="dashboard-stat-label text-truncate">{{ $card['label'] }}</p>
                                    <h3 class="dashboard-stat-value">{{ $card['value'] }}</h3>
                                    <a href="{{ $card['url'] }}" class="dashboard-link">
                                        {{ $card['link'] }}
                                        <i class="ri-arrow-right-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div> <!-- end row-->

                <div class="row g-3 mb-3">
                    <div class="col-xl-8">
                        <div class="card h-100">
                            <div class="card-header align-items-center d-flex flex-wrap gap-2">
                                <div class="flex-grow-1">
                                    <h4 class="card-title mb-1">Revenue</h4>
                                    <p class="text-muted mb-0">Orders, earnings and refunds over the last 12 months</p>
                                </div>
                                <span class="badge bg-primary-subtle text-primary fs-12">12 month view</span>
                            </div><!-- end card header -->

                            <div class="card-header p-0 border-0">
                                <div class="row g-0 text-center">
                                    <div class="col-6 col-sm-3">
                                        <div class="mini-metric">
                                            <h5 class="mb-1"><span class="counter-value" data-target="{{ $revenueOrders }}">{{ number_format($revenueOrders) }}</span>
                                            </h5>
                                            <p class="text-muted mb-0">Orders</p>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-6 col-sm-3">
                                        <div class="mini-metric">
                                            <h5 class="mb-1">৳<span class="counter-value" data-target="{{ round($revenueEarnings / 1000, 2) }}">{{ number_format($revenueEarnings / 1000, 2) }}</span>k
                                            </h5>
                                            <p class="text-muted mb-0">Earnings</p>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-6 col-sm-3">
                                        <div class="mini-metric">
                                            <h5 class="mb-1"><span class="counter-value" data-target="{{ $revenueRefunds }}">{{ number_format($revenueRefunds) }}</span>
                                            </h5>
                                            <p class="text-muted mb-0">Refunds</p>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-6 col-sm-3">
                                        <div class="mini-metric">
                                            <h5 class="mb-1 text-success"><span class="counter-value"
                                                                                data-target="{{ round($conversionRatio, 2) }}">{{ number_format($conversionRatio, 2) }}</span>%</h5>
                                            <p class="text-muted mb-0">Conversion Ratio</p>
                                        </div>
                                    </div>
                                    <!--end col-->
                                </div>
                            </div><!-- end card header -->

                            <div class="card-body p-0 pb-2">
                                <div class="w-100">
                                    <div id="customer_impression_charts"
                                         data-colors='["#0ab39c", "#405189", "#f06548"]' class="apex-charts"
                                         dir="ltr"></div>
                                </div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div><!-- end col -->

                    <div class="col-xl-4">
                        <div class="card card-height-100">
                            <div class="card-header align-items-center d-flex flex-wrap gap-2">
                                <div class="flex-grow-1">
                                    <h4 class="card-title mb-1">Sales by Locations</h4>
                                    <p class="text-muted mb-0">Top districts by order count</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <a href="{{ route('total-order-report.index') }}" class="btn btn-soft-primary btn-sm shadow-none">
                                        <i class="ri-file-list-3-line align-middle me-1"></i> Report
                                    </a>
                                </div>
                            </div><!-- end card header -->

                            <div class="card-body">
                                <div id="sales-by-locations"
                                     class="dashboard-map"
                                     data-colors='["#e5eef9", "#0ab39c", "#f7b84b"]'
                                     dir="ltr"></div>
                                <div>
                                    @if($salesByLocation->count() > 0)
                                        @php
                                            $maxCount = $salesByLocation->isNotEmpty() ? $salesByLocation->max('count') : 1;
                                        @endphp
                                        @foreach($salesByLocation as $location)
                                            @php
                                                $percentage = $maxCount > 0 ? ($location->count / $maxCount) * 100 : 0;
                                            @endphp
                                            <div class="location-row">
                                                <div class="d-flex justify-content-between gap-3 mb-1">
                                                    <h6 class="mb-0 text-truncate">{{ $location->district ?? 'Unknown' }}</h6>
                                                    <span class="fw-semibold">{{ number_format($location->count) }} orders</span>
                                                </div>
                                                <p class="text-muted fs-12 mb-2">৳{{ number_format($location->total_sales ?? 0, 2) }} sales</p>
                                                <div class="progress bg-primary-subtle">
                                                    <div class="progress-bar bg-primary" role="progressbar"
                                                     style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="empty-state">
                                            <i class="ri-map-pin-line d-block fs-1 mb-2"></i>
                                            No location data available
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end col -->
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-xl-6">
                        <div class="card h-100">
                            <div class="card-header align-items-center d-flex flex-wrap gap-2">
                                <div class="flex-grow-1">
                                    <h4 class="card-title mb-1">Best Selling Products</h4>
                                    <p class="text-muted mb-0">Products moving fastest by order quantity</p>
                                </div>
                                <a href="{{ route('products.index') }}" class="btn btn-soft-primary btn-sm shadow-none">
                                    <i class="ri-store-2-line align-middle me-1"></i> Products
                                </a>
                            </div><!-- end card header -->

                            <div class="card-body p-0">
                                <div class="table-responsive table-card">
                                    <table class="table table-hover table-centered align-middle table-nowrap mb-0">
                                        <thead class="table-light text-muted">
                                        <tr>
                                            <th scope="col">Product</th>
                                            <th scope="col">Price</th>
                                            <th scope="col">Orders</th>
                                            <th scope="col">Stock</th>
                                            <th scope="col">Amount</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($bestSellingProducts as $product)
                                            @php
                                                $totalSold = $product->total_sold ?? $product->num_of_sale ?? 0;
                                                $totalAmount = $product->price * $totalSold;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="product-thumb me-3">
                                                            <img src="{{ $product->thumbnail_image ? api_asset($product->thumbnail_image) : URL::asset('build/images/products/img-1.png') }}"
                                                                 alt="{{ $product->name }}" />
                                                        </div>
                                                        <div class="min-w-0">
                                                            <h5 class="fs-14 my-1"><a
                                                                    href="{{ route('products.show', $product->id) }}"
                                                                    class="text-reset">{{ $product->name }}</a></h5>
                                                            <span class="text-muted">{{ $product->created_at->format('d M Y') }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <h5 class="fs-14 my-1 fw-normal">৳{{ number_format($product->price, 2) }}</h5>
                                                    <span class="text-muted">Price</span>
                                                </td>
                                                <td>
                                                    <h5 class="fs-14 my-1 fw-normal">{{ $totalSold }}</h5>
                                                    <span class="text-muted">Orders</span>
                                                </td>
                                                <td>
                                                    @if($product->stock_status === 'out_of_stock' || $product->quantity <= 0)
                                                        <h5 class="fs-14 my-1 fw-normal"><span
                                                                class="badge bg-danger-subtle text-danger">Out of
                                                                    stock</span></h5>
                                                    @else
                                                        <h5 class="fs-14 my-1 fw-normal">{{ number_format($product->quantity) }}</h5>
                                                    @endif
                                                    <span class="text-muted">Stock</span>
                                                </td>
                                                <td>
                                                    <h5 class="fs-14 my-1 fw-normal">৳{{ number_format($totalAmount, 2) }}</h5>
                                                    <span class="text-muted">Amount</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5">
                                                    <div class="empty-state">
                                                        <i class="ri-shopping-bag-3-line d-block fs-1 mb-2"></i>
                                                        No products found
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-3 py-3 border-top">
                                    <div class="text-muted">
                                        Showing <span class="fw-semibold">{{ $bestSellingProducts->count() }}</span> products
                                    </div>
                                    <a href="{{ route('products.index') }}" class="dashboard-link">
                                        Open products <i class="ri-arrow-right-line"></i>
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card card-height-100">
                            <div class="card-header align-items-center d-flex flex-wrap gap-2">
                                <div class="flex-grow-1">
                                    <h4 class="card-title mb-1">Top Sellers</h4>
                                    <p class="text-muted mb-0">Revenue contribution from leading products</p>
                                </div>
                                <a href="{{ route('products.index') }}" class="btn btn-soft-primary btn-sm shadow-none">
                                    <i class="ri-bar-chart-2-line align-middle me-1"></i> View
                                </a>
                            </div><!-- end card header -->

                            <div class="card-body p-0">
                                <div class="table-responsive table-card">
                                    <table class="table table-centered table-hover align-middle table-nowrap mb-0">
                                        <thead class="table-light text-muted">
                                        <tr>
                                            <th scope="col">Product</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Stock</th>
                                            <th scope="col">Revenue</th>
                                            <th scope="col">Share</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($bestSellingProducts->take(5) as $index => $product)
                                            @php
                                                $totalSold = $product->total_sold ?? $product->num_of_sale ?? 0;
                                                $totalRevenue = $product->price * $totalSold;
                                                $maxRevenue = $bestSellingProducts->isNotEmpty() ? $bestSellingProducts->max(function($p) {
                                                    $sold = $p->total_sold ?? $p->num_of_sale ?? 0;
                                                    return $p->price * $sold;
                                                }) : 1;
                                                $percentage = $maxRevenue > 0 ? ($totalRevenue / $maxRevenue) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="product-thumb me-3">
                                                            <img src="{{ $product->thumbnail_image ? api_asset($product->thumbnail_image) : URL::asset('build/images/companies/img-' . ($index + 1) . '.png') }}"
                                                                 alt="{{ $product->name }}" />
                                                        </div>
                                                        <div class="min-w-0">
                                                            <h5 class="fs-14 my-1 fw-medium"><a
                                                                    href="{{ route('products.show', $product->id) }}"
                                                                    class="text-reset">{{ $product->name }}</a>
                                                            </h5>
                                                            <span class="text-muted">{{ $product->category->name ?? 'Uncategorized' }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ $product->category->name ?? 'Uncategorized' }}</span>
                                                </td>
                                                <td>
                                                    <p class="mb-0">{{ number_format($product->quantity) }}</p>
                                                    <span class="text-muted">Stock</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted">৳{{ number_format($totalRevenue, 2) }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress bg-success-subtle seller-progress">
                                                            <div class="progress-bar bg-success" role="progressbar"
                                                                 style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <span class="fw-semibold">{{ number_format($percentage, 0) }}%</span>
                                                    </div>
                                                </td>
                                            </tr><!-- end -->
                                        @empty
                                            <tr>
                                                <td colspan="5">
                                                    <div class="empty-state">
                                                        <i class="ri-bar-chart-box-line d-block fs-1 mb-2"></i>
                                                        No products found
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table><!-- end table -->
                                </div>

                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-3 py-3 border-top">
                                    <div class="text-muted">
                                        Showing <span class="fw-semibold">{{ min(5, $bestSellingProducts->count()) }}</span> top sellers
                                    </div>
                                    <a href="{{ route('products.index') }}" class="dashboard-link">
                                        Open products <i class="ri-arrow-right-line"></i>
                                    </a>
                                </div>

                            </div> <!-- .card-body-->
                        </div> <!-- .card-->
                    </div> <!-- .col-->
                </div> <!-- end row-->

                <div class="row g-3">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex flex-wrap gap-2">
                                <div class="flex-grow-1">
                                    <h4 class="card-title mb-1">Recent Orders</h4>
                                    <p class="text-muted mb-0">Latest customer orders and payment status</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('orders.index') }}" class="btn btn-soft-primary btn-sm shadow-none">
                                        <i class="ri-list-check-2 align-middle me-1"></i> Orders
                                    </a>
                                    <a href="{{ route('total-order-report.index') }}" class="btn btn-soft-info btn-sm shadow-none">
                                        <i class="ri-file-list-3-line align-middle me-1"></i> Report
                                    </a>
                                </div>
                            </div><!-- end card header -->

                            <div class="card-body p-0">
                                <div class="table-responsive table-card">
                                    <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                                        <thead class="text-muted table-light">
                                        <tr>
                                            <th scope="col">Order ID</th>
                                            <th scope="col">Customer</th>
                                            <th scope="col">Product</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Payment Method</th>
                                            <th scope="col">Payment</th>
                                            <th scope="col">Rating</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($recentOrders as $order)
                                            @php
                                                $firstItem = $order->items->first();
                                                $productName = $firstItem ? $firstItem->product_name : 'N/A';
                                                $review = $order->reviews->first();
                                                $avgRating = $review ? $review->rating : 0;
                                                $reviewCount = $order->reviews->count();
                                            @endphp
                                            <tr>
                                                <td>
                                                    @can('orders.details')
                                                        <a href="{{ route('orders.view', ['order' => $order->order_number, 'return_url' => route('orders.index')]) }}"
                                                           class="fw-medium link-primary">#{{ $order->order_number }}</a>
                                                    @else
                                                        <span class="fw-medium">#{{ $order->order_number }}</span>
                                                    @endcan
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0 me-2">
                                                            <img src="{{ $order->user && $order->user->avatar ? api_asset($order->user->avatar) : URL::asset('build/images/users/avatar-' . (($loop->index % 6) + 1) . '.jpg') }}"
                                                                 alt="{{ $order->customer_name }}" class="avatar-xs rounded-circle shadow" />
                                                        </div>
                                                        <div class="flex-grow-1">{{ $order->customer_name }}</div>
                                                    </div>
                                                </td>
                                                <td>{{ $productName }}</td>
                                                <td>
                                                    <span class="text-success">৳{{ number_format($order->total, 2) }}</span>
                                                </td>
                                                <td>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</td>
                                                <td>
                                                    @if($order->payment_status === 'paid')
                                                        <span class="badge bg-success-subtle text-success">Paid</span>
                                                    @elseif($order->payment_status === 'pending')
                                                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                                                    @elseif($order->payment_status === 'failed')
                                                        <span class="badge bg-danger-subtle text-danger">Failed</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">Unpaid</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($reviewCount > 0)
                                                        <h5 class="fs-14 fw-medium mb-0">{{ number_format($avgRating, 1) }}<span
                                                                class="text-muted fs-11 ms-1">({{ $reviewCount }}
                                                                    {{ $reviewCount === 1 ? 'vote' : 'votes' }})</span></h5>
                                                    @else
                                                        <h5 class="fs-14 fw-medium mb-0 text-muted">N/A</h5>
                                                    @endif
                                                </td>
                                            </tr><!-- end tr -->
                                        @empty
                                            <tr>
                                                <td colspan="7">
                                                    <div class="empty-state">
                                                        <i class="ri-inbox-line d-block fs-1 mb-2"></i>
                                                        No recent orders found
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody><!-- end tbody -->
                                    </table><!-- end table -->
                                </div>
                            </div>
                        </div> <!-- .card-->
                    </div> <!-- .col-->
                </div> <!-- end row-->

            </div> <!-- end .h-100-->

        </div> <!-- end col -->

        <div class="col-auto layout-rightside-col">
            <div class="overlay"></div>
            <div class="layout-rightside">
                <div class="card h-100 rounded-0">
                    <div class="card-body p-0">
                        <div class="side-section pb-0">
                            <h6 class="side-title">
                                <i class="ri-pulse-line"></i>
                                Recent Activity
                            </h6>
                        </div>
                        <div data-simplebar style="max-height: 410px;" class="px-3 pb-3">
                            <div class="acitivity-timeline acitivity-main">
                                @forelse($recentActivities as $activity)
                                    <div class="acitivity-item {{ !$loop->last ? 'py-3' : '' }} d-flex">
                                        <div class="flex-shrink-0 {{ $activity['type'] === 'order' ? 'avatar-xs acitivity-avatar' : '' }}">
                                            @if($activity['type'] === 'order' && isset($activity['data']->user) && $activity['data']->user->avatar)
                                                <img src="{{ api_asset($activity['data']->user->avatar) }}" alt=""
                                                     class="avatar-xs rounded-circle acitivity-avatar shadow" />
                                            @else
                                                <div class="avatar-xs acitivity-avatar">
                                                    <div class="avatar-title bg-{{ $activity['icon_color'] }}-subtle text-{{ $activity['icon_color'] }} rounded-circle shadow">
                                                        <i class="{{ $activity['icon'] }}"></i>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 lh-base">{{ $activity['title'] }}</h6>
                                            <p class="text-muted mb-1">{{ $activity['description'] }}</p>
                                            <small class="mb-0 text-muted">{{ $activity['time']->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state">
                                        <i class="ri-pulse-line d-block fs-1 mb-2"></i>
                                        No recent activity
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="side-section">
                            <h6 class="side-title">
                                <i class="ri-price-tag-3-line"></i>
                                Top 10 Categories
                            </h6>

                            <ol class="category-list">
                                @forelse($topCategories as $category)
                                    <li>
                                        <a href="{{ route('categories.index') }}">
                                            <span class="text-truncate">
                                                <span class="category-rank">{{ $loop->iteration }}</span>{{ $category->name }}
                                            </span>
                                            <span class="badge bg-primary-subtle text-primary">{{ number_format($category->products_count) }}</span>
                                        </a>
                                    </li>
                                @empty
                                    <li>
                                        <div class="empty-state">No categories found</div>
                                    </li>
                                @endforelse
                            </ol>
                            <div class="mt-3">
                                <a href="{{ route('categories.index') }}" class="dashboard-link">
                                    View categories <i class="ri-arrow-right-line"></i>
                                </a>
                            </div>
                        </div>
                        <div class="side-section">
                            <h6 class="side-title">
                                <i class="ri-star-smile-line"></i>
                                Product Reviews
                            </h6>
                            <div class="swiper vertical-swiper" style="height: 250px;">
                                <div class="swiper-wrapper">
                                    @forelse($productReviews as $review)
                                        <div class="swiper-slide">
                                            <div class="review-tile">
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0">
                                                        @if($review->user && $review->user->avatar)
                                                            <img src="{{ api_asset($review->user->avatar) }}"
                                                                 alt="{{ $review->user->name }}" class="avatar-sm rounded shadow">
                                                        @else
                                                            <div class="avatar-sm">
                                                                <div class="avatar-title bg-light rounded shadow">
                                                                    @if($review->product && $review->product->thumbnail_image)
                                                                        <img src="{{ api_asset($review->product->thumbnail_image) }}"
                                                                             alt="{{ $review->product->name }}" height="30">
                                                                    @else
                                                                        <i class="ri-user-line"></i>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <p class="text-muted mb-1 fst-italic text-truncate-two-lines">
                                                            "{{ $review->review_text }}"</p>
                                                        <div class="fs-11 align-middle text-warning">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                @if($i <= $review->rating)
                                                                    <i class="ri-star-fill"></i>
                                                                @elseif($i - 0.5 <= $review->rating)
                                                                    <i class="ri-star-half-fill"></i>
                                                                @else
                                                                    <i class="ri-star-line"></i>
                                                                @endif
                                                            @endfor
                                                        </div>
                                                        <div class="text-end mb-0 text-muted">
                                                            by <cite title="Source Title">{{ $review->user->name ?? 'Anonymous' }}</cite>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="swiper-slide">
                                            <div class="review-tile">
                                                <div class="empty-state">
                                                    <i class="ri-star-line d-block fs-1 mb-2"></i>
                                                    No reviews available
                                                </div>
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="side-section">
                            <h6 class="side-title">
                                <i class="ri-chat-smile-3-line"></i>
                                Customer Reviews
                            </h6>
                            <div class="rating-summary mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="fs-16 align-middle text-warning">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= floor($avgRating))
                                                    <i class="ri-star-fill"></i>
                                                @elseif($i - 0.5 <= $avgRating)
                                                    <i class="ri-star-half-fill"></i>
                                                @else
                                                    <i class="ri-star-line"></i>
                                                @endif
                                            @endfor
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <h6 class="mb-0">{{ number_format($avgRating, 1) }} out of 5</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-muted">
                                    Total <span class="fw-medium">{{ number_format($totalReviews) }}</span>
                                    {{ $totalReviews === 1 ? 'review' : 'reviews' }}
                                </div>
                            </div>

                            <div class="mt-3">
                                @for($star = 5; $star >= 1; $star--)
                                    @php
                                        $count = $ratingDistribution[$star] ?? 0;
                                        $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                                        $colorClass = $star >= 4 ? 'success' : ($star >= 3 ? 'warning' : 'danger');
                                    @endphp
                                    <div class="row align-items-center g-2">
                                        <div class="col-auto">
                                            <div class="p-1">
                                                <h6 class="mb-0">{{ $star }} star</h6>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="p-1">
                                                <div class="progress bg-{{ $colorClass }}-subtle animated-progress progress-sm">
                                                    <div class="progress-bar bg-{{ $colorClass }}" role="progressbar"
                                                         style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0"
                                                         aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="p-1">
                                                <h6 class="mb-0 text-muted">{{ number_format($count) }} ({{ number_format($percentage, 0) }}%)</h6>
                                            </div>
                                        </div>
                                    </div>
                                    @if($star > 1)
                                        <!-- end row -->
                                    @endif
                                @endfor
                            </div>
                        </div>

                        <div class="seller-invite">
                            <h6 class="side-title">
                                <i class="ri-flashlight-line"></i>
                                Quick Actions
                            </h6>
                            <div class="d-grid gap-2">
                                <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm shadow-none">
                                    <i class="ri-add-circle-line align-middle me-1"></i> Add Product
                                </a>
                                <a href="{{ route('orders.index') }}" class="btn btn-light btn-sm shadow-none">
                                    <i class="ri-shopping-bag-3-line align-middle me-1"></i> View Orders
                                </a>
                                <a href="{{ route('total-order-report.index') }}" class="btn btn-light btn-sm shadow-none">
                                    <i class="ri-file-chart-line align-middle me-1"></i> Total Order Report
                                </a>
                            </div>
                        </div>

                    </div>
                </div> <!-- end card-->
            </div> <!-- end .rightbar-->

        </div> <!-- end col -->
    </div>
    </div>
@endsection
@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/jsvectormap/jsvectormap.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/jsvectormap/maps/world-merc.js') }}"></script>
    <script src="{{ URL::asset('build/libs/swiper/swiper-bundle.min.js') }}"></script>

    <!-- Pass dynamic chart data to JavaScript -->
    <script>
        window.revenueChartData = {
            orders: @json($ordersData ?? []),
            earnings: @json($earningsData ?? []),
            refunds: @json($refundsData ?? []),
            months: @json($monthLabels ?? [])
        };
        window.salesByLocation = @json($salesLocationMarkers ?? []);
    </script>

    <!-- dashboard init -->
    <script src="{{ URL::asset('build/js/pages/dashboard-ecommerce.init.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
