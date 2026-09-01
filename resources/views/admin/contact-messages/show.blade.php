@extends('layouts.master')
@section('title', 'View Contact Message')
@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Message from {{ $contactMessage->name }}</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">{{ $contactMessage->email ?: '-' }}</dd>
                    <dt class="col-sm-3">Phone</dt>
                    <dd class="col-sm-9">{{ $contactMessage->phone ?: '-' }}</dd>
                    <dt class="col-sm-3">Subject</dt>
                    <dd class="col-sm-9">{{ $contactMessage->subject ?: '-' }}</dd>
                    <dt class="col-sm-3">Message</dt>
                    <dd class="col-sm-9" style="white-space: pre-wrap;">{{ $contactMessage->message }}</dd>
                    <dt class="col-sm-3">IP Address</dt>
                    <dd class="col-sm-9">{{ $contactMessage->ip_address ?: '-' }}</dd>
                    <dt class="col-sm-3">User Agent</dt>
                    <dd class="col-sm-9">{{ $contactMessage->user_agent ?: '-' }}</dd>
                    <dt class="col-sm-3">Referer</dt>
                    <dd class="col-sm-9">{{ $contactMessage->referer ?: '-' }}</dd>
                    <dt class="col-sm-3">Submitted</dt>
                    <dd class="col-sm-9">{{ $contactMessage->created_at?->format('d M, Y h:i A') }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Status</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('contact-messages.status', $contactMessage) }}">
                    @csrf
                    @method('PUT')
                    <select name="status" class="form-select mb-3">
                        <option value="pending" @selected($contactMessage->status === 'pending')>Pending</option>
                        <option value="approved" @selected($contactMessage->status === 'approved')>Approved</option>
                        <option value="rejected" @selected($contactMessage->status === 'rejected')>Rejected</option>
                    </select>
                    <button type="submit" class="btn btn-primary w-100">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
