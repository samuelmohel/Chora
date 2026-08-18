@extends('layouts.admin')
@section('page-title')
    {{ __('Settings') }}
@endsection
@php
    use App\Models\Utility;
    $logo = \App\Models\Utility::get_file('uploads/logo/');
    $meta_image = \App\Models\Utility::get_file('uploads/metaevent/');
    $lang = \App\Models\Utility::getValByName('default_language');
    $file_type = config('files_types');
    $setting = App\Models\Utility::settings();
    $admin = App\Models\Utility::getAdminPaymentSetting();
    $color = !empty($setting['color']) ? $setting['color'] : 'theme-3';
    $flag = !empty($setting['color_flag']) ? $setting['color_flag'] : '';

    $logo_light = \App\Models\Utility::getValByName('company_logo_light');
    $logo_dark = \App\Models\Utility::getValByName('company_logo_dark');

    $local_storage_validation = $setting['local_storage_validation'];
    $local_storage_validations = explode(',', $local_storage_validation);

    $s3_storage_validation = $setting['s3_storage_validation'];
    $s3_storage_validations = explode(',', $s3_storage_validation);

    $wasabi_storage_validation = $setting['wasabi_storage_validation'];
    $wasabi_storage_validations = explode(',', $wasabi_storage_validation);
    $enable_chatgpt = !empty($setting['chatgpt_key'] && $setting['chatgpt_model_name'] );

    $google_recaptcha_version = ['v2-checkbox' => __('v2'), 'v3' => __('v3')];

@endphp

<style>
    .dash-footer {
        margin-left: 0 !important
    }
</style>

@push('script-page')
    @if ($color == 'theme-3')
        <style>
            .btn-check:checked+.btn-outline-success,
            .btn-check:active+.btn-outline-success,
            .btn-outline-success:active,
            .btn-outline-success.active,
            .btn-outline-success.dropdown-toggle.show {
                color: #ffffff;
                background-color: #6fd943 !important;
                border-color: #6fd943 !important;

            }

            .btn-outline-success:hover {
                color: #ffffff;
                background-color: #6fd943 !important;
                border-color: #6fd943 !important;
            }

            .btn.btn-outline-success {
                color: #6fd943;
                border-color: #6fd943 !important;
            }
        </style>
    @endif
    @if ($color == 'theme-2')
        <style>
            .btn-check:checked+.btn-outline-success,
            .btn-check:active+.btn-outline-success,
            .btn-outline-success:active,
            .btn-outline-success.active,
            .btn-outline-success.dropdown-toggle.show {
                color: #ffffff;
                background: linear-gradient(141.55deg, rgbisset($setting['cookie_consent']) && $setting['cookie_consent']=='on' ? '' : 'disabled' a(240, 244, 243, 0) 3.46%, #4ebbd3 99.86%)#1f3996 !important;
                border-color: #1F3996 !important;

            }

            .btn-outline-success:hover {
                color: #ffffff;
                background: linear-gradient(141.55deg, rgba(240, 244, 243, 0) 3.46%, #4ebbd3 99.86%)#1f3996 !important;
                border-color: #1F3996 !important;
            }

            .btn.btn-outline-success {
                color: #1F3996;
                border-color: #1F3996 !important;
            }
        </style>
    @endif
    @if ($color == 'theme-4')
        <style>
            .btn-check:checked+.btn-outline-success,
            .btn-check:active+.btn-outline-success,
            .btn-outline-success:active,
            .btn-outline-success.active,
            .btn-outline-success.dropdown-toggle.show {
                color: #ffffff;
                background-color: #584ed2 !important;
                border-color: #584ed2 !important;

            }

            .btn-outline-success:hover {
                color: #ffffff;
                background-color: #584ed2 !important;
                border-color: #584ed2 !important;
            }

            .btn.btn-outline-success {
                color: #584ed2;
                border-color: #584ed2 !important;
            }
        </style>
    @endif
    @if ($color == 'theme-1')
        <style>
            .btn-check:checked+.btn-outline-success,
            .btn-check:active+.btn-outline-success,
            .btn-outline-success:active,
            .btn-outline-success.active,
            .btn-outline-success.dropdown-toggle.show {
                color: #ffffff;
                background: linear-gradient(141.55deg, rgba(81, 69, 157, 0) 3.46%, rgba(255, 58, 110, 0.6) 99.86%), #51459d !important;
                border-color: #51459d !important;

            }

            .btn-outline-success:hover {
                color: #ffffff;
                background: linear-gradient(141.55deg, rgba(81, 69, 157, 0) 3.46%, rgba(255, 58, 110, 0.6) 99.86%), #51459d !important;
                border-color: #51459d !important;
            }

            .btn.btn-outline-success {
                color: #51459d;
                border-color: #51459d !important;
            }
        </style>
    @endif

    <script>
        var scrollSpy = new bootstrap.ScrollSpy(document.body, {
            target: '#useradd-sidenav',
            offset: 300
        })

        var themescolors = document.querySelectorAll(".themes-color > a");
        for (var h = 0; h < themescolors.length; h++) {
            var c = themescolors[h];
            c.addEventListener("click", function(event) {
                var targetElement = event.target;
                if (targetElement.tagName == "SPAN") {
                    targetElement = targetElement.parentNode;
                }
                var temp = targetElement.getAttribute("data-value");
                removeClassByPrefix(document.querySelector("body"), "theme-");
                document.querySelector("body").classList.add(temp);
            });
        }

        function check_theme(color_val) {
            $('input[value="' + color_val + '"]').prop('checked', true);
            $('a[data-value]').removeClass('active_color');
            $('a[data-value="' + color_val + '"]').addClass('active_color');
        }

        if ($('#cust-darklayout').length > 0) {
            var custthemedark = document.querySelector("#cust-darklayout");
            custthemedark.addEventListener("click", function() {
                if (custthemedark.checked) {
                    document.querySelector("#style").setAttribute("href",
                        "{{ asset('assets/css/style-dark.css') }}");

                    $('.dash-sidebar .main-logo a img').attr('src', '{{ $logo . $logo_light }}');

                } else {
                    document.querySelector("#style").setAttribute("href", "{{ asset('assets/css/style.css') }}");
                    $('.dash-sidebar .main-logo a img').attr('src', '{{ $logo . $logo_dark }}');

                }
            });
        }
        if ($('#cust-theme-bg').length > 0) {
            var custthemebg = document.querySelector("#cust-theme-bg");
            custthemebg.addEventListener("click", function() {
                if (custthemebg.checked) {
                    document.querySelector(".dash-sidebar").classList.add("transprent-bg");
                    document
                        .querySelector(".dash-header:not(.dash-mob-header)")
                        .classList.add("transprent-bg");
                } else {
                    document.querySelector(".dash-sidebar").classList.remove("transprent-bg");
                    document
                        .querySelector(".dash-header:not(.dash-mob-header)")
                        .classList.remove("transprent-bg");
                }
            });
        }
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

        function check_theme(color_val) {
            $('#theme_color').prop('checked', false);
            $('input[value="' + color_val + '"]').prop('checked', true);
        }

        $(document).on('change', '[name=storage_setting]', function() {
            if ($(this).val() == 's3') {
                $('.s3-setting').removeClass('d-none');
                $('.wasabi-setting').addClass('d-none');
                $('.local-setting').addClass('d-none');
            } else if ($(this).val() == 'wasabi') {
                $('.s3-setting').addClass('d-none');
                $('.wasabi-setting').removeClass('d-none');
                $('.local-setting').addClass('d-none');
            } else {
                $('.s3-setting').addClass('d-none');
                $('.wasabi-setting').addClass('d-none');
                $('.local-setting').removeClass('d-none');
            }
        });
    </script>

    <script>
        $('.colorPicker').on('click', function(e) {
            $('body').removeClass('custom-color');
            if (/^theme-\d+$/) {
                $('body').removeClassRegex(/^theme-\d+$/);
            }
            $('body').addClass('custom-color');
            $('.themes-color-change').removeClass('active_color');
            $(this).addClass('active_color');
            const input = document.getElementById("color-picker");
            setColor();
            input.addEventListener("input", setColor);

            function setColor() {
                $(':root').css('--color-customColor', input.value);
            }

            $(`input[name='color_flag`).val('true');
        });

        $('.themes-color-change').on('click', function() {

            $(`input[name='color_flag`).val('false');

            var color_val = $(this).data('value');
            $('body').removeClass('custom-color');
            if (/^theme-\d+$/) {
                $('body').removeClassRegex(/^theme-\d+$/);
            }
            $('body').addClass(color_val);
            $('.theme-color').prop('checked', false);
            $('.themes-color-change').removeClass('active_color');
            $('.colorPicker').removeClass('active_color');
            $(this).addClass('active_color');
            $(`input[value=${color_val}]`).prop('checked', true);
        });

        $.fn.removeClassRegex = function(regex) {
            return $(this).removeClass(function(index, classes) {
                return classes.split(/\s+/).filter(function(c) {
                    return regex.test(c);
                }).join(' ');
            });
        };
    </script>

    <script type="text/javascript">
        function enablecookie() {
            const element = $('#enable_cookie').is(':checked');
            $('.cookieDiv').addClass('disabledCookie');
            if (element == true) {
                $('.cookieDiv').removeClass('disabledCookie');
                $("#cookie_logging").attr('checked', true);
            } else {
                $('.cookieDiv').addClass('disabledCookie');
                $("#cookie_logging").attr('checked', false);
            }
        }
    </script>


    <script>
        $(document).on("click", '.send_email', function(e) {

            e.preventDefault();
            var title = $(this).attr('data-title');

            var size = 'md';
            var url = $(this).attr('data-url');
            if (typeof url != 'undefined') {
                $("#commonModal .modal-title").html(title);
                $("#commonModal .modal-dialog").addClass('modal-' + size);
                $("#commonModal").modal('show');

                $.post(url, {
                    _token: '{{ csrf_token() }}',
                    mail_driver: $("#mail_driver").val(),
                    mail_host: $("#mail_host").val(),
                    mail_port: $("#mail_port").val(),
                    mail_username: $("#mail_username").val(),
                    mail_password: $("#mail_password").val(),
                    mail_encryption: $("#mail_encryption").val(),
                    mail_from_address: $("#mail_from_address").val(),
                    mail_from_name: $("#mail_from_name").val(),
                }, function(data) {
                    $('#commonModal .body').html(data);
                });
            }
        });


        $(document).on('submit', '#test_email', function(e) {
            e.preventDefault();
            $("#email_sending").show();
            var post = $(this).serialize();
            var url = $(this).attr('action');
            $.ajax({
                type: "post",
                url: url,
                data: post,
                cache: false,
                beforeSend: function() {
                    $('#test_email .btn-create').attr('disabled', 'disabled');
                },
                success: function(data) {
                    if (data.is_success) {
                        show_toastr('success', data.message, 'success');
                    } else {
                        show_toastr('error', data.message, 'error');
                    }
                    $("#email_sending").hide();
                    $('#commonModal').modal('hide');
                },
                complete: function() {
                    $('#test_email .btn-create').removeAttr('disabled');
                },
            });
        });
    </script>

@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Settings') }}</li>
@endsection



@section('content')
    <div class="row">
        <!-- [ sample-page ] start -->
        <div class="col-sm-12">
            <div class="row">
                <div class="col-xl-3">
                    <div class="card sticky-top" style="top:30px">
                        <div class="list-group list-group-flush" id="useradd-sidenav">
                            <a href="#useradd-1"
                                class="list-group-item list-group-item-action border-0">{{ __('Brand Settings') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#useradd-2"
                                class="list-group-item list-group-item-action border-0">{{ __('Email Settings') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#useradd-3"
                                class="list-group-item list-group-item-action border-0">{{ __('Payment Settings') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#useradd-4"
                                class="list-group-item list-group-item-action border-0">{{ __('ReCaptcha Settings') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#useradd-5"
                                class="list-group-item list-group-item-action border-0">{{ __('Storage Settings') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#useradd-6"
                                class="list-group-item list-group-item-action border-0">{{ __('SEO Settings') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#useradd-7"
                                class="list-group-item list-group-item-action border-0">{{ __('Cookie Settings') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#useradd-8"
                                class="list-group-item list-group-item-action border-0">{{ __('Cache Settings') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#useradd-9"
                                class="list-group-item list-group-item-action border-0">{{ __('Chat GPT Key Settings') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-xl-9">
                    <!--Site Setting-->
                    <div id="useradd-1" class="card">

                        <div class="card-header">
                            <h5>{{ __('Brand Settings') }}</h5>
                        </div>
                        {{ Form::model($settings, ['url' => 'settings', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'class' => 'mb-0']) }}

                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-4 col-sm-6 col-md-6 dashboard-card">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>{{ __('Logo dark') }}</h5>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class=" setting-card">
                                                <div class="logo-content mt-4">
                                                    <a href="{{ $logo . (isset($logo_dark) && !empty($logo_dark) ? $logo_dark : 'logo-dark.png') . '?' . time() }}"
                                                        target="_blank">
                                                        <img id="blah" alt="your image"
                                                            src="{{ $logo . (isset($logo_dark) && !empty($logo_dark) ? $logo_dark : 'logo-dark.png') . '?' . time() }}"
                                                            width="150px" class="big-logo">
                                                    </a>
                                                </div>
                                                <div class="choose-files mt-5">
                                                    <label for="full_logo">
                                                        <div class=" bg-primary company_logo_update"> <i
                                                                class="ti ti-upload px-1"></i>{{ __('Choose file here') }}
                                                        </div>
                                                        <input type="file" name="logo_dark" id="full_logo"
                                                            class="form-control file" data-filename="full_logo"
                                                            onchange="document.getElementById('blah').src = window.URL.createObjectURL(this.files[0])">


                                                    </label>
                                                </div>
                                                @error('full_logo')
                                                    <div class="row">
                                                        <span class="invalid-logo" role="alert">
                                                            <strong class="text-danger">{{ $message }}</strong>
                                                        </span>
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-md-6 dashboard-card">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>{{ __('Logo Light') }}</h5>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class=" setting-card">
                                                <div class="logo-content mt-4">
                                                    <a href="{{ $logo . (isset($logo_light) && !empty($logo_light) ? $logo_light : 'logo-light.png') . '?' . time() }}"
                                                        target="_blank">
                                                        <img id="blah1" alt="your image"
                                                            src="{{ $logo . (isset($logo_light) && !empty($logo_light) ? $logo_light : 'logo-light.png') . '?' . time() }}"
                                                            width="150px" class="big-logo img_setting">
                                                    </a>
                                                </div>
                                                <div class="choose-files mt-5">
                                                    <label for="logo_light">
                                                        <div class=" bg-primary dark_logo_update"> <i
                                                                class="ti ti-upload px-1"></i>{{ __('Choose file here') }}
                                                        </div>
                                                        <input type="file" name="logo_light" id="logo_light"
                                                            class="form-control file" data-filename="logo_light"
                                                            onchange="document.getElementById('blah1').src = window.URL.createObjectURL(this.files[0])">
                                                    </label>
                                                </div>
                                                @error('logo_light')
                                                    <div class="row">
                                                        <span class="invalid-logo" role="alert">
                                                            <strong class="text-danger">{{ $message }}</strong>
                                                        </span>
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-md-6 dashboard-card">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>{{ __('Favicon') }}</h5>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class=" setting-card">
                                                <div class="logo-content mt-4">
                                                    <a href="{{ $logo . (isset($company_favicon) && !empty($company_favicon) ? $company_favicon : 'favicon.png') . '?' . time() }}"
                                                        target="_blank">
                                                        <img id="blah2" alt="your image"
                                                            src="{{ $logo . (isset($company_favicon) && !empty($company_favicon) ? $company_ficon : 'favicon.png') . '?' . time() }}"
                                                            width="60px" height="63px" class=" img_setting">
                                                    </a>
                                                </div>
                                                <div class="choose-files mt-5">
                                                    <label for="favicon">
                                                        <div class="bg-primary company_favicon_update"> <i
                                                                class="ti ti-upload px-1"></i>{{ __('Choose file here') }}
                                                        </div>
                                                        <input type="file" name="favicon" id="favicon"
                                                            class="form-control file"
                                                            data-filename="favicon"
                                                            onchange="document.getElementById('blah2').src = window.URL.createObjectURL(this.files[0])">
                                                    </label>
                                                </div>
                                                @error('favicon')
                                                    <div class="row">
                                                        <span class="invalid-logo" role="alert">
                                                            <strong class="text-danger">{{ $message }}</strong>
                                                        </span>
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {{ Form::label('title_text', __('Title Text'), ['class' => 'form-label']) }}
                                            {{ Form::text('title_text', null, ['class' => 'form-control', 'placeholder' => __('Enter Title Text')]) }}
                                            @error('title_text')
                                                <span class="invalid-title_text" role="alert">
                                                    <strong class="text-danger">{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {{ Form::label('footer_text', __('Footer Text'), ['class' => 'form-label']) }}
                                            {{ Form::text('footer_text', Utility::getValByName('footer_text'), ['class' => 'form-control', 'placeholder' => __('Enter Footer Text')]) }}
                                            @error('footer_text')
                                                <span class="invalid-footer_text" role="alert">
                                                    <strong class="text-danger">{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {{ Form::label('default_language', __('Default Language'), ['class' => 'form-label text-dark']) }}
                                            <div class="changeLanguage">
                                                <select name="default_language" id="default_language"
                                                    class="form-control select">
                                                    @foreach (App\Models\Utility::languages() as $code => $language)
                                                        <option @if ($lang == $code) selected @endif
                                                            value="{{ $code }}">
                                                            {{ Str::upper($language) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('default_language')
                                                <span class="invalid-default_language" role="alert">
                                                    <strong class="text-danger">{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                    </div>
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-3 my-auto">
                                                <div class="form-group">
                                                    <label class="text-dark mb-1 mt-3"
                                                        for="SITE_RTL">{{ __('Enable RTL') }}</label>
                                                    <div class="">
                                                        <input type="checkbox" name="SITE_RTL" id="SITE_RTL"
                                                            data-toggle="switchbutton"
                                                            {{ $settings['SITE_RTL'] == 'on' ? 'checked="checked"' : '' }}
                                                            data-onstyle="primary">
                                                        <label class="form-check-labe" for="SITE_RTL"></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3 my-auto">
                                                <div class="form-group">
                                                    <label class="text-dark mb-1 mt-3"
                                                        for="display_landing_page">{{ __('Enable Landing Page') }}</label>
                                                    <div class="">
                                                        <input type="checkbox" name="display_landing_page"
                                                            class="form-check-input gdpr_fulltime gdpr_type"
                                                            id="display_landing_page" data-toggle="switchbutton"
                                                            {{ Utility::getValByName('display_landing_page') == 'on' ? 'checked' : '' }}
                                                            data-onstyle="primary">
                                                        <label class="form-check-labe" for="display_landing_page"></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3 my-auto">
                                                <div class="form-group">
                                                    <label class="text-dark mb-1 mt-3"
                                                        for="signup_button">{{ __('Enable Sign-Up Page') }}</label>
                                                    <div class="">
                                                        <input type="checkbox" name="enable_signup" id="enable_signup"
                                                            data-toggle="switchbutton"
                                                            {{ $settings['enable_signup'] == 'on' ? 'checked="checked"' : '' }}
                                                            data-onstyle="primary">
                                                        <label class="form-check-labe" for="enable_signup"></label>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- <div class="col-4 my-auto">
                                                <div class="form-group ">
                                                    <label class="text-dark mb-1 mt-3"
                                                        for="gdpr_cookie">{{ __('GDPR Notification') }}</label>
                                        <div class="">
                                            <input type="checkbox" class="gdpr_fulltime gdpr_type" name="gdpr_cookie" id="gdpr_cookie" data-toggle="switchbutton" {{ isset($settings['gdpr_cookie']) && $settings['gdpr_cookie'] == 'on' ? 'checked="checked"' : '' }} data-onstyle="primary">
                                            <label class="form-check-labe" for="gdpr_cookie"></label>
                                        </div>
                                    </div>
                                </div> --}}
                                            <div class="col-3 my-auto">
                                                <div class="form-group">
                                                    <label class="text-dark mb-1 mt-3"
                                                        for="email_verification">{{ __('Enable Email Verification') }}</label>
                                                    <div class="">
                                                        <input type="checkbox" name="email_verification"
                                                            id="email_verification" data-toggle="switchbutton"
                                                            {{ $settings['email_verification'] == 'on' ? 'checked="checked"' : '' }}
                                                            data-onstyle="primary">
                                                        <label class="form-check-labe" for="email_verification"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <h4 class="small-title">{{ __('Theme Customizer') }}</h4>
                                <div class="setting-card setting-logo-box p-3">
                                    <div class="row">
                                        <div class="col-lg-4 col-xl-4 col-md-4">
                                            <h6 class="mt-2">
                                                <i data-feather="credit-card"
                                                    class="me-2"></i>{{ __('Primary color settings') }}
                                            </h6>

                                            <hr class="my-2" />
                                            <div class="color-wrp">
                                                <div class="theme-color themes-color">
                                                    <a href="#!"
                                                        class="themes-color-change {{ $color == 'theme-1' ? 'active_color' : '' }}"
                                                        data-value="theme-1"></a>
                                                    <input type="radio" class="theme_color d-none" name="color"
                                                        value="theme-1" {{ $color == 'theme-1' ? 'checked' : '' }}>
                                                    <a href="#!"
                                                        class="themes-color-change {{ $color == 'theme-2' ? 'active_color' : '' }}"
                                                        data-value="theme-2"></a>
                                                    <input type="radio" class="theme_color d-none" name="color"
                                                        value="theme-2" {{ $color == 'theme-2' ? 'checked' : '' }}>
                                                    <a href="#!"
                                                        class="themes-color-change {{ $color == 'theme-3' ? 'active_color' : '' }}"
                                                        data-value="theme-3"></a>
                                                    <input type="radio" class="theme_color d-none" name="color"
                                                        value="theme-3" {{ $color == 'theme-3' ? 'checked' : '' }}>
                                                    <a href="#!"
                                                        class="themes-color-change {{ $color == 'theme-4' ? 'active_color' : '' }}"
                                                        data-value="theme-4"></a>
                                                    <input type="radio" class="theme_color d-none" name="color"
                                                        value="theme-4" {{ $color == 'theme-4' ? 'checked' : '' }}>
                                                    <a href="#!"
                                                        class="themes-color-change {{ $color == 'theme-5' ? 'active_color' : '' }}"
                                                        data-value="theme-5"></a>
                                                    <input type="radio" class="theme_color d-none" name="color"
                                                        value="theme-5" {{ $color == 'theme-5' ? 'checked' : '' }}>
                                                    <br>
                                                    <a href="#!"
                                                        class="themes-color-change {{ $color == 'theme-6' ? 'active_color' : '' }}"
                                                        data-value="theme-6"></a>
                                                    <input type="radio" class="theme_color d-none" name="color"
                                                        value="theme-6" {{ $color == 'theme-6' ? 'checked' : '' }}>
                                                    <a href="#!"
                                                        class="themes-color-change {{ $color == 'theme-7' ? 'active_color' : '' }}"
                                                        data-value="theme-7"></a>
                                                    <input type="radio" class="theme_color d-none" name="color"
                                                        value="theme-7" {{ $color == 'theme-7' ? 'checked' : '' }}>
                                                    <a href="#!"
                                                        class="themes-color-change {{ $color == 'theme-8' ? 'active_color' : '' }}"
                                                        data-value="theme-8"></a>
                                                    <input type="radio" class="theme_color d-none" name="color"
                                                        value="theme-8" {{ $color == 'theme-8' ? 'checked' : '' }}>
                                                    <a href="#!"
                                                        class="themes-color-change {{ $color == 'theme-9' ? 'active_color' : '' }}"
                                                        data-value="theme-9"></a>
                                                    <input type="radio" class="theme_color d-none" name="color"
                                                        value="theme-9" {{ $color == 'theme-9' ? 'checked' : '' }}>
                                                    <a href="#!"
                                                        class="themes-color-change {{ $color == 'theme-10' ? 'active_color' : '' }}"
                                                        data-value="theme-10"></a>
                                                    <input type="radio" class="theme_color d-none" name="color"
                                                        value="theme-10" {{ $color == 'theme-10' ? 'checked' : '' }}>
                                                </div>

                                                <div class="color-picker-wrp ">
                                                    <input type="color" value="{{ $color ? $color : '' }}"
                                                        class="colorPicker {{ isset($flag) && $flag == 'true' ? 'active_color' : '' }}"
                                                        name="custom_color" id="color-picker">
                                                    <input type='hidden' name="color_flag"
                                                        value={{ isset($flag) && $flag == 'true' ? 'true' : 'false' }}>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-xl-4 col-md-4">
                                            <h6 class="mt-2">
                                                <i data-feather="layout" class="me-2"></i>{{ __('Sidebar settings') }}
                                            </h6>
                                            <hr class="my-2" />
                                            <div class="form-check form-switch">
                                                <input type="checkbox" class="form-check-input" id="cust-theme-bg"
                                                    name="cust_theme_bg"
                                                    {{ !empty($settings['cust_theme_bg']) && $settings['cust_theme_bg'] == 'on' ? 'checked' : '' }} />
                                                <label class="form-check-label f-w-600 pl-1"
                                                    for="cust-theme-bg">{{ __('Transparent layout') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-xl-4 col-md-4">
                                            <h6 class="mt-2">
                                                <i data-feather="sun" class="me-2"></i>{{ __('Layout settings') }}
                                            </h6>
                                            <hr class="my-2" />
                                            <div class="form-check form-switch mt-2">
                                                <input type="checkbox" class="form-check-input" id="cust-darklayout"
                                                    name="cust_darklayout"
                                                    {{ !empty($settings['cust_darklayout']) && $settings['cust_darklayout'] == 'on' ? 'checked' : '' }} />
                                                <label class="form-check-label f-w-600 pl-1"
                                                    for="cust-darklayout">{{ __('Dark Layout') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <input class="btn btn-print-invoice btn-primary m-r-10" type="submit"
                                value="{{ __('Save Changes') }}">
                        </div>
                        {{ Form::close() }}
                    </div>

                    <!--Email Setting-->
                    <div id="useradd-2" class="card">
                        <div class="card-header">
                            <h5>{{ __('Email Settings') }}</h5>
                            <small
                                class="text-muted">{{ __('This SMTP will be used for system-level email sending. Additionally, if a company user does not set their SMTP,
                                                              then this SMTP will be used for sending emails.') }}</small>
                        </div>
                        <div class="card-body">
                            {{ Form::model($settings, ['route' => 'email.settings', 'method' => 'post', 'class' => 'mb-0']) }}
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {{ Form::label('mail_driver', __('Mail Driver'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_driver', null, ['class' => 'form-control', 'placeholder' => __('Enter Mail Driver')]) }}
                                        @error('mail_driver')
                                            <span class="invalid-mail_driver" role="alert">
                                                <strong class="text-danger">{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {{ Form::label('mail_host', __('Mail Host'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_host', null, ['class' => 'form-control ', 'placeholder' => __('Enter Mail Host')]) }}
                                        @error('mail_host')
                                            <span class="invalid-mail_driver" role="alert">
                                                <strong class="text-danger">{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {{ Form::label('mail_port', __('Mail Port'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_port', null, ['class' => 'form-control', 'placeholder' => __('Enter Mail Port')]) }}
                                        @error('mail_port')
                                            <span class="invalid-mail_port" role="alert">
                                                <strong class="text-danger">{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {{ Form::label('mail_username', __('Mail Username'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_username', null, ['class' => 'form-control', 'placeholder' => __('Enter Mail Username')]) }}
                                        @error('mail_username')
                                            <span class="invalid-mail_username" role="alert">
                                                <strong class="text-danger">{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {{ Form::label('mail_password', __('Mail Password'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_password', null, ['class' => 'form-control', 'placeholder' => __('Enter Mail Password')]) }}
                                        @error('mail_password')
                                            <span class="invalid-mail_password" role="alert">
                                                <strong class="text-danger">{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {{ Form::label('mail_encryption', __('Mail Encryption'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_encryption', null, ['class' => 'form-control', 'placeholder' => __('Enter Mail Encryption')]) }}
                                        @error('mail_encryption')
                                            <span class="invalid-mail_encryption" role="alert">
                                                <strong class="text-danger">{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {{ Form::label('mail_from_address', __('Mail From Address'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_from_address', null, ['class' => 'form-control', 'placeholder' => __('Enter Mail From Address')]) }}
                                        @error('mail_from_address')
                                            <span class="invalid-mail_from_address" role="alert">
                                                <strong class="text-danger">{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {{ Form::label('mail_from_name', __('Mail From Name'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_from_name', null, ['class' => 'form-control', 'placeholder' => __('Enter Mail From Name')]) }}
                                        @error('mail_from_name')
                                            <span class="invalid-mail_from_name" role="alert">
                                                <strong class="text-danger">{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            <div class="me-2 mb-0">
                                <a href="#" class="btn btn-primary send_email"
                                    data-title="{{ __('Send Test Mail') }}"
                                    data-url="{{ route('test.mail') }}">
                                    {{ __('Send Test Mail') }}
                                </a>
                            </div>
                            <div class="text-end mb-0">
                                <input class="btn btn-primary m-r-10" type="submit" value="{{ __('Save Changes') }}">
                            </div>
                        </div>
                        {{ Form::close() }}
                    </div>

                    <!--Payment Setting-->
                    <div class="card" id="useradd-3">
                        <div class="card-header">
                            <h5>{{ __('Payment Settings') }}</h5>
                            <small class="text-secondary font-weight-bold">
                                {{ __('These details will be used to collect subscription plan payments. Each subscription plan will have a payment button based on the below configuration.') }}
                            </small>
                        </div>
                        {{ Form::open(['route' => 'payment.settings', 'method' => 'post', 'class' => 'mb-0']) }}
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('currency', __('Currency *'), ['class' => 'form-label']) }}
                                        {{ Form::text('currency', $admin['currency'] ?? '', ['class' => 'form-control font-style', 'required', 'placeholder' => __('Enter Currency')]) }}
                                        <small>
                                            {{ __('Note: Add currency code as per three-letter ISO code.') }}<br>
                                            <a href="https://stripe.com/docs/currencies"
                                                target="_blank">{{ __('you can find out here..') }}</a></small>
                                        <br>
                                        @error('currency')
                                            <span class="invalid-currency" role="alert">
                                                <strong class="text-danger">{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('currency_symbol', __('Currency Symbol *'), ['class' => 'form-label']) }}
                                        {{ Form::text('currency_symbol', $admin['currency_symbol'] ?? '', ['class' => 'form-control', 'required', 'placeholder' => __('Enter Currency Symbol')]) }}
                                        @error('currency_symbol')
                                            <span class="invalid-currency_symbol" role="alert">
                                                <strong class="text-danger">{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="faq justify-content-center">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="accordion accordion-flush setting-accordion"
                                                    id="accordionExample">

                                                    {{-- //Manually --}}
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingOne">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapse14"
                                                                aria-expanded="false" aria-controls="collapse14">
                                                                <span class="d-flex align-items-center">

                                                                    {{ __('Manually') }}
                                                                </span>

                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_manually_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_manually_enabled"
                                                                            id="is_manually_enabled"
                                                                            {{ isset($admin_payment_setting['is_manually_enabled']) && $admin_payment_setting['is_manually_enabled'] == 'on' ? 'checked="checked"' : '' }}>

                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapse14" class="accordion-collapse collapse"
                                                            aria-labelledby="headingOne"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">
                                                                    <div class="form-group">
                                                                        <span>{{ __('Requesting manual payment for the planned amount for the subcriptions plan.') }}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Bank Transfer -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingOne">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapseBank"
                                                                aria-expanded="false" aria-controls="collapseBank">
                                                                <span class="d-flex align-items-center">

                                                                    {{ __('Bank Transfer') }}
                                                                </span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_bank_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_bank_enabled" id="is_bank_enabled"
                                                                            {{ isset($admin_payment_setting['is_bank_enabled']) && $admin_payment_setting['is_bank_enabled'] == 'on' ? 'checked="checked"' : '' }}>

                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseBank" class="accordion-collapse collapse"
                                                            aria-labelledby="headingOne"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">
                                                                    <div class="col-md-12">
                                                                        <div class="form-group">
                                                                            <label
                                                                                class="col-form-label">{{ __('Bank Details') }}</label>
                                                                            <textarea class="form-control" rows="5" name="bank_detail">{{ !empty($admin_payment_setting['bank_detail']) ? $admin_payment_setting['bank_detail'] : '' }}</textarea>
                                                                            <small>{{ __('Example : Bank : Bank name </br> Account Number : 0000 0000 </br>') }}</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Stripe -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingOne">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapseOne"
                                                                aria-expanded="false" aria-controls="collapseOne">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('Stripe') }}
                                                                </span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_stripe_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_stripe_enabled"
                                                                            id="is_stripe_enabled"
                                                                            {{ isset($admin_payment_setting['is_stripe_enabled']) && $admin_payment_setting['is_stripe_enabled'] == 'on' ? 'checked="checked"' : '' }}>

                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseOne" class="accordion-collapse collapse"
                                                            aria-labelledby="headingOne"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">
                                                                    <div class="col-lg-6">
                                                                        <div class="input-edits">
                                                                            <div class="form-group">
                                                                                <label for="stripe_key"
                                                                                    class="col-form-label">{{ __('Stripe Key') }}</label>
                                                                                <input class="form-control"
                                                                                    placeholder="{{ __('Enter Stripe Key') }}"
                                                                                    name="stripe_key" type="text"
                                                                                    value="{{ !isset($admin_payment_setting['stripe_key']) || is_null($admin_payment_setting['stripe_key']) ? '' : $admin_payment_setting['stripe_key'] }}"
                                                                                    id="stripe_key">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-6">
                                                                        <div class="input-edits">
                                                                            <div class="form-group">
                                                                                <label for="stripe_secret"
                                                                                    class="col-form-label">{{ __('Stripe Secret') }}</label>
                                                                                <input class="form-control"
                                                                                    placeholder="Enter Stripe Secret"
                                                                                    name="stripe_secret" type="text"
                                                                                    value="{{ !isset($admin_payment_setting['stripe_secret']) || is_null($admin_payment_setting['stripe_secret']) ? '' : $admin_payment_setting['stripe_secret'] }}"
                                                                                    id="stripe_secret">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Paypal -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingTwo">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapseTwo"
                                                                aria-expanded="false" aria-controls="collapseTwo">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('Paypal') }}</span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_paypal_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_paypal_enabled"
                                                                            id="is_paypal_enabled"
                                                                            {{ isset($admin_payment_setting['is_paypal_enabled']) && $admin_payment_setting['is_paypal_enabled'] == 'on' ? 'checked="checked"' : '' }}>

                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseTwo" class="accordion-collapse collapse"
                                                            aria-labelledby="headingTwo"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">
                                                                    <div class="col-md-12">
                                                                        <label class="paypal-label col-form-label"
                                                                            for="paypal_mode">{{ __('Paypal Mode') }}</label>
                                                                        <br>
                                                                        <div class="d-flex">
                                                                            <div class="mr-2"
                                                                                style="margin-right: 15px;">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark {{ isset($admin_payment_setting['paypal_mode']) && $admin_payment_setting['paypal_mode'] == 'sandbox' ? 'active' : '' }}">
                                                                                            <input type="radio"
                                                                                                name="paypal_mode"
                                                                                                value="sandbox"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['paypal_mode']) && $admin_payment_setting['paypal_mode'] == 'sandbox' ? 'checked="checked"' : '' }}>

                                                                                            {{ __('Sandbox') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="mr-2">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark">
                                                                                            <input type="radio"
                                                                                                name="paypal_mode"
                                                                                                value="live"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['paypal_mode']) && $admin_payment_setting['paypal_mode'] == 'live' ? 'checked="checked"' : '' }}>

                                                                                            {{ __('Live') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="paypal_client_id"
                                                                                class="col-form-label">{{ __('Client ID') }}</label>
                                                                            <input type="text" name="paypal_client_id"
                                                                                id="paypal_client_id" class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['paypal_client_id']) || is_null($admin_payment_setting['paypal_client_id']) ? '' : $admin_payment_setting['paypal_client_id'] }}"
                                                                                placeholder="{{ __('Client ID') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="paypal_secret_key"
                                                                                class="col-form-label">{{ __('Secret Key') }}</label>
                                                                            <input type="text" name="paypal_secret_key"
                                                                                id="paypal_secret_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['paypal_secret_key']) || is_null($admin_payment_setting['paypal_secret_key']) ? '' : $admin_payment_setting['paypal_secret_key'] }}"
                                                                                placeholder="{{ __('Secret Key') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Paystack -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingThree">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapseThree"
                                                                aria-expanded="false" aria-controls="collapseThree">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('Paystack') }}
                                                                </span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_paystack_enabled"
                                                                            id="is_paystack_enabled"
                                                                            {{ isset($admin_payment_setting['is_paystack_enabled']) && $admin_payment_setting['is_paystack_enabled'] == 'on' ? 'checked' : '' }}>

                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseThree" class="accordion-collapse collapse"
                                                            aria-labelledby="headingThree"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="paypal_client_id"
                                                                                class="col-form-label">{{ __('Public Key') }}</label>
                                                                            <input type="text"
                                                                                name="paystack_public_key"
                                                                                id="paystack_public_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['paystack_public_key']) || is_null($admin_payment_setting['paystack_public_key']) ? '' : $admin_payment_setting['paystack_public_key'] }}"
                                                                                placeholder="{{ __('Public Key') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="paystack_secret_key"
                                                                                class="col-form-label">{{ __('Secret Key') }}</label>
                                                                            <input type="text"
                                                                                name="paystack_secret_key"
                                                                                id="paystack_secret_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['paystack_secret_key']) || is_null($admin_payment_setting['paystack_secret_key']) ? '' : $admin_payment_setting['paystack_secret_key'] }}"
                                                                                placeholder="{{ __('Secret Key') }}">
                                                                            <div>

                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Flutterwave -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingFour">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapseFour"
                                                                aria-expanded="false" aria-controls="collapseFour">
                                                                <span
                                                                    class="d-flex align-items-center">{{ __('Flutterware') }}</span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden"
                                                                            name="is_flutterwave_enabled" value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_flutterwave_enabled"
                                                                            id="is_flutterwave_enabled"
                                                                            {{ isset($admin_payment_setting['is_flutterwave_enabled']) && $admin_payment_setting['is_flutterwave_enabled'] == 'on' ? 'checked' : '' }}>

                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseFour" class="accordion-collapse collapse"
                                                            aria-labelledby="headingFour"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="paypal_client_id"
                                                                                class="col-form-label">{{ __('Public Key') }}</label>
                                                                            <input type="text"
                                                                                name="flutterwave_public_key"
                                                                                id="flutterwave_public_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['flutterwave_public_key']) || is_null($admin_payment_setting['flutterwave_public_key']) ? '' : $admin_payment_setting['flutterwave_public_key'] }}"
                                                                                placeholder="Public Key">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="paystack_secret_key"
                                                                                class="col-form-label">{{ __('Secret Key') }}</label>
                                                                            <input type="text"
                                                                                name="flutterwave_secret_key"
                                                                                id="flutterwave_secret_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['flutterwave_secret_key']) || is_null($admin_payment_setting['flutterwave_secret_key']) ? '' : $admin_payment_setting['flutterwave_secret_key'] }}"
                                                                                placeholder="Secret Key">
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Razorpay -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingFive">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapseFive"
                                                                aria-expanded="false" aria-controls="collapseFive">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('Razorpay') }}</span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_razorpay_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_razorpay_enabled"
                                                                            id="is_razorpay_enabled"
                                                                            {{ isset($admin_payment_setting['is_razorpay_enabled']) && $admin_payment_setting['is_razorpay_enabled'] == 'on' ? 'checked="checked"' : '' }}>

                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseFive" class="accordion-collapse collapse"
                                                            aria-labelledby="headingFive"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="paypal_client_id"
                                                                                class="col-form-label">{{ __('Public Key') }}</label>

                                                                            <input type="text"
                                                                                name="razorpay_public_key"
                                                                                id="razorpay_public_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['razorpay_public_key']) || is_null($admin_payment_setting['razorpay_public_key']) ? '' : $admin_payment_setting['razorpay_public_key'] }}"
                                                                                placeholder="Public Key">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="paystack_secret_key"
                                                                                class="col-form-label">{{ __('Secret Key') }}</label>
                                                                            <input type="text"
                                                                                name="razorpay_secret_key"
                                                                                id="razorpay_secret_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['razorpay_secret_key']) || is_null($admin_payment_setting['razorpay_secret_key']) ? '' : $admin_payment_setting['razorpay_secret_key'] }}"
                                                                                placeholder="Secret Key">
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Paytm -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingSix">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapseSix"
                                                                aria-expanded="false" aria-controls="collapseSix">
                                                                <span
                                                                    class="d-flex align-items-center">{{ __('Paytm') }}</span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_paytm_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_paytm_enabled" id="is_paytm_enabled"
                                                                            {{ isset($admin_payment_setting['is_paytm_enabled']) && $admin_payment_setting['is_paytm_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseSix" class="accordion-collapse collapse"
                                                            aria-labelledby="headingSix"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="col-md-12 pb-4">
                                                                    <label class="paypal-label col-form-label"
                                                                        for="paypal_mode">{{ __('Paytm Environment') }}</label>
                                                                    <br>
                                                                    <div class="d-flex">
                                                                        <div class="mr-2" style="margin-right: 15px;">
                                                                            <div class="border card p-3">
                                                                                <div class="form-check">
                                                                                    <label
                                                                                        class="form-check-labe text-dark">

                                                                                        <input type="radio"
                                                                                            name="paytm_mode"
                                                                                            value="local"
                                                                                            class="form-check-input"
                                                                                            {{ !isset($admin_payment_setting['paytm_mode']) || $admin_payment_setting['paytm_mode'] == '' || $admin_payment_setting['paytm_mode'] == 'local' ? 'checked="checked"' : '' }}>

                                                                                        {{ __('Local') }}
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mr-2">
                                                                            <div class="border card p-3">
                                                                                <div class="form-check">
                                                                                    <label
                                                                                        class="form-check-labe text-dark">
                                                                                        <input type="radio"
                                                                                            name="paytm_mode"
                                                                                            value="production"
                                                                                            class="form-check-input"
                                                                                            {{ isset($admin_payment_setting['paytm_mode']) && $admin_payment_setting['paytm_mode'] == 'production' ? 'checked="checked"' : '' }}>

                                                                                        {{ __('Production') }}
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row gy-4">
                                                                    <div class="col-md-4">
                                                                        <div class="form-group">
                                                                            <label for="paytm_public_key"
                                                                                class="col-form-label">{{ __('Merchant ID') }}</label>
                                                                            <input type="text" name="paytm_merchant_id"
                                                                                id="paytm_merchant_id"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['paytm_merchant_id']) || is_null($admin_payment_setting['paytm_merchant_id']) ? '' : $admin_payment_setting['paytm_merchant_id'] }}"
                                                                                placeholder="Merchant ID">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="form-group">
                                                                            <label for="paytm_secret_key"
                                                                                class="col-form-label">{{ __('Merchant Key') }}</label>
                                                                            <input type="text"
                                                                                name="paytm_merchant_key"
                                                                                id="paytm_merchant_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['paytm_merchant_key']) || is_null($admin_payment_setting['paytm_merchant_key']) ? '' : $admin_payment_setting['paytm_merchant_key'] }}"
                                                                                placeholder="Merchant Key">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="form-group">
                                                                            <label for="paytm_industry_type"
                                                                                class="col-form-label">{{ __('Industry Type') }}</label>
                                                                            <input type="text"
                                                                                name="paytm_industry_type"
                                                                                id="paytm_industry_type"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['paytm_industry_type']) || is_null($admin_payment_setting['paytm_industry_type']) ? '' : $admin_payment_setting['paytm_industry_type'] }}"
                                                                                placeholder="Industry Type">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Mercado Pago -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingseven">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapseseven"
                                                                aria-expanded="false" aria-controls="collapseseven">
                                                                <span
                                                                    class="d-flex align-items-center">{{ __('Mercado Pago') }}</span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_mercado_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_mercado_enabled"
                                                                            id="is_mercado_enabled"
                                                                            {{ isset($admin_payment_setting['is_mercado_enabled']) && $admin_payment_setting['is_mercado_enabled'] == 'on' ? 'checked' : '' }}>

                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseseven" class="accordion-collapse collapse"
                                                            aria-labelledby="headingseven"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="col-md-12 pb-4">
                                                                    <label class="coingate-label col-form-label"
                                                                        for="mercado_mode">{{ __('Mercado Mode') }}</label>
                                                                    <br>
                                                                    <div class="d-flex">
                                                                        <div class="mr-2" style="margin-right: 15px;">
                                                                            <div class="border card p-3">
                                                                                <div class="form-check">
                                                                                    <label
                                                                                        class="form-check-labe text-dark">
                                                                                        <input type="radio"
                                                                                            name="mercado_mode"
                                                                                            value="sandbox"
                                                                                            class="form-check-input"
                                                                                            {{ (isset($admin_payment_setting['mercado_mode']) && $admin_payment_setting['mercado_mode'] == '') || (isset($admin_payment_setting['mercado_mode']) && $admin_payment_setting['mercado_mode'] == 'sandbox') ? 'checked="checked"' : '' }}>
                                                                                        {{ __('Sandbox') }}
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mr-2">
                                                                            <div class="border card p-3">
                                                                                <div class="form-check">
                                                                                    <label
                                                                                        class="form-check-labe text-dark">
                                                                                        <input type="radio"
                                                                                            name="mercado_mode"
                                                                                            value="live"
                                                                                            class="form-check-input"
                                                                                            {{ isset($admin_payment_setting['mercado_mode']) && $admin_payment_setting['mercado_mode'] == 'live' ? 'checked="checked"' : '' }}>
                                                                                        {{ __('Live') }}
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label for="mercado_access_token"
                                                                            class="col-form-label">{{ __('Access Token') }}</label>
                                                                        <input type="text" name="mercado_access_token"
                                                                            id="mercado_access_token" class="form-control"
                                                                            value="{{ isset($admin_payment_setting['mercado_access_token']) ? $admin_payment_setting['mercado_access_token'] : '' }}"
                                                                            placeholder="{{ __('Access Token') }}" />
                                                                        @if ($errors->has('mercado_secret_key'))
                                                                            <span class="invalid-feedback d-block">
                                                                                {{ $errors->first('mercado_access_token') }}
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Mollie -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingeight">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapseeight"
                                                                aria-expanded="false" aria-controls="collapseeight">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('Mollie') }}
                                                                </span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_mollie_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_mollie_enabled"
                                                                            id="is_mollie_enabled"
                                                                            {{ isset($admin_payment_setting['is_mollie_enabled']) && $admin_payment_setting['is_mollie_enabled'] == 'on' ? 'checked' : '' }}>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseeight" class="accordion-collapse collapse"
                                                            aria-labelledby="headingeight"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">

                                                                    <div class="col-md-4">
                                                                        <div class="form-group">
                                                                            <label for="mollie_api_key"
                                                                                class="col-form-label">{{ __('Mollie Api Key') }}</label>
                                                                            <input type="text" name="mollie_api_key"
                                                                                id="mollie_api_key" class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['mollie_api_key']) || is_null($admin_payment_setting['mollie_api_key']) ? '' : $admin_payment_setting['mollie_api_key'] }}"
                                                                                placeholder="Mollie Api Key">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="form-group">
                                                                            <label for="mollie_profile_id"
                                                                                class="col-form-label">{{ __('Mollie Profile ID') }}</label>
                                                                            <input type="text" name="mollie_profile_id"
                                                                                id="mollie_profile_id"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['mollie_profile_id']) || is_null($admin_payment_setting['mollie_profile_id']) ? '' : $admin_payment_setting['mollie_profile_id'] }}"
                                                                                placeholder="Mollie Profile Id">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="form-group">
                                                                            <label for="mollie_partner_id"
                                                                                class="col-form-label">{{ __('Mollie Partner ID') }}</label>
                                                                            <input type="text" name="mollie_partner_id"
                                                                                id="mollie_partner_id"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['mollie_partner_id']) || is_null($admin_payment_setting['mollie_partner_id']) ? '' : $admin_payment_setting['mollie_partner_id'] }}"
                                                                                placeholder="Mollie Partner Id">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Skrill -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingnine">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapsenine"
                                                                aria-expanded="false" aria-controls="collapsenine">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('Skrill') }}</span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_skrill_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_skrill_enabled"
                                                                            id="is_skrill_enabled"
                                                                            {{ isset($admin_payment_setting['is_skrill_enabled']) && $admin_payment_setting['is_skrill_enabled'] == 'on' ? 'checked' : '' }}>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapsenine" class="accordion-collapse collapse"
                                                            aria-labelledby="headingnine"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="mollie_api_key"
                                                                                class="col-form-label">{{ __('Skrill Email') }}</label>
                                                                            <input type="text" name="skrill_email"
                                                                                id="skrill_email" class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['skrill_email']) || is_null($admin_payment_setting['skrill_email']) ? '' : $admin_payment_setting['skrill_email'] }}"
                                                                                placeholder="Enter Skrill Email">
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- CoinGate -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingten">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapseten"
                                                                aria-expanded="false" aria-controls="collapseten">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('CoinGate') }}
                                                                </span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_coingate_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_coingate_enabled"
                                                                            id="is_coingate_enabled"
                                                                            {{ isset($admin_payment_setting['is_coingate_enabled']) && $admin_payment_setting['is_coingate_enabled'] == 'on' ? 'checked' : '' }}>

                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseten" class="accordion-collapse collapse"
                                                            aria-labelledby="headingten"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">
                                                                    <div class="col-md-12">
                                                                        <label class="col-form-label"
                                                                            for="coingate_mode">{{ __('CoinGate Mode') }}</label>
                                                                        <br>
                                                                        <div class="d-flex">
                                                                            <div class="mr-2"
                                                                                style="margin-right: 15px;">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark">

                                                                                            <input type="radio"
                                                                                                name="coingate_mode"
                                                                                                value="sandbox"
                                                                                                class="form-check-input"
                                                                                                {{ !isset($admin_payment_setting['coingate_mode']) || $admin_payment_setting['coingate_mode'] == '' || $admin_payment_setting['coingate_mode'] == 'sandbox' ? 'checked="checked"' : '' }}>

                                                                                            {{ __('Sandbox') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="mr-2">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark">
                                                                                            <input type="radio"
                                                                                                name="coingate_mode"
                                                                                                value="live"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['coingate_mode']) && $admin_payment_setting['coingate_mode'] == 'live' ? 'checked="checked"' : '' }}>
                                                                                            {{ __('Live') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="coingate_auth_token"
                                                                                class="col-form-label">{{ __('CoinGate Auth Token') }}</label>
                                                                            <input type="text"
                                                                                name="coingate_auth_token"
                                                                                id="coingate_auth_token"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['coingate_auth_token']) || is_null($admin_payment_setting['coingate_auth_token']) ? '' : $admin_payment_setting['coingate_auth_token'] }}"
                                                                                placeholder="CoinGate Auth Token">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- PaymentWall -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingeleven">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#collapseeleven" aria-expanded="false"
                                                                aria-controls="collapseeleven">
                                                                <span
                                                                    class="d-flex align-items-center">{{ __('PaymentWall') }}</span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden"
                                                                            name="is_paymentwall_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_paymentwall_enabled"
                                                                            id="is_paymentwall_enabled"
                                                                            {{ isset($admin_payment_setting['is_paymentwall_enabled']) && $admin_payment_setting['is_paymentwall_enabled'] == 'on' ? 'checked' : '' }}>

                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseeleven" class="accordion-collapse collapse"
                                                            aria-labelledby="headingeleven"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="paymentwall_public_key"
                                                                                class="col-form-label">{{ __('Public Key') }}</label>
                                                                            <input type="text"
                                                                                name="paymentwall_public_key"
                                                                                id="paymentwall_public_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['paymentwall_public_key']) || is_null($admin_payment_setting['paymentwall_public_key']) ? '' : $admin_payment_setting['paymentwall_public_key'] }}"
                                                                                placeholder="{{ __('Public Key') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="paymentwall_private_key"
                                                                                class="col-form-label">{{ __('Private Key') }}</label>
                                                                            <input type="text"
                                                                                name="paymentwall_private_key"
                                                                                id="paymentwall_private_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['paymentwall_private_key']) || is_null($admin_payment_setting['paymentwall_private_key']) ? '' : $admin_payment_setting['paymentwall_private_key'] }}"
                                                                                placeholder="{{ __('Private Key') }}">
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Toyyibpay -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingtwelve">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#collapsetwelve" aria-expanded="false"
                                                                aria-controls="collapsetwelve">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('Toyyibpay') }}
                                                                </span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden"
                                                                            name="is_toyyibpay_enabled" value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_toyyibpay_enabled"
                                                                            id="is_toyyibpay_enabled"
                                                                            {{ isset($admin_payment_setting['is_toyyibpay_enabled']) && $admin_payment_setting['is_toyyibpay_enabled'] == 'on' ? 'checked' : '' }}>

                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapsetwelve" class="accordion-collapse collapse"
                                                            aria-labelledby="headingtwelve"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="toyyibpay_secret_key"
                                                                                class="col-form-label">{{ __('Secret Key') }}</label>
                                                                            <input type="text"
                                                                                name="toyyibpay_secret_key"
                                                                                id="toyyibpay_secret_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['toyyibpay_secret_key']) || is_null($admin_payment_setting['toyyibpay_secret_key']) ? '' : $admin_payment_setting['toyyibpay_secret_key'] }}"
                                                                                placeholder="{{ __('Secret Key') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="category_code"
                                                                                class="col-form-label">{{ __('Category Code') }}</label>
                                                                            <input type="text" name="category_code"
                                                                                id="category_code" class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['category_code']) || is_null($admin_payment_setting['category_code']) ? '' : $admin_payment_setting['category_code'] }}"
                                                                                placeholder="{{ __('Category Code') }}">
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Payfast -->
                                                    <div class="accordion accordion-flush setting-accordion"
                                                        id="accordionExample">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingOne">
                                                                <button class="accordion-button collapsed"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#collapseOne13"
                                                                    aria-expanded="false" aria-controls="collapseOne13">
                                                                    <span class="d-flex align-items-center">
                                                                        {{ __('PayFast') }}
                                                                    </span>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2">{{ __('Enable:') }}</span>
                                                                        <div
                                                                            class="form-check form-switch custom-switch-v1">
                                                                            <input type="hidden"
                                                                                name="is_payfast_enabled"
                                                                                value="off">
                                                                            <input type="checkbox"
                                                                                class="form-check-input"
                                                                                name="is_payfast_enabled"
                                                                                id="is_payfast_enabled"
                                                                                {{ isset($admin_payment_setting['is_payfast_enabled']) && $admin_payment_setting['is_payfast_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                        </div>
                                                                    </div>
                                                                </button>
                                                            </h2>
                                                            <div id="collapseOne13" class="accordion-collapse collapse"
                                                                aria-labelledby="headingOne"
                                                                data-bs-parent="#accordionExample">
                                                                <div class="accordion-body">
                                                                    <div class="row">
                                                                        <label class="paypal-label col-form-label"
                                                                            for="payfast_mode">{{ __('Payfast Mode') }}</label>
                                                                        <div class="d-flex">
                                                                            <div class="mr-2"
                                                                                style="margin-right: 15px;">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark {{ isset($admin_payment_setting['payfast_mode']) && $admin_payment_setting['payfast_mode'] == 'sandbox' ? 'active' : '' }}">
                                                                                            <input type="radio"
                                                                                                name="payfast_mode"
                                                                                                value="sandbox"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['payfast_mode']) && $admin_payment_setting['payfast_mode'] == 'sandbox' ? 'checked="checked"' : '' }}>

                                                                                            {{ __('Sandbox') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="mr-2">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark">
                                                                                            <input type="radio"
                                                                                                name="payfast_mode"
                                                                                                value="live"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['payfast_mode']) && $admin_payment_setting['payfast_mode'] == 'live' ? 'checked="checked"' : '' }}>

                                                                                            {{ __('Live') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label for="paytm_public_key"
                                                                                    class="col-form-label">{{ __('Merchant ID') }}</label>
                                                                                <input type="text"
                                                                                    name="payfast_merchant_id"
                                                                                    id="payfast_merchant_id"
                                                                                    class="form-control"
                                                                                    value="{{ !isset($admin_payment_setting['payfast_merchant_id']) || is_null($admin_payment_setting['payfast_merchant_id']) ? '' : $admin_payment_setting['payfast_merchant_id'] }}"
                                                                                    placeholder="Merchant ID">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label for="paytm_secret_key"
                                                                                    class="col-form-label">{{ __('Merchant Key') }}</label>
                                                                                <input type="text"
                                                                                    name="payfast_merchant_key"
                                                                                    id="payfast_merchant_key"
                                                                                    class="form-control"
                                                                                    value="{{ !isset($admin_payment_setting['payfast_merchant_key']) || is_null($admin_payment_setting['payfast_merchant_key']) ? '' : $admin_payment_setting['payfast_merchant_key'] }}"
                                                                                    placeholder="Merchant Key">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label for="payfast_signature"
                                                                                    class="col-form-label">{{ __('Salt Passphrase') }}</label>
                                                                                <input type="text"
                                                                                    name="payfast_signature"
                                                                                    id="payfast_signature"
                                                                                    class="form-control"
                                                                                    value="{{ !isset($admin_payment_setting['payfast_signature']) || is_null($admin_payment_setting['payfast_signature']) ? '' : $admin_payment_setting['payfast_signature'] }}"
                                                                                    placeholder="Salt passphrase">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Iyzipay -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingFourteen">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#collapseFourteen" aria-expanded="false"
                                                                aria-controls="collapseFourteen">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('IyziPay') }}
                                                                </span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('Enable:') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_iyzipay_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_iyzipay_enabled"
                                                                            id="is_iyzipay_enabled"
                                                                            {{ isset($admin_payment_setting['is_iyzipay_enabled']) && $admin_payment_setting['is_iyzipay_enabled'] == 'on' ? 'checked="checked"' : '' }}>

                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseFourteen" class="accordion-collapse collapse"
                                                            aria-labelledby="headingFourteen"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row gy-4">
                                                                    <div class="col-md-12">
                                                                        <label class="paypal-label col-form-label"
                                                                            for="paypal_mode">{{ __('IyziPay Mode') }}</label>
                                                                        <br>
                                                                        <div class="d-flex">
                                                                            <div class="mr-2"
                                                                                style="margin-right: 15px;">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark {{ isset($admin_payment_setting['iyzipay_mode']) && $admin_payment_setting['iyzipay_mode'] == 'sandbox' ? 'active' : '' }}">
                                                                                            <input type="radio"
                                                                                                name="iyzipay_mode"
                                                                                                value="sandbox"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['iyzipay_mode']) && $admin_payment_setting['iyzipay_mode'] == 'sandbox' ? 'checked="checked"' : '' }}>

                                                                                            {{ __('Sandbox') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="mr-2">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark">
                                                                                            <input type="radio"
                                                                                                name="iyzipay_mode"
                                                                                                value="live"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['iyzipay_mode']) && $admin_payment_setting['iyzipay_mode'] == 'live' ? 'checked="checked"' : '' }}>

                                                                                            {{ __('Live') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="iyzipay_private_key"
                                                                                class="col-form-label">{{ __('Private Key') }}</label>
                                                                            <input type="text"
                                                                                name="iyzipay_private_key"
                                                                                id="iyzipay_private_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['iyzipay_private_key']) || is_null($admin_payment_setting['iyzipay_private_key']) ? '' : $admin_payment_setting['iyzipay_private_key'] }}"
                                                                                placeholder="{{ __('Private key') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="iyzipay_secret_key"
                                                                                class="col-form-label">{{ __('Secret Key') }}</label>
                                                                            <input type="text"
                                                                                name="iyzipay_secret_key"
                                                                                id="iyzipay_secret_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['iyzipay_secret_key']) || is_null($admin_payment_setting['iyzipay_secret_key']) ? '' : $admin_payment_setting['iyzipay_secret_key'] }}"
                                                                                placeholder="{{ __('Secret Key') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- SSPAY -->
                                                    <div class="accordion accordion-flush setting-accordion"
                                                        id="accordionExample">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingFourteen">
                                                                <button class="accordion-button collapsed"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#collapse15" aria-expanded="false"
                                                                    aria-controls="collapse15">
                                                                    <span class="d-flex align-items-center">
                                                                        {{ __('Sspay') }}
                                                                    </span>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2">{{ __('Enable:') }}</span>
                                                                        <div
                                                                            class="form-check form-switch custom-switch-v1">
                                                                            <input type="hidden"
                                                                                name="is_sspay_enabled" value="off">
                                                                            <input type="checkbox"
                                                                                class="form-check-input"
                                                                                name="is_sspay_enabled"
                                                                                id="is_sspay_enabled"
                                                                                {{ isset($admin_payment_setting['is_sspay_enabled']) && $admin_payment_setting['is_sspay_enabled'] == 'on' ? 'checked' : '' }}>

                                                                        </div>
                                                                    </div>
                                                                </button>
                                                            </h2>
                                                            <div id="collapse15" class="accordion-collapse collapse"
                                                                aria-labelledby="headingFourteen"
                                                                data-bs-parent="#accordionExample">
                                                                <div class="accordion-body">
                                                                    <div class="row gy-4">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="sspay_category_code"
                                                                                    class="col-form-label">{{ __('Category Code') }}</label>
                                                                                <input type="text"
                                                                                    name="sspay_category_code"
                                                                                    id="sspay_category_code"
                                                                                    class="form-control"
                                                                                    value="{{ !isset($admin_payment_setting['sspay_category_code']) || is_null($admin_payment_setting['sspay_category_code']) ? '' : $admin_payment_setting['sspay_category_code'] }}"
                                                                                    placeholder="{{ __('Category code') }}">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="sspay_secret_key"
                                                                                    class="col-form-label">{{ __('Secret Key') }}</label>
                                                                                <input type="text"
                                                                                    name="sspay_secret_key"
                                                                                    id="sspay_secret_key"
                                                                                    class="form-control"
                                                                                    value="{{ !isset($admin_payment_setting['sspay_secret_key']) || is_null($admin_payment_setting['sspay_secret_key']) ? '' : $admin_payment_setting['sspay_secret_key'] }}"
                                                                                    placeholder="{{ __('Secret Key') }}">
                                                                            </div>
                                                                        </div>

                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Paytab -->
                                                    <div class="accordion accordion-flush setting-accordion"
                                                        id="accordionExample">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingFourteen">
                                                                <button class="accordion-button collapsed"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#collapse16" aria-expanded="false"
                                                                    aria-controls="collapse16">
                                                                    <span class="d-flex align-items-center">
                                                                        {{ __('Paytab') }}
                                                                    </span>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2">{{ __('Enable:') }}</span>
                                                                        <div
                                                                            class="form-check form-switch d-inline-block custom-switch-v1">
                                                                            <input type="hidden"
                                                                                name="is_paytab_enabled" value="off">
                                                                            <input type="checkbox"
                                                                                class="form-check-input"
                                                                                name="is_paytab_enabled"
                                                                                id="is_paytab_enabled"
                                                                                {{ isset($admin_payment_setting['is_paytab_enabled']) && $admin_payment_setting['is_paytab_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                            <label class="custom-control-label form-label"
                                                                                for="is_paytab_enabled"></label>
                                                                        </div>
                                                                    </div>
                                                                </button>
                                                            </h2>
                                                            <div id="collapse16" class="accordion-collapse collapse"
                                                                aria-labelledby="headingFourteen"
                                                                data-bs-parent="#accordionExample">
                                                                <div class="accordion-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="paytab_profile_id"
                                                                                    class="col-form-label">{{ __('Profile Id') }}</label>
                                                                                <input type="text"
                                                                                    name="paytab_profile_id"
                                                                                    id="paytab_profile_id"
                                                                                    class="form-control"
                                                                                    value="{{ isset($admin_payment_setting['paytab_profile_id']) ? $admin_payment_setting['paytab_profile_id'] : '' }}"
                                                                                    placeholder="{{ __('Profile Id') }}">
                                                                            </div>
                                                                            @if ($errors->has('paytab_profile_id'))
                                                                                <span class="invalid-feedback d-block">
                                                                                    {{ $errors->first('paytab_profile_id') }}
                                                                                </span>
                                                                            @endif
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="paytab_server_key"
                                                                                    class="col-form-label">{{ __('Server Key') }}</label>
                                                                                <input type="text"
                                                                                    name="paytab_server_key"
                                                                                    id="paytab_server_key"
                                                                                    class="form-control"
                                                                                    value="{{ isset($admin_payment_setting['paytab_server_key']) ? $admin_payment_setting['paytab_server_key'] : '' }}"
                                                                                    placeholder="{{ __('Sspay Secret') }}">
                                                                            </div>
                                                                            @if ($errors->has('paytab_server_key'))
                                                                                <span class="invalid-feedback d-block">
                                                                                    {{ $errors->first('paytab_server_key') }}
                                                                                </span>
                                                                            @endif
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="paytab_region"
                                                                                    class="form-label">{{ __('Region') }}</label>
                                                                                <input type="text"
                                                                                    name="paytab_region"
                                                                                    id="paytab_region"
                                                                                    class="form-control form-control-label"
                                                                                    value="{{ isset($admin_payment_setting['paytab_region']) ? $admin_payment_setting['paytab_region'] : '' }}"
                                                                                    placeholder="{{ __('Region') }}" /><br>
                                                                                @if ($errors->has('paytab_region'))
                                                                                    <span
                                                                                        class="invalid-feedback d-block">
                                                                                        {{ $errors->first('paytab_region') }}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Benefit -->
                                                    <div class="accordion accordion-flush setting-accordion"
                                                        id="accordionExample">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingFourteen">
                                                                <button class="accordion-button collapsed"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#collapse17" aria-expanded="false"
                                                                    aria-controls="collapse17">
                                                                    <span class="d-flex align-items-center">
                                                                        {{ __('Benefit') }}
                                                                    </span>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2">{{ __('Enable:') }}</span>
                                                                        <div
                                                                            class="form-check form-switch custom-switch-v1">
                                                                            <input type="hidden"
                                                                                name="is_benefit_enabled"
                                                                                value="off">
                                                                            <input type="checkbox"
                                                                                class="form-check-input input-primary"
                                                                                name="is_benefit_enabled"
                                                                                id="is_benefit_enabled"
                                                                                {{ isset($admin_payment_setting['is_benefit_enabled']) && $admin_payment_setting['is_benefit_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                            <label class="form-check-label"
                                                                                for="is_benefit_enabled"></label>
                                                                        </div>
                                                                    </div>
                                                                </button>
                                                            </h2>
                                                            <div id="collapse17" class="accordion-collapse collapse"
                                                                aria-labelledby="headingFourteen"
                                                                data-bs-parent="#accordionExample">
                                                                <div class="accordion-body">
                                                                    <div class="row gy-4">

                                                                        <div class="col-lg-6">
                                                                            <div class="form-group">
                                                                                {{ Form::label('benefit_api_key', __('Benefit Key'), ['class' => 'col-form-label']) }}
                                                                                {{ Form::text('benefit_api_key', isset($admin_payment_setting['benefit_api_key']) ? $admin_payment_setting['benefit_api_key'] : '', ['class' => 'form-control', 'placeholder' => __('Enter Benefit Key')]) }}
                                                                                @error('benefit_api_key')
                                                                                    <span class="invalid-benefit_api_key"
                                                                                        role="alert">
                                                                                        <strong
                                                                                            class="text-danger">{{ $message }}</strong>
                                                                                    </span>
                                                                                @enderror
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-6">
                                                                            <div class="form-group">
                                                                                {{ Form::label('benefit_secret_key', __('Benefit Secret Key'), ['class' => 'col-form-label']) }}
                                                                                {{ Form::text('benefit_secret_key', isset($admin_payment_setting['benefit_secret_key']) ? $admin_payment_setting['benefit_secret_key'] : '', ['class' => 'form-control ', 'placeholder' => __('Enter Benefit Secret key')]) }}
                                                                                @error('benefit_secret_key')
                                                                                    <span class="invalid-benefit_secret_key"
                                                                                        role="alert">
                                                                                        <strong
                                                                                            class="text-danger">{{ $message }}</strong>
                                                                                    </span>
                                                                                @enderror
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Cashfree -->
                                                    <div class="accordion accordion-flush setting-accordion"
                                                        id="accordionExample">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingFourteen">
                                                                <button class="accordion-button collapsed"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#collapse18" aria-expanded="false"
                                                                    aria-controls="collapse18">
                                                                    <span class="d-flex align-items-center">
                                                                        {{ __('Cashfree') }}
                                                                    </span>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2">{{ __('Enable:') }}</span>
                                                                        <div
                                                                            class="form-check form-switch custom-switch-v1">
                                                                            <input type="hidden"
                                                                                name="is_cashfree_enabled"
                                                                                value="off">
                                                                            <input type="checkbox"
                                                                                class="form-check-input input-primary"
                                                                                name="is_cashfree_enabled"
                                                                                id="is_cashfree_enabled"
                                                                                {{ isset($admin_payment_setting['is_cashfree_enabled']) && $admin_payment_setting['is_cashfree_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                            <label class="form-check-label"
                                                                                for="is_cashfree_enabled"></label>
                                                                        </div>
                                                                    </div>
                                                                </button>
                                                            </h2>
                                                            <div id="collapse18" class="accordion-collapse collapse"
                                                                aria-labelledby="headingFourteen"
                                                                data-bs-parent="#accordionExample">
                                                                <div class="accordion-body">
                                                                    <div class="row gy-4">

                                                                        <div class="col-lg-6">
                                                                            <div class="form-group">
                                                                                {{ Form::label('cashfree_api_key', __('Cashfree Key'), ['class' => 'col-form-label']) }}
                                                                                {{ Form::text('cashfree_api_key', isset($admin_payment_setting['cashfree_api_key']) ? $admin_payment_setting['cashfree_api_key'] : '', ['class' => 'form-control', 'placeholder' => __('Enter Cashfree Key')]) }}
                                                                                @error('cashfree_api_key')
                                                                                    <span class="invalid-cashfree_api_key"
                                                                                        role="alert">
                                                                                        <strong
                                                                                            class="text-danger">{{ $message }}</strong>
                                                                                    </span>
                                                                                @enderror
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-6">
                                                                            <div class="form-group">
                                                                                {{ Form::label('cashfree_secret_key', __('Cashfree Secret Key'), ['class' => 'col-form-label']) }}
                                                                                {{ Form::text('cashfree_secret_key', isset($admin_payment_setting['cashfree_secret_key']) ? $admin_payment_setting['cashfree_secret_key'] : '', ['class' => 'form-control ', 'placeholder' => __('Enter Cashfree Secret key')]) }}
                                                                                @error('cashfree_secret_key')
                                                                                    <span class="invalid-cashfree_secret_key"
                                                                                        role="alert">
                                                                                        <strong
                                                                                            class="text-danger">{{ $message }}</strong>
                                                                                    </span>
                                                                                @enderror
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- aamarpay -->
                                                    <div class="accordion accordion-flush setting-accordion"
                                                        id="accordionExample">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingFourteen">
                                                                <button class="accordion-button collapsed"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#collapse19" aria-expanded="false"
                                                                    aria-controls="collapse19">
                                                                    <span class="d-flex align-items-center">
                                                                        {{ __('Aamarpay') }}
                                                                    </span>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2">{{ __('Enable:') }}</span>
                                                                        <div
                                                                            class="form-check form-switch custom-switch-v1">
                                                                            <input type="hidden"
                                                                                name="is_aamarpay_enabled"
                                                                                value="off">
                                                                            <input type="checkbox"
                                                                                class="form-check-input input-primary"
                                                                                name="is_aamarpay_enabled"
                                                                                id="is_aamarpay_enabled"
                                                                                {{ isset($admin_payment_setting['is_aamarpay_enabled']) && $admin_payment_setting['is_aamarpay_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                        </div>
                                                                    </div>
                                                                </button>
                                                            </h2>
                                                            <div id="collapse19" class="accordion-collapse collapse"
                                                                aria-labelledby="headingFourteen"
                                                                data-bs-parent="#accordionExample">
                                                                <div class="accordion-body">
                                                                    <div class="row pt-2">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                {{ Form::label('aamarpay_store_id', __('Store Id'), ['class' => 'form-label']) }}
                                                                                {{ Form::text('aamarpay_store_id', isset($admin_payment_setting['aamarpay_store_id']) ? $admin_payment_setting['aamarpay_store_id'] : '', ['class' => 'form-control', 'placeholder' => __('Store Id')]) }}<br>
                                                                                @if ($errors->has('aamarpay_store_id'))
                                                                                    <span
                                                                                        class="invalid-feedback d-block">
                                                                                        {{ $errors->first('aamarpay_store_id') }}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                {{ Form::label('aamarpay_signature_key', __('Signature Key'), ['class' => 'form-label']) }}
                                                                                {{ Form::text('aamarpay_signature_key', isset($admin_payment_setting['aamarpay_signature_key']) ? $admin_payment_setting['aamarpay_signature_key'] : '', ['class' => 'form-control', 'placeholder' => __('Signature Key')]) }}<br>
                                                                                @if ($errors->has('aamarpay_signature_key'))
                                                                                    <span
                                                                                        class="invalid-feedback d-block">
                                                                                        {{ $errors->first('aamarpay_signature_key') }}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                {{ Form::label('aamarpay_description', __('Description'), ['class' => 'form-label']) }}
                                                                                {{ Form::text('aamarpay_description', isset($admin_payment_setting['aamarpay_description']) ? $admin_payment_setting['aamarpay_description'] : '', ['class' => 'form-control', 'placeholder' => __('Description')]) }}<br>
                                                                                @if ($errors->has('aamarpay_description'))
                                                                                    <span
                                                                                        class="invalid-feedback d-block">
                                                                                        {{ $errors->first('aamarpay_description') }}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- PayTR -->
                                                    <div class="accordion accordion-flush setting-accordion"
                                                        id="accordionExample">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingFourteen">
                                                                <button class="accordion-button collapsed"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#collapse20" aria-expanded="false"
                                                                    aria-controls="collapse20">
                                                                    <span class="d-flex align-items-center">
                                                                        {{ __('PayTR') }}
                                                                    </span>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2">{{ __('Enable:') }}</span>
                                                                        <div
                                                                            class="form-check form-switch custom-switch-v1">
                                                                            <input type="hidden"
                                                                                name="is_paytr_enabled" value="off">
                                                                            <input type="checkbox"
                                                                                class="form-check-input input-primary"
                                                                                name="is_paytr_enabled"
                                                                                id="is_paytr_enabled"
                                                                                {{ isset($admin_payment_setting['is_paytr_enabled']) && $admin_payment_setting['is_paytr_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                        </div>
                                                                    </div>
                                                                </button>
                                                            </h2>
                                                            <div id="collapse20" class="accordion-collapse collapse"
                                                                aria-labelledby="headingFourteen"
                                                                data-bs-parent="#accordionExample">
                                                                <div class="accordion-body">
                                                                    <div class="row pt-2">
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                {{ Form::label('paytr_merchant_id', __('Merchant Id'), ['class' => 'form-label']) }}
                                                                                {{ Form::text('paytr_merchant_id', isset($admin_payment_setting['paytr_merchant_id']) ? $admin_payment_setting['paytr_merchant_id'] : '', ['class' => 'form-control', 'placeholder' => __('Merchant Id')]) }}<br>
                                                                                @if ($errors->has('paytr_merchant_id'))
                                                                                    <span
                                                                                        class="invalid-feedback d-block">
                                                                                        {{ $errors->first('paytr_merchant_id') }}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                {{ Form::label('paytr_merchant_key', __('Merchant Key'), ['class' => 'form-label']) }}
                                                                                {{ Form::text('paytr_merchant_key', isset($admin_payment_setting['paytr_merchant_key']) ? $admin_payment_setting['paytr_merchant_key'] : '', ['class' => 'form-control', 'placeholder' => __('Merchant Key')]) }}<br>
                                                                                @if ($errors->has('paytr_merchant_key'))
                                                                                    <span
                                                                                        class="invalid-feedback d-block">
                                                                                        {{ $errors->first('paytr_merchant_key') }}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                {{ Form::label('paytr_merchant_salt', __('Merchant Salt'), ['class' => 'form-label']) }}
                                                                                {{ Form::text('paytr_merchant_salt', isset($admin_payment_setting['paytr_merchant_salt']) ? $admin_payment_setting['paytr_merchant_salt'] : '', ['class' => 'form-control', 'placeholder' => __('Merchant Salt')]) }}<br>
                                                                                @if ($errors->has('paytr_merchant_salt'))
                                                                                    <span
                                                                                        class="invalid-feedback d-block">
                                                                                        {{ $errors->first('paytr_merchant_salt') }}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- YooKassa -->
                                                    <div class="accordion accordion-flush setting-accordion"
                                                        id="accordionExample">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingFifteen">
                                                                <button class="accordion-button collapsed"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#collapse21" aria-expanded="false"
                                                                    aria-controls="collapse21">
                                                                    <span class="d-flex align-items-center">
                                                                        {{ __('YooKassa') }}
                                                                    </span>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2">{{ __('Enable:') }}</span>
                                                                        <div
                                                                            class="form-check form-switch custom-switch-v1">
                                                                            <input type="hidden"
                                                                                name="is_yookassa_enabled"
                                                                                value="off">
                                                                            <input type="checkbox"
                                                                                class="form-check-input input-primary"
                                                                                name="is_yookassa_enabled"
                                                                                id="is_yookassa_enabled"
                                                                                {{ isset($admin_payment_setting['is_yookassa_enabled']) && $admin_payment_setting['is_yookassa_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                        </div>
                                                                    </div>
                                                                </button>
                                                            </h2>
                                                            <div id="collapse21" class="accordion-collapse collapse"
                                                                aria-labelledby="headingFifteen"
                                                                data-bs-parent="#accordionExample">
                                                                <div class="accordion-body">
                                                                    <div class="row pt-2">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                {{ Form::label('yookassa_shop_id', __('Shop id'), ['class' => 'form-label']) }}
                                                                                {{ Form::text('yookassa_shop_id', isset($admin_payment_setting['yookassa_shop_id']) ? $admin_payment_setting['yookassa_shop_id'] : '', ['class' => 'form-control', 'placeholder' => __('Shop id')]) }}<br>
                                                                                @if ($errors->has('yookassa_shop_id'))
                                                                                    <span
                                                                                        class="invalid-feedback d-block">
                                                                                        {{ $errors->first('yookassa_shop_id') }}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                {{ Form::label('yookassa_secret', __('Merchant Key'), ['class' => 'form-label']) }}
                                                                                {{ Form::text('yookassa_secret', isset($admin_payment_setting['yookassa_secret']) ? $admin_payment_setting['yookassa_secret'] : '', ['class' => 'form-control', 'placeholder' => __('Merchant Key')]) }}<br>
                                                                                @if ($errors->has('yookassa_secret'))
                                                                                    <span
                                                                                        class="invalid-feedback d-block">
                                                                                        {{ $errors->first('yookassa_secret') }}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Xendit -->
                                                    <div class="accordion accordion-flush setting-accordion"
                                                        id="accordionExample">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingSixteen">
                                                                <button class="accordion-button collapsed"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#collapse22" aria-expanded="false"
                                                                    aria-controls="collapse22">
                                                                    <span class="d-flex align-items-center">
                                                                        {{ __('Xendit') }}
                                                                    </span>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2">{{ __('Enable') }}</span>
                                                                        <div
                                                                            class="form-check form-switch custom-switch-v1">
                                                                            <input type="hidden"
                                                                                name="is_xendit_enabled" value="off">
                                                                            <input type="checkbox"
                                                                                class="form-check-input input-primary"
                                                                                name="is_xendit_enabled"
                                                                                id="is_xendit_enabled"
                                                                                {{ isset($admin_payment_setting['is_xendit_enabled']) && $admin_payment_setting['is_xendit_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                            <label class="form-check-label"
                                                                                for="customswitchv1-2"></label>
                                                                        </div>
                                                                    </div>
                                                                </button>
                                                            </h2>
                                                            <div id="collapse22" class="accordion-collapse collapse"
                                                                aria-labelledby="headingSixteen"
                                                                data-bs-parent="#accordionExample">
                                                                <div class="accordion-body">
                                                                    <div class="row">

                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="xendit_api"
                                                                                    class="form-label">{{ __('API Key') }}</label>
                                                                                <input type="text" name="xendit_api"
                                                                                    id="xendit_api" class="form-control"
                                                                                    value="{{ !isset($admin_payment_setting['xendit_api']) || is_null($admin_payment_setting['xendit_api']) ? '' : $admin_payment_setting['xendit_api'] }}"
                                                                                    placeholder="{{ __('API Key') }}">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="xendit_token"
                                                                                    class="form-label">{{ __('Token') }}</label>
                                                                                <input type="text"
                                                                                    name="xendit_token"
                                                                                    id="xendit_token"
                                                                                    class="form-control"
                                                                                    value="{{ !isset($admin_payment_setting['xendit_token']) || is_null($admin_payment_setting['xendit_token']) ? '' : $admin_payment_setting['xendit_token'] }}"
                                                                                    placeholder="{{ __('Token') }}">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Midtrans -->
                                                    <div class="accordion accordion-flush setting-accordion"
                                                        id="accordionExample">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingSeventeen">
                                                                <button class="accordion-button collapsed"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#collapse23" aria-expanded="false"
                                                                    aria-controls="collapse23">
                                                                    <span class="d-flex align-items-center">
                                                                        {{ __('Midtrans') }}
                                                                    </span>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2">{{ __('Enable') }}</span>
                                                                        <div
                                                                            class="form-check form-switch custom-switch-v1">
                                                                            <input type="hidden"
                                                                                name="is_midtrans_enabled"
                                                                                value="off">
                                                                            <input type="checkbox"
                                                                                class="form-check-input input-primary"
                                                                                name="is_midtrans_enabled"
                                                                                id="is_midtrans_enabled"
                                                                                {{ isset($admin_payment_setting['is_midtrans_enabled']) && $admin_payment_setting['is_midtrans_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                            <label class="form-check-label"
                                                                                for="customswitchv1-2"></label>
                                                                        </div>
                                                                    </div>
                                                                </button>
                                                            </h2>
                                                            <div id="collapse23" class="accordion-collapse collapse"
                                                                aria-labelledby="headingSeventeen"
                                                                data-bs-parent="#accordionExample">
                                                                <div class="accordion-body">
                                                                    <label class="paypal-label col-form-label"
                                                                        for="midtrans_mode">{{ __('Midtrans Mode') }}</label>
                                                                    <br>

                                                                    <div class="d-flex">
                                                                        <div class="mr-2" style="margin-right: 15px;">
                                                                            <div class="border card p-3">
                                                                                <div class="form-check">
                                                                                    <label
                                                                                        class="form-check-labe text-dark {{ isset($admin_payment_setting['midtrans_mode']) && $admin_payment_setting['midtrans_mode'] == 'sandbox' ? 'active' : '' }}">
                                                                                        <input type="radio"
                                                                                            name="midtrans_mode"
                                                                                            value="sandbox"
                                                                                            class="form-check-input"
                                                                                            {{ isset($admin_payment_setting['midtrans_mode']) && $admin_payment_setting['midtrans_mode'] == 'sandbox' ? 'checked="checked"' : '' }}>

                                                                                        {{ __('Sandbox') }}
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mr-2">
                                                                            <div class="border card p-3">
                                                                                <div class="form-check">
                                                                                    <label
                                                                                        class="form-check-labe text-dark">
                                                                                        <input type="radio"
                                                                                            name="midtrans_mode"
                                                                                            value="live"
                                                                                            class="form-check-input"
                                                                                            {{ isset($admin_payment_setting['midtrans_mode']) && $admin_payment_setting['midtrans_mode'] == 'live' ? 'checked="checked"' : '' }}>

                                                                                        {{ __('Live') }}
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="midtrans_secret"
                                                                                    class="form-label">{{ __('Secret Key') }}</label>
                                                                                <input type="text"
                                                                                    name="midtrans_secret"
                                                                                    id="midtrans_secret"
                                                                                    class="form-control"
                                                                                    value="{{ !isset($admin_payment_setting['midtrans_secret']) || is_null($admin_payment_setting['midtrans_secret']) ? '' : $admin_payment_setting['midtrans_secret'] }}"
                                                                                    placeholder="{{ __('Secret Key') }}">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Paiementpro --}}
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="heading-paiementpro">
                                                            <button class="accordion-button" type="button"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#collapse-paiementpro"
                                                                aria-expanded="true"
                                                                aria-controls="collapse-paiementpro">
                                                                <span
                                                                    class="d-flex align-items-center">{{ __('Paiementpro') }}</span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('On/Off') }}:</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden"
                                                                            name="is_paiementpro_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_paiementpro_enabled"
                                                                            id="is_paiementpro_enabled"
                                                                            {{ isset($admin_payment_setting['is_paiementpro_enabled']) && $admin_payment_setting['is_paiementpro_enabled'] == 'on' ? 'checked' : '' }}>
                                                                        <label
                                                                            class="custom-control-label form-control-label"
                                                                            for="is_paiementpro_enabled"></label>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapse-paiementpro"
                                                            class="accordion-collapse collapse"
                                                            aria-labelledby="heading-paiementpro"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="paiementpro_merchant_id"
                                                                                class="col-form-label">{{ __('Merchant ID') }}</label>
                                                                            <input type="text"
                                                                                name="paiementpro_merchant_id"
                                                                                id="paiementpro_merchant_id"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['paiementpro_merchant_id']) || is_null($admin_payment_setting['paiementpro_merchant_id']) ? '' : $admin_payment_setting['paiementpro_merchant_id'] }}"
                                                                                placeholder="{{ __('Merchant ID') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    {{-- Nepalste --}}
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="heading-nepalste">
                                                            <button class="accordion-button" type="button"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#collapse-nepalste" aria-expanded="true"
                                                                aria-controls="collapse-nepalste">
                                                                <span
                                                                    class="d-flex align-items-center">{{ __('Nepalste') }}</span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('On/Off') }}:</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_nepalste_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_nepalste_enabled"
                                                                            id="is_nepalste_enabled"
                                                                            {{ isset($admin_payment_setting['is_nepalste_enabled']) && $admin_payment_setting['is_nepalste_enabled'] == 'on' ? 'checked' : '' }}>
                                                                        <label
                                                                            class="custom-control-label form-control-label"
                                                                            for="is_nepalste_enabled"></label>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapse-nepalste" class="accordion-collapse collapse"
                                                            aria-labelledby="heading-nepalste"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-12 pb-4 form-group">
                                                                        <label class="nepalste-label form-label"
                                                                            for="nepalste_mode">{{ __('Nepalste Mode') }}</label>
                                                                        <br>
                                                                        <div class="d-flex">
                                                                            <div class="col-lg-3"
                                                                                style="margin-right: 15px;">
                                                                                <div class="border accordion-header p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-label text-dark">
                                                                                            <input type="radio"
                                                                                                name="nepalste_mode"
                                                                                                value="sandbox"
                                                                                                class="form-check-input"
                                                                                                {{ !isset($admin_payment_setting['nepalste_mode']) || $admin_payment_setting['nepalste_mode'] == '' || $admin_payment_setting['nepalste_mode'] == 'sandbox' ? 'checked="checked"' : '' }}>
                                                                                            {{ __('Sandbox') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-lg-3">
                                                                                <div class="border accordion-header p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-label text-dark">
                                                                                            <input type="radio"
                                                                                                name="nepalste_mode"
                                                                                                value="live"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['nepalste_mode']) && $admin_payment_setting['nepalste_mode'] == 'live' ? 'checked="checked"' : '' }}>
                                                                                            {{ __('Live') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="nepalste_public_key"
                                                                                class="col-form-label">{{ __('Nepalste Public Key') }}</label>
                                                                            <input type="text"
                                                                                name="nepalste_public_key"
                                                                                id="nepalste_public_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['nepalste_public_key']) || is_null($admin_payment_setting['nepalste_public_key']) ? '' : $admin_payment_setting['nepalste_public_key'] }}"
                                                                                placeholder="{{ __('Nepalste Public Key') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="nepalste_secret_key"
                                                                                class="col-form-label">{{ __('Nepalste Secret Key') }}</label>
                                                                            <input type="text"
                                                                                name="nepalste_secret_key"
                                                                                id="nepalste_secret_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['nepalste_secret_key']) || is_null($admin_payment_setting['nepalste_secret_key']) ? '' : $admin_payment_setting['nepalste_secret_key'] }}"
                                                                                placeholder="{{ __('Nepalste Secret Key') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Cinetpay --}}
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="heading-cinetpay">
                                                            <button class="accordion-button" type="button"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#collapse-cinetpay" aria-expanded="true"
                                                                aria-controls="collapse-cinetpay">
                                                                <span
                                                                    class="d-flex align-items-center">{{ __('Cinetpay') }}</span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('On/Off') }}:</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_cinetpay_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_cinetpay_enabled"
                                                                            id="is_cinetpay_enabled"
                                                                            {{ isset($admin_payment_setting['is_cinetpay_enabled']) && $admin_payment_setting['is_cinetpay_enabled'] == 'on' ? 'checked' : '' }}>
                                                                        <label
                                                                            class="custom-control-label form-control-label"
                                                                            for="is_cinetpay_enabled"></label>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapse-cinetpay" class="accordion-collapse collapse"
                                                            aria-labelledby="heading-cinetpay"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="cinetpay_api_key"
                                                                                class="col-form-label">{{ __('Cinetpay Api Key') }}</label>
                                                                            <input type="text"
                                                                                name="cinetpay_api_key"
                                                                                id="cinetpay_api_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['cinetpay_api_key']) || is_null($admin_payment_setting['cinetpay_api_key']) ? '' : $admin_payment_setting['cinetpay_api_key'] }}"
                                                                                placeholder="{{ __('Cinetpay Api Key') }}">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="cinetpay_site_id"
                                                                                class="col-form-label">{{ __('Cinetpay Site Id') }}</label>
                                                                            <input type="text"
                                                                                name="cinetpay_site_id"
                                                                                id="cinetpay_site_id"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['cinetpay_site_id']) || is_null($admin_payment_setting['cinetpay_site_id']) ? '' : $admin_payment_setting['cinetpay_site_id'] }}"
                                                                                placeholder="{{ __('Cinetpay Site Id') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="cinetpay_api_key"
                                                                                class="col-form-label">{{ __('Cinetpay Secret Key') }}</label>
                                                                            <input type="text"
                                                                                name="cinetpay_secret_key"
                                                                                id="cinetpay_secret_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['cinetpay_secret_key']) || is_null($admin_payment_setting['cinetpay_secret_key']) ? '' : $admin_payment_setting['cinetpay_secret_key'] }}"
                                                                                placeholder="{{ __('Cinetpay Secret Key') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Fedapay --}}
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="heading-fedapay">
                                                            <button class="accordion-button" type="button"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#collapse-fedapay" aria-expanded="true"
                                                                aria-controls="collapse-fedapay">
                                                                <span
                                                                    class="d-flex align-items-center">{{ __('Fedapay') }}</span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('On/Off') }}:</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_fedapay_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_fedapay_enabled"
                                                                            id="is_fedapay_enabled"
                                                                            {{ isset($admin_payment_setting['is_fedapay_enabled']) && $admin_payment_setting['is_fedapay_enabled'] == 'on' ? 'checked' : '' }}>
                                                                        <label
                                                                            class="custom-control-label form-control-label"
                                                                            for="is_fedapay_enabled"></label>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapse-fedapay" class="accordion-collapse collapse"
                                                            aria-labelledby="heading-fedapay"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-12 pb-4 form-group">
                                                                        <label class="fedapay-label form-label"
                                                                            for="fedapay_mode">{{ __('Fedapay Mode') }}</label>
                                                                        <br>
                                                                        <div class="d-flex">
                                                                            <div class="col-lg-3"
                                                                                style="margin-right: 15px;">
                                                                                <div class="border accordion-header p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-label text-dark">
                                                                                            <input type="radio"
                                                                                                name="fedapay_mode"
                                                                                                value="sandbox"
                                                                                                class="form-check-input"
                                                                                                {{ !isset($admin_payment_setting['fedapay_mode']) || $admin_payment_setting['fedapay_mode'] == '' || $admin_payment_setting['fedapay_mode'] == 'sandbox' ? 'checked="checked"' : '' }}>
                                                                                            {{ __('Sandbox') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-lg-3">
                                                                                <div class="border accordion-header p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-label text-dark">
                                                                                            <input type="radio"
                                                                                                name="fedapay_mode"
                                                                                                value="live"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['fedapay_mode']) && $admin_payment_setting['fedapay_mode'] == 'live' ? 'checked="checked"' : '' }}>
                                                                                            {{ __('Live') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="fedapay_public_key"
                                                                                class="col-form-label">{{ __('Public Key') }}</label>
                                                                            <input type="text"
                                                                                name="fedapay_public_key"
                                                                                id="fedapay_public_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['fedapay_public_key']) || is_null($admin_payment_setting['fedapay_public_key']) ? '' : $admin_payment_setting['fedapay_public_key'] }}"
                                                                                placeholder="{{ __('Public Key') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="fedapay_secret_key"
                                                                                class="col-form-label">{{ __('Secret Key') }}</label>
                                                                            <input type="text"
                                                                                name="fedapay_secret_key"
                                                                                id="fedapay_secret_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['fedapay_secret_key']) || is_null($admin_payment_setting['fedapay_secret_key']) ? '' : $admin_payment_setting['fedapay_secret_key'] }}"
                                                                                placeholder="{{ __('Secret Key') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- PayHere --}}
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="heading-2-3">
                                                            <button class="accordion-button" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapse18"
                                                                aria-expanded="true" aria-controls="collapse18">
                                                                <span
                                                                    class="d-flex align-items-center">{{ __('PayHere') }}</span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('On/Off') }}:</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_payhere_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_payhere_enabled"
                                                                            id="is_payhere_enabled"
                                                                            {{ isset($admin_payment_setting['is_payhere_enabled']) && $admin_payment_setting['is_payhere_enabled'] == 'on' ? 'checked' : '' }}>
                                                                        <label
                                                                            class="custom-control-label form-control-label"
                                                                            for="is_payhere_enabled"></label>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapse18" class="accordion-collapse collapse"
                                                            aria-labelledby="heading-2-3"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-12 pb-4">
                                                                        <label class="payhere-label col-form-label"
                                                                            for="payhere_mode">{{ __('PayHere Mode') }}</label>
                                                                        <br>
                                                                        <div class="d-flex">
                                                                            <div class="col-lg-3"
                                                                                style="margin-right: 15px;">
                                                                                <div class="border accordion-header p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-label text-dark">
                                                                                            <input type="radio"
                                                                                                name="payhere_mode"
                                                                                                value="sandbox"
                                                                                                class="form-check-input"
                                                                                                {{ !isset($admin_payment_setting['payhere_mode']) || $admin_payment_setting['payhere_mode'] == '' || $admin_payment_setting['payhere_mode'] == 'sandbox' ? 'checked="checked"' : '' }}>
                                                                                            {{ __('Sandbox') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-lg-3">
                                                                                <div class="border accordion-header p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-label text-dark">
                                                                                            <input type="radio"
                                                                                                name="payhere_mode"
                                                                                                value="live"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['payhere_mode']) && $admin_payment_setting['payhere_mode'] == 'live' ? 'checked="checked"' : '' }}>
                                                                                            {{ __('Live') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="payhere_merchant_id"
                                                                                class="col-form-label">{{ __('Merchant ID') }}</label>
                                                                            <input type="text"
                                                                                name="payhere_merchant_id"
                                                                                id="payhere_merchant_id"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['payhere_merchant_id']) || is_null($admin_payment_setting['payhere_merchant_id']) ? '' : $admin_payment_setting['payhere_merchant_id'] }}"
                                                                                placeholder="{{ __('Merchant ID') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="payhere_merchant_secret"
                                                                                class="col-form-label">{{ __('Merchant Secret') }}</label>
                                                                            <input type="text"
                                                                                name="payhere_merchant_secret"
                                                                                id="payhere_merchant_secret"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['payhere_merchant_secret']) || is_null($admin_payment_setting['payhere_merchant_secret']) ? '' : $admin_payment_setting['payhere_merchant_secret'] }}"
                                                                                placeholder="{{ __('Merchant Secret') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="payhere_app_id"
                                                                                class="col-form-label">{{ __('App ID') }}</label>
                                                                            <input type="text" name="payhere_app_id"
                                                                                id="payhere_app_id" class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['payhere_app_id']) || is_null($admin_payment_setting['payhere_app_id']) ? '' : $admin_payment_setting['payhere_app_id'] }}"
                                                                                placeholder="{{ __('App ID') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="payhere_app_secret"
                                                                                class="col-form-label">{{ __('App Secret') }}</label>
                                                                            <input type="text"
                                                                                name="payhere_app_secret"
                                                                                id="payhere_app_secret"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['payhere_app_secret']) || is_null($admin_payment_setting['payhere_app_secret']) ? '' : $admin_payment_setting['payhere_app_secret'] }}"
                                                                                placeholder="{{ __('App Secret') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>


                                                    {{-- Tap --}}
                                                    <div class="accordion-item card shadow-none">
                                                        <h2 class="accordion-header" id="heading-2-30">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapse32"
                                                                aria-expanded="true" aria-controls="collapse32">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('Tap') }}
                                                                </span>

                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('On/Off: ') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_tap_enabled"
                                                                            value="off">
                                                                        <input type="checkbox"
                                                                            class="form-check-input input-primary"
                                                                            name="is_tap_enabled" id="is_tap_enabled"
                                                                            {{ isset($admin_payment_setting['is_tap_enabled']) && $admin_payment_setting['is_tap_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                        <label class="form-check-label"
                                                                            for="customswitchv1-2"></label>
                                                                    </div>
                                                                </div>

                                                            </button>
                                                        </h2>

                                                        <div id="collapse32" class="accordion-collapse collapse"
                                                            aria-labelledby="heading-2-30"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-check form-group">
                                                                            <label for="company_tap_secret_key"
                                                                                class="form-label">{{ __('Secret Key') }}</label>
                                                                            <input type="text"
                                                                                name="company_tap_secret_key"
                                                                                id="company_tap_secret_key"
                                                                                class="form-control"
                                                                                value="{{ !isset($admin_payment_setting['company_tap_secret_key']) || is_null($admin_payment_setting['company_tap_secret_key']) ? '' : $admin_payment_setting['company_tap_secret_key'] }}"
                                                                                placeholder="{{ __('Secret Key') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- AuthorizeNet --}}
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="heading-2-27">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapse33"
                                                                aria-expanded="true" aria-controls="collapse33">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('AuthorizeNet') }}
                                                                </span>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('On/Off:') }}</span>
                                                                    <div
                                                                        class="form-check form-switch d-inline-block custom-switch-v1">
                                                                        <input type="hidden"
                                                                            name="is_authorizenet_enabled"
                                                                            value="off">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            name="is_authorizenet_enabled"
                                                                            id="is_authorizenet_enabled"
                                                                            {{ isset($admin_payment_setting['is_authorizenet_enabled']) && $admin_payment_setting['is_authorizenet_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                        <label class="custom-control-label form-label"
                                                                            for="is_authorizenet_enabled"></label>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapse33" class="accordion-collapse collapse"
                                                            aria-labelledby="heading-2-27"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <label for="authorizenet_mode"
                                                                            class="col-form-label">{{ __('AuthorizeNet Mode') }}</label>
                                                                        <div class="d-flex">
                                                                            <div class="me-2">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark {{ isset($admin_payment_setting['authorizenet_mode']) && $admin_payment_setting['authorizenet_mode'] == 'sandbox' ? 'active' : '' }}">
                                                                                            <input type="radio"
                                                                                                name="authorizenet_mode"
                                                                                                value="sandbox"
                                                                                                class="form-check-input"
                                                                                                {{ (isset($admin_payment_setting['authorizenet_mode']) && $admin_payment_setting['authorizenet_mode'] == '') || (isset($admin_payment_setting['authorizenet_mode']) && $admin_payment_setting['authorizenet_mode'] == 'sandbox') ? 'checked="checked"' : '' }}>{{ __('Sandbox') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="me-2">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark {{ isset($admin_payment_setting['authorizenet_mode']) && $admin_payment_setting['authorizenet_mode'] == 'live' ? 'active' : '' }}">
                                                                                            <input type="radio"
                                                                                                name="authorizenet_mode"
                                                                                                value="live"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['authorizenet_mode']) && $admin_payment_setting['authorizenet_mode'] == 'live' ? 'checked="checked"' : '' }}>{{ __('Live') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="authorizenet_merchant_login_id"
                                                                                class="col-form-label">{{ __('Merchant Login ID') }}</label>
                                                                            <input class="form-control"
                                                                                placeholder="Enter Merchant Login ID"
                                                                                name="authorizenet_merchant_login_id"
                                                                                type="text"
                                                                                value="{{ $admin_payment_setting['authorizenet_merchant_login_id'] ?? '' }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label
                                                                                for="authorizenet_merchant_transaction_key"
                                                                                class="col-form-label">{{ __('Merchant Transaction Key') }}</label>
                                                                            <input class="form-control"
                                                                                placeholder="Enter Merchant Transaction Key"
                                                                                name="authorizenet_merchant_transaction_key"
                                                                                type="text"
                                                                                value="{{ $admin_payment_setting['authorizenet_merchant_transaction_key'] ?? '' }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Khalti --}}
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="heading-2-30">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapse34"
                                                                aria-expanded="true" aria-controls="collapse34">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('Khalti') }}
                                                                </span>

                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('On/Off: ') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_khalti_enabled"
                                                                            value="off">
                                                                        <input type="checkbox"
                                                                            class="form-check-input input-primary"
                                                                            name="is_khalti_enabled"
                                                                            id="is_khalti_enabled"
                                                                            {{ isset($admin_payment_setting['is_khalti_enabled']) && $admin_payment_setting['is_khalti_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                        <label class="form-check-label"
                                                                            for="customswitchv1-2"></label>
                                                                    </div>
                                                                </div>

                                                            </button>
                                                        </h2>

                                                        <div id="collapse34" class="accordion-collapse collapse"
                                                            aria-labelledby="heading-2-30"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <label for="khalti_mode"
                                                                            class="col-form-label">{{ __('Khalti Mode') }}</label>
                                                                        <div class="d-flex">
                                                                            <div class="me-2">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark {{ isset($admin_payment_setting['khalti_mode']) && $admin_payment_setting['khalti_mode'] == 'sandbox' ? 'active' : '' }}">
                                                                                            <input type="radio"
                                                                                                name="khalti_mode"
                                                                                                value="sandbox"
                                                                                                class="form-check-input"
                                                                                                {{ (isset($admin_payment_setting['khalti_mode']) && $admin_payment_setting['khalti_mode'] == '') || (isset($admin_payment_setting['khalti_mode']) && $admin_payment_setting['khalti_mode'] == 'sandbox') ? 'checked="checked"' : '' }}>{{ __('Sandbox') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="me-2">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark {{ isset($admin_payment_setting['khalti_mode']) && $admin_payment_setting['khalti_mode'] == 'live' ? 'active' : '' }}">
                                                                                            <input type="radio"
                                                                                                name="khalti_mode"
                                                                                                value="live"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['khalti_mode']) && $admin_payment_setting['khalti_mode'] == 'live' ? 'checked="checked"' : '' }}>{{ __('Live') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="khalti_secret_key"
                                                                                class="col-form-label">{{ __('Secret Key') }}</label>
                                                                            <input class="form-control"
                                                                                placeholder="Enter Secret Key"
                                                                                name="khalti_secret_key" type="text"
                                                                                value="{{ $admin_payment_setting['khalti_secret_key'] ?? '' }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="khalti_public_key"
                                                                                class="col-form-label">{{ __('Public Key') }}</label>
                                                                            <input class="form-control"
                                                                                placeholder="Enter Public Key"
                                                                                name="khalti_public_key" type="text"
                                                                                value="{{ $admin_payment_setting['khalti_public_key'] ?? '' }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Ozow --}}
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="heading-2-35">
                                                            <button class="accordion-button collapsed" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#collapse35"
                                                                aria-expanded="true" aria-controls="collapse35">
                                                                <span class="d-flex align-items-center">
                                                                    {{ __('Ozow') }}
                                                                </span>

                                                                <div class="d-flex align-items-center">
                                                                    <span class="me-2">{{ __('On/Off: ') }}</span>
                                                                    <div class="form-check form-switch custom-switch-v1">
                                                                        <input type="hidden" name="is_ozow_enabled"
                                                                            value="off">
                                                                        <input type="checkbox"
                                                                            class="form-check-input input-primary"
                                                                            name="is_ozow_enabled" id="is_ozow_enabled"
                                                                            {{ isset($admin_payment_setting['is_ozow_enabled']) && $admin_payment_setting['is_ozow_enabled'] == 'on' ? 'checked="checked"' : '' }}>
                                                                        <label class="form-check-label"
                                                                            for="customswitchv1-2"></label>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapse35" class="accordion-collapse collapse"
                                                            aria-labelledby="heading-2-35"
                                                            data-bs-parent="#accordionExample">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <label for="ozow_mode"
                                                                            class="col-form-label">{{ __('Ozow Mode') }}</label>
                                                                        <div class="d-flex">
                                                                            <div class="me-2">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark {{ isset($admin_payment_setting['ozow_mode']) && $admin_payment_setting['ozow_mode'] == 'sandbox' ? 'active' : '' }}">
                                                                                            <input type="radio"
                                                                                                name="ozow_mode"
                                                                                                value="sandbox"
                                                                                                class="form-check-input"
                                                                                                {{ (isset($admin_payment_setting['ozow_mode']) && $admin_payment_setting['ozow_mode'] == '') || (isset($admin_payment_setting['ozow_mode']) && $admin_payment_setting['ozow_mode'] == 'sandbox') ? 'checked="checked"' : '' }}>{{ __('Sandbox') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="me-2">
                                                                                <div class="border card p-3">
                                                                                    <div class="form-check">
                                                                                        <label
                                                                                            class="form-check-labe text-dark {{ isset($admin_payment_setting['ozow_mode']) && $admin_payment_setting['ozow_mode'] == 'live' ? 'active' : '' }}">
                                                                                            <input type="radio"
                                                                                                name="ozow_mode"
                                                                                                value="live"
                                                                                                class="form-check-input"
                                                                                                {{ isset($admin_payment_setting['ozow_mode']) && $admin_payment_setting['ozow_mode'] == 'live' ? 'checked="checked"' : '' }}>{{ __('Live') }}
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="ozow_site_key"
                                                                                class="col-form-label">{{ __('Ozow Site Key') }}</label>
                                                                            <input class="form-control"
                                                                                placeholder="Enter Site Key"
                                                                                name="ozow_site_key" type="text"
                                                                                value="{{ $admin_payment_setting['ozow_site_key'] ?? '' }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="ozow_private_key"
                                                                                class="col-form-label">{{ __('Ozow Private Key') }}</label>
                                                                            <input class="form-control"
                                                                                placeholder="Enter Private Key"
                                                                                name="ozow_private_key" type="text"
                                                                                value="{{ $admin_payment_setting['ozow_private_key'] ?? '' }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="ozow_api_key"
                                                                                class="col-form-label">{{ __('Ozow Api Key') }}</label>
                                                                            <input class="form-control"
                                                                                placeholder="Enter Api Key"
                                                                                name="ozow_api_key" type="text"
                                                                                value="{{ $admin_payment_setting['ozow_api_key'] ?? '' }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                           
                        </div>
                        <div class="card-footer text-end">
                                <input class="btn btn-print-invoice  btn-primary m-r-10" type="submit"
                                    value="{{ __('Save Changes') }}">
                        </div>
                        </form>
                    </div>

                    <!--ReCaptcha Setting-->
                    <div id="useradd-4" class="card mb-3">
                        {{ Form::model($settings, ['route' => 'recaptcha.settings.store', 'method' => 'post', 'accept-charset' => 'UTF-8', 'class' => 'mb-0']) }}
                        @csrf
                        <div class="card-header row d-flex justify-content-between">
                            <div class="col-auto">
                                <h5>{{ __('ReCaptcha Settings') }}</h5>
                                <small class="text-muted">
                                    <a href="https://phppot.com/php/how-to-get-google-recaptcha-site-and-secret-key/"
                                        target="_blank" class="text-blue">
                                        (How to Get Google reCaptcha Site and Secret key)
                                    </a>
                                </small><br>
                            </div>
                            <div class="col-auto">
                                <div class="form-switch form-switch-right" style="width: 86.1375px; height: 41.4px;">
                                    <input type="checkbox" class="form-check-input" name="recaptcha_module"
                                        data-toggle="switchbutton" id="recaptcha_module" value="yes"
                                        {{ $settings['recaptcha_module'] == 'yes' ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group col switch-width">
                                        {{ Form::label('google_recaptcha_version', __('Google Recaptcha Version'), ['class' => ' col-form-label']) }}

                                        {{ Form::select('google_recaptcha_version', $google_recaptcha_version, isset($settings['google_recaptcha_version']) ? $settings['google_recaptcha_version'] : 'v2-checkbox', ['id' => 'google_recaptcha_version', 'class' => 'form-control choices', 'searchEnabled' => 'true']) }}
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('google_recaptcha_key', __('Google Recaptcha Key'), ['class' => 'form-label']) }}
                                        {{ Form::text('google_recaptcha_key', null, ['class' => 'form-control', 'placeholder' => __('Enter Google Recaptcha Key')]) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('google_recaptcha_secret', __('Google Recaptcha Secret Key'), ['class' => 'form-label']) }}
                                        {{ Form::text('google_recaptcha_secret', null, ['class' => 'form-control', 'placeholder' => __('Enter Google Recaptcha Secret Key')]) }}
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <input class="btn btn-print-invoice  btn-primary m-r-10" type="submit"
                                value="{{ __('Save Changes') }}">

                        </div>
                        {{ Form::close() }}
                    </div>

                    <!--storage Setting-->
                    <div id="useradd-5" class="card mb-3">
                        {{ Form::open(['route' => 'storage.setting.store', 'enctype' => 'multipart/form-data','class' => 'mb-0']) }}
                        <div class="card-header">
                            <div class="row">
                                <div class="col-lg-10 col-md-10 col-sm-10">
                                    <h5 class="">{{ __('Storage Settings') }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="pe-2">
                                    <input type="radio" class="btn-check" name="storage_setting"
                                        id="local-outlined" autocomplete="off"
                                        {{ $setting['storage_setting'] == 'local' ? 'checked' : '' }} value="local"
                                        checked>
                                    <label class="btn btn-outline-primary"
                                        for="local-outlined">{{ __('Local') }}</label>
                                </div>
                                <div class="pe-2">
                                    <input type="radio" class="btn-check" name="storage_setting" id="s3-outlined"
                                        autocomplete="off" {{ $setting['storage_setting'] == 's3' ? 'checked' : '' }}
                                        value="s3">
                                    <label class="btn btn-outline-primary" for="s3-outlined">
                                        {{ __('AWS S3') }}</label>
                                </div>

                                <div class="pe-2">
                                    <input type="radio" class="btn-check" name="storage_setting"
                                        id="wasabi-outlined" autocomplete="off"
                                        {{ $setting['storage_setting'] == 'wasabi' ? 'checked' : '' }} value="wasabi">
                                    <label class="btn btn-outline-primary"
                                        for="wasabi-outlined">{{ __('Wasabi') }}</label>
                                </div>
                            </div>
                            <div class="mt-2">
                                <div
                                    class="local-setting row {{ $setting['storage_setting'] == 'local' ? ' ' : 'd-none' }}">
                                    {{-- <h4 class="small-title">{{ __('Local Settings') }}</h4> --}}
                                    <div class="form-group col-8 switch-width">
                                        {{ Form::label('local_storage_validation', __('Only Upload Files'), ['class' => ' form-label']) }}
                                        <select name="local_storage_validation[]" class="select2"
                                            id="local_storage_validation" multiple>
                                            @foreach ($file_type as $f)
                                                <option @if (in_array($f, $local_storage_validations)) selected @endif>
                                                    {{ $f }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label class="form-label"
                                                for="local_storage_max_upload_size">{{ __('Max upload size ( In KB)') }}</label>
                                            <input type="number" name="local_storage_max_upload_size"
                                                class="form-control"
                                                value="{{ !isset($setting['local_storage_max_upload_size']) || is_null($setting['local_storage_max_upload_size']) ? '' : $setting['local_storage_max_upload_size'] }}"
                                                placeholder="{{ __('Max upload size') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="s3-setting row {{ $setting['storage_setting'] == 's3' ? ' ' : 'd-none' }}">

                                    <div class=" row ">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="s3_key">{{ __('S3 Key') }}</label>
                                                <input type="text" name="s3_key" class="form-control"
                                                    value="{{ !isset($setting['s3_key']) || is_null($setting['s3_key']) ? '' : $setting['s3_key'] }}"
                                                    placeholder="{{ __('S3 Key') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="s3_secret">{{ __('S3 Secret') }}</label>
                                                <input type="text" name="s3_secret" class="form-control"
                                                    value="{{ !isset($setting['s3_secret']) || is_null($setting['s3_secret']) ? '' : $setting['s3_secret'] }}"
                                                    placeholder="{{ __('S3 Secret') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="s3_region">{{ __('S3 Region') }}</label>
                                                <input type="text" name="s3_region" class="form-control"
                                                    value="{{ !isset($setting['s3_region']) || is_null($setting['s3_region']) ? '' : $setting['s3_region'] }}"
                                                    placeholder="{{ __('S3 Region') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="s3_bucket">{{ __('S3 Bucket') }}</label>
                                                <input type="text" name="s3_bucket" class="form-control"
                                                    value="{{ !isset($setting['s3_bucket']) || is_null($setting['s3_bucket']) ? '' : $setting['s3_bucket'] }}"
                                                    placeholder="{{ __('S3 Bucket') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label" for="s3_url">{{ __('S3 URL') }}</label>
                                                <input type="text" name="s3_url" class="form-control"
                                                    value="{{ !isset($setting['s3_url']) || is_null($setting['s3_url']) ? '' : $setting['s3_url'] }}"
                                                    placeholder="{{ __('S3 URL') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="s3_endpoint">{{ __('S3 Endpoint') }}</label>
                                                <input type="text" name="s3_endpoint" class="form-control"
                                                    value="{{ !isset($setting['s3_endpoint']) || is_null($setting['s3_endpoint']) ? '' : $setting['s3_endpoint'] }}"
                                                    placeholder="{{ __('S3 Endpoint') }}">
                                            </div>
                                        </div>
                                        <div class="form-group col-8 switch-width">
                                            {{ Form::label('s3_storage_validation', __('Only Upload Files'), ['class' => ' form-label']) }}
                                            <select name="s3_storage_validation[]" class="select2"
                                                id="s3_storage_validation" multiple>
                                                @foreach ($file_type as $f)
                                                    <option @if (in_array($f, $s3_storage_validations)) selected @endif>
                                                        {{ $f }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="s3_max_upload_size">{{ __('Max upload size ( In KB)') }}</label>
                                                <input type="number" name="s3_max_upload_size" class="form-control"
                                                    value="{{ !isset($setting['s3_max_upload_size']) || is_null($setting['s3_max_upload_size']) ? '' : $setting['s3_max_upload_size'] }}"
                                                    placeholder="{{ __('Max upload size') }}">
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div
                                    class="wasabi-setting row {{ $setting['storage_setting'] == 'wasabi' ? ' ' : 'd-none' }}">
                                    <div class=" row ">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="s3_key">{{ __('Wasabi Key') }}</label>
                                                <input type="text" name="wasabi_key" class="form-control"
                                                    value="{{ !isset($setting['wasabi_key']) || is_null($setting['wasabi_key']) ? '' : $setting['wasabi_key'] }}"
                                                    placeholder="{{ __('Wasabi Key') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="s3_secret">{{ __('Wasabi Secret') }}</label>
                                                <input type="text" name="wasabi_secret" class="form-control"
                                                    value="{{ !isset($setting['wasabi_secret']) || is_null($setting['wasabi_secret']) ? '' : $setting['wasabi_secret'] }}"
                                                    placeholder="{{ __('Wasabi Secret') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="s3_region">{{ __('Wasabi Region') }}</label>
                                                <input type="text" name="wasabi_region" class="form-control"
                                                    value="{{ !isset($setting['wasabi_region']) || is_null($setting['wasabi_region']) ? '' : $setting['wasabi_region'] }}"
                                                    placeholder="{{ __('Wasabi Region') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="wasabi_bucket">{{ __('Wasabi Bucket') }}</label>
                                                <input type="text" name="wasabi_bucket" class="form-control"
                                                    value="{{ !isset($setting['wasabi_bucket']) || is_null($setting['wasabi_bucket']) ? '' : $setting['wasabi_bucket'] }}"
                                                    placeholder="{{ __('Wasabi Bucket') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="wasabi_url">{{ __('Wasabi URL') }}</label>
                                                <input type="text" name="wasabi_url" class="form-control"
                                                    value="{{ !isset($setting['wasabi_url']) || is_null($setting['wasabi_url']) ? '' : $setting['wasabi_url'] }}"
                                                    placeholder="{{ __('Wasabi URL') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="wasabi_root">{{ __('Wasabi Root') }}</label>
                                                <input type="text" name="wasabi_root" class="form-control"
                                                    value="{{ !isset($setting['wasabi_root']) || is_null($setting['wasabi_root']) ? '' : $setting['wasabi_root'] }}"
                                                    placeholder="{{ __('Wasabi Root') }}">
                                            </div>
                                        </div>
                                        <div class="form-group col-8 switch-width">
                                            {{ Form::label('wasabi_storage_validation', __('Only Upload Files'), ['class' => 'form-label']) }}

                                            <select name="wasabi_storage_validation[]" class="select2"
                                                id="wasabi_storage_validation" multiple>
                                                @foreach ($file_type as $f)
                                                    <option @if (in_array($f, $wasabi_storage_validations)) selected @endif>
                                                        {{ $f }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label"
                                                    for="wasabi_root">{{ __('Max upload size ( In KB)') }}</label>
                                                <input type="number" name="wasabi_max_upload_size"
                                                    class="form-control"
                                                    value="{{ !isset($setting['wasabi_max_upload_size']) || is_null($setting['wasabi_max_upload_size']) ? '' : $setting['wasabi_max_upload_size'] }}"
                                                    placeholder="{{ __('Max upload size') }}">
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                                <input class="btn btn-print-invoice  btn-primary m-r-10" type="submit"
                                    value="{{ __('Save Changes') }}">
                        </div>
                        {{ Form::close() }}
                    </div>

                    {{-- SEO Setting --}}
                    <div class="card mb-3" id="useradd-6">
                        {{ Form::open(['url' => route('seo.settings'), 'enctype' => 'multipart/form-data','class' => 'mb-0']) }}
                        <div class="card-header">
                            <h5>{{ __('SEO Settings') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if ($enable_chatgpt)
                                    <div>
                                        <a href="javascript:void(0)" data-size="md" data-ajax-popup-over="true"
                                            data-url="{{ route('generate', ['seo settings']) }}"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="{{ __('Generate') }}"
                                            data-title="{{ __('Generate content with AI') }}"
                                            class="btn btn-primary btn-sm float-end">
                                            <i class="fas fa-robot"></i>
                                            {{ __('Generate with AI') }}
                                        </a>
                                    </div>
                                @endif
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        {{ Form::label('meta_keywords', __('Meta Keywords'), ['class' => 'col-form-label']) }}
                                        {{ Form::text('meta_keywords', !empty($settings['meta_keywords']) ? $settings['meta_keywords'] : '', ['class' => 'form-control ', 'placeholder' => __('Meta Keywords')]) }}
                                    </div>

                                    <div class="form-group">
                                        {{ Form::label('meta_description', __('Meta Description'), ['class' => 'form-label']) }}
                                        {{ Form::textarea('meta_description', !empty($settings['meta_description']) ? $settings['meta_description'] : '', ['class' => 'form-control ', 'row' => 3, 'placeholder' => 'Enter Meta Description']) }}
                                    </div>
                                </div>
                                {{-- <div class="col-md-6">
                                    <div class="form-group ">
                                        {{ Form::label('Meta Image', __('Meta Image'), ['class' => 'col-form-label ']) }}
                                            <div class="card-body pt-0">
                                                <div class="setting-card">
                                                    <div class="logo-content">
                                                        <a href="{{ $meta_image . '/' . (isset($settings['meta_image']) && !empty($settings['meta_image']) ? $settings['meta_image'] : 'meta_image.png') }}" target="_blank"> <img id="meta" src="{{ $meta_image . '/' . (isset($settings['meta_image']) && !empty($settings['meta_image']) ? $settings['meta_image'] : 'meta_image.png') }}" width="400px" class="img_setting"> </a>
                                                    </div>
                                                    <div class="choose-files mt-4">
                                                        <label for="meta_image">
                                                            <div class=" bg-primary logo"> <i class="ti ti-upload px-1"></i>{{ __('Choose file here') }}
                                                            </div>
                                                            <input style="margin-top: -40px;" type="file" class="form-control file" name="meta_image" id="meta_image" data-filename="meta_image" onchange="document.getElementById('meta').src = window.URL.createObjectURL(this.files[0])">
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div> --}}
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            {{ Form::label('Meta Image', __('Meta Image'), ['class' => 'col-form-label ']) }}
                                        </div>
                                        <div class="setting-card">
                                            <div class="logo-content">
                                                <a href="{{ $meta_image . '/' . (isset($settings['meta_image']) && !empty($settings['meta_image']) ? $settings['meta_image'] : 'meta_image.png') . '?' . time() }}"
                                                    target="_blank"> <img id="meta"
                                                        src="{{ $meta_image . '/' . (isset($settings['meta_image']) && !empty($settings['meta_image']) ? $settings['meta_image'] : 'meta_image.png') . '?' . time() }}"
                                                        width="400px" class="img_setting"> </a>
                                            </div>
                                            <div class="choose-files mt-4">
                                                <label for="meta_image">
                                                    <div class=" bg-primary logo"> <i
                                                            class="ti ti-upload px-1"></i>{{ __('Choose file here') }}
                                                    </div>
                                                    <input style="margin-top: -40px;" type="file"
                                                        class="form-control file" name="meta_image" id="meta_image"
                                                        data-filename="meta_image"
                                                        onchange="document.getElementById('meta').src = window.URL.createObjectURL(this.files[0])">
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <button class="btn-submit btn btn-primary m-r-10" type="submit">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                        {{ Form::close() }}
                    </div>


                    {{-- Cookie Consent --}}
                    <div class="card" id="useradd-7">
                        {{ Form::model($settings, ['route' => 'cookie.setting', 'method' => 'post','class' => 'mb-0']) }}
                        <div class="card-header flex-column flex-lg-row  d-flex align-items-lg-center gap-2 justify-content-between">
                            <h5>{{ __('Cookie Settings') }}</h5>
                            <div class="d-flex align-items-center">
                                {{ Form::label('enable_cookie', __('Enable cookie'), ['class' => 'col-form-label p-0 fw-bold me-3']) }}
                                <div class="custom-control custom-switch" onclick="enablecookie()">
                                    <input type="checkbox" data-toggle="switchbutton" data-onstyle="primary"
                                        name="enable_cookie" class="form-check-input input-primary "
                                        id="enable_cookie" {{ $settings['enable_cookie'] == 'on' ? ' checked ' : '' }}>
                                    <label class="custom-control-label mb-1" for="enable_cookie"></label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body cookieDiv {{ $settings['enable_cookie'] == 'off' ? 'disabledCookie ' : '' }}">
                            <div class="row ">
                                @if ($enable_chatgpt)
                                    <div>
                                        <a href="javascript:void(0)" data-size="md" data-ajax-popup-over="true"
                                            data-url="{{ route('generate', ['cookie']) }}" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="{{ __('Generate') }}"
                                            data-title="{{ __('Generate content with AI') }}"
                                            class="btn btn-primary btn-sm float-end">
                                            <i class="fas fa-robot"></i>
                                            {{ __('Generate with AI') }}
                                        </a>
                                    </div>
                                @endif
                                <div class="col-md-6">
                                    <div class="form-check form-switch custom-switch-v1" id="cookie_log">
                                        <input type="checkbox" name="cookie_logging"
                                            class="form-check-input input-primary cookie_setting" id="cookie_logging"
                                            onclick="enableButton()"
                                            {{ $settings['cookie_logging'] == 'on' ? ' checked ' : '' }}>
                                        <label class="form-check-label"
                                            for="cookie_logging">{{ __('Enable logging') }}</label>
                                    </div>
                                    <div class="form-group">
                                        {{ Form::label('cookie_title', __('Cookie Title'), ['class' => 'col-form-label']) }}
                                        {{ Form::text('cookie_title', null, ['class' => 'form-control cookie_setting','placeholder' => __('Enter Cookie Title')]) }}
                                    </div>
                                    <div class="form-group ">
                                        {{ Form::label('cookie_description', __('Cookie Description'), ['class' => ' form-label']) }}
                                        {!! Form::textarea('cookie_description', null, ['class' => 'form-control cookie_setting', 'rows' => '3','placeholder' => __('Enter Cookie Description')]) !!}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch custom-switch-v1 ">
                                        <input type="checkbox" name="necessary_cookies"
                                            class="form-check-input input-primary" id="necessary_cookies" checked
                                            onclick="return false">
                                        <label class="form-check-label"
                                            for="necessary_cookies">{{ __('Strictly necessary cookies') }}</label>
                                    </div>
                                    <div class="form-group ">
                                        {{ Form::label('strictly_cookie_title', __(' Strictly Cookie Title'), ['class' => 'col-form-label']) }}
                                        {{ Form::text('strictly_cookie_title', null, ['class' => 'form-control cookie_setting','placeholder' => __('Enter Strictly Cookie Title')]) }}
                                    </div>
                                    <div class="form-group ">
                                        {{ Form::label('strictly_cookie_description', __('Strictly Cookie Description'), ['class' => ' form-label']) }}
                                        {!! Form::textarea('strictly_cookie_description', null, [
                                            'class' => 'form-control cookie_setting ',
                                            'rows' => '3',
                                            'placeholder' => __('Enter Strictly Cookie Description')
                                        ]) !!}
                                    </div>
                                </div>
                                <div class="col-12">
                                    <h5>{{ __('More Information') }}</h5>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('more_information_description', __('Contact Us Description'), ['class' => 'col-form-label']) }}
                                        {{ Form::text('more_information_description', null, ['class' => 'form-control cookie_setting','placeholder' => __('Enter Contact Us Description') ]) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('contactus_url', __('Contact Us URL'), ['class' => 'col-form-label']) }}
                                        {{ Form::text('contactus_url', null, ['class' => 'form-control cookie_setting', 'placeholder' => __('Enter Contact Us URL') ]) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="card-footer d-flex align-items-center gap-2 flex-sm-column flex-lg-row justify-content-between">
                            <div>
                                @if (isset($settings['cookie_logging']) && $settings['cookie_logging'] == 'on')
                                    <label for="file"
                                        class="form-label">{{ __('Download cookie accepted data') }}</label>
                                    <a href="{{ asset(Storage::url('uploads/sample')) . '/data.csv' }}"
                                        class="btn btn-primary mr-2 ">
                                        <i class="ti ti-download"></i>
                                    </a>
                                @endif
                            </div>
                            <input type="submit"  class="btn-submit btn btn-primary m-r-10" value="{{ __('Save Changes') }}" class="btn btn-primary">
                        </div>
                        {{ Form::close() }}
                    </div>

                    <!--cache Setting-->
                    <div id="useradd-8" class="card">
                        <div class="card-header">
                            <h5>{{ __('Cache Settings') }}</h5>
                            <small class="text-secondary font-weight-bold">
                                {{ __('This is a page meant for more advanced users, simply ignore it if you don`t understand what cache is.') }}
                            </small>
                        </div>
                        <div class="card-body">
                            <div class="col-12 form-group">
                                <label for="Current cache size"
                                    class="col-form-label bold">{{ __('Current cache size') }}</label>
                                <div class="input-group search-form">
                                    <input type="text" value="{{ Utility::GetCacheSize() }}" class="form-control"
                                        readonly>
                                    <span class="input-group-text bg-transparent">{{ __('MB') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="{{ url('config-cache') }}"
                                class="btn btn-print-invoice btn-primary m-r-10">{{ __('Clear Cache') }}</a>
                        </div>
                    </div>

                    <!-- chatgpt key  -->
                    <div id="useradd-9" class="card">
                        <div class="card-header">
                            {{ Form::model($settings, ['route' => 'settings.chatgptkey', 'method' => 'post','class' => 'mb-0']) }}
                            <h5>{{ __('Chat GPT Key Settings') }}</h5>
                            <small>{{ __('Edit your key details') }}</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="form-group col-6">
                                    <label for="Current cache size"
                                        class="col-form-label bold">{{ __('Chat GPT Key') }}</label>
                                    {{ Form::text('chatgpt_key', isset($settings['chatgpt_key']) ? $settings['chatgpt_key'] : '', ['class' => 'form-control', 'placeholder' => __('Enter Chatgpt Key Here')]) }}
                                </div>

                                <div class="form-group col-6">
                                    <label for="Current cache size"
                                        class="col-form-label bold">{{ __('Chat GPT Model name') }}</label>
                                    {{ Form::text('chatgpt_model_name', isset($settings['chatgpt_model_name']) ? $settings['chatgpt_model_name'] : '', ['class' => 'form-control', 'placeholder' => __('Enter Chatgpt Model Name')]) }}
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button class="btn btn-primary m-r-10" type="submit">{{ __('Save Changes') }}</button>
                        </div>
                        {{ Form::close() }}
                    </div>

                    <!-- [ sample-page ] end -->
                </div>
            </div>
@endsection

@push('script-page')
    <script>
        $(document).on('click', 'input[name="theme_color"]', function() {
            var eleParent = $(this).attr('data-theme');
            $('#themefile').val(eleParent);
            var imgpath = $(this).attr('data-imgpath');
            $('.' + eleParent + '_img').attr('src', imgpath);
        });

        $(document).ready(function() {
            setTimeout(function(e) {
                var checked = $("input[type=radio][name='theme_color']:checked");
                $('#themefile').val(checked.attr('data-theme'));
                $('.' + checked.attr('data-theme') + '_img').attr('src', checked.attr('data-imgpath'));
            }, 300);
        });

        // function check_theme(color_val) {

        //     $('.theme-color').prop('checked', false);
        //     $('input[value="' + color_val + '"]').prop('checked', true);
        //     $('#color_value').val(color_val);
        // }
    </script>

    <script>
        $('.colorPicker').on('click', function(e) {
            $('body').removeClass('custom-color');
            if (/^theme-\d+$/) {
                $('body').removeClassRegex(/^theme-\d+$/);
            }
            $('body').addClass('custom-color');
            $('.themes-color-change').removeClass('active_color');
            $(this).addClass('active_color');
            const input = document.getElementById("color-picker");
            setColor();
            input.addEventListener("input", setColor);

            function setColor() {
                $(':root').css('--color-customColor', input.value);
            }

            $(`input[name='color_flag`).val('true');
        });

        $('.themes-color-change').on('click', function() {

            $(`input[name='color_flag`).val('false');

            var color_val = $(this).data('value');
            $('body').removeClass('custom-color');
            if (/^theme-\d+$/) {
                $('body').removeClassRegex(/^theme-\d+$/);
            }
            $('body').addClass(color_val);
            $('.theme-color').prop('checked', false);
            $('.themes-color-change').removeClass('active_color');
            $('.colorPicker').removeClass('active_color');
            $(this).addClass('active_color');
            $(`input[value=${color_val}]`).prop('checked', true);
        });

        $.fn.removeClassRegex = function(regex) {
            return $(this).removeClass(function(index, classes) {
                return classes.split(/\s+/).filter(function(c) {
                    return regex.test(c);
                }).join(' ');
            });
        };
    </script>
@endpush
