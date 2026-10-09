@extends('layouts.master')

@section('title', 'Product Import / Export')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="mb-3">
                <h4 class="mb-0">Product Import / Export</h4>
                <p class="text-muted mb-0">Add products with their variants and prices in bulk using an Excel file.</p>
            </div>
        </div>
    </div>

    @php($importErrors = session('import_errors'))
    @if ($importErrors)
        <div class="alert alert-danger">
            <h5 class="alert-heading mb-2">Import failed — nothing was saved</h5>
            <p class="mb-2">Fix the following {{ count($importErrors) }} row(s) in your file and upload it again.</p>
            <div style="max-height: 320px; overflow:auto;">
                <table class="table table-sm table-borderless mb-0">
                    <thead><tr><th style="width:90px;">Excel row</th><th>Problem</th></tr></thead>
                    <tbody>
                        @foreach ($importErrors as $line => $messages)
                            <tr>
                                <td class="fw-semibold">{{ $line ?: '-' }}</td>
                                <td>@foreach ($messages as $message)<div>{{ $message }}</div>@endforeach</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">1. Download example file</h5>
                    <p class="text-muted">The template contains sample products (simple and with variants), a Reference sheet with valid categories, brands, units and variant values, and instructions.</p>
                    <a href="{{ route('products.import-template') }}" class="btn btn-outline-primary">
                        <i class="ri-download-2-line align-middle me-1"></i> Download Template
                    </a>
                    <a href="{{ route('products.export') }}" class="btn btn-outline-success ms-2">
                        <i class="ri-file-excel-2-line align-middle me-1"></i> Export All Products
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mt-3 mt-lg-0">
            <div class="card h-100">
                <div class="card-body">
                    @can('products.create')
                        <h5 class="card-title">2. Upload filled file</h5>
                        <form action="{{ route('products.import-create') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx" required>
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <p class="text-muted small mt-2">Only .xlsx, max 10MB. The whole file is checked first; if any row is wrong, nothing is imported.</p>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-upload-2-line align-middle me-1"></i> Import Products
                            </button>
                        </form>
                    @else
                        <p class="text-muted mb-0">You do not have permission to import products.</p>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection
