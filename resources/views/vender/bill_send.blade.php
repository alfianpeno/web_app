
<div class="card bg-none card-box">
    {{ Form::open(array('route' => array('vender.bill.send.mail', $bill_id),'class'=>'needs-validation','novalidate')) }}
    <div class="row mt-2 mx-2">
        <div class="form-group col-md-12">
            {{ Form::label('email', __('Email'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::email('email', '', ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter email')]) }}
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

    <div class="row mt-3 mx-2">
        <div class="col-md-12 text-end">
            <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
            <input type="submit" value="{{ __('Send') }}" class="btn btn-primary">
        </div>
    </div>
    {{ Form::close() }}
</div>
