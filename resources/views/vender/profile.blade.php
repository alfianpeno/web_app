@extends('layouts.admin')
@php
    $profile=asset(Storage::url('uploads/avatar/'));
@endphp
@section('page-title')
    {{__('Profile Account')}}
@endsection
@push('script-page')
    <script>
        var scrollSpy = new bootstrap.ScrollSpy(document.body, {
            target: '#useradd-sidenav',
            offset: 300,
        })
        $(".list-group-item").click(function(){
            $('.list-group-item').filter(function(){
                return this.href == id;
            }).parent().removeClass('text-primary');
        });
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Profile')}}</li>
@endsection

@section('content')
<style>
    .list-group-item.active{
        border: none !important;
    }
</style>
    <div class="row">
        <div class="col-xl-3">
            <div class="card sticky-top" style="top:30px">
                <div class="list-group list-group-flush" id="useradd-sidenav">
                    <a href="#personal_info" class="list-group-item list-group-item-action border-0">{{__('Personal Info')}} <div class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                    <a href="#billing_info" class="list-group-item list-group-item-action border-0">{{__('Billing Info')}}<div class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                    <a href="#shipping_info" class="list-group-item list-group-item-action border-0">{{__('Shipping Info')}}<div class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                    <a href="#change_password" class="list-group-item list-group-item-action border-0">{{__('Change Password')}}<div class="float-end"><i class="ti ti-chevron-right"></i></div></a>
                </div>
            </div>
        </div>
        <div class="col-xl-9">
            <div id="personal_info" class="card">
                <div class="card-header">
                    <h5>{{('System Setting')}}</h5>
                </div>
                <div class="card-body">
                    {{Form::model($userDetail,array('route' => array('vender.update.profile'), 'method' => 'post', 'enctype' => "multipart/form-data",'class'=>'needs-validation','novalidate'))}}
                    @csrf
                    <div class="row">
                        <div class="col-lg-6 col-sm-6">
                            <div class="form-group">
                                <label class="col-form-label text-dark">{{__('Name')}}</label><x-required></x-required>
                                <input class="form-control @error('name') is-invalid @enderror" name="name" type="text" id="name" placeholder="{{ __('Enter Your Name') }}" value="{{ $userDetail->name }}" required autocomplete="name">
                                @error('name')
                                <span class="invalid-feedback text-danger text-xs" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-6">
                            <div class="form-group">
                                <label for="email" class="col-form-label text-dark">{{__('Email')}}</label><x-required></x-required>
                                <input class="form-control @error('email') is-invalid @enderror" name="email" type="text" id="email" placeholder="{{ __('Enter Your Email Address') }}" value="{{ $userDetail->email }}" required autocomplete="email">
                                @error('email')
                                <span class="invalid-feedback text-danger text-xs" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6">
                            <div class="form-group">
                                <div class="choose-files">
                                    <label for="avatar">
                                        <div class=" bg-primary profile_update"> <i class="ti ti-upload px-1"></i>{{__('Choose file here')}}</div>
                                        <input type="file" name="profile" id="avatar" class="form-control file " onchange="document.getElementById('blah').src = window.URL.createObjectURL(this.files[0])" data-multiple-caption="{count} files selected" multiple/>
                                        <img id="blah" src="{{ !empty(\Auth::user()->avatar) ? \App\Models\Utility::get_file(\Auth::user()->avatar) : asset(Storage::url('uploads/avatar/avatar.png')) }}"
                                        class="img-fluid rounded border-2 border border-primary" width="120px" height="120px">
                                    </label>
                                </div>
                                <span class="text-xs text-muted">{{ __('Please upload a valid image file. Size of image should not be more than 2MB.')}}</span>
                                @error('avatar')
                                <span class="invalid-feedback text-danger text-xs" role="alert">{{ $message }}</span>
                                @enderror

                            </div>

                        </div>
                        <div class="col-lg-12 text-end">
                            <input type="submit" value="{{__('Save Changes')}}" class="btn btn-print-invoice  btn-primary m-r-10">
                        </div>
                    </div>
                    </form>

                </div>

            </div>
            <div id="billing_info" class="card">
                <div class="card-header">
                    <h5>{{('Billing Info')}}</h5>
                </div>
                <div class="card-body">
                    {{Form::model($userDetail,array('route' => array('vender.update.billing.info'), 'method' => 'post'))}}
                    @csrf
                    <div class="row">
                        <div class="col-lg-4 col-sm-4 form-group">
                            {{Form::label('billing_name',__('Billing Name'),array('class'=>'form-label'))}}
                            {{Form::text('billing_name',null,array('class'=>'form-control','placeholder'=>__('Enter Billing Name')))}}
                            @error('billing_name')
                            <span class="invalid-billing_name" role="alert">
                                    <strong class="text-danger">{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-lg-4 col-sm-4 form-group">
                            {{Form::label('billing_phone',__('Billing Phone'),array('class'=>'form-label'))}}
                            {{Form::text('billing_phone',null,array('class'=>'form-control','placeholder'=>__('Enter Billing Phone')))}}
                            @error('billing_phone')
                            <span class="invalid-billing_phone" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-4 col-sm-4 form-group">
                            {{Form::label('billing_zip',__('Billing Zip'),array('class'=>'form-label'))}}
                            {{Form::text('billing_zip',null,array('class'=>'form-control','placeholder'=>__('Enter Billing Zip')))}}
                            @error('billing_zip')
                            <span class="invalid-billing_zip" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4 col-sm-4 form-group">
                            {{Form::label('billing_country',__('Billing Country'),array('class'=>'form-label'))}}
                            {{Form::text('billing_country',null,array('class'=>'form-control','placeholder'=>__('Enter Billing Country')))}}
                            @error('billing_country')
                            <span class="invalid-billing_country" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-4 col-sm-4 form-group">
                            {{Form::label('billing_state',__('Billing State'),array('class'=>'form-label'))}}
                            {{Form::text('billing_state',null,array('class'=>'form-control','placeholder'=>__('Enter Billing State')))}}
                            @error('billing_state')
                            <span class="invalid-billing_state" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-4 col-sm-4 form-group">
                            {{Form::label('billing_city',__('Billing City'),array('class'=>'form-label'))}}
                            {{Form::text('billing_city',null,array('class'=>'form-control','placeholder'=>__('Enter Billing City')))}}
                            @error('billing_city')
                            <span class="invalid-billing_city" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-lg-12 col-sm-12 form-group">
                        {{Form::label('billing_address',__('Billing Address'),array('class'=>'form-label'))}}
                        {{Form::textarea('billing_address',null,array('class'=>'form-control','rows'=>3,'placeholder'=>__('Enter Billing Address')))}}
                        @error('billing_address')
                        <span class="invalid-billing_address" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                        @enderror
                    </div>
                    <div class="col-lg-12 text-end">
                        <input type="submit" value="{{__('Save Changes')}}" class="btn btn-print-invoice  btn-primary m-r-10">

                    </div>
                </div>
                </form>
            </div>
            <div id="shipping_info" class="card">
                <div class="card-header">
                    <h5>{{('Shipping Info')}}</h5>
                </div>
                <div class="card-body">
                    {{Form::model($userDetail,array('route' => array('vender.update.shipping.info'), 'method' => 'post'))}}
                    @csrf
                    <div class="row">
                        <div class="col-lg-4 col-sm-4 form-group">
                            {{Form::label('shipping_name',__('Shipping Name'),array('class'=>'form-label'))}}
                            {{Form::text('shipping_name',null,array('class'=>'form-control','placeholder'=>__('Enter Shipping Name')))}}
                            @error('shipping_name')
                            <span class="invalid-shipping_name" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-4 col-sm-4 form-group">
                            {{Form::label('shipping_phone',__('Shipping Phone'),array('class'=>'form-label'))}}
                            {{Form::text('shipping_phone',null,array('class'=>'form-control','placeholder'=>__('Enter Shipping Phone')))}}
                            @error('shipping_phone')
                            <span class="invalid-shipping_phone" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-4 col-sm-4 form-group">
                            {{Form::label('shipping_zip',__('Shipping Zip'),array('class'=>'form-label'))}}
                            {{Form::text('shipping_zip',null,array('class'=>'form-control','placeholder'=>__('Enter Shipping Zip')))}}
                            @error('shipping_zip')
                            <span class="invalid-shipping_zip" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                            @enderror
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-lg-4 col-sm-4 form-group">
                            {{Form::label('shipping_country',__('Shipping Country'),array('class'=>'form-label'))}}
                            {{Form::text('shipping_country',null,array('class'=>'form-control','placeholder'=>__('Enter Shipping Country')))}}
                            @error('shipping_country')
                            <span class="invalid-shipping_country" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                            @enderror
                        </div>

                        <div class="col-lg-4 col-sm-4 form-group">
                            {{Form::label('shipping_state',__('Shipping State'),array('class'=>'form-label'))}}
                            {{Form::text('shipping_state',null,array('class'=>'form-control','placeholder'=>__('Enter Shipping State')))}}
                            @error('shipping_state')
                            <span class="invalid-shipping_state" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-4 col-sm-4 form-group">
                            {{Form::label('shipping_city',__('Shipping City'),array('class'=>'form-label'))}}
                            {{Form::text('shipping_city',null,array('class'=>'form-control','placeholder'=>__('Enter Shipping City')))}}
                            @error('shipping_city')
                            <span class="invalid-shipping_city" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-lg-12 col-sm-12 form-group">
                        {{Form::label('shipping_address',__('Shipping Address'),array('class'=>'form-label'))}}
                        {{Form::textarea('shipping_address',null,array('class'=>'form-control','rows'=>3,'placeholder'=>__('Enter Shipping Address')))}}
                        @error('shipping_address')
                        <span class="invalid-billing_address" role="alert">
                                                                <strong class="text-danger">{{ $message }}</strong>
                                                            </span>
                        @enderror
                    </div>
                    <div class="col-lg-12 text-end">
                        <input type="submit" value="{{__('Save Changes')}}" class="btn btn-print-invoice  btn-primary m-r-10">

                    </div>
                </div>
                </form>
            </div>
            <div id="change_password" class="card">
                <div class="card-header">
                    <h5>{{('Change Password')}}</h5>
                </div>
                <div class="card-body">
                    {{Form::model($userDetail,array('route' => array('vender.update.password',$userDetail->id), 'method' => 'post','class'=>'needs-validation','novalidate'))}}
                        @csrf
                        <div class="row">
                            <div class="col-lg-6 col-sm-6 form-group">
                                <label for="old_password" class="col-form-label text-dark">{{ __('Old Password') }}</label><x-required></x-required>
                                <input class="form-control @error('old_password') is-invalid @enderror" name="old_password" type="password" id="old_password" required autocomplete="old_password" placeholder="{{ __('Enter Old Password') }}">
                                @error('old_password')
                                <span class="invalid-feedback text-danger text-xs" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-6 col-sm-6 form-group">
                                <label for="password" class="col-form-label text-dark">{{ __('Password') }}</label><x-required></x-required>
                                <input class="form-control @error('password') is-invalid @enderror" name="password" type="password" required autocomplete="new-password" id="password" placeholder="{{ __('Enter Your Password') }}">
                                @error('password')
                                <span class="invalid-feedback text-danger text-xs" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-lg-6 col-sm-6 form-group">
                                <label for="password_confirmation" class="col-form-label text-dark">{{ __('Confirm Password') }}</label><x-required></x-required>
                                <input class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation" type="password" required autocomplete="new-password" id="password_confirmation" placeholder="{{ __('Enter Your Password') }}">
                            </div>
                            <div class="col-lg-12 text-end">
                                <input type="submit" value="{{__('Change Password')}}" class="btn btn-print-invoice  btn-primary m-r-10">
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
@endsection
<style>
    .dash-footer {
        margin-left: 0 !important
    }
</style>
