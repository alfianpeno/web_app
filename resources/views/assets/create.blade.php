@php
    $chatGPT = \App\Models\Utility::settings('enable_chatgpt');
    $enable_chatgpt = !empty($chatGPT);
@endphp
{{ Form::open(['url' => 'account-assets','class'=>'needs-validation','novalidate']) }}
<div class="modal-body">
    <div class="row">
        @if ($enable_chatgpt)
            <div>
                <a href="#" data-size="md" data-ajax-popup-over="true" data-url="{{ route('generate', ['assets']) }}"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Generate') }}"
                    data-title="{{ __('Generate content with AI') }}" class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-robot"></i>
                    {{ __('Generate with AI') }}
                </a>
            </div>
        @endif
        <div class="form-group col-md-6">
            {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::text('name', '', ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('amount', __('Amount'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::number('amount', '', ['class' => 'form-control', 'required' => 'required', 'step' => '0.01']) }}
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('purchase_date', __('Purchase Date'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::date('purchase_date', date('Y-m-d'), ['class' => 'form-control pc-datepicker-1', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('supported_date', __('Supported Date'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::date('supported_date', date('Y-m-d'), ['class' => 'form-control pc-datepicker-1', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {{ Form::textarea('description', '', ['class' => 'form-control', 'rows' => 3]) }}
        </div>

    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}
