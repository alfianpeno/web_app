@extends('layouts.admin')
@section('page-title')
    {{__('Manage Roles')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Role')}}</li>
@endsection


@section('action-btn')
    <div class="d-flex">
        @can('create role')
            <a href="#" data-size="xl" data-url="{{ route('roles.create') }}" data-ajax-popup="true"  data-bs-toggle="tooltip" title="{{__('Create')}}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5></h5>
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr>
                                <th>{{__('Role')}} </th>
                                <th>{{__('Permissions')}} </th>
                                <th width="150">{{__('Action')}} </th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($roles as $role)
                                <tr class="font-style">
                                    <td class="Role">{{ $role->name }}</td>
                                    <td class="Permission">
                                        @for($j=0;$j<count($role->permissions()->pluck('name'));$j++)
                                            <span class="badge rounded-pill bg-primary">{{$role->permissions()->pluck('name')[$j]}}</span>
                                        @endfor
                                    </td>
                                    <td class="Action">
                                        <span>
                                        @can('edit role')
                                            <div class="action-btn me-2">
                                                <a href="#" class="mx-3 btn btn-sm d-inline-flex align-items-center bg-info" data-url="{{ route('roles.edit',$role->id) }}" data-size="xl" data-ajax-popup="true" data-bs-toggle="tooltip" data-original-title="{{__('Edit')}}" title="{{__('Edit')}}">
                                                <span><i class="ti ti-pencil text-white"></i></span>
                                                </a>
                                            </div>

                                            @endcan
                                            @can('delete role')
                                                <div class="action-btn">
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['roles.destroy', $role->id],'id'=>'delete-form-'.$role->id]) !!}
                                                    <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para bg-danger" data-bs-toggle="tooltip" title="{{__('Delete')}}" ><i class="ti ti-trash text-white text-white"></i></a>


                                                    {!! Form::close() !!}
                                                 </div>
                                            @endcan
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
