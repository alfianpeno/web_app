@extends('layouts.admin')
@section('page-title')
    {{ __('Users logs') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Users logs') }}</li>
@endsection

@section('content')
    <div class="col-sm-12 col-lg-12 col-xl-12 col-md-12">
        <div class=" mt-2 " id="multiCollapseExample1" style="">
            <div class="card">
                <div class="card-body">
                    {{ Form::open(['route' => ['userlogs.index'], 'method' => 'get', 'id' => 'userlogs_filter']) }}
                    <div class="d-flex align-items-center justify-content-end">
                        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12 col-12 mx-2">
                            <div class="btn-box">
                                {{ Form::label('month', __('Month'), ['class' => 'form-label']) }}
                                {{ Form::month('month', isset($_GET['month']) ? $_GET['month'] : date('Y-m'), ['class' => 'form-control']) }}
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12 col-12 mx-2">
                            <div class="btn-box">
                                {{ Form::label('users', __('Users'), ['class' => 'form-label']) }}
                                {{ Form::select('user', $usersList, isset($_GET['user']) ? $_GET['user'] : '', ['class' => 'form-control select ', 'id' => 'id']) }}
                            </div>
                        </div>
                        <div class="col-auto d-flex mt-4">
                            <a href="#" class="btn btn-sm btn-primary me-2"
                                onclick="document.getElementById('userlogs_filter').submit(); return false;"
                                data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                data-original-title="{{ __('apply') }}">
                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                            </a>
                            <a href="{{ route('userlogs.index') }}" class="btn btn-sm btn-danger " data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                <span class="btn-inner--icon"><i class="ti ti-refresh text-white-off "></i></span>
                            </a>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5></h5>
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th> {{ __('User') }}</th>
                                    <th> {{ __('Role') }}</th>
                                    <th> {{ __('Ip') }}</th>
                                    <th> {{ __('Last Login') }}</th>
                                    <th> {{ __('Country') }}</th>
                                    <th> {{ __('Device Type') }}</th>
                                    <th> {{ __('Os') }}</th>
                                    <th class="" width="200px"> {{ __('Action') }}</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach ($logindetails as $logindetail)
                                    @php
                                        $details = json_decode($logindetail->details);
                                    @endphp
                                    @if ($details->status != 'fail')
                                        <tr class="font-style">
                                            <td>
                                                @php
                                                    $user = $logindetail->Getuser($logindetail->type, $logindetail->user_id);
                                                    $name = !empty($user) ? $user->name : '';
                                                    $email = !empty($user) ? $user->email : '';
                                                    $avatar = !empty($user) ? (!empty($user->avatar) ? \App\Models\Utility::get_file($user->avatar) : asset(Storage::url('uploads/avatar/avatar.png'))) : asset(Storage::url('uploads/avatar/avatar.png'));
                                                @endphp
                                                <div class="d-flex align-items-start">
                                                    <div class="theme-avtar">
                                                        <a href="#">
                                                            <img src="{{ $avatar }}"
                                                                class="img-fluid rounded border-2 border border-primary">
                                                        </a>
                                                    </div>
                                                    <h6 class="text-muted ms-2 mb-0">{{ $name }}</h6>
                                                   
                                                </div>
                                            </td>
                                            <td>
                                                <span
                                                    class="me-5 badge p-2 px-3  bg-success text-capitalize fix_badge">{{ $logindetail->type }}</span>
                                            </td>
                                            <td>{{ $logindetail->ip }}</td>
                                            <td>{{ $logindetail->date }}</td>
                                            <td>{{ $details->country }}</td>
                                            <td>{{ $details->device_type }}</td>
                                            <td>{{ $details->os_name }}</td>
                                            <td class="Action">
                                                <span>
                                                    <div class="action-btn me-2">
                                                     
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm d-inline-flex align-items-center bg-warning"
                                                            data-bs-toggle="modal" data-size="lg" data-ajax-popup="true"
                                                            data-url="{{ route('userlogs.show', [$logindetail->id]) }}"
                                                            data-title="{{ __('View User Logs') }}" data-size="lg">
                                                            <span class="text-white"> <i class="ti ti-eye"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-original-title="{{ __('View') }}"></i></span></a>

                                                    </div>
                                                    {{-- @can('delete role') --}}
                                                    <div class="action-btn">
                                                        {!! Form::open([
                                                            'method' => 'DELETE',
                                                            'route' => ['userlogs.destroy', $logindetail->id],
                                                            'id' => 'delete-form-' . $logindetail->id,
                                                        ]) !!}
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm  align-items-center bs-pass-para bg-danger"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                                class="ti ti-trash text-white text-white"></i></a>

                                                        {!! Form::close() !!}
                                                    </div>
                                                    {{-- @endcan --}}
                                                </span>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
