{{Form::model($customer,array('route' => array('customer.password.update', $customer->id), 'method' => 'post','class'=>'needs-validation','novalidate')) }}
<div class="modal-body">

<div class="row">
    <div class="form-group col-md-6">
        {{Form::label('password',__('Password'),array('class'=>'form-label')) }}<x-required></x-required>

        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
        @error('password')
        <span class="invalid-feedback" role="alert">
               <strong>{{ $message }}</strong>
           </span>
        @enderror
    </div>
    <div class="form-group col-md-6">
        {{Form::label('password_confirmation',__('Confirm Password'),array('class'=>'form-label')) }}<x-required></x-required>
        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
    </div>
</div>

</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Update')}}" class="btn btn-primary">
</div>

{{ Form::close() }}
