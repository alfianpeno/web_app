@extends('layouts.admin')
@push('script-page')
@endpush
@section('page-title')
    {{ __('Contract Type') }}
@endsection
@section('title')
    <div class="d-inline-block">
        <h5 class="h4 d-inline-block font-weight-400 mb-0 ">{{ __('Contract Type') }}</h5>
    </div>
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item " aria-current="page">{{ __('Contract Type') }}</li>
@endsection
@section('action-btn')
    @can('manage constant contract type')
        <div class="float-end">
            <a href="#" data-url="{{ route('contractType.create') }}" data-bs-toggle="tooltip" data-size="md"
                title="{{ __('Create') }}" data-ajax-popup="true" data-title="{{ __('Create New Contract') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        </div>
    @endcan
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
                                    <th scope="col">{{ __('Name') }}</th>
                                        <th scope="col" class="text-end">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($types as $type)
                                    <tr class="font-style">
                                        <td>{{ $type->name }}</td>
                                        @if (Gate::check('edit constant contract type') || Gate::check('delete constant contract type'))
                                            <td class="action text-end">
                                                @can('edit constant contract type')
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                            data-url="{{ route('contractType.edit', $type->id) }}"
                                                            data-ajax-popup="true" data-title="{{ __('Edit Contract Type') }}"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                            data-original-title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan

                                                @can('delete constant contract type')
                                                    <div class="action-btn bg-danger ms-2">
                                                        {!! Form::open([
                                                            'method' => 'DELETE',
                                                            'route' => ['contractType.destroy', $type->id],
                                                            'id' => 'delete-form-' . $type->id,
                                                        ]) !!}
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                            data-original-title="{{ __('Delete') }}"
                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="document.getElementById('delete-form-{{ $type->id }}').submit();">
                                                            <i class="ti ti-trash text-white"></i>
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