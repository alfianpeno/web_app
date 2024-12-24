@extends('layouts.admin')
@php
use \App\Models\Utility;
$settings = Utility::settings();
$lang = isset($currEmailTempLang->lang) ? $currEmailTempLang->lang : 'en';
if ($lang == null) {
$lang = 'en';
}
$LangName = \App\Models\Language::where('code', $lang)->first();

$chatGPT = \App\Models\Utility::settings('enable_chatgpt');
$enable_chatgpt = !empty($chatGPT);

@endphp
@section('page-title')
{{ __('Manage Email Templates') }}
@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Email Template') }}</li>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{asset('css/summernote/summernote-bs4.css')}}">
@endpush

@push('script-page')
<script src="{{asset('css/summernote/summernote-bs4.js')}}"></script>
@endpush


@section('content')
<div class="row">
        <div class="col-md-4 col-12">
                <div class="card mb-0 h-100">
                        <div class="card-header card-body">
                                <h5></h5>
                                {{Form::model($emailTemplate, array('route' => array('email_template.update', $emailTemplate->id), 'method' => 'PUT')) }}
                                <div class="row">
                                        <div class="form-group col-md-12">
                                                {{Form::label('name',__('Name'),['class'=>'col-form-label text-dark'])}}
                                                {{Form::text('name',null,array('class'=>'form-control font-style','disabled'=>'disabled'))}}
                                        </div>
                                        <div class="form-group col-md-12">
                                                {{Form::label('from',__('From'),['class'=>'col-form-label text-dark'])}}
                                                {{Form::text('from',null,array('class'=>'form-control font-style','required'=>'required'))}}
                                        </div>
                                        {{Form::hidden('lang',$currEmailTempLang->lang,array('class'=>''))}}
                                        <div class="col-12 text-end">
                                                <input type="submit" value="{{__('Save')}}" class="btn btn-print-invoice  btn-primary m-r-10">
                                        </div>
                                </div>
                                {{ Form::close() }}
                        </div>
                </div>
        </div>
        <div class="col-md-8 col-12">
                <div class="card mb-0 h-100">
                        <div class="card-header card-body">
                                <h5></h5>
                                <div class="row text-xs">

                                        <h6 class="font-weight-bold mb-4">{{__('Variables')}}</h6>
                                        @if ($emailTemplate->slug == 'new_bill_payment')
                                        <div class="row">
                                                <!-- <h6 class="font-weight-bold pb-3">{{ __('Bill Payment Create') }}</h6> -->
                                                <p class="col-6">{{ __('App Name') }} : <span
                                                                class="pull-end text-primary">{app_name}</span></p>
                                                <p class="col-6">{{ __('Company Name') }} : <span
                                                                class="pull-right text-primary">{company_name}</span></p>
                                                <p class="col-6">{{ __('App Url') }} : <span
                                                                class="pull-right text-primary">{app_url}</span></p>
                                                <p class="col-6">{{ __('Payment Name') }} : <span
                                                                class="pull-right text-primary">{payment_name}</span></p>
                                                <p class="col-6">{{ __('Payment Bill') }} : <span
                                                                class="pull-right text-primary">{payment_bill}</span></p>
                                                <p class="col-6">{{ __('Payment Amount') }} : <span
                                                                class="pull-right text-primary">{payment_amount}</span></p>
                                                <p class="col-6">{{ __('Payment Date') }} : <span
                                                                class="pull-right text-primary">{payment_date}</span></p>
                                                <p class="col-6">{{ __('Payment Method') }} : <span
                                                                class="pull-right text-primary">{payment_method}</span></p>
                                        </div>
                                        @elseif($emailTemplate->slug == 'customer_invoice_sent')
                                        <div class="row">
                                                <!-- <h6 class="font-weight-bold pb-3">{{ __('Customer Invoice Send') }}</h6> -->
                                                <p class="col-6">{{ __('App Name') }} : <span
                                                                class="pull-end text-primary">{app_name}</span></p>
                                                <p class="col-6">{{ __('Company Name') }} : <span
                                                                class="pull-right text-primary">{company_name}</span></p>
                                                <p class="col-6">{{ __('App Url') }} : <span
                                                                class="pull-right text-primary">{app_url}</span></p>
                                                <p class="col-6">{{ __('Invoice Name') }} : <span
                                                                class="pull-right text-primary">{invoice_name}</span></p>
                                                <p class="col-6">{{ __('Invoice Number') }} : <span
                                                                class="pull-right text-primary">{invoice_number}</span></p>
                                                <p class="col-6">{{ __('Invoice Url') }} : <span
                                                                class="pull-right text-primary">{invoice_url}</span></p>

                                        </div>
                                        @elseif($emailTemplate->slug == 'bill_sent')
                                        <div class="row">
                                                <!-- <h6 class="font-weight-bold pb-3">{{ __('Bill Send') }}</h6> -->
                                                <p class="col-6">{{ __('App Name') }} : <span
                                                                class="pull-end text-primary">{app_name}</span></p>
                                                <p class="col-6">{{ __('Company Name') }} : <span
                                                                class="pull-right text-primary">{company_name}</span></p>
                                                <p class="col-6">{{ __('App Url') }} : <span
                                                                class="pull-right text-primary">{app_url}</span></p>
                                                <p class="col-6">{{ __('Bill Name') }} : <span
                                                                class="pull-right text-primary">{bill_name}</span></p>
                                                <p class="col-6">{{ __('Bill Number') }} : <span
                                                                class="pull-right text-primary">{bill_number}</span></p>
                                                <p class="col-6">{{ __('Bill Url') }} : <span
                                                                class="pull-right text-primary">{bill_url}</span></p>

                                        </div>
                                        @elseif($emailTemplate->slug == 'new_invoice_payment')
                                        <div class="row">
                                                <!-- <h6 class="font-weight-bold pb-3">{{ __('Invoice payment Create') }}</h6> -->
                                                <p class="col-6">{{ __('App Name') }} : <span
                                                                class="pull-end text-primary">{app_name}</span></p>
                                                <p class="col-6">{{ __('Company Name') }} : <span
                                                                class="pull-right text-primary">{company_name}</span></p>
                                                <p class="col-6">{{ __('App Url') }} : <span
                                                                class="pull-right text-primary">{app_url}</span></p>
                                                <p class="col-6">{{ __('Payment Name') }} : <span
                                                                class="pull-right text-primary">{payment_name}</span></p>
                                                <p class="col-6">{{ __('Payment Amount') }} : <span
                                                                class="pull-right text-primary">{payment_amount}</span></p>
                                                <p class="col-6">{{ __('Invoice Number') }} : <span
                                                                class="pull-right text-primary">{invoice_number}</span></p>
                                                <p class="col-6">{{ __('Payment Date') }} : <span
                                                                class="pull-right text-primary">{payment_date}</span></p>
                                                <p class="col-6">{{ __('Payment DueAmount') }} : <span
                                                                class="pull-right text-primary">{payment_dueAmount}</span></p>

                                        </div>
                                        @elseif($emailTemplate->slug == 'invoice_sent')
                                        <div class="row">
                                                <!-- <h6 class="font-weight-bold pb-3">{{ __('Invoice Send') }}</h6> -->
                                                <p class="col-6">{{ __('App Name') }} : <span
                                                                class="pull-end text-primary">{app_name}</span></p>
                                                <p class="col-6">{{ __('Company Name') }} : <span
                                                                class="pull-right text-primary">{company_name}</span></p>
                                                <p class="col-6">{{ __('App Url') }} : <span
                                                                class="pull-right text-primary">{app_url}</span></p>
                                                <p class="col-6">{{ __('Invoice Name') }} : <span
                                                                class="pull-right text-primary">{invoice_name}</span></p>
                                                <p class="col-6">{{ __('Invoice Number') }} : <span
                                                                class="pull-right text-primary">{invoice_number}</span></p>
                                                <p class="col-6">{{ __('Invoice Url') }} : <span
                                                                class="pull-right text-primary">{invoice_url}</span></p>

                                        </div>
                                        @elseif($emailTemplate->slug == 'payment_reminder')
                                        <div class="row">
                                                <!-- <h6 class="font-weight-bold pb-3">{{ __('Payment Reminder') }}</h6> -->
                                                <p class="col-6">{{ __('App Name') }} : <span
                                                                class="pull-end text-primary">{app_name}</span></p>
                                                <p class="col-6">{{ __('Company Name') }} : <span
                                                                class="pull-right text-primary">{company_name}</span></p>
                                                <p class="col-6">{{ __('App Url') }} : <span
                                                                class="pull-right text-primary">{app_url}</span></p>
                                                <p class="col-6">{{ __('Payment Name') }} : <span
                                                                class="pull-right text-primary">{payment_name}</span></p>
                                                <p class="col-6">{{ __('Invoice Number') }} : <span
                                                                class="pull-right text-primary">{invoice_number}</span></p>
                                                <p class="col-6">{{ __('Payment Due Amount') }} : <span
                                                                class="pull-right text-primary">{payment_dueAmount}</span></p>
                                                <p class="col-6">{{ __('Payment Date') }} : <span
                                                                class="pull-right text-primary">{payment_date}</span></p>

                                        </div>
                                        @elseif($emailTemplate->slug == 'proposal_sent')
                                        <div class="row">
                                                <!-- <h6 class="font-weight-bold pb-3">{{ __('Proposal Send') }}</h6> -->
                                                <p class="col-6">{{ __('App Name') }} : <span
                                                                class="pull-end text-primary">{app_name}</span></p>
                                                <p class="col-6">{{ __('Company Name') }} : <span
                                                                class="pull-right text-primary">{company_name}</span></p>
                                                <p class="col-6">{{ __('App Url') }} : <span
                                                                class="pull-right text-primary">{app_url}</span></p>
                                                <p class="col-6">{{ __('Proposal Name') }} : <span
                                                                class="pull-right text-primary">{proposal_name}</span></p>
                                                <p class="col-6">{{ __('Proposal Number') }} : <span
                                                                class="pull-right text-primary">{proposal_number}</span></p>
                                                <p class="col-6">{{ __('Proposal Url') }} : <span
                                                                class="pull-right text-primary">{proposal_url}</span></p>
                                        </div>
                                        @elseif($emailTemplate->slug == 'user_created')
                                        <div class="row">
                                                <!-- <h6 class="font-weight-bold pb-3">{{ __('Create User') }}</h6> -->
                                                <p class="col-6">{{ __('App Name') }} : <span
                                                                class="pull-end text-primary">{app_name}</span></p>
                                                <p class="col-6">{{ __('Company Name') }} : <span
                                                                class="pull-right text-primary">{company_name}</span></p>
                                                <p class="col-6">{{ __('App Url') }} : <span
                                                                class="pull-right text-primary">{app_url}</span></p>
                                                <p class="col-6">{{ __('Email') }} : <span
                                                                class="pull-right text-primary">{email}</span></p>
                                                <p class="col-6">{{ __('Password') }} : <span
                                                                class="pull-right text-primary">{password}</span></p>

                                        </div>
                                        @elseif($emailTemplate->slug == 'vendor_bill_sent')
                                        <div class="row">
                                                <!-- <h6 class="font-weight-bold pb-3">{{ __('Vendor Bill Send') }}</h6> -->
                                                <p class="col-6">{{ __('App Name') }} : <span
                                                                class="pull-end text-primary">{app_name}</span></p>
                                                <p class="col-6">{{ __('Company Name') }} : <span
                                                                class="pull-right text-primary">{company_name}</span></p>
                                                <p class="col-6">{{ __('App Url') }} : <span
                                                                class="pull-right text-primary">{app_url}</span></p>
                                                <p class="col-6">{{ __('Bill Name') }} : <span
                                                                class="pull-right text-primary">{bill_name}</span></p>
                                                <p class="col-6">{{ __('Bill Number') }} : <span
                                                                class="pull-right text-primary">{bill_number}</span></p>
                                                <p class="col-6">{{ __('Bill Url') }} : <span
                                                                class="pull-right text-primary">{bill_url}</span></p>

                                        </div>
                                        @elseif($emailTemplate->slug == 'new_contract')
                                        <div class="row">
                                                <!-- <h6 class="font-weight-bold pb-3">{{ __('Create User') }}</h6> -->
                                                <p class="col-6">{{ __('App Name') }} : <span
                                                                class="pull-end text-primary">{app_name}</span></p>
                                                <p class="col-6">{{ __('Company Name') }} : <span
                                                                class="pull-right text-primary">{company_name}</span></p>
                                                <p class="col-6">{{ __('App Url') }} : <span
                                                                class="pull-right text-primary">{app_url}</span></p>
                                                <p class="col-6">{{ __('Contract Customer') }} : <span
                                                                class="pull-right text-primary">{contract_customer}</span></p>
                                                <p class="col-6">{{ __('Contract Subject') }} : <span
                                                                class="pull-right text-primary">{contract_subject}</span></p>
                                                <p class="col-6">{{ __('Contract Start_Date') }} : <span
                                                                class="pull-right text-primary">{contract_start_date}</span></p>
                                                <p class="col-6">{{ __('Contract End_Date') }} : <span
                                                                class="pull-right text-primary">{contract_end_date}</span></p>
                                                <p class="col-6">{{ __('Contract Type') }} : <span
                                                                class="pull-right text-primary">{contract_type}</span></p>
                                                <p class="col-6">{{ __('Contract Value') }} : <span
                                                                class="pull-right text-primary">{contract_value}</span></p>
                                        </div>
                                        @elseif($emailTemplate->slug == 'retainer_sent' || $emailTemplate->slug == 'customer_retainer_sent')
                                        <div class="row">
                                                <!-- <h6 class="font-weight-bold pb-3">{{ __('Proposal Send') }}</h6> -->
                                                <p class="col-6">{{ __('App Name') }} : <span
                                                                class="pull-end text-primary">{app_name}</span></p>
                                                <p class="col-6">{{ __('Company Name') }} : <span
                                                                class="pull-right text-primary">{company_name}</span></p>
                                                <p class="col-6">{{ __('App Url') }} : <span
                                                                class="pull-right text-primary">{app_url}</span></p>
                                                <p class="col-6">{{ __('Retainer Name') }} : <span
                                                                class="pull-right text-primary">{retainer_name}</span></p>
                                                <p class="col-6">{{ __('Retainer Number') }} : <span
                                                                class="pull-right text-primary">{retainer_number}</span></p>
                                                <p class="col-6">{{ __('Retainer Url') }} : <span
                                                                class="pull-right text-primary">{retainer_url}</span></p>
                                        </div>
                                        @elseif($emailTemplate->slug == 'new_retainer_payment')
                                        <div class="row">
                                                <!-- <h6 class="font-weight-bold pb-3">{{ __('Invoice payment Create') }}</h6> -->
                                                <p class="col-6">{{ __('App Name') }} : <span
                                                                clas="pull-end text-primary">{app_name}</span></p>
                                                <p class="col-6">{{ __('Company Name') }} : <span
                                                                class="pull-right text-primary">{company_name}</span></p>
                                                <p class="col-6">{{ __('App Url') }} : <span
                                                                class="pull-right text-primary">{app_url}</span></p>
                                                <p class="col-6">{{ __('Payment Name') }} : <span
                                                                class="pull-right text-primary">{payment_name}</span></p>
                                                <p class="col-6">{{ __('Payment Amount') }} : <span
                                                                class="pull-right text-primary">{payment_amount}</span></p>
                                                <p class="col-6">{{ __('Retainer Number') }} : <span
                                                                class="pull-right text-primary">{retainer_number}</span></p>
                                                <p class="col-6">{{ __('Payment Date') }} : <span
                                                                class="pull-rigsht text-primary">{payment_date}</span></p>
                                                <p class="col-6">{{ __('Payment DueAmount') }} : <span
                                                                class="pull-right text-primary">{payment_dueAmount}</span></p>

                                        </div>
                                        @endif
                                </div>
                        </div>
                </div>
        </div>
        <div class="mt-4 col-12">
                <h5></h5>
                <div class="row">
                        <div class="col-sm-3 col-md-3 col-lg-3 col-xl-3 ">
                                <div class="card sticky-top language-sidebar mb-0">
                                        <div class="list-group list-group-flush" id="useradd-sidenav">
                                                @foreach($languages as $key => $lang)
                                                <a class="list-group-item list-group-item-action border-0 {{($currEmailTempLang->lang == $key)?'active':''}}" href="{{route('manage.email.language',[$emailTemplate->id,$key])}}">
                                                        {{Str::ucfirst($lang)}}
                                                </a>
                                                @endforeach
                                        </div>
                                </div>
                        </div>

                        <div class="col-lg-9 col-md-9 col-sm-9  ">
                        <div class="card h-100 p-3">
                                {{Form::model($currEmailTempLang, array('route' => array('store.email.language',$currEmailTempLang->parent_id), 'method' => 'PUT')) }}
                                <div class="row">
                                        <div class="form-group col-12">
                                                {{Form::label('subject',__('Subject'),['class'=>'col-form-label text-dark'])}}
                                                {{Form::text('subject',null,array('class'=>'form-control font-style','required'=>'required'))}}
                                        </div>
                                        <div class="form-group col-12">
                                                {{Form::label('content',__('Email Message'),['class'=>'col-form-label text-dark'])}}
                                                {{Form::textarea('content',$currEmailTempLang->content,array('class'=>'summernote-simple','id'=>'content','required'=>'required'))}}
                                        </div>

                                        <div class="col-md-12 text-end mb-3">
                                                {{Form::hidden('lang',null)}}
                                                <input type="submit" value="{{__('Save')}}" class="btn btn-print-invoice  btn-primary m-r-10">
                                        </div>
                                </div>
                                {{ Form::close() }}
                        </div>
                        </div>
                </div>
        </div>
</div>

@endsection
@push('scripts')
<script src="{{ asset('assets/js/plugins/summernote-0.8.18-dist/summernote-lite.min.js') }}"></script>
@endpush