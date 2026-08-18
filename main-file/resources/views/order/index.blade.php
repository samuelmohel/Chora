@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Order') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Order') }}</li>
@endsection
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Order Id') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Plan Name') }}</th>
                                    <th>{{ __('Price') }}</th>
                                    <th>{{ __('Payment Type') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Coupon') }}</th>
                                    <th class="text-center">{{ __('Invoice') }}</th>
                                    @if (\Auth::user()->type == 'super admin')
                                        <th>{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $path = \App\Models\Utility::get_file('/uploads/bank_receipt');
                                    $admin = \App\Models\Utility::getAdminPaymentSetting();
                                    $currency_symbol = $admin['currency_symbol'] ?? '$';
                                @endphp
                                @foreach ($orders as $order)
                                    <tr>
                                        <td>{{ $order->order_id }}</td>
                                        <td>{{ $order->created_at->format('d M Y') }}</td>
                                        <td>{{ $order->user_name }}</td>
                                        <td>{{ $order->plan_name }}</td>
                                        <td>{{ $currency_symbol. $order->price }}</td>
                                        <td>{{ $order->payment_type }}</td>
                                        <td>
                                            @if ($order->payment_status == 'succeeded')
                                                <i class="badge bg-success p-2 px-3 rounded"></i>
                                                {{ ucfirst($order->payment_status) }}
                                            @else
                                                <i class="badge bg-danger p-2 px-3 rounded"></i>
                                                {{ ucfirst($order->payment_status) }}
                                            @endif
                                        </td>

                                        <td>{{ !empty($order->total_coupon_used) ? (!empty($order->total_coupon_used->coupon_detail) ? $order->total_coupon_used->coupon_detail->code : '-') : '-' }}
                                        </td>

                                        <td class="text-center">
                                            @if ($order->receipt != 'free coupon' && $order->payment_type == 'STRIPE')
                                                <a href="{{ $order->receipt }}" title="Invoice" target="_blank"
                                                    class="">
                                                    <i class="ti ti-file-invoice"></i>
                                                </a>
                                            @elseif($order->receipt == 'free coupon')
                                                <p>{{ __('Used 100 % discount coupon code.') }}</p>
                                            @elseif($order->payment_type == 'Manually')
                                                <p>{{ __('Manually plan upgraded by super admin') }}</p>
                                            @elseif(!empty($order->receipt))
                                                <a href="{{ $path . '/' . $order->receipt }}" target="_blank">
                                                    <i class="ti ti-file"></i></a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @if (\Auth::user()->type == 'super admin')
                                            <td>
                                                @if ($order->payment_status == 'pending' && $order->payment_type == 'Bank Transfer')
                                                    <div class="action-btn bg-warning ms-2">
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm d-inline-flex align-items-center"
                                                            data-bs-toggle="modal" data-size="lg" data-ajax-popup="true"
                                                            data-url="{{ route('banktransfer.show', [$order->id]) }}"
                                                            data-title="{{ __('Payment Status') }}" data-size="lg">
                                                            <span class="text-white"> <i
                                                                    class="ti ti-caret-right text-white"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-original-title="{{ __('Payment Status') }}"></i></span></a>
                                                    </div>
                                                @endif
                                                @php
                                                    $user = App\Models\User::find($order->user_id);
                                                @endphp
                                                <div class="action-btn">
                                                    {!! Form::open([
                                                        'method' => 'DELETE',
                                                        'route' => ['banktransfer.destroy', $order->id],
                                                        'id' => 'delete-form-' . $order->id,
                                                    ]) !!}
                                                    <a href="#"
                                                        class="mx-3 btn btn-sm  align-items-center bs-pass-para bg-danger"
                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                            class="ti ti-trash text-white text-white "></i></a>

                                                    {!! Form::close() !!}
                                                </div>
                                                @foreach ($userOrders as $userOrder)
                                                    @if ($user->plan == $order->plan_id && $order->order_id == $userOrder->order_id && $order->is_refund == 0)
                                                        <div class="badge bg-warning  p-2 px-3 ms-2">
                                                            <a href="{{ route('order.refund', [$order->id, $order->user_id]) }}"
                                                                class="mx-3 align-items-center" data-bs-toggle="tooltip"
                                                                title="{{ __('Delete') }}"
                                                                data-original-title="{{ __('Delete') }}">
                                                                <span class ="text-white">{{ __('Refund') }}</span>
                                                            </a>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </td>
                                        @endif
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
