@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Bills') }}
@endsection
@section('breadcrumb')
    @if (\Auth::guard('vender')->check())
        <li class="breadcrumb-item"><a href="{{ route('vender.dashboard') }}">{{ __('Dashboard') }}</a></li>
    @else
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    @endif

    <li class="breadcrumb-item">{{ __('Bill') }}</li>
@endsection

@section('action-btn')
    <div class="d-flex">
     
        <a href="{{ route('Bill.export') }}" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip"
            title="{{ __('Export') }}">
            <i class="ti ti-file-export"></i>
        </a>

        @can('create bill')
            <a href="{{ route('bill.create', 0) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection


@section('content')
    @php

    @endphp
    <div class="row">
        <div class="col-sm-12">
            <div class=" multi-collapse mt-2" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        @if (!\Auth::guard('vender')->check())
                            {{ Form::open(['route' => ['bill.index'], 'method' => 'GET', 'id' => 'frm_submit']) }}
                        @else
                            {{ Form::open(['route' => ['vender.bill'], 'method' => 'GET', 'id' => 'frm_submit']) }}
                        @endif
                        <div class="row d-flex align-items-center justify-content-end">
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                <div class="btn-box">
                                    {{ Form::date('bill_date', isset($_GET['bill_date']) ? $_GET['bill_date'] : date('Y-m-d'), ['class' => 'month-btn form-control', 'id' => 'pc-daterangepicker-1', 'placeholder' => 'YYYY-MM-DD']) }}

                                </div>
                            </div>
                            @if (!\Auth::guard('vender')->check())
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                    <div class="btn-box">
                                        {{ Form::select('vender', $vender, isset($_GET['vender']) ? $_GET['vender'] : '', ['class' => 'form-control']) }}
                                    </div>
                                </div>
                            @endif
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                <div class="btn-box">
                                    {{ Form::select('status', ['' => 'Select Status'] + $status, isset($_GET['status']) ? $_GET['status'] : '', ['class' => 'form-control']) }}
                                </div>
                            </div>
                            <div class="col-auto d-flex">
                                <a href="#" class="btn btn-sm btn-primary me-2"
                                    onclick="document.getElementById('frm_submit').submit(); return false;"
                                    data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                    data-original-title="{{ __('Apply') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                </a>

                                @if (!\Auth::guard('vender')->check())
                                    <a href="{{ route('bill.index') }}" class="btn btn-sm btn-danger"
                                        data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                        data-original-title="{{ __('Reset') }}">
                                        <span class="btn-inner--icon"><i class="ti ti-refresh text-white-off"></i></span>
                                    </a>
                                @else
                                    <a href="{{ route('vender.bill') }}" class="btn btn-sm btn-danger"
                                        data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                        data-original-title="{{ __('Reset') }}">
                                        <span class="btn-inner--icon"><i class="ti ti-refresh text-white-off"></i></span>
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
                                    <th> {{ __('Bill') }}</th>
                                    @if (!\Auth::guard('vender')->check())
                                        <th> {{ __('Vendor') }}</th>
                                    @endif
                                    <th> {{ __('Category') }}</th>
                                    <th> {{ __('Bill Date') }}</th>
                                    <th> {{ __('Due Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bills as $bill)
                                    <tr>
                                        <td class="Id">
                                            @if (\Auth::guard('vender')->check())
                                                <a href="{{ route('vender.bill.show', \Crypt::encrypt($bill->id)) }}"
                                                    class="btn btn-outline-primary">{{ AUth::user()->billNumberFormat($bill->bill_id) }}</a>
                                            @else
                                                <a href="{{ route('bill.show', \Crypt::encrypt($bill->id)) }}"
                                                    class="btn btn-outline-primary">{{ AUth::user()->billNumberFormat($bill->bill_id) }}</a>
                                            @endif
                                        </td>
                                        @if (!\Auth::guard('vender')->check())
                                            <td> {{ !empty($bill->vender) ? $bill->vender->name : '' }} </td>
                                        @endif
                                        <td>{{ !empty($bill->category) ? $bill->category->name : '' }}</td>
                                        <td>{{ Auth::user()->dateFormat($bill->bill_date) }}</td>
                                        <td>{{ Auth::user()->dateFormat($bill->due_date) }}</td>
                                        <td>
                                            @if ($bill->status == 0)
                                                <span
                                                    class="badge fix_badges bg-secondary p-2 px-3">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 1)
                                                <span
                                                    class="badge fix_badges bg-warning p-2 px-3">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 2)
                                                <span
                                                    class="badge fix_badges bg-danger p-2 px-3">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 3)
                                                <span
                                                    class="badge fix_badges bg-info p-2 px-3">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 4)
                                                <span
                                                    class="badge fix_badges bg-primary p-2 px-3">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                            <td class="Action">
                                                <span>
                                                    @can('duplicate bill')
                                                        <div class="action-btn me-2">
                                                            {!! Form::open([
                                                                'method' => 'get',
                                                                'route' => ['bill.duplicate', $bill->id],
                                                                'id' => 'duplicate-form-' . $bill->id,
                                                            ]) !!}

                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para bg-secondary"
                                                                data-bs-toggle="tooltip"
                                                                data-original-title="{{ __('Duplicate') }}"
                                                                data-bs-toggle="tooltip" title="{{ __('Duplicate Bill') }}"
                                                                data-original-title="{{ __('Delete') }}"
                                                                data-confirm="You want to confirm this action. Press Yes to continue or Cancel to go back"
                                                                data-confirm-yes="document.getElementById('duplicate-form-{{ $bill->id }}').submit();">
                                                                <i class="ti ti-copy text-white"></i>
                                                                {!! Form::close() !!}
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('show bill')
                                                        @if (\Auth::guard('vender')->check())
                                                            <div class="action-btn me-2">
                                                                <a href="{{ route('vender.bill.show', \Crypt::encrypt($bill->id)) }}"
                                                                    class="mx-3 btn btn-sm align-items-center bg-warning"
                                                                    data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                    data-original-title="{{ __('Detail') }}">
                                                                    <i class="ti ti-eye text-white"></i>
                                                                </a>
                                                            </div>
                                                        @else
                                                            <div class="action-btn me-2">
                                                                <a href="{{ route('bill.show', \Crypt::encrypt($bill->id)) }}"
                                                                    class="mx-3 btn btn-sm align-items-center bg-warning"
                                                                    data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                    data-original-title="{{ __('Detail') }}">
                                                                    <i class="ti ti-eye text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    @endcan
                                                    @can('edit bill')
                                                        <div class="action-btn me-2">
                                                            <a href="{{ route('bill.edit', \Crypt::encrypt($bill->id)) }}"
                                                                class="mx-3 btn btn-sm align-items-center bg-info"
                                                                data-bs-toggle="tooltip" title="Edit"
                                                                data-original-title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('delete bill')
                                                        <div class="action-btn">

                                                            {!! Form::open(['method' => 'DELETE', 'route' => ['bill.destroy', $bill->id]]) !!}
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para bg-danger"
                                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                data-original-title="{{ __('Delete') }}">
                                                                <i class="ti ti-trash text-white"></i>
                                                            </a>
                                                            {!! Form::close() !!}
                                                        </div>
                                                    @endcan
                                                </span>
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
