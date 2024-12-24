
{{ Form::model($webhook, array('route' => array('webhook.update', $webhook->id), 'method' => 'PUT')) }}
<div class="modal-body">
<div class="row">
    <div class="col-md-12">
    <div class="form-group">
        {{Form::label('module',__('Module'),['class'=>'col-form-label']) }}
        {{ Form::select('module', $module, null, ['class' => 'form-control', 'required' => 'required']) }}
    </div>
    </div>
    <div class="col-md-12">
    <div class="form-group">
        {{Form::label('method',__('Method'),['class'=>'col-form-label']) }}
        {{ Form::select('method', $method, null, ['class' => 'form-control', 'required' => 'required']) }}
    </div>
    </div>
    <div class="col-md-12">
    <div class="form-group">
        {{Form::label('url',__('URL'),['class'=>'form-label']) }}
        {{Form::text('url',null,array('class'=>'form-control','placeholder'=>__('Enter Url'),'required'=>'required'))}}
    </div>
    </div>

</div>
</div>

<div class="modal-footer">
<button type="button" class="btn  btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
<input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
</div>

{{ Form::close() }}