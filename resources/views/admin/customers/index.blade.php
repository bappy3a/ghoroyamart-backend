@extends('layouts.master')

@section('title', 'Customers')

@section('content')
    @php
        $statusColors = [
            'active' => 'success',
            'inactive' => 'secondary',
            'suspended' => 'warning',
            'blocked' => 'danger',
            'deleted' => 'dark',
        ];
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Customers</h4>
                    <p class="text-muted mb-0">Registered customer list with order count, registration date, and profile info.</p>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-subtle text-primary fs-12">Total: {{ number_format($totalCustomers) }}</span>
                    <span class="badge bg-success-subtle text-success fs-12">Verified: {{ number_format($verifiedCustomers) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body border border-dashed border-end-0 border-start-0">
            <form method="GET" action="{{ route('customers.index') }}">
                <div class="row g-3">
                    <div class="col-xxl-4 col-sm-6">
                        <div class="search-box">
                            <input type="text" class="form-control search" name="search" placeholder="Search name, email, phone or username..." value="{{ request('search') }}">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                    <div class="col-xxl-2 col-sm-6">
                        <input type="text" class="form-control" data-provider="flatpickr" data-date-format="d M, Y" data-range-date="true" name="date_range" placeholder="Registration date" value="{{ request('date_range') }}">
                    </div>
                    <div class="col-xxl-2 col-sm-4">
                        <select class="form-control" name="status">
                            <option value="all" @selected(request('status', 'all') === 'all')>All Status</option>
                            @foreach($statusColors as $status => $color)
                                <option value="{{ $status }}" @selected(request('status') === $status)>
                                    {{ ucfirst($status) }} ({{ number_format($statusCounts[$status] ?? 0) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xxl-2 col-sm-4">
                        <select class="form-control" name="verified">
                            <option value="all" @selected(request('verified', 'all') === 'all')>All Verification</option>
                            <option value="verified" @selected(request('verified') === 'verified')>Phone Verified</option>
                            <option value="unverified" @selected(request('verified') === 'unverified')>Phone Unverified</option>
                        </select>
                    </div>
                    <div class="col-xxl-2 col-sm-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-equalizer-fill me-1 align-bottom"></i>
                            Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body pt-0">
            <div class="table-responsive table-card mb-1">
                <table class="table table-nowrap align-middle mb-0">
                    <thead class="table-light text-muted text-uppercase">
                        <tr>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th class="text-center">Order Count</th>
                            <th>Total Spend</th>
                            <th>Reg Date</th>
                            <th>Latest Order</th>
                            <th>Other Info</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            @php
                                $statusColor = $statusColors[$customer->status] ?? 'secondary';
                                $address = $customer->defaultAddress;
                                $location = collect([
                                    $address?->deliveryArea?->name,
                                    $address?->deliveryArea?->district_name,
                                ])->filter()->unique()->implode(', ');
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($customer->avatar)
                                            <img
                                                src="{{ api_asset($customer->avatar) }}"
                                                alt="{{ $customer->name }}"
                                                class="rounded-circle border"
                                                style="height: 44px; width: 44px; object-fit: cover;"
                                            >
                                        @else
                                            <div
                                                class="bg-light border rounded-circle d-flex align-items-center justify-content-center text-muted"
                                                style="height: 44px; width: 44px;"
                                            >
                                                <i class="ri-user-line"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $customer->name }}</div>
                                            <div class="text-muted small">{{ $customer->username ? '@'.$customer->username : 'Customer ID: '.$customer->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $customer->phone ?: '-' }}</div>
                                    <div class="text-muted small">{{ $customer->email ?: '-' }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info">{{ number_format($customer->orders_count) }}</span>
                                </td>
                                <td>৳{{ number_format((float) ($customer->orders_total ?? 0), 2) }}</td>
                                <td>
                                    {{ $customer->created_at?->format('d M, Y') }}
                                    <div class="text-muted small">{{ $customer->created_at?->format('h:i A') }}</div>
                                </td>
                                <td>
                                    @if($customer->latestOrder)
                                        @can('orders.details')
                                            <a href="{{ route('orders.view', $customer->latestOrder->order_number) }}" class="fw-medium link-primary">
                                                {{ $customer->latestOrder->order_number }}
                                            </a>
                                        @else
                                            <span class="fw-medium">{{ $customer->latestOrder->order_number }}</span>
                                        @endcan
                                        <div class="text-muted small">{{ $customer->latestOrder->created_at?->format('d M, Y') }}</div>
                                    @else
                                        <span class="text-muted">No orders</span>
                                    @endif
                                </td>
                                <td style="min-width: 220px;">
                                    <div>
                                        @if($customer->phone_verified_at)
                                            <span class="badge bg-success-subtle text-success">Phone Verified</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Phone Unverified</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small mt-1">
                                        {{ $location ?: 'No default location' }}
                                    </div>
                                    @if($customer->gender || $customer->date_of_birth)
                                        <div class="text-muted small">
                                            {{ $customer->gender ? ucfirst($customer->gender) : '' }}
                                            {{ $customer->date_of_birth ? ' | DOB: '.$customer->date_of_birth : '' }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                        {{ ucfirst($customer->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @can('customers.update')
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-warning"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCustomerModal"
                                            data-customer-id="{{ $customer->id }}"
                                            data-customer-name="{{ $customer->name }}"
                                            data-customer-phone="{{ $customer->phone }}"
                                            data-customer-email="{{ $customer->email }}"
                                            data-update-url="{{ route('customers.update', array_merge(request()->query(), ['customer' => $customer->id])) }}"
                                        >
                                            <i class="ri-edit-line align-bottom me-1"></i>
                                            Edit
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No customers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                {{ $customers->links() }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editCustomerForm" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="customer_id" id="edit-customer-id" value="{{ old('customer_id') }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit-customer-name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="edit-customer-name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="edit-customer-phone" class="form-label">Mobile</label>
                            <input
                                type="text"
                                class="form-control @error('phone') is-invalid @enderror"
                                id="edit-customer-phone"
                                name="phone"
                                value="{{ old('phone') }}"
                            >
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-0">
                            <label for="edit-customer-email" class="form-label">Email</label>
                            <input
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                id="edit-customer-email"
                                name="email"
                                value="{{ old('email') }}"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editModal = document.getElementById('editCustomerModal');
            const editForm = document.getElementById('editCustomerForm');
            const customerIdInput = document.getElementById('edit-customer-id');
            const nameInput = document.getElementById('edit-customer-name');
            const phoneInput = document.getElementById('edit-customer-phone');
            const emailInput = document.getElementById('edit-customer-email');
            const updateRouteTemplate = @json(route('customers.update', ['customer' => '__CUSTOMER__']));

            editModal?.addEventListener('show.bs.modal', (event) => {
                const triggerButton = event.relatedTarget;

                if (!triggerButton) {
                    return;
                }

                customerIdInput.value = triggerButton.getAttribute('data-customer-id') ?? '';
                nameInput.value = triggerButton.getAttribute('data-customer-name') ?? '';
                phoneInput.value = triggerButton.getAttribute('data-customer-phone') ?? '';
                emailInput.value = triggerButton.getAttribute('data-customer-email') ?? '';
                editForm.action = triggerButton.getAttribute('data-update-url') ?? '';
            });

            const oldCustomerId = @json(old('customer_id'));
            const bootstrapGlobal = window.bootstrap;

            if (oldCustomerId && bootstrapGlobal && editModal) {
                editForm.action = updateRouteTemplate.replace('__CUSTOMER__', oldCustomerId);
                const modal = new bootstrapGlobal.Modal(editModal);
                modal.show();
            }
        });
    </script>
@endsection
