@extends('layouts.admin')
@php
    $profile=asset(Storage::url('uploads/avatar/'));
@endphp
@section('page-title')
    {{__('Manage User')}}
@endsection
@push('script-page')

@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('User')}}</li>
@endsection
@section('action-btn')
    <div class="d-flex">
        <a href="#" data-size="lg" data-url="{{ route('users.create') }}" data-ajax-popup="true"  data-bs-toggle="tooltip" title="{{__('Create New User')}}"  class="btn btn-sm btn-primary me-2">
            <i class="ti ti-plus"></i>
        </a>
        @if(\Auth::user()->type == 'company')    
            <a href="{{ route('userlogs.index') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('User Log') }}">
                <i class="ti ti-user-check"></i>
            </a>
        @endif
    </div>
@endsection
@section('content')
    <div class="row">
        <div class="col-xxl-12">
            <div class="row">

                @foreach($users as $user)
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-header border-0 pb-0">
                                <div class="d-flex align-items-center" data-bs-toggle="tooltip" title="{{__('Last Login')}}">
                                    {{ (!empty($user->last_login_at)) ? $user->last_login_at : '' }}
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <div class="badge p-2 px-3 bg-primary">{{ $user->type }}</div>
                                    </h6>
                                </div>
                                @if(Gate::check('edit user') || Gate::check('delete user'))
                                    <div class="card-header-right">
                                        <div class="btn-group card-option">
                                        @if($user->is_active==1)
                                            <button type="button" class="btn dropdown-toggle"
                                                    data-bs-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="false">
                                                <i class="ti ti-dots-vertical"></i>
                                            </button>

                                            <div class="dropdown-menu dropdown-menu-end">

                                                @can('edit user')
                                                        <a href="#!" data-size="lg" data-url="{{ route('users.edit',$user->id) }}" data-ajax-popup="true" class="dropdown-item" data-bs-original-title="{{__('Edit User')}}">
                                                            <i class="ti ti-pencil"></i>
                                                            <span>{{__('Edit')}}</span>
                                                        </a>
                                                @endcan

                                                @can('delete user')
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['users.destroy', $user['id']],'id'=>'delete-form-'.$user['id']]) !!}
                                                    <a href="#!"  class="dropdown-item bs-pass-para">
                                                        <i class="ti ti-archive"></i>
                                                        <span> @if($user->delete_status!=0){{__('Delete')}} @else {{__('Restore')}}@endif</span>
                                                    </a>

                                                    {!! Form::close() !!}
                                                @endcan

                                                <a href="#!" data-url="{{route('users.reset',\Crypt::encrypt($user->id))}}" data-ajax-popup="true" data-size="md" class="dropdown-item" data-bs-original-title="{{__('Reset Password')}}">
                                                    <i class="ti ti-adjustments"></i>
                                                    <span>  {{__('Reset Password')}}</span>
                                                </a>

                                                @if ($user->is_enable_login == 1)
                                                    <a href="{{ route('users.login', \Crypt::encrypt($user->id)) }}"
                                                        class="dropdown-item">
                                                        <i class="ti ti-road-sign"></i>
                                                        <span class="text-danger"> {{ __('Login Disable') }}</span>
                                                    </a>
                                                @elseif ($user->is_enable_login == 0 && $user->password == null)
                                                    <a href="#" data-url="{{ route('users.reset', \Crypt::encrypt($user->id)) }}"
                                                        data-ajax-popup="true" data-size="md" class="dropdown-item login_enable"
                                                        data-title="{{ __('New Password') }}" class="dropdown-item">
                                                        <i class="ti ti-road-sign"></i>
                                                        <span class="text-success"> {{ __('Login Enable') }}</span>
                                                    </a>
                                                @else
                                                    <a href="{{ route('users.login', \Crypt::encrypt($user->id)) }}"
                                                        class="dropdown-item">
                                                        <i class="ti ti-road-sign"></i>
                                                        <span class="text-success"> {{ __('Login Enable') }}</span>
                                                    </a>
                                                @endif
                                            </div>
                                            @else
                                                <a href="#" class="action-item"><i class="ti ti-lock"></i></a>
                                            @endif

                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="avatar me-3">
                                    <img src="{{(!empty($user->avatar))? asset(Storage::url($user->avatar)): asset(Storage::url('uploads/avatar/avatar.png'))}}" alt="kal" class="img-fluid rounded border-2 border border-primary" width="120px" height="120px">
                                </div>
                                <h4 class=" mt-2">{{ $user->name }}</h4>
                                    @if($user->delete_status==0)
                                        <h5 class="office-time mb-0">{{__('Soft Deleted')}}</h5>
                                    @endif
                                <small>{{ $user->email }}</small>

                               
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="col-md-3">
                    <a href="#" class="btn-addnew-project border-primary"  data-ajax-popup="true" data-size="lg" data-title="{{ __('Create New User') }}" data-url="{{route('users.create')}}">
                        <div class="badge bg-primary proj-add-icon">
                            <i class="ti ti-plus"></i>
                        </div>
                        <h6 class="mt-4 mb-2">{{ __('New User') }}</h6>
                        <p class="text-muted text-center">{{ __('Click here to add New User') }}</p>
                    </a>
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