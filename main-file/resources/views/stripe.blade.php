@extends('layouts.admin')
@php
    use App\Models\Utility;
    $admin = Utility::getAdminPaymentSetting();
@endphp
@push('script-page')
<style>
    .dash-footer {
        margin-left: 0 !important
    }
</style>

    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    {{-- {{ dd($admin_payment_setting) }} --}}
    <script type="text/javascript">
        @if (
            $plan->price > 0.0 &&
                $admin_payment_setting['is_stripe_enabled'] == 'on' &&
                !empty($admin_payment_setting['stripe_key']) &&
                !empty($admin_payment_setting['stripe_secret']))
            var stripe = Stripe('{{ $admin_payment_setting['stripe_key'] }}');
            var elements = stripe.elements();

            // Custom styling can be passed to options when creating an Element.
            var style = {
                base: {
                    // Add your base input styles here. For example:
                    fontSize: '14px',
                    color: '#32325d',
                },
            };

            // Create an instance of the card Element.
            var card = elements.create('card', {
                style: style,
            });

            // Add an instance of the card Element into the `card-element` <div>.
            card.mount('#card-element');


            // Create a token or display an error when the form is submitted.
            var form = document.getElementById('payment-form');
            form.addEventListener('submit', function(event) {
                event.preventDefault();

                stripe.createToken(card).then(function(result) {
                    if (result.error) {
                        $("#card-errors").html(result.error.message);
                        show_toastr('Error', result.error.message, 'error');
                    } else {
                        // Send the token to your server.
                        stripeTokenHandler(result.token);
                    }
                });
            });

            function stripeTokenHandler(token) {
                // Insert the token ID into the form so it gets submitted to the server
                var form = document.getElementById('payment-form');
                var hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'stripeToken');
                hiddenInput.setAttribute('value', token.id);
                form.appendChild(hiddenInput);

                // Submit the form
                form.submit();
            }
        @endif


        $(document).ready(function() {
            $(document).on('click', '.apply-coupon', function() {

                var ele = $(this);
                var coupon = ele.closest('.row').find('.coupon').val();

                $.ajax({
                    url: '{{ route('apply.coupon') }}',
                    datType: 'json',
                    data: {
                        plan_id: '{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}',
                        coupon: coupon
                    },
                    success: function(data) {
                        $('.final-price').text(data.final_price);
                        $('#stripe_coupon, #paypal_coupon').val(coupon);
                        if (data != '') {
                            if (data.is_success == true) {
                                show_toastr('success', data.message, 'success');
                            } else {
                                show_toastr('error', data.message, 'error');
                            }

                        } else {
                            show_toastr('error', "{{ __('Coupon code required.') }}", 'error');
                        }
                    }
                })
            });
        });


        @if (isset($admin_payment_setting['is_paystack_enabled']) && $admin_payment_setting['is_paystack_enabled'] == 'on')

            $("#pay_with_paystack").click(function() {

                $('#paystack-payment-form').ajaxForm(function(res) {
                    if (res.flag == 1) {
                        var paystack_callback = "{{ url('/plan/paystack') }}";
                        var order_id = '{{ time() }}';
                        var coupon_id = res.coupon;
                        var handler = PaystackPop.setup({
                            key: '{{ $admin_payment_setting['paystack_public_key'] }}',
                            email: res.email,
                            amount: res.total_price * 100,
                            currency: res.currency,
                            ref: 'pay_ref_id' + Math.floor((Math.random() * 1000000000) +
                                1
                            ), // generates a pseudo-unique reference. Please replace with a reference you generated. Or remove the line entirely so our API will generate one for you
                            metadata: {
                                custom_fields: [{
                                    display_name: "Email",
                                    variable_name: "email",
                                    value: res.email,
                                }]
                            },

                            callback: function(response) {
                                window.location.href = paystack_callback + '/' + response
                                    .reference + '/' + '{{ encrypt($plan->id) }}' +
                                    '?coupon_id=' + coupon_id
                            },
                            onClose: function() {
                                alert('window closed');
                            }
                        });
                        handler.openIframe();
                    } else if (res.flag == 2) {}
                }).submit();
            });
        @endif


        @if (isset($admin_payment_setting['is_razorpay_enabled']) && $admin_payment_setting['is_razorpay_enabled'] == 'on')
            // Razorpay Payment
            $(document).on("click", "#pay_with_razorpay", function() {
                $('#razorpay-payment-form').ajaxForm(function(res) {
                    if (res.flag == 1) {

                        var razorPay_callback = '{{ url(' / plan / razorpay ') }}';
                        var totalAmount = res.total_price * 100;
                        var coupon_id = res.coupon;
                        var options = {
                            "key": "{{ $admin_payment_setting['razorpay_public_key'] }}", // your Razorpay Key Id
                            "amount": totalAmount,
                            "name": 'Plan',
                            "currency": "{{ $admin_payment_setting['currency'] }}",
                            "description": "",
                            "handler": function(response) {
                                window.location.href = razorPay_callback + '/' + response
                                    .razorpay_payment_id + '/' +
                                    '{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}?coupon_id=' +
                                    coupon_id;
                            },
                            "theme": {
                                "color": "#528FF0"
                            }
                        };
                        var rzp1 = new Razorpay(options);
                        rzp1.open();
                    } else if (res.flag == 2) {

                    } else {
                        show_toastr('error', 'This coupon code is invalid or has expired.');
                    }

                }).submit();
            });
        @endif
    </script>

    <script>
        var scrollSpy = new bootstrap.ScrollSpy(document.body, {
            target: '#useradd-sidenav',
            offset: 300,
        })
        $(".list-group-item").click(function() {
            $('.list-group-item').filter(function() {
                return this.href == id;
            }).parent().removeClass('text-primary');
        });
    </script>

    <script>
        @if (isset($admin_payment_setting['is_flutterwave_enabled']) && $admin_payment_setting['is_flutterwave_enabled'] == 'on')
            $("#pay_with_flaterwave").click(function() {
                $('#flaterwave-payment-form').ajaxForm(function(res) {

                    if (res.flag == 1) {
                        var coupon_id = res.coupon;
                        var API_publicKey = '{{ $admin_payment_setting['flutterwave_public_key'] }}';
                        var nowTim = "{{ date('d-m-Y-h-i-a') }}";
                        var flutter_callback = "{{ url('/plan/flaterwave') }}";
                        var x = getpaidSetup({
                            PBFPubKey: API_publicKey,
                            customer_email: '{{ Auth::user()->email }}',
                            amount: res.total_price,
                            currency: '{{ $admin_payment_setting['currency'] }}',
                            txref: nowTim + '__' + Math.floor((Math.random() * 1000000000)) +
                                'fluttpay_online-' + '{{ date('Y-m-d') }}',
                            meta: [{
                                metaname: "payment_id",
                                metavalue: "id"
                            }],
                            onclose: function() {},
                            callback: function(response) {
                                var txref = response.tx.txRef;
                                if (response.tx.chargeResponseCode == "00" || response.tx
                                    .chargeResponseCode == "0") {
                                    window.location.href = flutter_callback + '/' + txref +
                                        '/' +
                                        '{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}?coupon_id=' +
                                        coupon_id;
                                } else {
                                    // redirect to a failure page.
                                }
                                x
                            .close(); // use this to close the modal immediately after payment.
                            }
                        });
                    } else if (res.flag == 2) {

                    } else {
                        show_toastr('error', 'This coupon code is invalid or has expired.');
                    }

                }).submit();
            });
        @endif
    </script>

    {{-- <script>
        @if (isset($admin_payment_setting['is_paystack_enabled']) && $admin_payment_setting['is_paystack_enabled'] == 'on')
            $("#pay_with_paystack").click(function() {
                $('#paystack-payment-form').ajaxForm(function(res) {
                    if (res.flag == 1) {
                        var paystack_callback = "{{ url('/plan/paystack') }}";
                        var order_id = '{{ time() }}';
                        var coupon_id = res.coupon;
                        var handler = PaystackPop.setup({
                            key: '{{ $admin_payment_setting['paystack_public_key '] }}',
                            email: res.email,
                            amount: res.total_price * 100,
                            currency: res.currency,
                            ref: 'pay_ref_id' + Math.floor((Math.random() * 1000000000) +
                                1
                            ), // generates a pseudo-unique reference. Please replace with a reference you generated. Or remove the line entirely so our API will generate one for you
                            metadata: {
                                custom_fields: [{
                                    display_name: "Email",
                                    variable_name: "email",
                                    value: res.email,
                                }]
                            },

                            callback: function(response) {
                                console.log(response.reference, order_id);
                                window.location.href = paystack_callback + '/' + response
                                    .reference + '/' + '{{ encrypt($plan->id) }}' +
                                    '?coupon_id=' +
                                    coupon_id
                            },
                            onClose: function() {
                                alert('window closed');
                            }
                        });
                        handler.openIframe();
                    } else if (res.flag == 2) {

                    } else {
                        show_toastr('error', 'This coupon code is invalid or has expired.');
                    }

                }).submit();
            });
        @endif




        @if (isset($admin_payment_setting['is_razorpay_enabled']) && $admin_payment_setting['is_razorpay_enabled'] == 'on')
            $("#pay_with_razorpay").click(function() {
                $('#razorpay-payment-form').ajaxForm(function(res) {
                    if (res.flag == 1) {

                        var razorPay_callback = '{{ url(' / plan / razorpay ') }}';
                        var totalAmount = res.total_price * 100;
                        var coupon_id = res.coupon;
                        var options = {
                            "key": "{{ $admin_payment_setting['razorpay_public_key'] }}", // your Razorpay Key Id
                            "amount": totalAmount,
                            "name": 'Plan',
                            "currency": '{{ $admin_payment_setting[
                                '
                                                currency '
                            ] }}',
                            "description": "",
                            "handler": function(response) {
                                window.location.href = razorPay_callback + '/' + response
                                    .razorpay_payment_id + '/' +
                                    '{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}?coupon_id=' +
                                    coupon_id;
                            },
                            "theme": {
                                "color": "#528FF0"
                            }
                        };
                        var rzp1 = new Razorpay(options);
                        rzp1.open();
                    } else if (res.flag == 2) {

                    } else {
                        show_toastr('error', 'This coupon code is invalid or has expired.');
                    }

                }).submit();
            });
        @endif


    </script> --}}
    {{-- payfast --}}

    <script>
        @if ($admin_payment_setting['is_payfast_enabled'] == 'on' && !empty($admin_payment_setting['payfast_merchant_id']) && !empty($admin_payment_setting['payfast_merchant_key']))
                $(document).ready(function() {
                    get_payfast_status(amount = 0, coupon = null);
                })

                function get_payfast_status(amount, coupon) {
                    var plan_id = $('#plan_id').val();

                    $.ajax({
                        url: '{{ route('payfast.payment') }}',
                        method: 'POST',
                        data: {
                            'plan_id': plan_id,
                            'coupon_amount': amount,
                            'coupon_code': coupon
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(data) {

                            if (data.success == true) {
                                $('#get-payfast-inputs').append(data.inputs);

                            } else {
                                show_toastr('Error', data.inputs, 'error')
                            }
                        }
                    });
                }
            @endif
    </script>

    <!-- Khalti Payment -->
    @if (isset($admin_payment_setting['is_khalti_enabled']) && $admin_payment_setting['is_khalti_enabled'] == 'on')
        <script src="https://khalti.s3.ap-south-1.amazonaws.com/KPG/dist/2020.12.17.0.0.0/khalti-checkout.iffe.js"></script>

        <script>
            var config = {
                "publicKey": "{{ isset($admin_payment_setting['khalti_public_key']) ? $admin_payment_setting['khalti_public_key'] : '' }}",
                "productIdentity": "1234567890",
                "productName": "demo",
                "productUrl": "{{ env('APP_URL') }}",
                "paymentPreference": [
                    "KHALTI",
                    "EBANKING",
                    "MOBILE_BANKING",
                    "CONNECT_IPS",
                    "SCT",
                ],
                "eventHandler": {
                    onSuccess(payload) {
                        if (payload.status == 200) {
                            $.ajaxSetup({
                                headers: {
                                    'X-CSRF-Token': '{{ csrf_token() }}'
                                }
                            });
                            $.ajax({
                                url: "{{ route('plan.get.khalti.status') }}",
                                method: 'POST',
                                data: {
                                    'payload': payload,
                                    'coupon_code': $('#khalti_coupon').val(),
                                    'plan_id': $('.khalti_plan_id').val(),
                                    'plan_frequency': $('.khalti_plan_frequency').val(),
                                },
                                beforeSend: function() {
                                    $(".loader-wrapper").removeClass('d-none');
                                },
                                success: function(data) {
                                    $(".loader-wrapper").addClass('d-none');
                                    if (data.status_code === 200) {
                                        show_toastr('success', 'Payment Done Successfully', 'success');
                                        setTimeout(() => {
                                            window.location.href = "{{ route('plans.index') }}";
                                        }, 1000);
                                    } else {
                                        show_toastr('Error', 'Payment Failed', 'msg');
                                    }
                                },
                                error: function(err) {
                                    show_toastr('Error', err.response, 'msg');
                                },
                            });
                        }
                    },
                    onError(error) {
                        show_toastr('Error', error, 'msg')
                    },
                    onClose() {}
                }

            };

            var checkout = new KhaltiCheckout(config);
            var btn = document.getElementsByClassName("payment-btn")[0];

            $(document).on("click", "#pay_with_khalti", function(event) {
                event.preventDefault()
                var coupon_code = $('#khalti_coupon').val();
                var plan_id = $('.khalti_plan_id').val();
                var plan_frequency = $('.khalti_plan_frequency').val();
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: "{{ route('plan.pay.with.khalti') }}",
                    method: 'POST',
                    data: {
                        'coupon_code': coupon_code,
                        'plan_id': plan_id,
                        'plan_frequency': plan_frequency,
                    },

                    beforeSend: function() {
                        $(".loader-wrapper").removeClass('d-none');
                    },
                    success: function(data) {
                        $(".loader-wrapper").addClass('d-none');
                        if (data == 0) {
                            show_toastr('Success', 'Plan Successfully Activated', 'success');
                            setTimeout(() => {
                                window.location.href = '{{ route('plans.index') }}';
                            }, 1000);
                        } else {
                            let price = data * 100;
                            checkout.show({
                                amount: price
                            });
                        }
                    }
                });
            })
        </script>
    @endif
    <!-- Khalti Payment End -->
@endpush

@push('css-page')
    <style>
        #card-element {
            border: 1px solid #a3afbb !important;
            border-radius: 10px !important;
            padding: 10px !important;
        }
    </style>
@endpush

@php
    $dir = asset(Storage::url('uploads/plan'));
    $dir_payment = asset(Storage::url('uploads/payments'));
@endphp
@section('page-title')
    {{ __('Manage Order Summary') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('plans.index') }}">{{ __('Plan') }}</a></li>
    <li class="breadcrumb-item">{{ $plan->name }}</li>
@endsection
@section('content')
    <div class="row">
        <!-- [ sample-page ] start -->
        <div class="col-sm-12">
            <div class="row">
                <div class="col-xl-3">
                    <div class="sticky-top" >
                        <div class="">
                            <div class="card price-card price-1 wow animate__fadeInUp" data-wow-delay="0.2s"
                                style="
                                                                    visibility: visible;
                                                                    animation-delay: 0.2s;
                                                                    animation-name: fadeInUp;
                                                                    ">
                                <div class="card-body">
                                    <span class="price-badge bg-primary rounded">{{ $plan->name }}</span>

                                    <h3 class="mb-4 f-w-600  ">
                                        {{ $admin['currency_symbol'] ? $admin['currency_symbol'] : '$' }}{{  \App\Models\Utility::formatPrice($plan->price) . ' / ' . __(\App\Models\Plan::$arrDuration[$plan->duration]) }}</small>
                                        </small>
                                    </h3>

                                    <ul class="list-unstyled">
                                        <li>
                                            <span class="theme-avtar"><i class="text-primary ti ti-circle-plus"></i></span>
                                            {{ $plan->max_users == -1 ? __('Lifetime') : $plan->max_users }}
                                            {{ __('Users') }}
                                        </li>
                                        <li>
                                            <span class="theme-avtar"><i class="text-primary ti ti-circle-plus"></i></span>
                                            {{ $plan->max_customers == -1 ? __('Lifetime') : $plan->max_customers }}
                                            {{ __('Customers') }}
                                        </li>
                                        <li>
                                            <span class="theme-avtar"><i class="text-primary ti ti-circle-plus"></i></span>
                                            {{ $plan->max_venders == -1 ? __('Lifetime') : $plan->max_venders }}
                                            {{ __('Vendors') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="card ">
                            <div class="list-group list-group-flush" id="useradd-sidenav">
                                @if ($admin_payment_setting['is_manually_enabled'] == 'on')
                                    <a href="#manually_payment"
                                        class="list-group-item list-group-item-action active border-0">{{ __('Manually') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if ($admin_payment_setting['is_bank_enabled'] == 'on')
                                    <a href="#banktransfer_payment"
                                        class="list-group-item list-group-item-action  border-0">{{ __('Bank Transfer') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (
                                    $admin_payment_setting['is_stripe_enabled'] == 'on' &&
                                        !empty($admin_payment_setting['stripe_key']) &&
                                        !empty($admin_payment_setting['stripe_secret']))
                                    <a href="#stripe_payment"
                                        class="list-group-item list-group-item-action  border-0">{{ __('Stripe') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (
                                    $admin_payment_setting['is_paypal_enabled'] == 'on' &&
                                        !empty($admin_payment_setting['paypal_client_id']) &&
                                        !empty($admin_payment_setting['paypal_secret_key']))
                                    <a href="#paypal_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Paypal') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (
                                    $admin_payment_setting['is_paystack_enabled'] == 'on' &&
                                        !empty($admin_payment_setting['paystack_public_key']) &&
                                        !empty($admin_payment_setting['paystack_secret_key']))
                                    <a href="#paystack_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Paystack') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_flutterwave_enabled']) && $admin_payment_setting['is_flutterwave_enabled'] == 'on')
                                    <a href="#flutterwave_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Flutterwave') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_razorpay_enabled']) && $admin_payment_setting['is_razorpay_enabled'] == 'on')
                                    <a href="#razorpay_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Razorpay') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_mercado_enabled']) && $admin_payment_setting['is_mercado_enabled'] == 'on')
                                    <a href="#mercado_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Mercado Pago') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_paytm_enabled']) && $admin_payment_setting['is_paytm_enabled'] == 'on')
                                    <a href="#paytm_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Paytm') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_mollie_enabled']) && $admin_payment_setting['is_mollie_enabled'] == 'on')
                                    <a href="#mollie_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Mollie') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_skrill_enabled']) && $admin_payment_setting['is_skrill_enabled'] == 'on')
                                    <a href="#skrill_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Skrill') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_coingate_enabled']) && $admin_payment_setting['is_coingate_enabled'] == 'on')
                                    <a href="#coingate_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Coingate') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_paymentwall_enabled']) && $admin_payment_setting['is_paymentwall_enabled'] == 'on')
                                    <a href="#paymentwall_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Paymentwall') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_toyyibpay_enabled']) && $admin_payment_setting['is_toyyibpay_enabled'] == 'on')
                                    <a href="#toyyibpay_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Toyyibpay') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_payfast_enabled']) && $admin_payment_setting['is_payfast_enabled'] == 'on')
                                    <a href="#payfast_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('PayFast') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_iyzipay_enabled']) && $admin_payment_setting['is_iyzipay_enabled'] == 'on')
                                    <a href="#iyzipay_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('IyziPay') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_sspay_enabled']) && $admin_payment_setting['is_sspay_enabled'] == 'on')
                                    <a href="#sspay_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Sspay') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_paytab_enabled']) && $admin_payment_setting['is_paytab_enabled'] == 'on')
                                    <a href="#paytab_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Paytab') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_benefit_enabled']) && $admin_payment_setting['is_benefit_enabled'] == 'on')
                                    <a href="#benefit_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Benefit') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_cashfree_enabled']) && $admin_payment_setting['is_cashfree_enabled'] == 'on')
                                    <a href="#cashfree_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Cashfree') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_aamarpay_enabled']) && $admin_payment_setting['is_aamarpay_enabled'] == 'on')
                                    <a href="#aamarpay_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Aamarpay') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_paytr_enabled']) && $admin_payment_setting['is_paytr_enabled'] == 'on')
                                    <a href="#paytr_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('PayTR') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_yookassa_enabled']) && $admin_payment_setting['is_yookassa_enabled'] == 'on')
                                    <a href="#yookassa_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('YooKassa') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_xendit_enabled']) && $admin_payment_setting['is_xendit_enabled'] == 'on')
                                    <a href="#xendit_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Xendit') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_midtrans_enabled']) && $admin_payment_setting['is_midtrans_enabled'] == 'on')
                                    <a href="#midtrans_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Midtrans') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_paiementpro_enabled']) && $admin_payment_setting['is_paiementpro_enabled'] == 'on')
                                    <a href="#paiementpro_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Paiementpro') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_nepalste_enabled']) && $admin_payment_setting['is_nepalste_enabled'] == 'on')
                                    <a href="#nepalste_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Nepalste') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_cinetpay_enabled']) && $admin_payment_setting['is_cinetpay_enabled'] == 'on')
                                    <a href="#cinetpay_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Cinetpay') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_fedapay_enabled']) && $admin_payment_setting['is_fedapay_enabled'] == 'on')
                                    <a href="#fedapay_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('Fedapay') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                @if (isset($admin_payment_setting['is_payhere_enabled']) && $admin_payment_setting['is_payhere_enabled'] == 'on')
                                    <a href="#payhere_payment"
                                        class="list-group-item list-group-item-action border-0">{{ __('PayHere') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                {{-- Tap --}}
                                @if (isset($admin_payment_setting['is_tap_enabled']) && $admin_payment_setting['is_tap_enabled'] == 'on')
                                    <a href="#tap_payment" class="list-group-item list-group-item-action border-0">
                                        {{ __('Tap') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                {{-- AuthorizeNet --}}
                                @if (isset($admin_payment_setting['is_authorizenet_enabled']) &&
                                        $admin_payment_setting['is_authorizenet_enabled'] == 'on')
                                    <a href="#authorizenet_payment"
                                        class="list-group-item list-group-item-action border-0">
                                        {{ __('AuthorizeNet') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                {{-- Khalti --}}
                                @if (isset($admin_payment_setting['is_khalti_enabled']) && $admin_payment_setting['is_khalti_enabled'] == 'on')
                                    <a href="#khalti_payment" class="list-group-item list-group-item-action border-0">
                                        {{ __('Khalti') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif

                                {{-- ozow --}}
                                @if (isset($admin_payment_setting['is_ozow_enabled']) && $admin_payment_setting['is_ozow_enabled'] == 'on')
                                    <a href="#ozow_payment" class="list-group-item list-group-item-action border-0">
                                        {{ __('Ozow') }}
                                        <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-9">

                    {{-- Manually payment --}}
                    @if ($admin_payment_setting['is_manually_enabled'] == 'on')
                        <div id="manually_payment" class="card">
                            <div class="card-header">
                                <h5 class=" h6 mb-0">{{ __('Manually') }}</h5>
                            </div>
                            <div class="card-body border p-3">
                                <label>{{ __('Requesting manual payment for the planned amount for the subscriptions plan.') }}</label>
                            </div>
                            @if (\Auth::user()->requested_plan != $plan->id)
                                <div class="col-sm-12 my-2 px-2">
                                    <div class="text-end">
                                        <a href="{{ route('send.request', [\Illuminate\Support\Facades\Crypt::encrypt($plan->id)]) }}"
                                            class="btn btn-primary m-r-10 btn-icon m-1" data-title="{{ __('Send Request') }}"
                                            data-bs-toggle="tooltip">
                                            <span class="btn-inner--icon">{{ __('Send Request') }}</span>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="col-sm-12 my-2 px-2">
                                    <div class="text-end">
                                        <a href="{{ route('request.cancel', \Auth::user()->id) }}"
                                            class="btn btn-danger btn-icon m-1" data-title="{{ __('Cancel Request') }}"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="{{ __('Cancel Request') }}">
                                            <span class="btn-inner--icon">{{ __('Cancel Request') }}</span>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                    {{-- Manually payment end --}}

                    {{-- BankTransfer payment --}}
                    @if ($admin_payment_setting['is_bank_enabled'] == 'on')
                        <div id="banktransfer_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Bank Transfer') }}</h5>
                            </div>

                            <div class="tab-pane {{ ($admin_payment_setting['is_bank_enabled'] == 'on' && !empty($admin_payment_setting['bank_detail'])) == 'on' ? 'active' : '' }}"
                                id="banktransfer_payment">
                                <form role="form" action="{{ route('plan.pay.with.bank') }}" method="post"
                                    class="require-validation" enctype="multipart/form-data">
                                    @csrf
                                    <div class="border p-3 rounded banktransfer-payment-div">
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                <div class="custom-radio">
                                                    <h6 class="font-16 font-weight-bold">{{ __('Bank Details :') }}</h6>
                                                </div>
                                                <p class="mb-0 pt-1 text-sm">
                                                    @if (isset($admin_payment_setting['bank_detail']) && !empty($admin_payment_setting['bank_detail']))
                                                    @endif
                                                    {!! $admin_payment_setting['bank_detail'] !!}
                                                </p>
                                            </div>
                                            <div class="col-6">
                                                {{ Form::label('payment_receipt', __('Payment Receipt'), ['class' => 'form-label']) }}
                                                <div class="choose-file form-group">
                                                    <input type="file" name="receipt" id="image"
                                                        class="form-control" enctype="multipart/form-data"
                                                        required="required">
                                                    <p class="upload_file"></p>
                                                </div>
                                            </div>
                                            <div class="form-group"><label for="bank_coupon">{{ __('Coupon') }}</label>
                                            </div>

                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="bank_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-banktransfer-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right banktransfer-coupon-tr"
                                                    style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="banktransfer-coupon-price"></b>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <span><b>{{ 'Plan Price : ' }}</b>{{ (!empty($admin['currency_symbol']) ? $admin['currency_symbol'] : '$') .  \App\Models\Utility::formatPrice($plan->price) }}</span>
                                            </div>

                                            <div class="form-group col-md-6">
                                                <b>{{ 'Net Amount : ' }}</b><span
                                                    class="bank_amount">{{ (!empty($admin['currency_symbol']) ? $admin['currency_symbol'] : '$') .  \App\Models\Utility::formatPrice($plan->price) }}</span><br>
                                                <small>{{ __('(After coupon apply)') }}</small>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                            <input type="submit" value="{{ __('Pay Now') }}" class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    @endif
                    {{-- BankTransfer payment end --}}


                    {{-- stripe payment --}}
                    @if (
                        $admin_payment_setting['is_stripe_enabled'] == 'on' &&
                            !empty($admin_payment_setting['stripe_key']) &&
                            !empty($admin_payment_setting['stripe_secret']))
                        <div id="stripe_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Stripe') }}</h5>
                            </div>

                            <div class="tab-pane {{ ($admin_payment_setting['is_stripe_enabled'] == 'on' && !empty($admin_payment_setting['stripe_key']) && !empty($admin_payment_setting['stripe_secret'])) == 'on' ? 'active' : '' }}"
                                id="stripe_payment">
                                <form role="form" action="{{ route('stripe.post') }}" method="post"
                                    class="require-validation" id="payment-form">
                                    @csrf
                                    <div class="border p-3 rounded stripe-payment-div">
                                        <div class="row">
                                            <div class="col-sm-8">
                                                <div class="custom-radio">
                                                    <label
                                                        class="font-16 font-weight-bold">{{ __('Credit / Debit Card') }}</label>
                                                </div>
                                                <p class="mb-0 pt-1 text-sm">
                                                    {{ __('Safe money transfer using your bank account. We support Mastercard, Visa, Discover and American express.') }}
                                                </p>
                                            </div>

                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="card-name-on"
                                                        class="form-label text-dark">{{ __('Name on card') }}</label>
                                                    <input type="text" name="name" id="card-name-on"
                                                        class="form-control required"
                                                        placeholder="{{ \Auth::user()->name }}">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div id="card-element">
                                                    <!-- A Stripe Element will be inserted here. -->
                                                </div>
                                                <div id="card-errors" role="alert"></div>
                                            </div>

                                            <div class="form-group mt-3"><label
                                                    for="stripe_coupon">{{ __('Coupon') }}</label></div>

                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="stripe_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12">
                                                <div class="error" style="display: none;">
                                                    <div class='alert-danger alert'>
                                                        {{ __('Please correct the errors and try again.') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                            <input type="submit" value="{{ __('Pay Now') }}" class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    @endif
                    {{-- stripr payment end --}}

                    {{-- paypal end --}}
                    @if ($admin_payment_setting['is_paypal_enabled'] == 'on' &&
                            !empty($admin_payment_setting['paypal_client_id']) &&
                            !empty($admin_payment_setting['paypal_secret_key']))
                        <div id="paypal_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Paypal') }}</h5>
                            </div>
                            {{-- <div class="card-body"> --}}
                            <div class="tab-pane {{ ($admin_payment_setting['is_paypal_enabled'] != 'on' && $admin_payment_setting['is_paypal_enabled'] == 'on' && !empty($admin_payment_setting['paypal_client_id']) && !empty($admin_payment_setting['paypal_secret_key'])) == 'on' ? 'active' : '' }}"
                                id="paypal_payment">
                                {{-- <div class="card"> --}}
                                <form class="w3-container w3-display-middle w3-card-4" method="POST" id="payment-form"
                                    action="{{ route('plan.pay.with.paypal') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paypal_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="paypal_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="submit" value="{{ __('Pay Now') }}" class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </form>
                                {{-- </div> --}}
                            </div>
                            {{-- </div> --}}
                        </div>
                    @endif
                    {{-- paypal end --}}

                    {{-- Paystack --}}
                    @if (isset($admin_payment_setting['is_paystack_enabled']) && $admin_payment_setting['is_paystack_enabled'] == 'on')
                        <div id="paystack_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Paystack') }}</h5>

                            </div>
                            {{-- <div class="card-body"> --}}

                            <form role="form" action="{{ route('plan.pay.with.paystack', []) }}" method="post"
                                class="require-validation" id="paystack-payment-form">
                                @csrf
                                <input type="hidden" name="plan_id"
                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paystack_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="paystack_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="button" id="pay_with_paystack"
                                                value="{{ __('Pay Now') }}" class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                            </form>

                            {{-- </div> --}}
                        </div>
                    @endif
                    {{-- Paystack end --}}

                    {{-- Flutterwave --}}
                    @if (isset($admin_payment_setting['is_flutterwave_enabled']) && $admin_payment_setting['is_flutterwave_enabled'] == 'on')
                        <div id="flutterwave_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Flutterwave') }}</h5>

                            </div>
                            {{-- <div class="card-body"> --}}
                            <form role="form" action="{{ route('plan.pay.with.flaterwave') }}" method="post"
                                class="require-validation" id="flaterwave-payment-form">
                                @csrf

                                <div class="tab-pane " id="flutterwave_payment">

                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="flutterwave_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="flutterwave_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="button" id="pay_with_flaterwave"
                                                value="{{ __('Pay Now') }}" class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif
                    {{-- Flutterwave END --}}

                    {{-- Razorpay --}}
                    @if (isset($admin_payment_setting['is_razorpay_enabled']) && $admin_payment_setting['is_razorpay_enabled'] == 'on')
                        <div id="razorpay_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Razorpay') }} </h5>

                            </div>
                            {{-- <div class="card-body"> --}}
                            <form role="form" action="{{ route('plan.pay.with.razorpay') }}" method="post"
                                class="require-validation" id="razorpay-payment-form">
                                @csrf
                                <div class="tab-pane " id="razorpay_payment">
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="flutterwave_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="flutterwave_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="button" id="pay_with_razorpay"
                                                value="{{ __('Pay Now') }}" class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif
                    {{-- Razorpay end --}}

                    {{-- Mercado Pago --}}
                    @if (isset($admin_payment_setting['is_mercado_enabled']) && $admin_payment_setting['is_mercado_enabled'] == 'on')
                        <div id="mercado_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Mercado Pago') }}</h5>

                            </div>
                            {{-- <div class="card-body"> --}}
                            <div class="tab-pane " id="mercado_payment">

                                <form class="w3-container w3-display-middle w3-card-4" method="POST" id="payment-form"
                                    action="{{ route('plan.pay.with.mercado') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="marcado_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="marcado_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="submit" id="pay_with_mercado"
                                                value="{{ __('Pay Now') }}" class="btn btn-primary m-r-10">
                                        </div>
                                    </div>

                                </form>

                            </div>
                        </div>
                    @endif
                    {{-- Mercado Pago end --}}

                    {{-- Paytm --}}
                    @if (isset($admin_payment_setting['is_paytm_enabled']) && $admin_payment_setting['is_paytm_enabled'] == 'on')
                        <div id="paytm_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Paytm') }}</h5>

                            </div>
                            {{-- <div class="card-body"> --}}

                            <div class="tab-pane " id="paytm_payment">
                                <form role="form" action="{{ route('plan.pay.with.paytm') }}" method="post"
                                    class="require-validation" id="paytm-payment-form">

                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                    <input type="hidden" name="total_price" id="paytm_total_price"
                                        value="{{ $plan->price }}" class="form-control">
                                    <div class="border p-3 mb-3 rounded payment-box">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="mobile_number">{{ __('Mobile Number') }}</label>
                                                    <input type="text" id="mobile_number" name="mobile_number"
                                                        class="form-control coupon"
                                                        placeholder="{{ __('Enter Mobile Number') }}" required>
                                                </div>
                                            </div>
                                            <div class="form-group mt-3"><label
                                                    for="paytm_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="paytm_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <button class="btn btn-primary m-r-10" type="submit">
                                                {{ __('Pay Now') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>
                    @endif
                    {{-- Paytm end --}}

                    {{-- Mollie --}}
                    @if (isset($admin_payment_setting['is_mollie_enabled']) && $admin_payment_setting['is_mollie_enabled'] == 'on')
                        <div id="mollie_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Mollie') }}</h5>

                            </div>
                            {{-- <div class="card-body"> --}}
                            <div class="tab-pane " id="mollie_payment">
                                <form role="form" action="{{ route('plan.pay.with.mollie') }}" method="post"
                                    class="require-validation" id="mollie-payment-form">

                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                    <input type="hidden" name="total_price" id="mollie_total_price"
                                        value="{{ $plan->price }}" class="form-control">
                                   
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="mollie_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="mollie_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-mollie-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right mollie-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="mollie-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <button class="btn btn-primary m-r-10" id="pay_with_mollie" type="submit">
                                                {{ __('Pay Now') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            {{-- Mollie end --}}
                        </div>
                    @endif
                    {{-- Skrill --}}
                    @if (isset($admin_payment_setting['is_skrill_enabled']) && $admin_payment_setting['is_skrill_enabled'] == 'on')
                        <div id="skrill_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Skrill') }}</h5>

                            </div>
                            {{-- <div class="card-body"> --}}

                            <div class="tab-pane " id="skrill_payment">

                                <form role="form" action="{{ route('plan.pay.with.skrill') }}" method="post"
                                    class="require-validation" id="skrill-payment-form">

                                    @csrf
                                    <input type="hidden" name="id"
                                        value="{{ date('Y-m-d') }}-{{ strtotime(date('Y-m-d H:i:s')) }}-payatm">
                                    <input type="hidden" name="order_id"
                                        value="{{ str_pad(!empty($order->id) ? $order->id + 1 : 0 + 1, 4, '100', STR_PAD_LEFT) }}">
                                    @php
                                        $skrill_data = [
                                            'transaction_id' => md5(
                                                date('Y-m-d') . strtotime('Y-m-d H:i:s') . 'user_id',
                                            ),
                                            'user_id' => 'user_id',
                                            'amount' => 'amount',
                                            'currency' => 'currency',
                                        ];
                                        session()->put('skrill_data', $skrill_data);

                                    @endphp
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                    <input type="hidden" name="total_price" id="skrill_total_price"
                                        value="{{ $plan->price }}" class="form-control">
                                    
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="skrill_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="skrill_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-skrill-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right skrill-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="skrill-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <button class="btn btn-primary m-r-10" id="pay_with_skrill" type="submit">
                                                {{ __('Pay Now') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>
                    @endif
                    {{-- Skrill end --}}

                    {{-- Coingate --}}
                    @if (isset($admin_payment_setting['is_coingate_enabled']) && $admin_payment_setting['is_coingate_enabled'] == 'on')
                        <div id="coingate_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Coingate') }}</h5>

                            </div>
                            {{-- <div class="card-body"> --}}

                            <div class="tab-pane " id="coingate_payment">
                                <form role="form" action="{{ route('plan.pay.with.coingate') }}" method="post"
                                    class="require-validation" id="coingate-payment-form">
                                    @csrf
                                    <input type="hidden" name="counpon" id="coingate_coupon" value="">
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                    <input type="hidden" name="total_price" id="coingate_total_price"
                                        value="{{ $plan->price }}" class="form-control">
                                    
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="coingate_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="coingate_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-coingate-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right coingate-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="coingate-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <button class="btn btn-primary m-r-10" id="pay_with_coingate" type="submit">
                                                {{ __('Pay Now') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    {{-- Coingate end --}}

                    {{-- Paymentwall --}}
                    @if (isset($admin_payment_setting['is_paymentwall_enabled']) && $admin_payment_setting['is_paymentwall_enabled'] == 'on')
                        <div id="paymentwall_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Paymentwall') }}</h5>

                            </div>
                            {{-- <div class="card-body"> --}}
                            <div class="tab-pane " id="paymentwall_payment">

                                <form role="form" action="{{ route('plan.paymentwallpayment') }}" method="post"
                                    id="paymentwall-payment-form" class="w3-container w3-display-middle w3-card-4">
                                    @csrf
                                   
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paymentwall_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="paymentwall_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-paymentwall-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right paymentwall-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="paymentwall-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                            <button class="btn btn-primary m-r-10" type="submit" id="pay_with_paymentwall">
                                                {{ __('Pay Now') }}
                                            </button>

                                        </div>
                                    </div>
                                </form>

                            </div>
                            {{-- </div> --}}
                        </div>
                    @endif
                    {{-- Paymentwall end --}}

                    {{-- Toyyibpay --}}
                    @if (isset($admin_payment_setting['is_toyyibpay_enabled']) && $admin_payment_setting['is_toyyibpay_enabled'] == 'on')
                        <div id="toyyibpay_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Toyyibpay') }}</h5>

                            </div>
                            <div class="tab-pane">

                                <form role="form" action="{{ route('plan.toyyibpaypayment') }}" method="post"
                                    id="paymentwall-payment-form" class="w3-container w3-display-middle w3-card-4">
                                    @csrf
                                    
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="toyyibpay_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="toyyibpay_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-toyyibpay-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right toyyibpay-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="toyyibpay-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                            <button class="btn btn-primary m-r-10" type="submit" id="">
                                                {{ __('Pay Now') }}
                                            </button>

                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>
                    @endif
                    {{-- Toyyibpay end --}}

                    {{-- PayFast --}}
                    @if (isset($admin_payment_setting['is_payfast_enabled']) && $admin_payment_setting['is_payfast_enabled'] == 'on')
                        <div id="payfast_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Payfast') }}</h5>
                            </div>


                            {{-- <div class="card-body"> --}}
                            @if (
                                $admin_payment_setting['is_payfast_enabled'] == 'on' &&
                                    !empty($admin_payment_setting['payfast_merchant_id']) &&
                                    !empty($admin_payment_setting['payfast_merchant_key']) &&
                                    !empty($admin_payment_setting['payfast_signature']) &&
                                    !empty($admin_payment_setting['payfast_mode']))
                                <div
                                    class="tab-pane {{ ($admin_payment_setting['is_payfast_enabled'] == 'on' && !empty($admin_payment_setting['payfast_merchant_id']) && !empty($admin_payment_setting['payfast_merchant_key'])) == 'on' ? 'active' : '' }}">
                                    @php
                                        $pfHost =
                                            $admin_payment_setting['payfast_mode'] == 'sandbox'
                                                ? 'sandbox.payfast.co.za'
                                                : 'www.payfast.co.za';
                                    @endphp
                                    <form role="form" action={{ 'https://' . $pfHost . '/eng/process' }}
                                        method="post" class="require-validation" id="payfast-form">
                                        @csrf
                                       
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="payfast_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="payfast_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-payfast-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right payfast-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="payfast-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                        <div id="get-payfast-inputs"></div>
                                        <div class="col-sm-12 my-2 px-2">
                                            <div class="text-end">
                                                <input type="hidden" name="plan_id" id="plan_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                                <input type="submit" value="{{ __('Pay Now') }}"
                                                    id="payfast-get-status" class="btn btn-xs btn-primary m-r-10">

                                            </div>
                                        </div>
                                    </form>
                                </div>
                            @endif

                        </div>
                    @endif
                    {{-- PayFast End --}}

                    {{-- IyziPay --}}
                    @if (
                        $admin_payment_setting['is_iyzipay_enabled'] == 'on' &&
                            !empty($admin_payment_setting['iyzipay_private_key']) &&
                            !empty($admin_payment_setting['iyzipay_secret_key']))
                        <div id="iyzipay_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('IyziPay') }}</h5>
                            </div>
                            {{-- <div class="card-body"> --}}
                            <div class="tab-pane {{ ($admin_payment_setting['is_iyzipay_enabled'] != 'on' && $admin_payment_setting['is_iyzipay_enabled'] == 'on' && !empty($admin_payment_setting['iyzipay_private_key']) && !empty($admin_payment_setting['iyzipay_secret_key'])) == 'on' ? 'active' : '' }}"
                                id="iyzipay_payment">
                                {{-- <div class="card"> --}}
                                <form class="w3-container w3-display-middle w3-card-4" method="POST" id="payment-form"
                                    action="{{ route('iyzipay.payment.init') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paypal_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="paypal_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="submit" value="{{ __('Pay Now') }}" class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </form>
                                {{-- </div> --}}
                            </div>
                            {{-- </div> --}}
                        </div>
                    @endif
                    {{-- IyziPay end --}}

                    {{-- Sspay --}}
                    @if (isset($admin_payment_setting['is_sspay_enabled']) && $admin_payment_setting['is_sspay_enabled'] == 'on')
                        <div id="sspay_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Sspay') }}</h5>

                            </div>
                            <div class="tab-pane">
                                <form role="form" action="{{ route('plan.sspaypayment') }}" method="post"
                                    id="sspay-payment-form" class="w3-container w3-display-middle w3-card-4">
                                    @csrf
                                   
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="sspay_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="sspay_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-sspay-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right sspay-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="sspay-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                            <button class="btn btn-primary m-r-10" type="submit" id="">
                                                {{ __('Pay Now') }}
                                            </button>

                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>
                    @endif
                    {{-- Toyyibpay end --}}

                    {{-- paytab --}}
                    @if (
                        $admin_payment_setting['is_paytab_enabled'] == 'on' &&
                            !empty($admin_payment_setting['paytab_profile_id']) &&
                            !empty($admin_payment_setting['paytab_region']) &&
                            !empty($admin_payment_setting['paytab_server_key']))
                        <div id="iyzipay_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('PayTab') }}</h5>
                            </div>
                            {{-- <div class="card-body"> --}}
                            <div class="tab-pane {{ $admin_payment_setting['is_paytab_enabled'] != 'on' && $admin_payment_setting['is_paytab_enabled'] == 'on' && !empty($admin_payment_setting['paytab_profile_id']) && !empty($admin_payment_setting['paytab_region']) && !empty($admin_payment_setting['paytab_server_key']) == 'on' ? 'active' : '' }}"
                                id="paytab_payment">
                                {{-- <div class="card"> --}}
                                <form class="w3-container w3-display-middle w3-card-4" method="POST" id="payment-form"
                                    action="{{ route('plan.pay.with.paytab') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paypal_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="paypal_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="submit" value="{{ __('Pay Now') }}" class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </form>
                                {{-- </div> --}}
                            </div>
                            {{-- </div> --}}
                        </div>
                    @endif
                    {{-- paytab end --}}

                    {{-- benefit --}}
                    @if (
                        $admin_payment_setting['is_benefit_enabled'] == 'on' &&
                            !empty($admin_payment_setting['benefit_api_key']) &&
                            !empty($admin_payment_setting['benefit_secret_key']))
                        <div id="benefit_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Benefit') }}</h5>
                            </div>
                            {{-- <div class="card-body"> --}}
                            <div class="tab-pane {{ $admin_payment_setting['is_benefit_enabled'] != 'on' && $admin_payment_setting['is_benefit_enabled'] == 'on' && !empty($admin_payment_setting['benefit_api_key']) && !empty($admin_payment_setting['benefit_secret_key']) == 'on' ? 'active' : '' }}"
                                id="benefit_payment">
                                {{-- <div class="card"> --}}
                                <form class="w3-container w3-display-middle w3-card-4" method="POST" id="payment-form"
                                    action="{{ route('plan.pay.with.benefit') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paypal_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="paypal_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="submit" value="{{ __('Pay Now') }}" class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </form>
                                {{-- </div> --}}
                            </div>
                            {{-- </div> --}}
                        </div>
                    @endif
                    {{-- benefit end --}}

                    {{-- Cashfree --}}
                    @if (
                        $admin_payment_setting['is_cashfree_enabled'] == 'on' &&
                            !empty($admin_payment_setting['cashfree_api_key']) &&
                            !empty($admin_payment_setting['cashfree_secret_key']))
                        <div id="cashfree_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Cashfree') }}</h5>
                            </div>
                            {{-- <div class="card-body"> --}}
                            <div class="tab-pane {{ $admin_payment_setting['is_cashfree_enabled'] != 'on' && $admin_payment_setting['is_cashfree_enabled'] == 'on' && !empty($admin_payment_setting['cashfree_api_key']) && !empty($admin_payment_setting['cashfree_secret_key']) == 'on' ? 'active' : '' }}"
                                id="cashfree_payment">
                                {{-- <div class="card"> --}}
                                <form class="w3-container w3-display-middle w3-card-4" method="POST" id="payment-form"
                                    action="{{ route('plan.pay.with.cashfree') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paypal_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="cashfree_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="submit" value="{{ __('Pay Now') }}" class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </form>
                                {{-- </div> --}}
                            </div>
                            {{-- </div> --}}
                        </div>
                    @endif
                    {{-- Cashfree end --}}

                    {{-- Aamarpay --}}
                    @if (
                        $admin_payment_setting['is_aamarpay_enabled'] == 'on' &&
                            !empty($admin_payment_setting['aamarpay_store_id']) &&
                            !empty($admin_payment_setting['aamarpay_signature_key']))
                        <div id="aamarpay_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Aamarpay') }}</h5>
                            </div>
                            {{-- <div class="card-body"> --}}
                            <div class="tab-pane {{ $admin_payment_setting['is_aamarpay_enabled'] != 'on' && $admin_payment_setting['is_aamarpay_enabled'] == 'on' && !empty($admin_payment_setting['aamarpay_store_id']) && !empty($admin_payment_setting['aamarpay_signature_key']) == 'on' ? 'active' : '' }}"
                                id="aamarpay_payment">
                                {{-- <div class="card"> --}}
                                <form class="w3-container w3-display-middle w3-card-4" method="POST" id="payment-form"
                                    action="{{ route('pay.aamarpay.payment') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paypal_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="cashfree_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="submit" value="{{ __('Pay Now') }}"
                                                class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </form>
                                {{-- </div> --}}
                            </div>
                            {{-- </div> --}}
                        </div>
                    @endif
                    {{-- Aamarpay end --}}

                    {{-- PayTR --}}
                    @if (
                        $admin_payment_setting['is_paytr_enabled'] == 'on' &&
                            !empty($admin_payment_setting['paytr_merchant_key']) &&
                            !empty($admin_payment_setting['paytr_merchant_salt']))
                        <div id="paytr_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('PayTR') }}</h5>
                            </div>
                            {{-- <div class="card-body"> --}}
                            <div class="tab-pane {{ $admin_payment_setting['is_paytr_enabled'] != 'on' && $admin_payment_setting['is_paytr_enabled'] == 'on' && !empty($admin_payment_setting['paytr_merchant_key']) && !empty($admin_payment_setting['paytr_merchant_salt']) == 'on' ? 'active' : '' }}"
                                id="paytr_payment">
                                {{-- <div class="card"> --}}
                                <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                    id="payment-form" action="{{ route('pay.paytr.payment') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paypal_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="cashfree_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="submit" value="{{ __('Pay Now') }}"
                                                class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </form>
                                {{-- </div> --}}
                            </div>
                            {{-- </div> --}}
                        </div>
                    @endif
                    {{-- PayTR end --}}


                    {{-- YooKassa --}}
                    @if (
                        $admin_payment_setting['is_yookassa_enabled'] == 'on' &&
                            !empty($admin_payment_setting['yookassa_shop_id']) &&
                            !empty($admin_payment_setting['yookassa_secret']))
                        <div id="yookassa_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('YooKassa') }}</h5>
                            </div>
                            <div class="tab-pane {{ $admin_payment_setting['is_yookassa_enabled'] != 'on' && $admin_payment_setting['is_yookassa_enabled'] == 'on' && !empty($admin_payment_setting['yookassa_shop_id']) && !empty($admin_payment_setting['yookassa_secret']) == 'on' ? 'active' : '' }}"
                                id="yookassa_payment">
                                <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                    id="payment-form" action="{{ route('plan.pay.with.yookassa') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paypal_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="cashfree_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="submit" value="{{ __('Pay Now') }}"
                                                class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    {{-- Yookassa end --}}


                    {{-- Xendit --}}
                    @if (isset($admin_payment_setting['is_xendit_enabled']) && $admin_payment_setting['is_xendit_enabled'] == 'on')
                        <div id="xendit_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Xendit') }}</h5>
                            </div>
                            <div class="tab-pane {{ $admin_payment_setting['is_xendit_enabled'] != 'on' && $admin_payment_setting['is_xendit_enabled'] == 'on' && !empty($admin_payment_setting['xendit_token']) && !empty($admin_payment_setting['xendit_api']) == 'on' ? 'active' : '' }}"
                                id="xendit_payment">
                                <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                    id="payment-form" action="{{ route('plan.xendit.payment') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paypal_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="cashfree_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="submit" value="{{ __('Pay Now') }}"
                                                class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    {{-- Xendit end --}}

                    {{-- Midtrans --}}
                    @if (isset($admin_payment_setting['is_midtrans_enabled']) && $admin_payment_setting['is_midtrans_enabled'] == 'on')
                        <div id="midtrans_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Midtrans') }}</h5>
                            </div>
                            <div class="tab-pane {{ $admin_payment_setting['is_midtrans_enabled'] != 'on' && $admin_payment_setting['is_midtrans_enabled'] == 'on' && !empty($admin_payment_setting['midtrans_secret']) == 'on' ? 'active' : '' }}"
                                id="midtrans_payment">
                                <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                    id="payment-form" action="{{ route('plan.get.midtrans') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id"
                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paypal_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="cashfree_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="submit" value="{{ __('Pay Now') }}"
                                                class="btn btn-primary m-r-10">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    {{-- Xendit end --}}

                    {{-- Paiementpro --}}
                    @if (isset($admin_payment_setting['is_paiementpro_enabled']) && $admin_payment_setting['is_paiementpro_enabled'] == 'on')
                        <div id="paiementpro_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Paiementpro') }}</h5>
                                <p class="text-sm text-muted">{{ __('Details about your plan paiementpro payment') }}
                                </p>
                            </div>
                            <div class="tab-pane " id="paiementpro_payment">
                                <form role="form" action="{{ route('plan.pay.with.paiementpro') }}"
                                    method="post" id="paiementpro-payment-form"
                                    class="w3-container w3-display-middle w3-card-4">
                                    @csrf

                                    <div class="border p-3 rounded ">
                                        <div class="col-md-12 mt-4 row">
                                            <div class="form-group col-md-6">
                                                {{ Form::label('mobile_number', __('Mobile Number'), ['class' => 'form-label']) }}
                                                <input type="text" name="mobile_number"
                                                    class="form-control font-style mobile_number">
                                            </div>
                                            <div class="form-group col-md-6">
                                                {{ Form::label('channel', __('Channel'), ['class' => 'form-label']) }}
                                                <input type="text" name="channel"
                                                    class="form-control font-style channel">
                                                <small class="text-danger">Example : OMCIV2,MOMO,CARD,FLOOZ
                                                    ,PAYPAL</small>
                                            </div>
                                        </div>
                                       
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paiementpro_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="paiementpro_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-paiementpro-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right paiementpro-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="paiementpro-coupon-price"></b>
                                                </div>
                                            </div>
                                    </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id" id="plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                            <button type="submit" data-from="paiementpro"
                                                value="{{ __('Pay Now') }}" id="pay_with_paiementpro"
                                                class="btn btn-xs btn-primary m-r-10">{{ __('Pay Now') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    {{-- Paiementpro end --}}

                    {{-- Nepalste --}}
                    @if (isset($admin_payment_setting['is_nepalste_enabled']) && $admin_payment_setting['is_nepalste_enabled'] == 'on')
                        <div id="nepalste_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Nepalste') }}</h5>
                                <p class="text-sm text-muted">{{ __('Details about your plan nepalste payment') }}</p>
                            </div>
                            <div class="tab-pane " id="nepalste_payment">
                                <form role="form" action="{{ route('plan.pay.with.nepalste') }}" method="post"
                                    id="nepalste-payment-form" class="w3-container w3-display-middle w3-card-4">
                                    @csrf
                                   
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="benefit_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="benefit_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-benefit-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right benefit-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="benefit-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id" id="plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                            <button type="submit" data-from="nepalste" value="{{ __('Pay Now') }}"
                                                id="pay_with_nepalste"
                                                class="btn btn-xs btn-primary m-r-10">{{ __('Pay Now') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    {{-- Nepalste end --}}

                    {{-- Cinetpay --}}
                    @if (isset($admin_payment_setting['is_cinetpay_enabled']) && $admin_payment_setting['is_cinetpay_enabled'] == 'on')
                        <div id="cinetpay_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Cinetpay') }}</h5>
                                <p class="text-sm text-muted">{{ __('Details about your plan cinetpay payment') }}</p>
                            </div>
                            <div class="tab-pane " id="cinetpay_payment">
                                <form role="form" action="{{ route('plan.pay.with.cinetpay') }}" method="post"
                                    id="cinetpay-payment-form" class="w3-container w3-display-middle w3-card-4">
                                    @csrf
                                    <div class="border p-3 rounded ">
                                        <div class="row">
                                            <div class="form-group mt-3"><label for="cinetpay_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="cinetpay_coupon" name="coupon" class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-stripe-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip" title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right stripe-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b class="stripe-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- <div class="col-md-12 mt-4 row">
                                            <div class="d-flex align-items-center">
                                                <div class="form-group w-100">
                                                    <label for="benefit_coupon"
                                                        class="form-label">{{ __('Coupon') }}</label>
                                                    <input type="text" id="cinetpay_coupon" name="coupon"
                                                        class="form-control coupon" data-from="cinetpay"
                                                        placeholder="{{ __('Enter Coupon Code') }}">

                                                </div>
                                                <div class="form-group ms-3 mt-4">
                                                    <a href="#" class="text-muted apply-coupon"
                                                        data-bs-toggle="tooltip" data-from="cinetpay"
                                                        data-title="Apply"><i
                                                            class="ti ti-square-check btn-apply"></i></a>
                                                </div>
                                            </div>
                                        </div> --}}
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id" id="plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                            <button type="submit" data-from="cinetpay" value="{{ __('Pay Now') }}"
                                                id="pay_with_cinetpay"
                                                class="btn btn-xs btn-primary m-r-10">{{ __('Pay Now') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    {{-- Cinetpay end --}}

                    {{-- Fedapay --}}
                    @if (isset($admin_payment_setting['is_fedapay_enabled']) && $admin_payment_setting['is_fedapay_enabled'] == 'on')
                        <div id="fedapay_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Fedapay') }}</h5>
                                <p class="text-sm text-muted">{{ __('Details about your plan fedapay payment') }}</p>
                            </div>
                            <div class="tab-pane " id="fedapay_payment">
                                <form role="form" action="{{ route('plan.pay.with.fedapay') }}" method="post"
                                    id="fedapay-payment-form" class="w3-container w3-display-middle w3-card-4">
                                    @csrf
                                   
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="fedapay_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="fedapay_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-fedapay-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right fedapay-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="fedapay-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id" id="plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                            <button type="submit" data-from="fedapay" value="{{ __('Pay Now') }}"
                                                id="pay_with_fedapay"
                                                class="btn btn-xs btn-primary m-r-10">{{ __('Pay Now') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    {{-- Fedapay end --}}

                    {{-- PayHere --}}
                    @if (isset($admin_payment_setting['is_payhere_enabled']) && $admin_payment_setting['is_payhere_enabled'] == 'on')
                        <div id="payhere_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('PayHere') }}</h5>
                                <p class="text-sm text-muted">{{ __('Details about your plan PayHere payment') }}</p>
                            </div>
                            <div class="tab-pane " id="paymentwall_payment">
                                <form role="form" action="{{ route('plan.pay.with.payhere') }}" method="post"
                                    id="payhere-payment-form" class="w3-container w3-display-middle w3-card-4">
                                    @csrf
                                    
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="paymentwall_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="paymentwall_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-paymentwall-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right paymentwall-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="paymentwall-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id" id="plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">

                                            <button type="submit" data-from="payhere" value="{{ __('Pay Now') }}"
                                                id="pay_with_benefit"
                                                class="btn btn-xs btn-primary m-r-10">{{ __('Pay Now') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    {{-- PayHere end --}}

                    <!-- Tap Payment -->
                    @if (isset($admin_payment_setting['is_tap_enabled']) && $admin_payment_setting['is_tap_enabled'] == 'on')
                        <div id="tap_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Tap') }}</h5>
                            </div>
                            <div class="tab-pane" id="tap_payment">
                                <form role="form" action="{{ route('plan.pay.with.tap') }}" method="post"
                                    id="tap-payment-form" class="w3-container w3-display-middle w3-card-4">
                                    @csrf
                                    
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="tap_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="tap_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-tap-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right tap-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="tap-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                            <button class="btn btn-primary m-r-10" type="submit" id="pay_with_tap">
                                                {{ __('Pay Now') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    <!-- Tap Payment End -->

                    <!-- AuthorizeNet Payment -->
                    @if (isset($admin_payment_setting['is_authorizenet_enabled']) &&
                            $admin_payment_setting['is_authorizenet_enabled'] == 'on')
                        <div id="authorizenet_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('AuthorizeNet') }}</h5>
                            </div>
                            <div class="tab-pane" id="authorizenet_payment">
                                <form role="form" action="{{ route('plan.pay.with.authorizenet') }}"
                                    method="post" id="authorizenet-payment-form"
                                    class="w3-container w3-display-middle w3-card-4">
                                    @csrf
                                    
                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="authorizenet_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="authorizenet_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-authorizenet-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right authorizenet-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="authorizenet-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                            <button class="btn btn-primary m-r-10" type="submit" id="pay_with_authorizenet">
                                                {{ __('Pay Now') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    <!-- AuthorizeNet Payment End -->

                    <!-- Khalti Payment -->
                    @if (isset($admin_payment_setting['is_khalti_enabled']) && $admin_payment_setting['is_khalti_enabled'] == 'on')
                        <div id="khalti_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Khalti') }}</h5>
                            </div>
                            <div class="tab-pane" id="khalti_payment">
                                <form role="form" action="{{ route('plan.pay.with.khalti') }}" method="post"
                                    id="khalti-payment-form" class="w3-container w3-display-middle w3-card-4">
                                    @csrf

                                    <div class="border p-3 mb-3 rounded">
                                        <div class="row">
                                            <div class="form-group mt-3"><label
                                                    for="khalti_coupon">{{ __('Coupon') }}</label></div>
                                            <div class="row align-items-center">
                                                <div class="col-md-11">
                                                    <div class="form-group">
                                                        <input type="text" id="khalti_coupon" name="coupon"
                                                            class="form-control coupon"
                                                            placeholder="{{ __('Enter Coupon Code') }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-1">
                                                    <div class="form-group apply-khalti-btn-coupon">
                                                        <a class="text-muted apply-coupon" data-bs-toggle="tooltip"
                                                            title="{{ __('Apply') }}"><i
                                                                class="ti ti-square-check btn-apply"></i>
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-right khalti-coupon-tr" style="display: none">
                                                    <b>{{ __('Coupon Discount') }}</b> : <b
                                                        class="khalti-coupon-price"></b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id" class="khalti_plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                            <button class="btn btn-primary m-r-10" type="submit" id="pay_with_khalti">
                                                {{ __('Pay Now') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                    <!-- Khalti Payment End -->

                     <!-- ozow Payment -->
                     @if (isset($admin_payment_setting['is_ozow_enabled']) && $admin_payment_setting['is_ozow_enabled'] == 'on')
                        <div id="ozow_payment" class="card">
                            <div class="card-header">
                                <h5>{{ __('Ozow') }}</h5>
                            </div>
                            <div class="tab-pane" id="ozow_payment">
                                <form role="form" action="{{ route('plan.pay.with.ozow') }}" method="post"
                                    id="ozow-payment-form" class="w3-container w3-display-middle w3-card-4">
                                    @csrf

                                    <div class="border p-3 mb-3 rounded payment-box">
                                        <div class="d-flex align-items-center">
                                            <div class="col-12 mt-4 mb-3">
                                                <div class="row">
                                                    <div class="form-group mt-3"><label for="ozow_coupon">{{ __('Coupon') }}</label></div>
                                                    <div class="row align-items-center">
                                                        <div class="col-md-11">
                                                            <div class="form-group">
                                                                <input type="text" id="ozow_coupon" name="coupon" class="form-control coupon"
                                                                    placeholder="{{ __('Enter Coupon Code') }}">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-1">
                                                            <div class="form-group apply-ozow-btn-coupon">
                                                                <a class="text-muted apply-coupon" data-bs-toggle="tooltip" title="{{ __('Apply') }}"><i
                                                                        class="ti ti-square-check btn-apply"></i>
                                                                </a>
                                                            </div>
                                                        </div>

                                                        <div class="col-12 text-right ozow-coupon-tr" style="display: none">
                                                            <b>{{ __('Coupon Discount') }}</b> : <b class="ozow-coupon-price"></b>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 my-2 px-2">
                                        <div class="text-end">
                                            <input type="hidden" name="plan_id" class="ozow_plan_id"
                                                value="{{ \Illuminate\Support\Facades\Crypt::encrypt($plan->id) }}">
                                            <button class="btn btn-primary m-r-10" type="submit" id="pay_with_ozow">
                                                {{ __('Pay Now') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif


                </div>
            </div>
        </div>
    @endsection
