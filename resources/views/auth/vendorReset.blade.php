@extends('layouts.auth')
@php
    use App\Models\Utility;
    $logo = asset(Storage::url('uploads/logo/'));
    $company_logo = App\Models\Utility::getValByName('company_logo');
@endphp
@section('page-title')
    {{ __('Forgot Password') }}
@endsection
@section('content')
    <div class="">
        <h2 class="mb-3 f-w-600">{{ __('Reset Password') }}</h2>
    </div>
    @if ($errors->any())
        @foreach ($errors->all() as $error)
            <span class="text-danger">{{ $error }}</span>
        @endforeach
    @endif
    {{ Form::open(['route' => 'vender.password.reset', 'method' => 'post', 'id' => 'loginForm']) }}
    <input type="hidden" name="token" value="{{ $token }}">

    @csrf
    <div class="">
        <div class="form-group mb-3">
            {{ Form::label('email', __('E-Mail Address'), ['class' => 'form-label']) }}
            {{ Form::text('email', null, ['class' => 'form-control']) }}
            @error('email')
                <span class="invalid-email text-danger" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
        <div class="form-group mb-3">
            {{ Form::label('password', __('Password'), ['class' => 'form-label']) }}
            {{ Form::password('password', ['class' => 'form-control']) }}
            @error('password')
                <span class="invalid-password text-danger" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror

        </div>
        <div class="form-group mb-3">
            {{ Form::label('password_confirmation', __('Password Confirmation'), ['class' => 'form-label']) }}
            {{ Form::password('password_confirmation', ['class' => 'form-control']) }}
            @error('password_confirmation')
                <span class="invalid-password_confirmation text-danger" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>


        <div class="form-group mb-4">

            {{ Form::submit(__('Reset Password'), ['class' => 'btn btn-primary', 'id' => 'resetBtn']) }}

        </div>



    </div>
    {{ Form::close() }}
@endsection
