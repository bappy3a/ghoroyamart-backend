@extends('layouts.master')

@section('title', 'Staff Dashboard')

@section('content')
    <div class="row mb-3 pb-1 align-items-center">
        <div class="col-12">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                <div>
                    <h4 class="fs-16 mb-1">
                        Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 18 ? 'Afternoon' : 'Evening') }}, {{ $userName }}!
                    </h4>
                    <p class="text-muted mb-0">Here is a quick summary of the orders you created.</p>
                </div>
                @if(auth()->user()->can('moderator-order-management.create'))
                    <a href="{{ route('moderator-order-management.create') }}" class="btn btn-primary">
                        <i class="ri-add-circle-line align-middle me-1"></i>Create Order
                    </a>
                @elseif(auth()->user()->can('orders.create'))
                    <a href="{{ route('orders.create') }}" class="btn btn-primary">
                        <i class="ri-add-circle-line align-middle me-1"></i>Create Order
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        @php
            $cards = [
                ['label' => 'My Orders', 'value' => $staffStats['total'], 'color' => 'primary', 'icon' => 'ri-shopping-bag-3-line'],
                ['label' => 'Pending', 'value' => $staffStats['pending'], 'color' => 'warning', 'icon' => 'ri-time-line'],
                ['label' => 'In Progress', 'value' => $staffStats['in_progress'], 'color' => 'info', 'icon' => 'ri-truck-line'],
                ['label' => 'Delivered', 'value' => $staffStats['delivered'], 'color' => 'success', 'icon' => 'ri-checkbox-circle-line'],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0">{{ $card['label'] }}</p>
                                <h4 class="fs-22 fw-semibold mt-3 mb-0">{{ number_format($card['value']) }}</h4>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-{{ $card['color'] }}-subtle text-{{ $card['color'] }} rounded fs-3">
                                    <i class="{{ $card['icon'] }}"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="card-title mb-1">My Weekly Order Activity</h4>
                        <p class="text-muted mb-0">Orders created during the last seven days</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary fs-12">{{ $staffStats['today'] }} today</span>
                </div>
                <div class="card-body">
                    <div id="staff-weekly-orders-chart" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">My Performance</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom">
                        <div>
                            <p class="text-muted mb-1">Total Order Value</p>
                            <h5 class="mb-0">৳{{ number_format($staffStats['total_value'], 2) }}</h5>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-primary-subtle text-primary rounded fs-4">৳</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom">
                        <div>
                            <p class="text-muted mb-1">Delivered Order Value</p>
                            <h5 class="mb-0">৳{{ number_format($staffStats['delivered_value'], 2) }}</h5>
                        </div>
                        <span class="badge bg-success-subtle text-success">{{ $staffStats['completion_rate'] }}% delivered</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Cancelled Orders</p>
                            <h5 class="mb-0">{{ number_format($staffStats['cancelled']) }}</h5>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-danger-subtle text-danger rounded fs-4">
                                <i class="ri-close-circle-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title mb-0 flex-grow-1">My Recent Orders</h4>
                    @if(auth()->user()->can('moderator-order-management.create'))
                        <a href="{{ route('moderator-order-management.create') }}" class="btn btn-primary btn-sm">
                            <i class="ri-add-line align-middle me-1"></i>Create Order
                        </a>
                    @elseif(auth()->user()->can('orders.create'))
                        <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">
                            <i class="ri-add-line align-middle me-1"></i>Create Order
                        </a>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    @php
                                        $statusColor = match ($order->order_status) {
                                            'delivered' => 'success',
                                            'cancelled' => 'danger',
                                            'pending' => 'warning',
                                            'shipped' => 'primary',
                                            default => 'info',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="fw-medium">{{ $order->order_number }}</td>
                                        <td>{{ $order->customer_name }}</td>
                                        <td>{{ $order->created_at?->format('d M Y, h:i A') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} text-capitalize">
                                                {{ str_replace('_', ' ', $order->order_status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">৳{{ number_format((float) $order->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-5 text-center text-muted">
                                            <i class="ri-inbox-line d-block fs-1 mb-2"></i>
                                            You have not created any orders yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartElement = document.querySelector('#staff-weekly-orders-chart');

            if (!chartElement || typeof ApexCharts === 'undefined') {
                return;
            }

            const chartData = @json($weeklyChart);
            const chart = new ApexCharts(chartElement, {
                chart: {
                    type: 'area',
                    height: 315,
                    toolbar: { show: false },
                    zoom: { enabled: false }
                },
                series: [
                    { name: 'Orders', data: chartData.map(item => item.orders) }
                ],
                colors: ['#405189'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] }
                },
                xaxis: {
                    categories: chartData.map(item => item.label),
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                    labels: { formatter: value => Math.round(value) }
                },
                grid: { borderColor: '#e9ebec', strokeDashArray: 4 },
                tooltip: {
                    custom: function ({ dataPointIndex }) {
                        const item = chartData[dataPointIndex];
                        return `<div class="p-2"><strong>${item.orders} order${item.orders === 1 ? '' : 's'}</strong><br><span>Value: ৳${Number(item.value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></div>`;
                    }
                }
            });

            chart.render();
        });
    </script>
@endsection
