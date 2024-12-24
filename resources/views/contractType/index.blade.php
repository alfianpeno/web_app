@extends('layouts.admin')
@push('script-page')
@endpush
@section('page-title')
    {{__('Contract Type')}}
@endsection
@section('title')
    <div class="d-inline-block">
        <h5 class="h4 d-inline-block font-weight-400 mb-0 ">{{__('Contract Type')}}</h5>
    </div>
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item " aria-current="page">{{__('Contract Type')}}</li>
@endsection
@section('action-btn')
    @if(\Auth::user()->type=='company')
    <div class="d-flex">
        <a href="#" data-url="{{ route('contractType.create') }}" data-bs-toggle="tooltip" data-size="md" title="{{__('Create')}}" data-ajax-popup="true" data-title="{{__('Create New Contract')}}" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    </div>
    @endif
@endsection
@section('filter')
@endsection
@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body table-border-style">
                <!-- <h5></h5> -->
                <div class="table-responsive">
                    <table class="table datatable">
                        <thead>
                            <tr>
                                <th scope="col">{{__('Name')}}</th>
                                @if(\Auth::user()->type=='company')
                                    <th  width="10%">{{__('Action')}}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($types as $type)
                                <tr class="font-style">
                                    <td>{{ $type->name }}</td>
                                    @if(\Auth::user()->type=='company')
                                    
                                        <td class="action ">

                                            <div class="action-btn me-2">
                                                <a href="#" class="mx-3 btn btn-sm align-items-center bg-info" data-url="{{ route('contractType.edit',$type->id) }}" data-ajax-popup="true" data-title="{{__('Edit Contract Type')}}" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                                    <i class="ti ti-pencil text-white"></i>
                                                </a>
                                            </div>

                                            <div class="action-btn">
                                                {!! Form::open(['method' => 'DELETE', 'route' => ['contractType.destroy', $type->id],'id'=>'delete-form-'.$type->id]) !!}
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para bg-danger" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$type->id}}').submit();">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                {!! Form::close() !!}
                                            </div>

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

