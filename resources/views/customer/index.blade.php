@extends('layouts.admin')
@php
    $profile = asset(Storage::url('uploads/avatar/'));
@endphp
@push('script-page')
    <script>
        $(document).on('click', '#billing_data', function() {
            $("[name='shipping_name']").val($("[name='billing_name']").val());
            $("[name='shipping_country']").val($("[name='billing_country']").val());
            $("[name='shipping_state']").val($("[name='billing_state']").val());
            $("[name='shipping_city']").val($("[name='billing_city']").val());
            $("[name='shipping_phone']").val($("[name='billing_phone']").val());
            $("[name='shipping_zip']").val($("[name='billing_zip']").val());
            $("[name='shipping_address']").val($("[name='billing_address']").val());
        })
    </script>
@endpush
@section('page-title')
    {{ __('Manage Customers') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Customer') }}</li>
@endsection

@section('action-btn')
    <div class="d-flex">
        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
            data-url="{{ route('customer.file.import') }}" data-ajax-popup="true"
            data-title="{{ __('Import customer CSV file') }}" class="btn btn-sm btn-primary me-2">
            <i class="ti ti-file-import"></i>
        </a>
        <a href="{{ route('customer.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary me-2">
            <i class="ti ti-file-export"></i>
        </a>

        <a href="#" data-size="xl" data-url="{{ route('customer.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Create Customer') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style
table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th> {{ __('Name') }}</th>
                                    <th> {{ __('Contact') }}</th>
                                    <th> {{ __('Email') }}</th>
                                    <th> {{ __('Balance') }}</th>
                                    <th> {{ __('Last Login') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customers as $k => $customer)
                                    <tr class="cust_tr" id="cust_detail"
                                        data-url="{{ route('customer.show', $customer['id']) }}"
                                        data-id="{{ $customer['id'] }}">
                                        <td class="Id">
                                            @can('show customer')
                                                <a href="{{ route('customer.show', \Crypt::encrypt($customer['id'])) }}"
                                                    class="btn btn-outline-primary">
                                                    {{ AUth::user()->customerNumberFormat($customer['customer_id']) }}
                                                </a>
                                            @else
                                                <a href="#" class="btn btn-outline-primary">
                                                    {{ AUth::user()->customerNumberFormat($customer['customer_id']) }}
                                                </a>
                                            @endcan
                                        </td>
                                        <td class="font-style">{{ $customer['name'] }}</td>
                                        <td>{{ $customer['contact'] }}</td>
                                        <td>{{ $customer['email'] }}</td>
                                        <td>{{ \Auth::user()->priceFormat($customer['balance']) }}</td>
                                        <td>
                                            {{ !empty($customer->last_login_at) ? $customer->last_login_at : '-' }}
                                        </td>
                                        <td class="Action">
                                            <span>
                                                @if ($customer['is_active'] == 0)
                                                    <i class="ti ti-lock" title="Inactive"></i>
                                                @else
                                                    @if ($customer->is_enable_login == 0 && $customer->password == null)
                                                        <div class="action-btn me-2">
                                                            <a href="#" class="mx-3 btn btn-sm  align-items-center login_enable bg-primary"
                                                                data-url="{{ route('customer.reset', \Crypt::encrypt($customer['id'])) }}"
                                                                data-ajax-popup="true" data-size="md"
                                                                data-bs-toggle="tooltip"
                                                                title="{{ __('Forgot Password') }}"
                                                                data-title="{{ __('Reset Password') }}">
                                                                <i class="ti ti-key text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @can('show customer')
                                                        <div class="action-btn me-2">
                                                            <a href="{{ route('customer.show', \Crypt::encrypt($customer['id'])) }}"
                                                                class="mx-3 btn btn-sm align-items-center bg-warning"
                                                                data-bs-toggle="tooltip" title="{{ __('View') }}">
                                                                <i class="ti ti-eye text-white text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('edit customer')
                                                        <div class="action-btn me-2">
                                                            <a href="#" class="mx-3 btn btn-sm  align-items-center bg-info"
                                                                data-url="{{ route('customer.edit', $customer['id']) }}"
                                                                data-ajax-popup="true" data-size="xl" data-bs-toggle="tooltip"
                                                                title="{{ __('Edit') }}"
                                                                data-title="{{ __('Edit Customer') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan



                                                    @can('delete customer')
                                                        <div class="action-btn">
                                                            {!! Form::open([
                                                                'method' => 'DELETE',
                                                                'route' => ['customer.destroy', $customer['id']],
                                                                'id' => 'delete-form-' . $customer['id'],
                                                            ]) !!}
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm  align-items-center bs-pass-para bg-danger"
                                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                                    class="ti ti-trash text-white text-white"></i></a>
                                                            {!! Form::close() !!}
                                                        </div>
                                                    @endcan
                                                @endif
                                            </span>
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

@push('script-page')
    <script>
        $(document).on('change', '#password_switch', function() {
            if ($(this).is(':checked')) {
                $('.ps_div').removeClass('d-none');
                $('#password').attr("required", true);

            } else {
                $('.ps_div').addClass('d-none');
                $('#password').val(null);
                $('#password').removeAttr("required");
            }
        });
        $(document).on('click', '.login_enable', function() {
            setTimeout(function() {
                $('.modal-body').append($('<input>', {
                    type: 'hidden',
                    val: 'true',
                    name: 'login_enable'
                }));
            }, 2000);
        });
    </script>
@endpush
