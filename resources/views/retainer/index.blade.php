@extends('layouts.admin')
@section('page-title')
    {{__('Manage Retainers')}}
@endsection
@section('breadcrumb')
@if(\Auth::guard('customer')->check())
    <li class="breadcrumb-item"><a href="{{route('customer.dashboard')}}">{{__('Dashboard')}}</a></li>
    @else
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    @endif
    <li class="breadcrumb-item">{{__('Retainers')}}</li>
@endsection

@section('action-btn')
    <div class="d-flex">
       
        <a href="{{route('retainer.export')}}" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="{{__('Export')}}">
            <i class="ti ti-file-export"></i>
        </a>

        @can('create retainer') 
            <a href="{{ route('retainer.create',0) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{__('Create')}}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>

@endsection
@push('css-page')

@endpush
@push('script-page')

@endpush
@section('content')

    <div class="row">
        <div class="col-sm-12">
            <div class=" multi-collapse mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        @if(!\Auth::guard('customer')->check())
                            {{ Form::open(array('route' => array('retainer.index'),'method' => 'GET','id'=>'frm_submit')) }}
                        @else
                            {{ Form::open(array('route' => array('customer.retainer'),'method' => 'GET','id'=>'frm_submit')) }}
                        @endif
                        <div class="row d-flex align-items-center justify-content-end">
                            @if(!\Auth::guard('customer')->check())
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                    <div class="btn-box">
                                        {{ Form::label('customer', __('Customer'),['class'=>'text-type']) }}
                                        {{ Form::select('customer',$customer,isset($_GET['customer'])?$_GET['customer']:'', array('class' => 'form-control select')) }}
                                    </div>
                                </div>
                            @endif
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                <div class="btn-box">
                                    {{ Form::label('issue_date', __('Date'),['class'=>'text-type']) }}
                                    {{ Form::text('issue_date', isset($_GET['issue_date'])?$_GET['issue_date']:date('Y-m-d'), array('class' => 'form-control month-btn','id'=>'pc-daterangepicker-1','placeholder'=>"Select Date")) }}
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                <div class="btn-box">
                                    {{ Form::label('status', __('Status'),['class'=>'text-type']) }}
                                    {{ Form::select('status', [''=>'Select Status']+$status,isset($_GET['status'])?$_GET['status']:'', array('class' => 'form-control')) }}
                                </div>
                            </div>
                            <div class="col-auto d-flex mt-4">

                                <a href="#" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="{{__('apply')}}" onclick="document.getElementById('frm_submit').submit(); return false;" >
                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                </a>
                                @if(\Auth::user()->type == 'company')
                                <a href="{{ route('retainer.index') }}" class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                   title="{{ __('Reset') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-refresh text-white-off "></i></span>
                                </a>
                                @else
                                <a href="{{ route('customer.retainer') }}" class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                   title="{{ __('Reset') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-refresh text-white-off "></i></span>
                                </a>
                                @endif
                            </div>

                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr>
                                <th> {{__('Retainer')}}</th>
                                @if(!\Auth::guard('customer')->check())
                                    <th> {{__('Customer')}}</th>
                                @endif
                                <th> {{__('Category')}}</th>
                                <th> {{__('Issue Date')}}</th>
                                <th> {{__('Status')}}</th>
                                @if(Gate::check('edit proposal') || Gate::check('delete proposal') || Gate::check('show proposal'))
                                    <th width="10%"> {{__('Action')}}</th>
                                @endif
                                {{-- <th>
                                    <td class="barcode">
                                        {!! DNS1D::getBarcodeHTML($invoice->sku, "C128",1.4,22) !!}
                                        <p class="pid">{{$invoice->sku}}</p>
                                    </td>
                                </th> --}}
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($retainers as $retainer)
                                <tr class="font-style">
                                    <td class="Id">
                                        @if(\Auth::guard('customer')->check())
                                            <a href="{{ route('customer.retainer.show',\Crypt::encrypt($retainer->id)) }}" class="btn btn-outline-primary">{{ AUth::user()->retainerNumberFormat($retainer->retainer_id) }}
                                            </a>
                                        @else
                                            <a href="{{ route('retainer.show',\Crypt::encrypt($retainer->id)) }}" class="btn btn-outline-primary">{{ AUth::user()->retainerNumberFormat($retainer->retainer_id) }}
                                            </a>
                                        @endif
                                    </td>
                                    @if(!\Auth::guard('customer')->check())
                                        <td> {{!empty($retainer->customer)? $retainer->customer->name:'' }} </td>
                                    @endif
                                    <td>{{ !empty($retainer->category)?$retainer->category->name:''}}</td>
                                    <td>{{ Auth::user()->dateFormat($retainer->issue_date) }}</td>
                                    <td>
                                        @if($retainer->status == 0)
                                            <span class="badge fix_badges bg-primary p-2 px-3">{{ __(\App\Models\retainer::$statues[$retainer->status]) }}</span>
                                        @elseif($retainer->status == 1)
                                            <span class="badge fix_badges bg-info p-2 px-3">{{ __(\App\Models\retainer::$statues[$retainer->status]) }}</span>
                                        @elseif($retainer->status == 2)
                                            <span class="badge fix_badges bg-secondary p-2 px-3">{{ __(\App\Models\retainer::$statues[$retainer->status]) }}</span>
                                        @elseif($retainer->status == 3)
                                            <span class="badge fix_badges bg-warning p-2 px-3">{{ __(\App\Models\retainer::$statues[$retainer->status]) }}</span>
                                        @elseif($retainer->status == 4)
                                            <span class="badge fix_badges bg-danger p-2 px-3">{{ __(\App\Models\retainer::$statues[$retainer->status]) }}</span>
                                        @endif
                                    </td>
                                    @if(Gate::check('edit proposal') || Gate::check('delete proposal') || Gate::check('show proposal'))
                                        <td class="Action">
                                            @if($retainer->is_convert==0)
                                                @can('convert invoice retainer')
                                                    <div class="action-btn me-2">
                                                        {!! Form::open(['method' => 'get', 'route' => ['retainer.convert', $retainer->id],'id'=>'proposal-form-'.$retainer->id]) !!}

                                                        <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para bg-warning-subtle" data-bs-toggle="tooltip" title="{{__('Convert into  Invoice')}}" data-original-title="{{__('Convert to Invoice')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('You want to confirm convert to invoice. Press Yes to continue or Cancel to go back')}}" data-confirm-yes="document.getElementById('proposal-form-{{$retainer->id}}').submit();">
                                                            <i class="ti ti-exchange text-white"></i>
                                                            {!! Form::close() !!}
                                                        </a>
                                                    </div>
                                                @endcan
                                            @else
                                                @can('convert invoice retainer')
                                                    <div class="action-btn me-2">
                                                        <a href="{{ route('invoice.show',\Crypt::encrypt($retainer->converted_invoice_id)) }}" class="mx-3 btn btn-sm  align-items-center bg-warning-subtle" data-bs-toggle="tooltip" title="{{__('Already convert to Invoice')}}" data-original-title="{{__('Already convert to Invoice')}}" data-original-title="{{__('Delete')}}">
                                                            <i class="ti ti-file-invoice text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                            @endif
                                            @can('duplicate retainer')
                                                <div class="action-btn me-2">
                                                    {!! Form::open(['method' => 'get', 'route' => ['retainer.duplicate', $retainer->id],'id'=>'duplicate-form-'.$retainer->id]) !!}

                                                    <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para bg-secondary" data-bs-toggle="tooltip" title="{{__('Duplicate')}}" data-original-title="{{__('Duplicate')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('You want to confirm duplicate this invoice. Press Yes to continue or Cancel to go back')}}" data-confirm-yes="document.getElementById('duplicate-form-{{$retainer->id}}').submit();">
                                                        <i class="ti ti-copy text-white text-white"></i>
                                                        {!! Form::close() !!}
                                                    </a>
                                                </div>
                                            @endcan

                                            @can('show proposal')
                                                @if(\Auth::guard('customer')->check())
                                                    <div class="action-btn me-2">
                                                        <a href="{{ route('customer.retainer.show',\Crypt::encrypt($retainer->id)) }}" class="mx-3 btn btn-sm align-items-center bg-warning" data-bs-toggle="tooltip" title="{{__('Show')}}" data-original-title="{{__('Detail')}}">
                                                            <i class="ti ti-eye text-white text-white"></i>
                                                        </a>
                                                    </div>
                                                @else
                                                    <div class="action-btn me-2">
                                                        <a href="{{ route('retainer.show',\Crypt::encrypt($retainer->id)) }}" class="mx-3 btn btn-sm  align-items-center bg-warning" data-bs-toggle="tooltip" title="{{__('Show')}}" data-original-title="{{__('Detail')}}">
                                                            <i class="ti ti-eye text-white text-white"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                            @endcan
                                            @can('edit retainer')
                                                <div class="action-btn me-2">
                                                    <a href="{{ route('retainer.edit',\Crypt::encrypt($retainer->id)) }}" class="mx-3 btn btn-sm  align-items-center bg-info" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                            @endcan

                                            @can('delete retainer')
                                                <div class="action-btn">
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['retainer.destroy', $retainer->id],'id'=>'delete-form-'.$retainer->id]) !!}

                                                    <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para bg-danger " data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$retainer->id}}').submit();">
                                                        <i class="ti ti-trash text-white text-white"></i>
                                                    </a>
                                                    {!! Form::close() !!}
                                                </div>
                                            @endcan
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
