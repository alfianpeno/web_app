@php
    $chatGPT = \App\Models\Utility::settings('enable_chatgpt');
    $enable_chatgpt = !empty($chatGPT);
@endphp
{{ Form::open(['url' => 'custom-field','class'=>'needs-validation','novalidate']) }}
<div class="modal-body">
    <div class="row">
        @if ($enable_chatgpt)
            <div>
                <a href="#" data-size="md" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['custom field']) }}" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('Generate') }}" data-title="{{ __('Generate content with AI') }}"
                    class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-robot"></i>
                    {{ __('Generate with AI') }}
                </a>
            </div>
        @endif
        <div class="form-group col-md-12">
            {{ Form::label('name', __('Custom Field Name'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('type', __('Type'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::select('type', $types, null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('module', __('Module'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::select('module', $modules, null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>

    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}
