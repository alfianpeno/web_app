{{ Form::open(array('url' => 'revenue','enctype' => 'multipart/form-data','class'=>'needs-validation','novalidate')) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('date', __('Date'),['class'=>'form-label']) }}<x-required></x-required>
            <div class="form-icon-user">
                {{Form::date('date',date('Y-m-d'),array('class'=>'form-control','required'=>'required'))}}
            </div>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('amount', __('Amount'),['class'=>'form-label']) }}<x-required></x-required>
            <div class="form-icon-user">
                {{ Form::number('amount', '', array('class' => 'form-control','required'=>'required','step'=>'0.01')) }}
            </div>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('account_id', __('Account'),['class'=>'form-label']) }}<x-required></x-required>
            {{ Form::select('account_id',$accounts,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('customer_id', __('Customer'),['class'=>'form-label']) }}<x-required></x-required>
            {{ Form::select('customer_id', $customers,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
        <div class="form-group  col-md-12">
            {{ Form::label('description', __('Description'),['class'=>'form-label']) }}
            {{ Form::textarea('description', '', array('class' => 'form-control','rows'=>3)) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('category_id', __('Category'),['class'=>'form-label']) }}<x-required></x-required>
            {{ Form::select('category_id', $categories,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('reference', __('Reference'),['class'=>'form-label']) }}
            <div class="form-icon-user">
                {{ Form::text('reference', '', array('class' => 'form-control')) }}
            </div>
        </div>

        <div class="col-md-6">
            {{ Form::label('add_receipt', __('Payment Receipt'), ['class' => 'form-label']) }}
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" name="add_receipt" id="files" class="form-control file-validate" data-filename="upload_file">
                    <span id="" class="file-error text-danger"></span>
                </label>
                <p class="upload_file"></p>
                <img id="image" class="mt-2 border border-primary" src="{{asset(Storage::url('uploads/defualt/defualt.png'))}}" width="120px" height="120px"/>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Create')}}" class="btn  btn-primary">
</div>
{{ Form::close() }}

<script>
    document.getElementById('files').onchange = function () {
    var src = URL.createObjectURL(this.files[0])
    document.getElementById('image').src = src
    }
</script>
