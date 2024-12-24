@php
    $chatGPT = \App\Models\Utility::settings('enable_chatgpt');
    $enable_chatgpt = !empty($chatGPT);
@endphp
{{ Form::model($productService, ['route' => ['productservice.update', $productService->id], 'method' => 'PUT','class'=>'needs-validation','novalidate']) }}
<div class="modal-body">
    <div class="row">
        @if ($enable_chatgpt)
        <div>
            <a href="#" data-size="md" data-ajax-popup-over="true"
                data-url="{{ route('generate', ['product & service']) }}" data-bs-toggle="tooltip" data-bs-placement="top"
                title="{{ __('Generate') }}" data-title="{{ __('Generate content with AI') }}"
                class="btn btn-primary btn-sm float-end">
                <i class="fas fa-robot"></i>
                {{ __('Generate with AI') }}
            </a>
        </div>
        @endif
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}<x-required></x-required>
                <div class="form-icon-user">
                    {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) }}
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('sku', __('SKU'), ['class' => 'form-label']) }}<x-required></x-required>
                <div class="form-icon-user">
                    {{ Form::text('sku', null, ['class' => 'form-control', 'required' => 'required']) }}
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('sale_price', __('Sale Price'), ['class' => 'form-label']) }}<x-required></x-required>
                <div class="form-icon-user">
                    {{ Form::number('sale_price', null, ['class' => 'form-control', 'required' => 'required', 'step' => '0.01']) }}
                </div>
            </div>
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('sale_chartaccount_id', __('Income Account'),['class'=>'form-label']) }}<x-required></x-required>
            <select name="sale_chartaccount_id" class="form-control" required="required">
                @foreach ($incomeChartAccounts as $key => $chartAccount)
                    <option value="{{ $key }}" class="subAccount" {{ ($productService->sale_chartaccount_id == $key) ? 'selected' : ''}}>{{ $chartAccount }}</option>
                    @foreach ($incomeSubAccounts as $subAccount)
                        @if ($key == $subAccount['account'])
                            <option value="{{ $subAccount['id'] }}" class="ms-5" {{ ($productService->sale_chartaccount_id == $subAccount['id']) ? 'selected' : ''}}> &nbsp; &nbsp;&nbsp; {{ $subAccount['name'] }}</option>
                        @endif
                    @endforeach
                @endforeach
            </select>
        </div>


        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('purchase_price', __('Purchase Price'), ['class' => 'form-label']) }}<x-required></x-required>
                <div class="form-icon-user">
                    {{ Form::number('purchase_price', null, ['class' => 'form-control', 'required' => 'required', 'step' => '0.01']) }}
                </div>
            </div>
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('expense_chartaccount_id', __('Expense Account'),['class'=>'form-label']) }}<x-required></x-required>
            <select name="expense_chartaccount_id" class="form-control" required="required">
                @foreach ($expenseChartAccounts as $key => $chartAccount)
                    <option value="{{ $key }}" class="subAccount" {{ ($productService->expense_chartaccount_id == $key) ? 'selected' : ''}}>{{ $chartAccount }}</option>
                    @foreach ($expenseSubAccounts as $subAccount)
                        @if ($key == $subAccount['account'])
                            <option value="{{ $subAccount['id'] }}" class="ms-5" {{ ($productService->expense_chartaccount_id == $subAccount['id']) ? 'selected' : ''}}> &nbsp; &nbsp;&nbsp; {{ $subAccount['name'] }}</option>
                        @endif
                    @endforeach
                @endforeach
            </select>
        </div>

        <div class="form-group  col-md-6">
            {{ Form::label('tax_id', __('Tax'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::select('tax_id[]', $tax, null, ['class' => 'form-control select2', 'id' => 'choices-multiple1', 'multiple' => '','required' => 'required']) }}
        </div>

        <div class="form-group  col-md-6">
            {{ Form::label('category_id', __('Category'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::select('category_id', $category, null, ['class' => 'form-control select', 'required' => 'required']) }}
        </div>
        <div class="form-group  col-md-6">
            {{ Form::label('unit_id', __('Unit'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::select('unit_id', $unit, null, ['class' => 'form-control select', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('quantity', __('Quantity'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::text('quantity', null, ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="d-block form-label">{{ __('Type') }}</label>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check form-check-inline">
                            <input type="radio" class="form-check-input" id="customRadio5" name="type"
                                value="Product" @if ($productService->type == 'Product') checked @endif
                                onclick="hide_show(this)">
                            <label class="custom-control-label form-label"
                                for="customRadio5">{{ __('Product') }}</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-check-inline">
                            <input type="radio" class="form-check-input" id="customRadio6" name="type"
                                value="Service" @if ($productService->type == 'Service') checked @endif
                                onclick="hide_show(this)">
                            <label class="custom-control-label form-label"
                                for="customRadio6">{{ __('Service') }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group  col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => '2']) !!}
        </div>
        @if (!$customFields->isEmpty())
            <div class="col-md-6">
                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                    @include('customFields.formBuilder')
                </div>
            </div>
        @endif
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}
