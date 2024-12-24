{{ Form::open(array('route' => array('customer.retainer.send.mail',$retainer_id))) }}
<div class="modal-body">

    <div class="row">
    <div class="form-group col-md-12">
        {{ Form::label('email', __('Email')) }}
        {{ Form::text('email', '', array('class' => 'form-control','required'=>'required')) }}
        @error('email')
        <span class="invalid-email" role="alert">
            <strong class="text-danger">{{ $message }}</strong>
        </span>
        @enderror
    </div>
</div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Create')}}" class="btn btn-primary">
</div>
{{ Form::close() }}

