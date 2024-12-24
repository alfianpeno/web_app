{{Form::open(array('url'=>'roles','method'=>'post','class'=>'needs-validation','novalidate'))}}
<div class="modal-body">

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{Form::label('name',__('Name'),['class'=>'form-label'])}}<x-required></x-required>
                {{Form::text('name',null,array('class'=>'form-control','placeholder'=>__('Enter Role Name'),'required'=>'required'))}}
                @error('name')
                <small class="invalid-name" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </small>
                @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                @if(!empty($permissions))
                    <h6 class="my-3">{{__('Assign Permission to Roles')}}</h6>
                    <table class="table  mb-0" id="dataTable-1">
                        <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input align-middle" name="checkall"  id="checkall" >
                            </th>
                            <th>{{__('Module')}} </th>
                            <th>{{__('Permissions')}} </th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $modules=['dashboard','user','role','proposal','retainer','invoice','bill','revenue','payment','proposal product','invoice product','bill product','goal','credit note','debit note','bank account','transfer','transaction','product & service','customer','vender','contract','constant tax','constant category','constant unit','constant custom field','constant contract type','company settings','assets','chart of account','journal entry','report'];
                            if(\Auth::user()->type == 'super admin'){
                                $modules[] = 'language';
                                $modules[] = 'permission';
                            }
                        @endphp
                        {{-- @dd($modules,$permissions) --}}
                        @foreach($modules as $module)
                            <tr>
                                <td><input type="checkbox" class="form-check-input align-middle ischeck"  data-id="{{str_replace(' ', '', $module)}}" ></td>
                                <td><label class="ischeck" data-id="{{str_replace(' ', '', $module)}}">{{ ucfirst($module) }}</label></td>
{{--                            <td>{{ ucfirst($module) }}</td>--}}
                                <td>
                                    <div class="row">
                                        @if(in_array('manage '.$module,(array) $permissions))
                                            @if($key = array_search('manage '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Manage',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('create '.$module,(array) $permissions))
                                            @if($key = array_search('create '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Create',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('edit '.$module,(array) $permissions))
                                            @if($key = array_search('edit '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Edit',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('delete '.$module,(array) $permissions))
                                            @if($key = array_search('delete '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Delete',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('show '.$module,(array) $permissions))
                                            @if($key = array_search('show '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Show',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif

                                        @if(in_array('buy '.$module,(array) $permissions))
                                            @if($key = array_search('buy '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Buy',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('send '.$module,(array) $permissions))
                                            @if($key = array_search('send '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Send',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif

                                        @if(in_array('create payment '.$module,(array) $permissions))
                                            @if($key = array_search('create payment '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Create Payment',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('delete payment '.$module,(array) $permissions))
                                            @if($key = array_search('delete payment '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Delete Payment',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('income '.$module,(array) $permissions))
                                            @if($key = array_search('income '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Income',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('expense '.$module,(array) $permissions))
                                            @if($key = array_search('expense '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Expense',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('income vs expense '.$module,(array) $permissions))
                                            @if($key = array_search('income vs expense '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Income VS Expense',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('loss & profit '.$module,(array) $permissions))
                                            @if($key = array_search('loss & profit '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Loss & Profit',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('tax '.$module,(array) $permissions))
                                            @if($key = array_search('tax '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Tax',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('invoice '.$module,(array) $permissions))
                                            @if($key = array_search('invoice '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Invoice',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('bill '.$module,(array) $permissions))
                                            @if($key = array_search('bill '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Bill',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('duplicate '.$module,(array) $permissions))
                                            @if($key = array_search('duplicate '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Duplicate',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('balance sheet '.$module,(array) $permissions))
                                            @if($key = array_search('balance sheet '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Balance Sheet',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('ledger '.$module,(array) $permissions))
                                            @if($key = array_search('ledger '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Ledger',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('trial balance '.$module,(array) $permissions))
                                            @if($key = array_search('trial balance '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Trial Balance',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('contract'.$module,(array) $permissions))
                                            @if($key = array_search('contract'.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Contract',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('convert invoice '.$module,(array) $permissions))
                                        {{-- @dd($permissions) --}}
                                            @if($key = array_search('convert invoice '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Convert To Invoice',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        @if(in_array('convert retainer '.$module,(array) $permissions))
                                        {{-- @dd($permissions)'' --}}
                                            @if($key = array_search('convert retainer '.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Convert To Retainer',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif
                                        {{-- @dd($permissions) --}}

                                        {{-- @if(in_array('retainer'.$module,(array) $permissions))
                                            @if($key = array_search('retainer'.$module,$permissions))
                                                <div class="col-md-3 custom-control custom-checkbox">
                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck isscheck_'.str_replace(' ', '', $module),'id' =>'permission'.$key])}}
                                                    {{Form::label('permission'.$key,'Retainer',['class'=>'form-check-label'])}}<br>
                                                </div>
                                            @endif
                                        @endif --}}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Create')}}" class="btn  btn-primary">
</div>
{{Form::close()}}

<script>
    $(document).ready(function () {
        $("#checkall").click(function(){
            $('input:checkbox').not(this).prop('checked', this.checked);
        });
        $(".ischeck").click(function(){
            var ischeck = $(this).data('id');
            $('.isscheck_'+ ischeck).prop('checked', this.checked);
        });
    });
</script>

