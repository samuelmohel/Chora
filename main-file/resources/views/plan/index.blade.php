    @extends('layouts.admin')
    @php
        $dir = asset(Storage::url('uploads/plan'));
        $admin = \App\Models\Utility::getAdminPaymentSetting();
    @endphp
    @section('page-title')
        {{ __('Manage Plan') }}
    @endsection
    @section('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">{{ __('Plan') }}</li>
    @endsection
    @section('action-btn')
        <div class="float-end">
            @can('create plan')
                <a href="#" data-url="{{ route('plans.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip"
                    title="{{ __('Create') }}" data-title="{{ __('Create New Plan') }}" class="btn btn-sm btn-primary" data-size="lg">
                    <i class="ti ti-plus"></i>
                </a>
            @endcan
        </div>
    @endsection
    @section('content')
        <div class="row">
            @foreach ($plans as $plan)
                <div class="col-lg-4 col-xl-3 col-md-6 col-sm-6 d-flex">
                    <div class="card w-100 price-card price-1 wow animate__fadeInUp" data-wow-delay="0.2s"
                        style="
                   visibility: visible;
                   animation-delay: 0.2s;
                   animation-name: fadeInUp;
                   ">
                        <div class="card-body">
                            <span class="price-badge bg-primary">{{ $plan->name }}</span>

                            @if (\Auth::user()->type == 'company' && \Auth::user()->plan == $plan->id)
                                <div class="d-flex flex-row-reverse m-0 p-0 ">
                                    <span class="d-flex align-items-center ">
                                        <i class="f-10 lh-1 fas fa-circle text-success"></i>
                                        <span class="ms-2">{{ __('Active') }}</span>
                                    </span>
                                </div>
                            @endif
                            @if (\Auth::user()->type == 'super admin' && $plan->price > 0)
                                <div class="d-flex flex-row-reverse m-0 p-0 active-tag">
                                    <div class="form-check form-switch custom-switch-v1 float-end">
                                        <input type="checkbox" name="plan_disable"
                                            class="form-check-input input-primary is_disable" value="1"
                                            data-id='{{ $plan->id }}' data-name="{{ __('plan') }}"
                                            {{ $plan->is_disable == 1 ? 'checked' : '' }}>
                                        <label class="form-check-label" for="plan_disable"></label>
                                    </div>
                                </div>
                            @endif
                            <h1 class="mb-3 f-w-600 ">
                                {{ !empty($admin['currency_symbol']) ? $admin['currency_symbol'] : '$' }}{{ number_format($plan->price) }}<small
                                    class="text-sm">/{{ __(\App\Models\Plan::$arrDuration[$plan->duration]) }}</small></h1>
                            <p class="mb-0">
                                {{ __('Duration : ') . __(\App\Models\Plan::$arrDuration[$plan->duration]) }}<br />
                            </p>
                            <p class="mb-0">
                                {{ __('Free Trial Days : ') . __($plan->trial_days ? $plan->trial_days : 0) }}<br />
                            </p>

                            <ul class="list-unstyled my-4">
                                <li> <span class="theme-avtar"><i
                                            class="text-primary ti ti-circle-plus"></i></span>{{ $plan->max_users == -1 ? __('') : $plan->max_users }}
                                    {{ __('Users') }}</li>
                                <li><span class="theme-avtar"><i
                                            class="text-primary ti ti-circle-plus"></i></span>{{ $plan->max_customers == -1 ? __('Unlimited') : $plan->max_customers }}
                                    {{ __('Customers') }}</li>
                                <li><span class="theme-avtar"><i
                                            class="text-primary ti ti-circle-plus"></i></span>{{ $plan->max_venders == -1 ? __('Unlimited') : $plan->max_venders }}
                                    {{ __('Vendors') }}</li>
                                <li><span class="theme-avtar"><i
                                            class="text-primary ti ti-circle-plus"></i></span>{{ $plan->storage_limit == -1 ? __('Unlimited') : $plan->storage_limit }}
                                    {{ __('Storage Limits') }}</li>

                                <li><span class="theme-avtar"><i
                                            class="text-primary ti ti-circle-plus"></i></span>{{ $plan->enable_chatgpt == 'on' ? __('Enable') : __('Disable') }}
                                    {{ __('Chat GPT') }}</li>
                            </ul>
                            <br>

                            @if (\Auth::user()->type == 'super admin')
                                <div class="row align-items-center">
                                    <div class="col-3"></div>
                                    <div class="col-2 me-3">
                                        <a  href="#"
                                            class="btn btn-info btn-icon m-1"
                                            data-url="{{ route('plans.edit', $plan->id) }}" data-ajax-popup="true"
                                            data-bs-toggle="tooltip"
                                            title="{{ __('Edit Plan') }}">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                    </div>
                                    @if ($plan->price > 0)
                                        <div class="col-3">
                                            {!! Form::open([
                                                'method' => 'DELETE',
                                                'route' => ['plans.destroy', $plan->id],
                                                'id' => 'delete-form-' . $plan->id,
                                            ]) !!}
                                            <a href="#!" 
                                                class="bs-pass-para btn btn-danger btn-icon m-1"
                                                data-bs-toggle="tooltip"
                                                title="{{ __('Delete') }}">
                                                <i class="ti ti-trash"></i>
                                            </a>
                                            {!! Form::close() !!}
                                        </div>
                                    @endif
                                    <div class="col-3"></div>
                                </div>
                            @endif
                                @if (\Auth::user()->type != 'super admin')
                                    @if ($plan->price > 0 && \Auth::user()->trial_plan == 0 && \Auth::user()->plan != $plan->id && $plan->trial == 1)
                                        <a href="{{ route('plan.trial', \Illuminate\Support\Facades\Crypt::encrypt($plan->id)) }}"
                                            class="btn btn-lg btn-primary btn-icon m-1">{{ __('Start Free Trial') }}</a>
                                    @endif
                                    @if ($plan->id != \Auth::user()->plan)
                                        @if ($plan->price > 0)
                                            <a href="{{ route('stripe', \Illuminate\Support\Facades\Crypt::encrypt($plan->id)) }}"
                                                class="btn btn-lg btn-primary btn-icon m-1">{{ __('Buy Plan') }}</a>
                                        @endif
                                    @endif
                                    @if ($plan->id != 1 && $plan->id != \Auth::user()->plan)
                                        @if (\Auth::user()->requested_plan != $plan->id)
                                            <a href="{{ route('send.request', [\Illuminate\Support\Facades\Crypt::encrypt($plan->id)]) }}"
                                                class="btn btn-lg btn-primary btn-icon m-1"
                                                data-title="{{ __('Send Request') }}" data-bs-toggle="tooltip"
                                                title="{{ __('Send Request') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-corner-up-right"></i></span>
                                            </a>
                                        @else
                                            <a href="{{ route('request.cancel', \Auth::user()->id) }}"
                                                class="btn btn-danger btn-icon m-1"
                                                data-title="{{ __('`Cancel Request') }}" data-bs-toggle="tooltip"
                                                title="{{ __('Cancel Request') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-x"></i></span>
                                            </a>
                                        @endif
                                    @endif
                                @endif
                            @php
                                $plan_expire_date = \Auth::user()->plan_expire_date;
                            @endphp

                            @if (\Auth::user()->type == 'company' && \Auth::user()->trial_expire_date)
                                @if (\Auth::user()->type == 'company' && \Auth::user()->trial_plan == $plan->id)
                                    <p class="display-total-time text-dark mb-0">
                                        {{ __('Plan Trial Expired : ') }}
                                        {{ !empty(\Auth::user()->trial_expire_date) ? \Auth::user()->dateFormat(\Auth::user()->trial_expire_date) : 'lifetime' }}
                                    </p>
                                @endif
                            @else
                                @if (\Auth::user()->type == 'company' && \Auth::user()->plan == $plan->id)
                                    <p class="display-total-time text-dark mb-0">
                                        {{ __('Plan Expired : ') }}
                                        {{ !empty(\Auth::user()->plan_expire_date) ? \Auth::user()->dateFormat(\Auth::user()->plan_expire_date) : 'lifetime' }}
                                    </p>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endsection

    @push('script-page')
        <script>
            $(document).on('change', '#trial', function() {
                if ($(this).is(':checked')) {
                    $('.plan_div').removeClass('d-none');
                    $('#trial_days').attr("required", true);

                } else {
                    $('.plan_div').addClass('d-none');
                    $('#trial_days').removeAttr("required");
                }
            });
        </script>

        <script>
            $(document).on("click", ".is_disable", function() {
                var id = $(this).attr('data-id');
                var is_disable = ($(this).is(':checked')) ? $(this).val() : 0;

                $.ajax({
                    url: '{{ route('plan.disable') }}',
                    type: 'POST',
                    data: {
                        "is_disable": is_disable,
                        "id": id,
                        "_token": "{{ csrf_token() }}",
                    },
                    success: function(data) {
                        if (data.success) {
                            show_toastr('success', data.success, 'success');
                        } else {
                            show_toastr('error', data.error, 'error');

                        }

                    }
                });
            });
        </script>
    @endpush
