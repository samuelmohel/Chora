@extends('layouts.admin')
@section('page-title')
    {{ __('Annual Dues Billing Manager') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Annual Dues Billing') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>{{ __('Post Annual Billing Batch') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('annual-billing.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Financial Billing Year') }}</label>
                        <input type="number" name="year" class="form-control" value="{{ $currentYear }}" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Annual Charge per Resident (₦)') }}</label>
                        <input type="number" name="annual_amount" class="form-control" value="96000.00" step="0.01" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Monthly Instalment Amount (₦)') }}</label>
                        <input type="number" name="monthly_amount" class="form-control" value="8000.00" step="0.01" required>
                    </div>

                    <div class="alert alert-info py-2">
                        <small><i class="ti ti-info-circle"></i> {{ __('Liable Residents Count:') }} <strong>{{ $liableResidents }}</strong> accounts</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" {{ $isCurrentYearBilled ? 'disabled' : '' }}>
                        <i class="ti ti-check"></i> {{ $isCurrentYearBilled ? __('Year ' . $currentYear . ' Already Billed') : __('Post Annual Dues Batch') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>{{ __('Annual Billing History') }}</h5>
            </div>
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table datatable">
                        <thead>
                            <tr>
                                <th>{{ __('Year') }}</th>
                                <th>{{ __('Liable Residents') }}</th>
                                <th>{{ __('Annual Charge') }}</th>
                                <th>{{ __('Total Billed') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Posted Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($billings as $b)
                                <tr>
                                    <td><strong>{{ $b->year }}</strong></td>
                                    <td>{{ $b->total_liable_residents }}</td>
                                    <td>₦{{ number_format($b->annual_amount, 2) }}</td>
                                    <td>₦{{ number_format($b->total_billed_amount, 2) }}</td>
                                    <td><span class="badge bg-success">{{ ucfirst($b->status) }}</span></td>
                                    <td>{{ $b->posted_at }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">{{ __('No annual billing batches posted yet.') }}</td>
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
