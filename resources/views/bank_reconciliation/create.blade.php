@extends('layouts.admin')
@section('page-title')
    {{ __('New Bank Reconciliation') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bank-reconciliation.index') }}">{{ __('Bank Reconciliation') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>{{ __('Create Access Bank Statement Reconciliation') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('bank-reconciliation.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Select Bank Account') }}</label>
                        <select name="bank_account_id" class="form-select" required>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_number }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Statement Ending Date') }}</label>
                        <input type="date" name="statement_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Access Bank Statement Ending Balance (₦)') }}</label>
                        <input type="number" step="0.01" name="statement_ending_balance" class="form-control" placeholder="e.g. 12420000.00" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('General Ledger Book Ending Balance (₦)') }}</label>
                        <input type="number" step="0.01" name="book_ending_balance" class="form-control" placeholder="e.g. 12420000.00" required>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('bank-reconciliation.index') }}" class="btn btn-secondary me-2">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Create Reconciliation Draft') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
