
{{Form::model($user,array('route' => array('user.password.update', $user->id), 'method' => 'post')) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('password', __('New Password'),['class'=>'form-label']) }}

            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="{{ __('Enter New Password') }}">
            @error('password')
            <span class="invalid-feedback" role="alert">
                   <strong>{{ $message }}</strong>
               </span>
            @enderror
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('password_confirmation', __('Confirm New Password'),['class'=>'form-label']) }}
            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="{{ __('Confirm New Password') }}">
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Update')}}" class="btn btn-primary">
</div>

{{ Form::close() }}
