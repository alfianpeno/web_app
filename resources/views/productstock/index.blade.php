@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Product Stock') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Product Stock')}}</li>
@endsection


@section('content')

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr role="row">
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Sku') }}</th>
                                <th>{{ __('Current Quantity') }}</th>
                                 <th>{{ __('Action') }}</th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach ($productServices as $productService)
                                <tr class="font-style">
                                    <td>{{ $productService->name }}</td>
                                    <td>{{ $productService->sku }}</td>
                                    <td>{{ $productService->quantity }}</td>
                                    <td class="Action">
                                        <div class="action-btn">
                                            <a data-size="md" href="#" class="mx-3 btn btn-sm d-inline-flex align-items-center bg-info " data-url="{{ route('productstock.edit', $productService->id) }}" data-ajax-popup="true"  data-size="xl" data-bs-toggle="tooltip" title="{{__('Update Quantity')}}">
                                            <span><i class="ti ti-pencil text-white"></i></span>
                                            </a>
                                        </div>
                                    </td>
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
