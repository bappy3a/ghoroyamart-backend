@extends('layouts.master')
@section('title', 'Contact Messages')
@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-0">
                <div class="row align-items-center gy-3">
                    <div class="col-sm">
                        <h5 class="card-title mb-0">Contact Messages</h5>
                    </div>
                </div>
            </div>
            <div class="card-body border border-dashed border-end-0 border-start-0">
                <form method="GET" action="{{ route('contact-messages.index') }}">
                    <div class="row g-3">
                        <div class="col-xxl-6 col-sm-6">
                            <div class="search-box">
                                <input type="text" class="form-control search" name="search" placeholder="Search name, email, phone, subject or message..." value="{{ request('search') }}">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-xxl-3 col-sm-4">
                            <select class="form-control" name="status">
                                <option value="">All Status</option>
                                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                                <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                            </select>
                        </div>
                        <div class="col-xxl-3 col-sm-4">
                            <button type="submit" class="btn btn-primary w-100">Filters</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive table-card mb-1">
                    <table class="table table-nowrap align-middle">
                        <thead class="table-light text-uppercase">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($messages as $message)
                                <tr>
                                    <td class="fw-medium">{{ $message->name }}</td>
                                    <td>{{ $message->email ?: '-' }}</td>
                                    <td>{{ $message->phone ?: '-' }}</td>
                                    <td>{{ $message->subject ?: '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $message->status === 'approved' ? 'success' : ($message->status === 'rejected' ? 'danger' : 'warning') }}-subtle text-{{ $message->status === 'approved' ? 'success' : ($message->status === 'rejected' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($message->status) }}
                                        </span>
                                    </td>
                                    <td style="max-width: 280px;">
                                        <div class="text-truncate">{{ $message->message }}</div>
                                    </td>
                                    <td>{{ $message->created_at?->format('d M, Y h:i A') }}</td>
                                    <td>
                                        <a href="{{ route('contact-messages.show', $message) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No contact messages found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end">
                    {{ $messages->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
