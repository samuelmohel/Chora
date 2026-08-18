@extends('layouts.admin')
@section('page-title')
    {{ __('Reconciliation Details') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bank-reconciliation.index') }}">{{ __('Bank Reconciliation') }}</a></li>
    <li class="breadcrumb-item">{{ __('Details') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>{{ __('Access Bank Reconciliation Summary') }} - {{ $reconciliation->statement_date }}</h5>
                @if($reconciliation->status !== 'completed')
                    <form action="{{ route('bank-reconciliation.sign-off', $reconciliation->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm" {{ $reconciliation->difference != 0 ? 'disabled' : '' }}>
                            <i class="ti ti-check"></i> {{ __('Sign Off Reconciliation') }}
                        </button>
                    </form>
                @endif
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h6>Statement Ending Balance</h6>
                        <h4>₦{{ number_format($reconciliation->statement_ending_balance, 2) }}</h4>
                    </div>
                    <div class="col-md-3">
                        <h6>GL Book Ending Balance</h6>
                        <h4>₦{{ number_format($reconciliation->book_ending_balance, 2) }}</h4>
                    </div>
                    <div class="col-md-3">
                        <h6>Difference</h6>
                        <h4 class="text-{{ $reconciliation->difference == 0 ? 'success' : 'danger' }}">
                            ₦{{ number_format($reconciliation->difference, 2) }}
                        </h4>
                    </div>
                    <div class="col-md-3">
                        <h6>Status</h6>
                        <span class="badge bg-{{ $reconciliation->status == 'completed' ? 'success' : 'warning' }} fs-6">
                            {{ ucfirst($reconciliation->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
