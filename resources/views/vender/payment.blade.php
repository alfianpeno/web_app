@extends('layouts.admin')
@push('script-page')
@endpush
@section('page-title')
    {{__('Payment')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('vender.dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Payment')}}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        <!-- <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{__('Filter')}}">
            <i class="ti ti-filter"></i>
        </a> -->
    </div>
@endsection
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class=" multi-collapse mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(array('route' => array('vender.payment'),'method' => 'GET','id'=>'frm_submit')) }}
                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-10">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('date', __('Date'),['class'=>'text-type']) }}
                                            {{ Form::text('date', isset($_GET['date'])?$_GET['date']:date('Y-m-d'), array('class' => 'form-control month-btn','id'=>'pc-daterangepicker-1')) }}
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('category', __('Category'),['class'=>'text-type']) }}

                                            <select class="form-control select" tabindex="-1" aria-hidden="true" name="category" id="">
                                                <option value="">Select Category</option>
                                                @foreach($category as $key => $value)
                                                    <option class="opt" value="{{ $value }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                            {{-- {{ Form::select('category',  [''=>'All']+$category,isset($_GET['category'])?$_GET['category']:'', array('class' => 'form-control select')) }}                                         --}}
                                        </div>
                                    </div>


                                </div>
                            </div>
                            <div class="col-auto mt-3">
                                <div class="row">
                                    <div class="col-auto">

                                        <a href="#" class="btn btn-sm btn-primary" onclick="document.getElementById('frm_submit').submit(); return false;" data-bs-toggle="tooltip" title="{{__('Apply')}}" data-original-title="{{__('apply')}}">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </a>

                                        <a href="{{route('vender.payment')}}" class="btn btn-sm btn-danger " data-bs-toggle="tooltip"  title="{{ __('Reset') }}" data-original-title="{{__('Reset')}}">
                                            <span class="btn-inner--icon"><i class="ti ti-refresh text-white-off "></i></span>
                                        </a>


                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    {{ Form::close() }}
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
                                <th> {{__('Date')}}</th>
                                <th> {{__('Amount')}}</th>
                                <th> {{__('Category')}}</th>
                                <th> {{__('Description')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td>{{  Auth::user()->dateFormat($payment->date)}}</td>
                                    <td>{{  Auth::user()->priceFormat($payment->amount)}}</td>
                                    <td>{{  $payment->category}}</td>
                                    <td>{{  $payment->description}}</td>
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
