@php
$logo=\App\Models\Utility::get_file('uploads/logo/');
    if(\Auth::user()->type=="Super Admin")
        {
            $company_logo=Utility::get_superadmin_logo();
        }
        else
        {
            $company_logo=Utility::get_company_logo();
        }
// $dark_logo    = Utility::getValByName('company_logo_dark');
// $img = asset($logo . '/' . (isset($dark_logo) && !empty($dark_logo) ? $dark_logo : 'logo-dark.png'));
$settings              = Utility::settings();
@endphp

@extends('layouts.contractheader')

@section('action-btn')
    <!-- <div class="float-end">
        <div class="col-auto pe-0">
            <a href="{{route('contract.download.pdf',\Crypt::encrypt($contract->id))}}" target="_blanks" class="btn btn-sm btn-primary btn-icon-only width-auto" title="{{__('Download')}}" ><i class="ti ti-download"></i> {{__('Download')}}</a>
        </div>
        
        <div class="col-auto pe-0">
            <a href="#" class="btn btn-sm btn-primary btn-icon" data-url="{{ route('signature',$contract->id) }}" data-ajax-popup="true" data-title="{{__('Create New contracts')}}" data-size="lg" title="{{__('Signature')}}" data-bs-toggle="tooltip" data-bs-placement="top">
                <i class="ti ti-pencil"></i> {{ __('Signature') }}
            </a>
        </div>
    </div> -->
@endsection

@section('content')
    <div class="row">

    <div class="col-lg-10">
        <div class="container">
            <div>
                
                    <div class="text-md-end mb-0" style="margin-right:-44px;">
                        @if(\Auth::user()->type =='company')
                            <a href="{{route('contract.download.pdf',\Crypt::encrypt($contract->id))}}" target="_blanks" class="btn btn-sm btn-primary btn-icon-only width-auto" title="{{__('Download')}}" ><i class="ti ti-download"></i> </a>
                        @else     
                            <a href="{{route('customer.contract.download.pdf',\Crypt::encrypt($contract->id))}}" target="_blanks" class="btn btn-sm btn-primary btn-icon-only width-auto" title="{{__('Download')}}" ><i class="ti ti-download"></i> </a>
                        @endif

                        {{-- @if((\Auth::user()->type =='company') && ($contract->company_signature == ''))
                        <a href="#" class="btn btn-sm btn-primary btn-icon" data-url="{{ route('signature',$contract->id) }}" data-ajax-popup="true" data-title="{{__('Create Signature')}}" data-size="md" title="{{__('Signature')}}" data-bs-toggle="tooltip" data-bs-placement="top">
                            <i class="ti ti-pencil"></i>
                        </a>
                        @endif --}}


                        @if((\Auth::user()->type !='company') && ($contract->customer_signature == ''))
                        <a href="#" class="btn btn-sm btn-primary btn-icon" data-url="{{ route('customer.signature',$contract->id) }}" data-ajax-popup="true" data-title="{{__('Create Signature')}}" data-size="md" title="{{__('Signature')}}" data-bs-toggle="tooltip" data-bs-placement="top">
                            <i class="ti ti-pencil"></i>
                        </a>
                        @endif

                        <!-- <a href="#" class="btn btn-sm btn-primary btn-icon" data-url="{{ route('signature',$contract->id) }}" data-ajax-popup="true" data-title="{{__('Create New Signature')}}" data-size="lg" title="{{__('Signature')}}" data-bs-toggle="tooltip" data-bs-placement="top">
                            <i class="ti ti-pencil"></i> {{ __('Signature') }}
                        </a> -->
                    </div>
                
                <div class="card mt-3" id="printTable" style="margin-left: 180px;margin-right: -57px;">
                    <div class="card-body">
                        <div class="row invoice-title mt-2">
                            <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 ">
                                <img  src="{{ $logo . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png') }}" style="max-width: 150px;"/>
                            </div>
                            
                            <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                <h3 class="invoice-number">{{\Auth::user()->contractNumberFormat($contract->id)}}</h3>
                            </div>    
                        </div>
                        <div class="row align-items-center mb-4">
                            
                            <div class="col-sm-6 mb-3 mb-sm-0 mt-3">
                                <div class="col-lg-6 col-md-8">
                                <h6 class="d-inline-block m-0 d-print-none">{{__('Type   :')}}</h6>
                                <span class="col-md-8"><span class="text-md">{{ $contract->types->name  }}</span></span>
                            </div>
                            <!-- <div class="col-lg-6 col-md-8 mt-3">
                                <h6 class="d-inline-block m-0 d-print-none">{{__('Contract Number   :')}}</h6>
                                <span class="col-md-8"><span class="text-md">{{$contract->id}}</span></span>
                            </div> -->
                            <div class="col-lg-6 col-md-8 mt-3">
                                <h6 class="d-inline-block m-0 d-print-none">{{__('Value  :')}}</h6>
                                <span class="col-md-8"><span class="text-md">{{Auth::user()->priceFormat($contract->value) }}</span></span>
                            </div>
                            
                                
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <div>
                                    <div class="float-end">
                                        <div class="">
                                            <h6 class="d-inline-block m-0 d-print-none">{{__('Start Date   :')}}</h6>
                                            <span class="col-md-8"><span class="text-md">{{Auth::user()->dateFormat($contract->start_date) }}</span></span>
                                        </div>
                                        <div class="mt-3">
                                            <h6 class="d-inline-block m-0 d-print-none">{{__('End Date   :')}}</h6>
                                            <span class="col-md-8"><span class="text-md">{{Auth::user()->dateFormat($contract->end_date)}}</span></span>
                                        </div>
                                       
                                        {{-- {!! DNS2D::getBarcodeHTML(route('pay.invoice',\Illuminate\Support\Facades\Crypt::encrypt($invoice->id)), "QRCODE",2,2) !!} --}}
                                    </div>

                                </div>
                            </div>
                        </div>
                        <p data-v-f2a183a6="">
                            
                            <div>{!!$contract->description!!}</div>
                            <br>
                            <div>{!!$contract->notes!!}</div>
                        </p>
                        
                        <div class="row">
                            <div class="col-6">
                                <img src="{{$contract->company_signature}}" style="width:20%;" alt="">
                                <h5 class="mt-auto">{{__('Company Signature')}}</h5>
                            </div>
                            <div class="col-6 text-end">
                                <img src="{{$contract->customer_signature}}" style="width:20%;" alt="">
                                <h5 class="mt-auto">{{__('Customer Signature')}}</h5>
                            </div>
                        </div>
                    </div>


                </div>
             
            </div>
        </div>
    </div>

    
</div>
{{-- @if(!isset($preview))
    @include('contracts.script');
@endif --}}

@endsection