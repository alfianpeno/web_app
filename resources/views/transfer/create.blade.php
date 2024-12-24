@php
    $chatGPT = \App\Models\Utility::settings('enable_chatgpt');
    $enable_chatgpt = !empty($chatGPT);
@endphp
{{ Form::open(array('url' => 'transfer','class'=>'needs-validation','novalidate')) }}
<div class="modal-body">
    <div class="row">
        @if ($enable_chatgpt)
        <div>
            <a href="#" data-size="md" data-ajax-popup-over="true" data-url="{{ route('generate', ['transfer']) }}"
                data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Generate') }}"
                data-title="{{ __('Generate content with AI') }}" class="btn btn-primary btn-sm float-end">
                <i class="fas fa-robot"></i>
                {{__('Generate with AI')}}
            </a>
        </div>
        @endif
        <div class="form-group  col-md-6">
            {{ Form::label('from_account', __('From Account'),['class'=>'form-label']) }}<x-required></x-required>
            {{ Form::select('from_account', $bankAccount,null, array('class' => 'form-control','required'=>'required')) }}
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('to_account', __('To Account'),['class'=>'form-label']) }}<x-required></x-required>
            {{ Form::select('to_account', $bankAccount,null, array('class' => 'form-control','required'=>'required')) }}
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('amount', __('Amount'),['class'=>'form-label']) }}<x-required></x-required>
            <div class="form-icon-user">
                {{ Form::number('amount', '', array('class' => 'form-control','required'=>'required','step'=>'0.01')) }}
            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('date', __('Date'),['class'=>'form-label']) }}<x-required></x-required>
            <div class="form-icon-user">
                {{Form::date('date',date('Y-m-d'),array('class'=>'form-control','required'=>'required'))}}

            </div>
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('reference', __('Reference'),['class'=>'form-label']) }}
            <div class="form-icon-user">
                {{ Form::text('reference', '', array('class' => 'form-control')) }}
            </div>
        </div>
        <div class="form-group  col-md-12">
            {{ Form::label('description', __('Description'),['class'=>'form-label']) }}
            {{ Form::textarea('description', '', array('class' => 'form-control','rows'=>3)) }}
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Create')}}" class="btn  btn-primary">
</div>
{{ Form::close() }}
