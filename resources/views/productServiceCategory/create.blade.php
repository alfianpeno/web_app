@php
    $chatGPT = \App\Models\Utility::settings('enable_chatgpt');
    $enable_chatgpt = !empty($chatGPT);
@endphp
{{ Form::open(array('url' => 'product-category','class'=>'needs-validation','novalidate')) }}
<div class="modal-body">
    <div class="row">
        @if ($enable_chatgpt)
        <div>
            <a href="#" data-size="md" data-ajax-popup-over="true" data-url="{{ route('generate', ['category']) }}"
                data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Generate') }}"
                data-title="{{ __('Generate content with AI') }}" class="btn btn-primary btn-sm float-end">
                <i class="fas fa-robot"></i>
                {{__('Generate with AI')}}
            </a>
        </div>
        @endif
        <div class="form-group col-md-12">
            {{ Form::label('name', __('Category Name'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::text('name', '', ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-12 d-block">
            {{ Form::label('type', __('Category Type'),['class'=>'form-label']) }}<x-required></x-required>
            {{ Form::select('type',$types,null, array('class' => 'form-control select cattype ','required'=>'required')) }}
        </div>
        <div class="form-group col-md-12 account d-none">
            {{Form::label('chart_account_id',__('Account'),['class'=>'form-label'])}}
            <select class="form-control select" name="chart_account" id="chart_account"></select>
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('color', __('Category Color'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::color('color', '', ['class' => 'form-control jscolor', 'required' => 'required']) }}
            <small>{{ __('For chart representation') }}</small>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Create')}}" class="btn  btn-primary">
</div>
{{ Form::close() }}



<script>

    //hide & show chartofaccount

    $(document).on('click', '.cattype', function ()
    {
        var type = $(this).val();
        if (type != 'product & service') {
            $('.account').removeClass('d-none')
            $('.account').addClass('d-block');
        } else {
            $('.account').addClass('d-none')
            $('.account').removeClass('d-block');
        }
    });


    $(document).on('change', '#type', function () {
        var type = $(this).val();

        $.ajax({
            url: '{{route('productServiceCategory.getaccount')}}',
            type: 'POST',
            data: {
                "type": type,
                "_token": "{{ csrf_token() }}",
            },

            success: function (data) {
                $('#chart_account').empty();
                $.each(data.chart_accounts, function (key, value) {
                    $('#chart_account').append('<option value="' + key + '" class="subAccount">' + value + '</option>');
                    $.each(data.sub_accounts, function (subkey, subvalue) {
                        if(key == subvalue.account)
                        {
                            $('#chart_account').append('<option value="' + subvalue.id + '">' + '&nbsp; &nbsp;&nbsp;' + subvalue.name + '</option>');
                        }
                });
                });
            }

        });
    });
</script>

