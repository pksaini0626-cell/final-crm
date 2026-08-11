@extends('layouts.app')

@section('content')
<div class="container text-center py-5">
    <div class="card bg-dark border-secondary shadow-lg mx-auto p-4" style="max-width: 500px;">
        <div class="card-body">
            <div class="display-1 fw-bold text-warning mb-3">
                <i class="bi bi-exclamation-triangle"></i> 404
            </div>
            <h2 class="h4 fw-bold text-white mb-2">Page Not Found</h2>
            <p class="text-secondary mb-4">
                The requested page or resource could not be found or has been moved.
            </p>
            <a href="{{ route('bookings.index') }}" class="btn btn-primary fw-semibold px-4 py-2">
                <i class="bi bi-house-door me-1"></i> Return to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
