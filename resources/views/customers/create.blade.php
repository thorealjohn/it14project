@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-6 fw-bold text-primary">
            <i class="bi bi-person-plus me-2"></i>Add New Customer
        </h1>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Customers
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex">
                <div class="me-3">
                    <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                </div>
                <div>
                    <h5 class="alert-heading">Please fix the following errors:</h5>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('customers.store') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address (Optional)</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="customer@example.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Email address for notifications and order confirmations</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control @error('house_number') is-invalid @enderror" name="house_number" placeholder="Blk/Lot/Unit" value="{{ old('house_number') }}" required>
                                    @error('house_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control @error('street') is-invalid @enderror" name="street" placeholder="Street" value="{{ old('street') }}" required>
                                    @error('street') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control @error('barangay') is-invalid @enderror" name="barangay" placeholder="Barangay" value="{{ old('barangay') }}" required>
                                    @error('barangay') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" placeholder="City/Municipality" value="{{ old('city') }}" required>
                                    @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control @error('province') is-invalid @enderror" name="province" placeholder="Province" value="{{ old('province') }}" required>
                                    @error('province') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control @error('postal_code') is-invalid @enderror" name="postal_code" placeholder="Postal Code (optional)" value="{{ old('postal_code') }}">
                                    @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_regular" name="is_regular" value="1" {{ old('is_regular') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_regular">
                                    Regular Customer
                                </label>
                            </div>
                            <div class="form-text">Regular customers are those who order on a consistent basis.</div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-person-plus-fill me-2"></i>Create Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-lg-4 mt-4 mt-lg-0">
            <div class="card shadow-sm bg-light">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle me-2"></i>Customer Information
                    </h5>
                    <ul class="card-text">
                        <li>Regular customers may receive special offers or priority service.</li>
                        <li>Ensure phone numbers are accurate for delivery coordination.</li>
                        <li>Detailed addresses help delivery personnel locate customers efficiently.</li>
                        <li>Notes are helpful for special instructions or preferences.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection