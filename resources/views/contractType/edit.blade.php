{{ Form::model($contractType, array('route' => array('contractType.update', $contractType->id), 'method' => 'PUT','class'=>'needs-validation','novalidate')) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('name', __('Name'),['class' => 'col-form-label']) }}<x-required></x-required>
            {{ Form::text('name', null, array('class' => 'form-control','required'=>'required')) }}
        </div>
    </div>

</div>

<div class="modal-footer">
        <button type="button" class="btn  btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
        {{Form::submit(__('Update'),array('class'=>'btn  btn-primary'))}}
    </div>

{{ Form::close() }}
