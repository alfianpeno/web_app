@extends('layouts.auth')
@php
    use App\Models\Utility;
    $logo = asset(Storage::url('uploads/logo/'));
    $company_logo = App\Models\Utility::getValByName('company_logo');
    $settings = Utility::settings();
@endphp

@section('page-title')
    {{ __('Forgot Password') }}
@endsection

@section('auth-lang')
    @php
        $languages = App\Models\Utility::languages();
    @endphp
    <div class="lang-dropdown-only-desk">
        <li class="dropdown dash-h-item drp-language">
            <a class="dash-head-link dropdown-toggle btn" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="drp-text"> {{ ucFirst($languages[$lang]) }}
                </span>
            </a>
            <div class="dropdown-menu dash-h-dropdown dropdown-menu-end">
                @foreach ($languages as $code => $language)
                    <a href="{{ route('langPass', $code) }}" tabindex="0"
                        class="dropdown-item {{ $code == $lang ? 'active' : '' }}">
                        <span>{{ ucFirst($language) }}</span>
                    </a>
                @endforeach
            </div>
        </li>
    </div>
@endsection

@section('content')
    <div class="">
        <h2 class="mb-3 f-w-600">{{ __('Forgot Password') }}</h2>
        <small class="text-muted">{{ __('We will send a link to reset your password') }}</small>
        @if (session('status'))
            <small class="text-muted">{{ session('status') }}</small>
        @endif
    </div>
    <form method="POST" action="{{ route('password.email') }}" class="needs-validation" novalidate>
        @csrf
        <div class="">

            <div class="form-group mb-3">
                <label for="email" class="form-label">{{ __('E-Mail') }}</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                    name="email" placeholder="{{ __('Enter Your Email   ') }}" value="{{ old('email') }}" required
                    autocomplete="email" autofocus>
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <small>{{ $message }}</small>
                    </span>
                @enderror
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
                <button type="submit"
                    class="btn-login btn btn-primary btn-block mt-2">{{ __('Send Password Reset Link') }}</button>
            </div>
            <p class="my-4 text-center">{{ __('Back to') }} <a href="{{ route('login',$lang) }}"
                    class="text-primary">{{ __('Login') }}</a></p>


        </div>
        {{ Form::close() }}
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
