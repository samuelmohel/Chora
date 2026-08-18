@extends('layouts.admin')
@section('page-title')
    {{ __('Resident Account Statement') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Resident Statement') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5>{{ $customer->name }} ({{ $customer->property_account_id ?? $customer->customer_id }})</h5>
                    <small class="text-muted">{{ __('Billing Status:') }} {{ ucfirst($customer->billing_status) }} | {{ __('Occupancy:') }} {{ ucfirst($customer->occupancy_status) }}</small>
                </div>
                <button onclick="window.print()" class="btn btn-sm btn-secondary"><i class="ti ti-printer"></i> {{ __('Print Statement') }}</button>
            </div>
            <div class="card-body">
                <div class="row text-center mb-4">
                    <div class="col-md-2">
                        <h6>Annual Billed</h6>
                        <h5>₦{{ number_format($annualAmount, 2) }}</h5>
                    </div>
                    <div class="col-md-2">
                        <h6>Total Payments</h6>
                        <h5 class="text-success">₦{{ number_format($totalPayments, 2) }}</h5>
                    </div>
                    <div class="col-md-3">
                        <h6>Total Annual Balance</h6>
                        <h5>₦{{ number_format($totalOutstanding, 2) }}</h5>
                    </div>
                    <div class="col-md-3">
                        <h6>Overdue Balance</h6>
                        <h5 class="text-danger">₦{{ number_format($overdueBalance, 2) }}</h5>
                    </div>
                    <div class="col-md-2">
                        <h6>Future Instalments</h6>
                        <h5 class="text-info">₦{{ number_format($futureInstalments, 2) }}</h5>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Debit (₦)') }}</th>
                                <th>{{ __('Credit (₦)') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($journalItems as $item)
                                <tr>
                                    <td>{{ $item->journalEntry?->date }}</td>
                                    <td>{{ $item->journalEntry?->reference }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ $item->debit > 0 ? '₦' . number_format($item->debit, 2) : '-' }}</td>
                                    <td>{{ $item->credit > 0 ? '₦' . number_format($item->credit, 2) : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">{{ __('No transaction history found for this resident.') }}</td>
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
