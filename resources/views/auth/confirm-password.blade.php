@extends('layouts.auth')

@php
    use App\Models\Utility;
    $logo=asset(Storage::url('uploads/logo/'));
$company_logo=App\Models\Utility::getValByName('company_logo');
@endphp

@section('page-title')
    {{__('Confirm Password')}}
@endsection

@section('content')
    <div class="login-contain">
        <div class="login-inner-contain">
            <a class="navbar-brand" href="">
                <img src="{{$logo.'/'.(isset($company_logo) && !empty($company_logo)?$company_logo:'logo-dark.png').'?'. time()}}" class="navbar-brand-img big-logo" alt="logo">
            </a>
            <div class="login-form">
                <div class="page-title"><h5>{{__('Confirm Password')}}</h5></div>
                <small class="text-muted">{{ __('Please confirm your password before continuing.') }}</small>
                <form method="POST" action="{{ route('password.confirm') }}">
                    @csrf
                    <div class="form-group">
                        <label for="password" class="form-label">{{ __('Password') }}</label>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-login">{{ __('Confirm Password') }}</button>
                        @if (Route::has('password.request'))
                            <div class="or-text">{{__('OR')}}</div>
                            <a href="{{ route('password.request') }}" class="btn-login login-gray-btn">{{ __('Forgot Your Password?') }}</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
