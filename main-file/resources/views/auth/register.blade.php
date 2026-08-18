@extends('layouts.auth')
@php
    use App\Models\Utility;
    $logo = asset(Storage::url('uploads/logo/'));
    $company_logo = App\Models\Utility::getValByName('company_logo');
    $settings = Utility::settings();

    $setting = \Modules\LandingPage\Entities\LandingPageSetting::settings();
@endphp

@section('page-title')
    {{ __('Register') }}
@endsection


@section('auth-lang')
    @php
        $languages = App\Models\Utility::languages();
    @endphp
    <div class="lang-dropdown-only-desk">
        <li class="dropdown dash-h-item drp-language">
            <a class="dash-head-link dropdown-toggle btn" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="drp-text">{{ ucfirst($languages[$lang] ?? '') }}</span>
            </a>
            <div class="dropdown-menu dash-h-dropdown dropdown-menu-end">
                @foreach ($languages as $code => $language)
                    <a href="{{ route('register', ['ref' => $ref, 'lang' => $code]) }}" tabindex="0"
                        class="dropdown-item {{ $code == $lang ? 'active' : '' }}">
                        <span>{{ ucfirst($language) }}</span>
                    </a>
                @endforeach
            </div>
            {{-- <div class="dropdown-menu dash-h-dropdown dropdown-menu-end">
                @foreach ($languages as $code => $language)
                    <a href="{{ route('register', ['lang' => $code] + (request()->has('ref') ? ['ref' => request('ref')] : [])) }}"
                       tabindex="0"
                       class="dropdown-item {{ $code == $lang ? 'active' : '' }}">
                        <span>{{ ucfirst($language) }}</span>
                    </a>
                @endforeach
            </div> --}}

        </li>
    </div>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between">
        <h2 class="mb-3 f-w-600">{{ __('Register') }}</h2>
    </div>
    <form method="POST" action="{{ route('register.store', ['plan' => $plan]) }}" class="needs-validation" novalidate>
        @csrf
        <div class="">

            @if (session('status'))
                <div class="mb-4 font-medium text-lg text-green-600 text-danger">
                    {{ __('Email SMTP settings does not configured so please contact to your site admin.') }}
                </div>
            @endif
            <div class="form-group">
                <label for="name"
                    class="form-label d-flex align-items-center justify-content-between">{{ __('Full Name') }}</label>
                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                    value="{{ old('name') }}" placeholder="{{ __('Enter Your Full Name') }}" required autocomplete="name" autofocus>
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group">
                <label for="email"
                    class="form-label d-flex align-items-center justify-content-between">{{ __('Email') }}</label>
                <input class="form-control @error('email') is-invalid @enderror" id="email" type="email"
                    name="email" value="{{ old('email') }}" placeholder="{{ __('Enter Your Email') }}" required autocomplete="email"
                    autofocus>
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror

            </div>
            <div class="form-group">
                <label for="password"
                    class="form-label d-flex align-items-center justify-content-between">{{ __('Password') }}</label>
                <input id="password" type="password" data-indicator="pwindicator"
                    class="form-control pwstrength @error('password') is-invalid @enderror" name="password"
                    placeholder= "{{ __('Enter Your Password') }}" required autocomplete="new-password">
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
                <div id="pwindicator" class="pwindicator">
                    <div class="bar"></div>
                    <div class="label"></div>
                </div>
            </div>
            <div class="form-group">
                <label for="password_confirmation"
                    class="form-label d-flex align-items-center justify-content-between">{{ __('Password Confirmation') }}</label>
                <input id="password_confirmation" type="password"
                    class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation"
                    placeholder="{{ __('Enter Your Confirm Password') }}" required autocomplete="new-password">
                @error('password_confirmation')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-check custom-checkbox mb-3">
                <input type="checkbox" class="form-check-input" id="termsCheckbox" name="terms" required>
                <label class="form-check-label text-sm" for="termsCheckbox">{{ __('I agree to the ') }}
                    @if (is_array(json_decode($setting['menubar_page'])) || is_object(json_decode($setting['menubar_page'])))
                        @foreach (json_decode($setting['menubar_page']) as $key => $value)
                            @if (in_array($value->menubar_page_name, ['Terms and Conditions']) && isset($value->template_name))
                                <a href="{{ $value->template_name == 'page_content' ? route('custom.page', $value->page_slug) : $value->page_url }}"
                                    target="_blank">{{ $value->menubar_page_name }}</a>
                            @endif
                        @endforeach
                        {{ __('and the ') }}
                        @foreach (json_decode($setting['menubar_page']) as $key => $value)
                            @if (in_array($value->menubar_page_name, ['Privacy Policy']) && isset($value->template_name))
                                <a href="{{ $value->template_name == 'page_content' ? route('custom.page', $value->page_slug) : $value->page_url }}"
                                    target="_blank">{{ $value->menubar_page_name }}</a>
                            @endif
                        @endforeach
                    @endif
                </label>
            </div>

            @if ($settings['recaptcha_module'] == 'yes')
                @if (isset($settings['google_recaptcha_version']) && $settings['google_recaptcha_version'] == 'v2-checkbox')
                    <div class="form-group mb-4">
                        {!! NoCaptcha::display($settings['cust_darklayout'] == 'on' ? ['data-theme' => 'dark'] : []) !!}
                        @error('g-recaptcha-response')
                            <span class="small text-danger" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                @else
                    <div class="form-group mb-4">
                        <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" class="form-control">
                        @error('g-recaptcha-response')
                            <span class="error small text-danger" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                @endif
            @endif

            <div class="d-grid">
                <input type="hidden" name="ref_code" value="{{ !empty($ref) ? $ref : '' }}">

                <button type="submit" class="btn-login btn btn-primary btn-block mt-2"
                    id="login_button">{{ __('Register') }}</button>
            </div>
            <p class="my-4 text-center">{{ __("Already' have an account?") }} <a href="{{ route('login', $lang) }}"
                    class="text-primary">{{ __('Login') }}</a></p>

        </div>
    </form>
@endsection

@push('custom-scripts')
    {{-- @if ($settings['recaptcha_module'] == 'yes')
        {!! NoCaptcha::renderJs() !!}
    @endif --}}
    @if (isset($settings['recaptcha_module']) && $settings['recaptcha_module'] == 'yes')
        @if (isset($settings['google_recaptcha_version']) && $settings['google_recaptcha_version'] == 'v2-checkbox')
            {!! NoCaptcha::renderJs() !!}
        @else
            <script src="https://www.google.com/recaptcha/api.js?render={{ $settings['google_recaptcha_key'] }}"></script>
            <script>
                $(document).ready(function() {
                    grecaptcha.ready(function() {
                        grecaptcha.execute('{{ $settings['google_recaptcha_key'] }}', {
                            action: 'submit'
                        }).then(function(token) {
                            $('#g-recaptcha-response').val(token);
                        });
                    });
                });
            </script>
        @endif
    @endif
@endpush
