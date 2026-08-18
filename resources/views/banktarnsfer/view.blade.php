@php
    $path=\App\Models\Utility::get_file('uploads/bank_receipt');
@endphp
{{ Form::model($details, ['route' => ['banktransfer.show', $details->id], 'method' => 'PUT']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <table class="table modal-table">
                <tr>
                    <th>{{__('Order Id')}}</th>
                    <td> {{ $details->order_id }}</td>

                </tr>
                <tr >
                    <th>{{__('Plan Name')}}</th>
                    <td> {{ $details->plan_name }}</td>
                </tr>
                <tr>
                    <th>{{__('Plan Price')}}</th>
                    <td> {{ $details->price }}</td>
                </tr>
                <tr>
                    <th>{{__('Payment Type')}}</th>
                    <td>{{ $details->payment_type }}</td>
                </tr>
                <tr>
                    <th>{{__('Payment Status')}}</th>
                    <td> {{ $details->payment_status }}</td>
                </tr>
                <tr>
                    <th>{{__('Bank Details')}}</th>
                    <td> {!! $bank_detail !!}</td>
                </tr>
                @if(!empty( $details->receipt))
                    <tr>
                        <th>{{__('Payment Receipt')}}</th>
                        <td>
                        <a  class="action-btn bg-primary ms-2 btn btn-sm align-items-center" href="{{ $path . '/' . $details->receipt }}" download=""  data-bs-toggle="tooltip" title="{{ __('Download') }}">
                        <i class="ti ti-download text-white"></i>
                    </a>
                        </td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

</div>
<div class="modal-footer">
    <a href="{{ route('change.status', [$details->id, 1]) }}"
        class="btn btn-success btn-xs">
        {{__('Approval')}}
    </a>
    <a href="{{ route('change.status', [$details->id, 0]) }}"
        class="btn btn-danger btn-xs">
        {{__('Reject')}}
    </a>
</div>
{{Form::close()}}