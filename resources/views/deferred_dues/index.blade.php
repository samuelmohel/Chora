@extends('layouts.admin')
@section('page-title')
    {{ __('Deferred Dues Income Recognition') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Deferred Dues Release') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('deferred-dues.index') }}" method="GET" class="row align-items-center">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Select Billing Year') }}</label>
                        <select name="year" class="form-select" onchange="this.form.submit()">
                            @for($y = date('Y'); $y >= 2024; $y--)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>{{ __('Monthly Dues Revenue Recognition Schedule') }} - {{ $year }}</h5>
            </div>
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Month') }}</th>
                                <th>{{ __('Total Residents') }}</th>
                                <th>{{ __('Pending Release (₦)') }}</th>
                                <th>{{ __('Recognized Income (₦)') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($monthlySummary as $m => $sum)
                                <tr>
                                    <td><strong>{{ $sum['month_name'] }}</strong></td>
                                    <td>{{ $sum['total_residents'] }}</td>
                                    <td>₦{{ number_format($sum['pending_amount'], 2) }}</td>
                                    <td>₦{{ number_format($sum['recognized_amount'], 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $sum['status'] == 'Recognized' ? 'success' : ($sum['status'] == 'Pending Release' ? 'warning' : 'secondary') }}">
                                            {{ $sum['status'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($sum['status'] == 'Pending Release')
                                            <form action="{{ route('deferred-dues.recognize') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="year" value="{{ $year }}">
                                                <input type="hidden" name="month" value="{{ $m }}">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="ti ti-check"></i> {{ __('Recognize Dues Income') }}
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-sm btn-light" disabled>{{ __('Recognized') }}</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
