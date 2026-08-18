@extends('layouts.admin')
@section('page-title')
    {{ __('Access Bank Reconciliation') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Access Bank Reconciliation') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        <a href="{{ route('bank-reconciliation.create') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Create Reconciliation Draft') }}">
            <i class="ti ti-plus"></i> {{ __('Create Reconciliation') }}
        </a>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>{{ __('Access Bank Reconciliation Statements') }}</h5>
            </div>
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table datatable">
                        <thead>
                            <tr>
                                <th>{{ __('Statement Date') }}</th>
                                <th>{{ __('Statement Ending Balance') }}</th>
                                <th>{{ __('Book Ending Balance') }}</th>
                                <th>{{ __('Difference') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Prepared By') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reconciliations as $rec)
                                <tr>
                                    <td>{{ $rec->statement_date }}</td>
                                    <td>₦{{ number_format($rec->statement_ending_balance, 2) }}</td>
                                    <td>₦{{ number_format($rec->book_ending_balance, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $rec->difference == 0 ? 'success' : 'danger' }}">
                                            ₦{{ number_format($rec->difference, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $rec->status == 'completed' ? 'success' : 'warning' }}">
                                            {{ ucfirst($rec->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $rec->preparedBy?->name ?? 'System' }}</td>
                                    <td>
                                        <a href="{{ route('bank-reconciliation.show', $rec->id) }}" class="btn btn-sm btn-info">
                                            <i class="ti ti-eye"></i> {{ __('View Details') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">{{ __('No bank reconciliation records found.') }}</td>
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
