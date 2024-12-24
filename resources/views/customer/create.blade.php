{{ Form::open(['url' => 'customer', 'method' => 'post','class'=>'needs-validation','novalidate']) }}
@csrf
<div class="modal-body">

    <h6 class="sub-title">{{ __('Basic Info') }}</h6>
    <div class="row">
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="form-group">
                {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}<x-required></x-required>
                <div class="form-icon-user">
                    {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) }}
                </div>
            </div>
        </div>

        <x-mobile  div-class="col-md-4 " name="contact" label="{{ __('Contact') }} " required></x-mobile>

        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="form-group">
                {{ Form::label('email', __('Email'), ['class' => 'form-label']) }}<x-required></x-required>
                <div class="form-icon-user">
                    {{ Form::text('email', null, ['class' => 'form-control', 'required' => 'required']) }}
                </div>
            </div>
        </div>
        {!! Form::hidden('role', 'company', null, ['class' => 'form-control select2', 'required' => 'required']) !!}
        <div class="col-lg-4 col-md-4 form-group mt-4">
                <label for="password_switch">{{ __('Login is enable') }}</label>
                <div class="form-check form-switch custom-switch-v1 float-end">
                    <input type="checkbox" name="password_switch" class="form-check-input input-primary pointer" value="on" id="password_switch">
                    <label class="form-check-label" for="password_switch"></label>
                </div>
            </div>
        <div class="col-lg-4 col-md-4 col-sm-6 ps_div d-none">
            <div class="form-group">
                {{ Form::label('password', __('Password'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::password('password', ['class' => 'form-control', 'required' => 'required', 'minlength' => '6']) }}
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="form-group">
                {{ Form::label('tax_number', __('Tax Number'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('tax_number', null, ['class' => 'form-control']) }}
                </div>
            </div>
        </div>
        @if (!$customFields->isEmpty())
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                    @include('customFields.formBuilder')
                </div>
            </div>
        @endif
    </div>

    <h6 class="sub-title">{{ __('Billing Address') }}</h6>
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('billing_name', __('Name'), ['class' => '', 'class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('billing_name', null, ['class' => 'form-control']) }}
                </div>
            </div>
        </div>

        <x-mobile  div-class="col-md-6 " name="billing_phone" label="{{ __('Phone') }}" ></x-mobile>

        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('billing_address', __('Address'), ['class' => 'form-label']) }}
                <div class="input-group">
                    {{ Form::textarea('billing_address', null, ['class' => 'form-control', 'rows' => 3]) }}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('billing_city', __('City'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('billing_city', null, ['class' => 'form-control']) }}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('billing_state', __('State'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('billing_state', null, ['class' => 'form-control']) }}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('billing_country', __('Country'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('billing_country', null, ['class' => 'form-control']) }}
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('billing_zip', __('Zip Code'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('billing_zip', null, ['class' => 'form-control']) }}
                </div>
            </div>
        </div>

    </div>

    <div class="col-md-12 text-end">
        <input type="button" id="billing_data" value="Shipping Same As Billing" class="btn btn-primary">
    </div>
    <h6 class="sub-title">{{ __('Shipping Address') }}</h6>
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('shipping_name', __('Name'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('shipping_name', null, ['class' => 'form-control']) }}
                </div>
            </div>
        </div>

        <x-mobile  div-class="col-md-6 " name="shipping_phone" label="{{ __('Phone') }}" ></x-mobile>

        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('shipping_address', __('Address'), ['class' => 'form-label']) }}
                <label class="form-label" for="example2cols1Input"></label>
                <div class="input-group">
                    {{ Form::textarea('shipping_address', null, ['class' => 'form-control', 'rows' => 3]) }}
                </div>
            </div>
        </div>


        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('shipping_city', __('City'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('shipping_city', null, ['class' => 'form-control']) }}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('shipping_state', __('State'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('shipping_state', null, ['class' => 'form-control']) }}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('shipping_country', __('Country'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('shipping_country', null, ['class' => 'form-control']) }}
                </div>
            </div>
        </div>


        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{ Form::label('shipping_zip', __('Zip Code'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('shipping_zip', null, ['class' => 'form-control']) }}
                </div>
            </div>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
@push('script-page')
    <script>
        $(document).on('change', '#password_switch', function() {
            if ($(this).is(':checked')) {
                $('.ps_div').removeClass('d-none');
                $('#password').attr("required", true);

            } else {
                $('.ps_div').addClass('d-none');
                $('#password').val(null);
                $('#password').removeAttr("required");
            }
        });
        $(document).on('click', '.login_enable', function() {
            setTimeout(function() {
                $('.modal-body').append($('<input>', {
                    type: 'hidden',
                    val: 'true',
                    name: 'login_enable'
                }));
            }, 2000);
        });
    </script>
@endpush
