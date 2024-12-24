@php
    use App\Models\Utility;
    $setting = Utility::settingsById($retainer->created_by);
@endphp
@extends('layouts.invoicepayheader')
@section('page-title')
    {{ __('Retainer Detail') }}
@endsection
@push('css-page')
    <style>
        #card-element {
            border: 1px solid #a3afbb !important;
            border-radius: 10px !important;
            padding: 10px !important;
        }
    </style>
@endpush

@push('script-page')
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script type="text/javascript">
        @if (
            $retainer->getDue() > 0 &&
                !empty($company_payment_setting) &&
                $company_payment_setting['is_stripe_enabled'] == 'on' &&
                !empty($company_payment_setting['stripe_key']) &&
                !empty($company_payment_setting['stripe_secret']))

            var stripe = Stripe('{{ $company_payment_setting['stripe_key'] }}');
            var elements = stripe.elements();

            // Custom styling can be passed to options when creating an Element.
            var style = {
                base: {
                    // Add your base input styles here. For example:
                    fontSize: '14px',
                    color: '#32325d',
                },
            };

            // Create an instance of the card Element.
            var card = elements.create('card', {
                style: style
            });

            // Add an instance of the card Element into the `card-element` <div>.
            card.mount('#card-element');

            // Create a token or display an error when the form is submitted.
            var form = document.getElementById('payment-form');
            form.addEventListener('submit', function(event) {
                event.preventDefault();

                stripe.createToken(card).then(function(result) {
                    if (result.error) {
                        $("#card-errors").html(result.error.message);
                        show_toastr('error', result.error.message, 'error');
                    } else {
                        // Send the token to your server.
                        stripeTokenHandler(result.token);
                    }
                });
            });

            function stripeTokenHandler(token) {
                // Insert the token ID into the form so it gets submitted to the server
                var form = document.getElementById('payment-form');
                var hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'stripeToken');
                hiddenInput.setAttribute('value', token.id);
                form.appendChild(hiddenInput);

                // Submit the form
                form.submit();
            }
        @endif

        @if (isset($company_payment_setting['is_paystack_enabled']) && $company_payment_setting['is_paystack_enabled'] == 'on')
            $(document).on("click", "#pay_with_paystack", function() {

                $('#paystack-payment-form').ajaxForm(function(res) {
                    var amount = res.total_price;
                    if (res.flag == 1) {
                        // var paystack_callback = "{{ url('customer/invoice/paystack') }}";

                        var handler = PaystackPop.setup({
                            key: '{{ $company_payment_setting['paystack_public_key'] }}',
                            email: res.email,
                            amount: res.total_price * 100,
                            currency: res.currency,
                            ref: 'pay_ref_id' + Math.floor((Math.random() * 1000000000) +
                                1
                            ),
                            metadata: {
                                custom_fields: [{
                                    display_name: "Email",
                                    variable_name: "email",
                                    value: res.email,
                                }]
                            },

                            callback: function(response) {
                                window.location.href =
                                    '{{ url('customer/retainer/paystack') }}' + '/' +
                                    '{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}' +
                                    '/' + amount + '/' + response.reference;

                                // window.location.href = paystack_callback + '/' + response.reference + '/' + '{{ encrypt($retainer->id) }}' +
                                // '?amount=' + amount;
                            },
                            onClose: function() {
                                alert('window closed');
                            }
                        });
                        handler.openIframe();
                    } else if (res.flag == 2) {
                        toastrs('Error', res.msg, 'msg');
                    } else {
                        toastrs('Error', res.message, 'msg');
                    }

                }).submit();
            });
        @endif

        @if (isset($company_payment_setting['is_flutterwave_enabled']) &&
                $company_payment_setting['is_flutterwave_enabled'] == 'on')
            // Flaterwave Payment
            $(document).on("click", "#pay_with_flaterwave", function() {
                $('#flaterwave-payment-form').ajaxForm(function(res) {
                    if (res.flag == 1) {
                        var amount = res.total_price;
                        var API_publicKey = '{{ $company_payment_setting['flutterwave_public_key'] }}';
                        var nowTim = "{{ date('d-m-Y-h-i-a') }}";
                        // var flutter_callback = "{{ url('/customer/invoice/flaterwave') }}";
                        var x = getpaidSetup({
                            PBFPubKey: API_publicKey,
                            customer_email: '{{ $retainer->customer->email }}',
                            amount: res.total_price,
                            currency: '{{ App\Models\Utility::getValByName('site_currency') }}',
                            txref: nowTim + '__' + Math.floor((Math.random() * 1000000000)) +
                                'fluttpay_online-' + '{{ date('Y-m-d') }}' + '?amount=' + amount,
                            meta: [{
                                metaname: "payment_id",
                                metavalue: "id"
                            }],
                            onclose: function() {},
                            callback: function(response) {

                                var txref = response.tx.txRef;
                                if (
                                    response.tx.chargeResponseCode == "00" ||
                                    response.tx.chargeResponseCode == "0"
                                ) {
                                    window.location.href =
                                        '{{ url('customer/retainer/flaterwave') }}' + '/' +
                                        '{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}' +
                                        '/' + txref;

                                    // window.location.href = flutter_callback + '/' + txref + '/' +
                                    // '{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}'+ '?amount=' + amount;
                                } else {
                                    // redirect to a failure page.
                                }
                                x
                                    .close(); // use this to close the modal immediately after payment.
                            }
                        });
                    } else if (res.flag == 2) {
                        toastrs('Error', res.msg, 'msg');
                    } else {
                        toastrs('Error', data.message, 'msg');
                    }

                }).submit();
            });
        @endif

        @if (isset($company_payment_setting['is_razorpay_enabled']) && $company_payment_setting['is_razorpay_enabled'] == 'on')
            // Razorpay Payment
            $(document).on("click", "#pay_with_razorpay", function() {
                $('#razorpay-payment-form').ajaxForm(function(res) {
                    if (res.flag == 1) {
                        var amount = res.total_price;
                        // var razorPay_callback = '{{ url('/customer/invoice/razorpay') }}';
                        var totalAmount = res.total_price * 100;
                        var coupon_id = res.coupon;
                        var options = {
                            "key": "{{ $company_payment_setting['razorpay_public_key'] }}", // your Razorpay Key Id
                            "amount": totalAmount,
                            "name": 'Plan',
                            "currency": '{{ App\Models\Utility::getValByName('site_currency') }}',
                            "description": "",
                            "handler": function(response) {
                                window.location.href = '{{ url('customer/retainer/razorpay') }}' +
                                    '/' +
                                    '{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}' +
                                    '/' + amount;

                                // window.location.href = razorPay_callback + '/' + response.razorpay_payment_id + '/' +
                                // '{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}' + '?amount=' + amount;
                            },
                            "theme": {
                                "color": "#528FF0"
                            }
                        };
                        var rzp1 = new Razorpay(options);
                        rzp1.open();
                    } else if (res.flag == 2) {
                        toastrs('Error', res.msg, 'msg');
                    } else {
                        toastrs('Error', data.message, 'msg');
                    }

                }).submit();
            });
        @endif

        @if (isset($company_payment_setting['is_payfast_enabled']) &&
                $company_payment_setting['is_payfast_enabled'] == 'on' &&
                !empty($company_payment_setting['payfast_merchant_id']) &&
                !empty($company_payment_setting['payfast_merchant_key']))
            function get_payfast_status() {
                var retainer_id = $('#retainer_id').val();
                var amount = $('#pay_fast_amount').val();

                $.ajax({
                    url: '{{ route('retainer.with.payfast') }}',
                    method: 'POST',
                    data: {
                        'retainer_id': retainer_id,
                        'amount': amount,
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        if (data.success == true) {
                            $('#get-payfast-inputs').append(data.inputs);
                        } else {
                            show_toastr('Error', data.inputs, 'error')
                        }
                    }
                });
            }
        @endif


        @if (isset($company_payment_setting['is_payfast_enabled']) && $company_payment_setting['is_payfast_enabled'] == 'on')



            function get_payfast_status() {

                var retainer_id = $('#retainer_id').val();
                var amount = $('#pay_fast_amount').val();

                $.ajax({
                    url: '{{ route('retainer.with.payfast') }}',
                    method: 'POST',
                    data: {
                        'retainer_id': retainer_id,
                        'amount': amount,
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        console.log(data);

                        if (data.success == true) {
                            $('#get-payfast-inputs').append(data.inputs);

                        } else {
                            show_toastr('Error', data.inputs, 'error')
                        }
                    }
                });
            }
        @endif



        $('.cp_link').on('click', function() {
            var value = $(this).attr('data-link');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(value).select();
            document.execCommand("copy");
            $temp.remove();
            toastrs('Success', '{{ __('Link Copy on Clipboard') }}', 'success')
        });
    </script>
    <script>
        $(document).on('click', '#shipping', function() {
            var url = $(this).data('url');
            var is_display = $("#shipping").is(":checked");
            $.ajax({
                url: url,
                type: 'get',
                data: {
                    'is_display': is_display,
                },
                success: function(data) {
                    // console.log(data);
                }
            });
        })
    </script>


<!-- Khalti Payment -->
@if (isset($company_payment_setting['is_khalti_enabled']) && $company_payment_setting['is_khalti_enabled'] == 'on')
    <script src="https://khalti.s3.ap-south-1.amazonaws.com/KPG/dist/2020.12.17.0.0.0/khalti-checkout.iffe.js"></script>

    <script>
        var config = {
            "publicKey"         : "{{ isset($company_payment_setting['khalti_public_key']) ? $company_payment_setting['khalti_public_key'] : '' }}",
            "productIdentity"   : "1234567890",
            "productName"       : "demo",
            "productUrl"        : "{{ env('APP_URL') }}",
            "paymentPreference" : [
                "KHALTI",
                "EBANKING",
                "MOBILE_BANKING",
                "CONNECT_IPS",
                "SCT",
            ],
            "eventHandler": {
                onSuccess(payload) {
                    if (payload.status == 200) {
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-Token': '{{ csrf_token() }}'
                            }
                        });
                        $.ajax({
                            url: "{{ route('retainer.get.khalti.status') }}",
                            method: 'POST',
                            data: {
                                'payload': payload,
                                'retainer_id': $('.khalti_retainer_id').val(),
                                'amount': $('.khalti_amount').val(),
                            },
                            beforeSend: function() {
                                $(".loader-wrapper").removeClass('d-none');
                            },
                            success: function(data) {
                                $(".loader-wrapper").addClass('d-none');
                                if (data.status_code === 200) {
                                    show_toastr('Success', 'Payment Done Successfully', 'success');
                                    setTimeout(() => {
                                        window.location.href = "{{ route('pay.retainerpay', \Illuminate\Support\Facades\Crypt::encrypt($retainer->id)) }}";
                                    }, 1000);
                                } else {
                                    show_toastr('Error', 'Payment Failed', 'msg');
                                }
                            },
                            error: function(err) {
                                show_toastr('Error', err.response, 'msg');
                            },
                        });
                    }
                },
                onError(error) {
                    show_toastr('Error', error, 'msg')
                },
                onClose() {}
            }

        };

        var checkout    = new KhaltiCheckout(config);
        var btn         = document.getElementsByClassName("payment-btn")[0];

    </script>
    <script>
        $(document).on("click", "#pay_with_khalti", function(event) {
            event.preventDefault()
            var retainer_id = $('.khalti_retainer_id').val();
            var amount     = $('.khalti_amount').val();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url     : "{{ route('retainer.with.khalti') }}",
                method  : 'POST',
                data    : {
                    'retainer_id': retainer_id,
                    'amount': amount,
                },

                beforeSend: function() {
                    $(".loader-wrapper").removeClass('d-none');
                },
                success : function(data) {
                    $(".loader-wrapper").addClass('d-none');
                    if (data == 0) {
                        show_toastr('Error', 'Enter valid amount', 'danger');
                        setTimeout(() => {
                            window.location.href = '{{ route("pay.retainerpay", \Illuminate\Support\Facades\Crypt::encrypt($retainer->id)) }}';
                        }, 1000);
                    } else {
                        let price = data * 100;
                        checkout.show({
                            amount: price
                        });
                    }
                }
            });
        })


    </script>
@endif
<!-- Khalti Payment End -->

@endpush

@section('content')
    @php
        $customer = $retainer->customer;
    @endphp

    @can('send invoice')
        @if ($retainer->status != 4)
            {{-- <div class="row">
                <div class="col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="timeline timeline-one-side" data-timeline-content="axis" data-timeline-axis-style="dashed">
                                <div class="timeline-block">
                                    <span class="timeline-step timeline-step-sm bg-primary border-primary text-white"><i class="ti ti-plus"></i></span>
                                    <div class="timeline-content">
                                        <div class="text-sm h6">{{__('Create Invoice')}}</div>
                                        @can('edit invoice')
                                            <div class="Action">
                                                <a href="{{ route('retainer.edit',\Crypt::encrypt($retainer->id)) }}" class="mx-3 btn btn-sm align-items-center float-right" data-bs-toggle="tooltip" data-original-title="{{__('Edit')}}"><i class="ti ti-pencil text-white"></i></a>
                                            </div>
                                        @endcan
                                        <small><i class="ti ti-clock mr-1"></i>{{__('Created on ')}} {{\Auth::user()->dateFormat($retainer->issue_date)}}</small>
                                    </div>
                                </div>
                                <div class="timeline-block">
                                    <span class="timeline-step timeline-step-sm bg-warning border-warning text-white"><i class="ti ti-envelope"></i></span>
                                    <div class="timeline-content">
                                        <div class="text-sm h6 ">{{__('Send Invoice')}}</div>
                                        @if ($retainer->status == 0)
                                            <div class="Action">
                                                @can('send invoice')
                                                    <a href="{{ route('retainer.sent',$retainer->id) }}" class="send-icon float-right" data-bs-toggle="tooltip" data-original-title="{{__('Mark Sent')}}"><i class="fa fa-paper-plane"></i></a>
                                                @endcan
                                            </div>
                                        @endif

                                        @if ($retainer->status != 0)
                                            <small><i class="ti ti-clock mr-1"></i>{{__('Sent on')}} {{\Auth::user()->dateFormat($retainer->send_date)}}</small>
                                        @else
                                            @can('send invoice')
                                                <small>{{__('Status')}} : {{__('Not Sent')}}</small>
                                            @endcan
                                        @endif
                                    </div>
                                </div>
                                <div class="timeline-block">
                                    <span class="timeline-step timeline-step-sm bg-info border-info text-white"><i class="far fa-money-bill-alt"></i></span>
                                    <div class="timeline-content">
                                        <div class="text-sm h6 ">{{__('Get Paid')}}</div>
                                        @if ($retainer->status != 0)
                                            @can('create payment invoice')
                                                <a href="#" data-url="{{ route('retainer.payment',$retainer->id) }}" data-ajax-popup="true" data-title="{{__('Add Receipt')}}" class="mx-3 btn btn-sm align-items-center float-right" data-bs-toggle="tooltip" data-original-title="{{__('Add Receipt')}}"><i class="far fa-file"></i></a>
                                            @endcan
                                        @endif
                                        <small>{{__('Awaiting payment')}}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}
        @endif
    @endcan

    @if ($retainer->status != 0)
        <div class="row justify-content-center align-items-center mb-3">
            <div class="col-10 d-flex align-items-center justify-content-between justify-content-md-end">
                @if (!empty($retainerPayment))
                    <div class="all-button-box mx-2">
                        <a href="#" class="btn btn-xs btn-white btn-icon-only width-auto"
                            data-url="{{ route('invoice.credit.note', $retainer->id) }}" data-ajax-popup="true"
                            data-title="{{ __('Add Credit Note') }}">
                            {{ __('Add Credit Note') }}
                        </a>
                    </div>
                @endif
                {{-- @if ($invoice->status != 4)
                        <div class="all-button-box mx-2">
                            <a href="{{ route('invoice.payment.reminder',$invoice->id)}}" class="btn btn-xs btn-white btn-icon-only width-auto">{{__('Receipt Reminder')}}</a>
                        </div>
                    @endif
                    <div class="all-button-box mx-2">
                        <a href="{{ route('invoice.resent',$invoice->id)}}" class="btn btn-xs btn-white btn-icon-only width-auto">{{__('Resend Invoice')}}</a>
                </div> --}}
                <div class="all-button-box mr-3">
                    <a href="{{ route('retainer.pdf', Crypt::encrypt($retainer->id)) }}" target="_blank"
                        class="btn btn-primary">{{ __('Download') }}</a>
                    @if ($retainer->getDue() > 0)
                        {{-- @if (\Auth::guard('customer')->check()) --}}
                        {{-- <a href="#" data-toggle="modal" data-target="#paymentModal"
                            class="btn btn-primary">
                            <span class="btn-inner--icon text-white"><i class="fa fa-credit-card"></i></span>
                            <span class="btn-inner--text text-white">{{ __(' Pay Now') }}</span>
                        </a> --}}

                        <a href="#" class="btn btn-xs btn-primary btn-icon-only width-auto"
                            title="{{ __('Pay Now') }}" data-bs-toggle="modal"
                            data-bs-target="#paymentModal">{{ __('Pay Now') }}</a>
                        {{-- <a href="#" class="btn btn-xs btn-primary btn-icon-only width-auto" title="{{__('Pay Now')}}" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="fas fa-plus"></i> {{__('Pay Now')}}</a> --}}
                        {{-- @endif --}}
                    @endif
                </div>
            </div>
        </div>
    @endif


    <div class="row justify-content-center">
        <div class="col-10">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h4>{{ __('Retainer') }}</h4>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h4 class="invoice-number">
                                        {{ Utility::retainerNumberFormat($company_setting, $retainer->retainer_id) }}</h4>
                                </div>
                                <div class="col-12">
                                    <hr>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <div class="me-4">
                                            <small>
                                                <strong>{{ __('Issue Date') }} :</strong><br>
                                                @if (\Auth::check())
                                                    {{ \Auth::user()->dateFormat($retainer->issue_date) }}<br><br>
                                                @else
                                                    {{ \App\Models\User::dateFormat($retainer->issue_date) }}<br><br>
                                                @endif
                                            </small>
                                        </div>
                                        <div>
                                            <small>
                                                <strong>{{ __('Due Date') }} :</strong><br>

                                                @if (\Auth::check())
                                                    {{ \Auth::user()->dateFormat($retainer->due_date) }}<br><br>
                                                @else
                                                    {{ \App\Models\User::dateFormat($retainer->due_date) }}<br><br>
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                @if (!empty($customer->billing_name))
                                    <div class="col">
                                        <small class="font-style">
                                            <strong>{{ __('Billed To') }} :</strong><br>
                                            {{ !empty($customer->billing_name) ? $customer->billing_name : '' }}<br>
                                            {{ !empty($customer->billing_address) ? $customer->billing_address : '' }}<br>
                                            {{ !empty($customer->billing_city) ? $customer->billing_city : '' . ', ' }},
                                            {{ !empty($customer->billing_state) ? $customer->billing_state : '', ', ' }}
                                            {{ !empty($customer->billing_zip) ? $customer->billing_zip : '' }}<br>
                                            {{ !empty($customer->billing_country) ? $customer->billing_country : '' }}<br>
                                            {{ !empty($customer->billing_phone) ? $customer->billing_phone : '' }}<br>
                                            @if(!empty($setting['tax_type']) && !empty($setting['vat_number'])){{$setting['tax_type'].' '. __('Number')}} : {{$setting['vat_number']}} <br>@endif
                                        </small>
                                    </div>
                                @endif
                                @if (App\Models\Utility::getValByName('shipping_display') == 'on')
                                    <div class="col">
                                        <small>
                                            <strong>{{ __('Shipped To') }} :</strong><br>
                                            {{ !empty($customer->shipping_name) ? $customer->shipping_name : '' }}<br>
                                            {{ !empty($customer->shipping_address) ? $customer->shipping_address : '' }}<br>
                                            {{ !empty($customer->shipping_city) ? $customer->shipping_city : '' . ', ' }},
                                            {{ !empty($customer->shipping_state) ? $customer->shipping_state : '' . ', ' }}
                                            {{ !empty($customer->shipping_zip) ? $customer->shipping_zip : '' }}<br>
                                            {{ !empty($customer->shipping_country) ? $customer->shipping_country : '' }}<br>
                                            {{ !empty($customer->shipping_phone) ? $customer->shipping_phone : '' }}<br>
                                        </small>
                                    </div>
                                @endif

                                <div class="col">
                                    <div class="float-end mt-3">
                                        @if (isset($setting['retainer_qr_display']) && $setting['retainer_qr_display'] == 'on')
                                        {!! DNS2D::getBarcodeHTML(
                                            route('pay.retainerpay', \Illuminate\Support\Facades\Crypt::encrypt($retainer->id)),
                                            'QRCODE',
                                            2,
                                            2,
                                        ) !!}
                                        @endif
                                    </div>
                                </div>

                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Status') }} :</strong><br>
                                        @if ($retainer->status == 0)
                                            <span
                                                class="badge bg-primary p-2 px-3">{{ __(\App\Models\Retainer::$statues[$retainer->status]) }}</span>
                                        @elseif($retainer->status == 1)
                                            <span
                                                class="badge bg-info p-2 px-3">{{ __(\App\Models\Retainer::$statues[$retainer->status]) }}</span>
                                        @elseif($retainer->status == 2)
                                            <span
                                                class="badge bg-secondary p-2 px-3">{{ __(\App\Models\Retainer::$statues[$retainer->status]) }}</span>
                                        @elseif($retainer->status == 3)
                                            <span
                                                class="badge bg-warning p-2 px-3">{{ __(\App\Models\Retainer::$statues[$retainer->status]) }}</span>
                                        @elseif($retainer->status == 4)
                                            <span
                                                class="badge bg-danger p-2 px-3">{{ __(\App\Models\Retainer::$statues[$retainer->status]) }}</span>
                                        @endif
                                    </small>
                                </div>



                                @if (!empty($customFields) && count($retainer->customField) > 0)
                                    @foreach ($customFields as $field)
                                        <div class="col text-end">
                                            <small>
                                                <strong>{{ $field->name }} :</strong><br>
                                                {{ !empty($retainer->customField) ? $retainer->customField[$field->id] : '-' }}
                                                <br><br>
                                            </small>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-weight-bold">{{ __('Product Summary') }}</div>
                                    <small>{{ __('All items here cannot be deleted.') }}</small>
                                    <div class="table-responsive mt-2">
                                        <table class="table mb-0 ">
                                            <tr>
                                                <th data-width="40" class="text-dark">#</th>
                                                <th class="text-dark">{{ __('Product') }}</th>
                                                <th class="text-dark">{{ __('Quantity') }}</th>
                                                <th class="text-dark">{{ __('Rate') }}</th>
                                                <th class="text-dark">{{ __('Discount') }}</th>
                                                <th class="text-dark">{{ __('Tax') }}</th>
                                                <th class="text-dark">{{ __('Description') }}</th>
                                                <th class="text-end text-dark" width="12%">{{ __('Price') }}<br>
                                                    <small
                                                        class="text-danger font-weight-bold">{{ __('before tax & discount') }}</small>
                                                </th>
                                            </tr>
                                            @php
                                                $totalQuantity = 0;
                                                $totalRate = 0;
                                                $totalTaxPrice = 0;
                                                $totalDiscount = 0;
                                                $taxesData = [];
                                            @endphp
                                            @foreach ($iteams as $key => $iteam)
                                                @if (!empty($iteam->tax))
                                                    @php
                                                        $taxes = App\Models\Utility::tax($iteam->tax);
                                                        $totalQuantity += $iteam->quantity;
                                                        $totalRate += $iteam->price;
                                                        $totalDiscount += $iteam->discount;
                                                        foreach ($taxes as $taxe) {
                                                            $taxDataPrice = App\Models\Utility::taxRate($taxe->rate, $iteam->price, $iteam->quantity);
                                                            if (array_key_exists($taxe->name, $taxesData)) {
                                                                $taxesData[$taxe->name] = $taxesData[$taxe->name] + $taxDataPrice;
                                                            } else {
                                                                $taxesData[$taxe->name] = $taxDataPrice;
                                                            }
                                                        }
                                                    @endphp
                                                @endif
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ !empty($iteam->product) ? $iteam->product->name : '' }}</td>
                                                    <td>{{ $iteam->quantity }}</td>
                                                    <td>{{ Utility::priceFormat($company_setting, $iteam->price) }}</td>

                                                    <td>

                                                        {{ Utility::priceFormat($company_setting, $iteam->discount) }}

                                                    </td>

                                                    <td>

                                                        @if (!empty($iteam->tax))
                                                            <table>
                                                                @php $totalTaxRate = 0;@endphp
                                                                @foreach ($taxes as $tax)
                                                                    @php
                                                                        $taxPrice = App\Models\Utility::taxRate($tax->rate, $iteam->price, $iteam->quantity);
                                                                        $totalTaxPrice += $taxPrice;
                                                                    @endphp
                                                                    <tr>
                                                                        <td>{{ $tax->name . ' (' . $tax->rate . '%)' }}
                                                                        </td>
                                                                        <td>{{ Utility::priceFormat($company_setting, $taxPrice) }}
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </table>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>

                                                    <td>{{ !empty($iteam->description) ? $iteam->description : '-' }}</td>
                                                    <td class="text-end">
                                                        {{ Utility::priceFormat($company_setting, $iteam->price * $iteam->quantity) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tfoot>
                                                <tr>
                                                    <td></td>

                                                    <td><b>{{ __('Total') }}</b></td>
                                                    <td><b>{{ $totalQuantity }}</b></td>
                                                    <td><b>{{ Utility::priceFormat($company_setting, $totalRate) }}</b>
                                                    </td>
                                                    <td>

                                                        <b>{{ Utility::priceFormat($company_setting, $totalDiscount) }}</b>

                                                    </td>
                                                    <td><b>{{ Utility::priceFormat($company_setting, $totalTaxPrice) }}</b>
                                                    </td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Sub Total') }}</b></td>
                                                    <td class="text-end">
                                                        {{ Utility::priceFormat($company_setting, $retainer->getSubTotal()) }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Discount') }}</b></td>
                                                    <td class="text-end">
                                                        {{ Utility::priceFormat($company_setting, $retainer->getTotalDiscount()) }}
                                                    </td>
                                                </tr>

                                                @if (!empty($taxesData))
                                                    @foreach ($taxesData as $taxName => $taxPrice)
                                                        <tr>
                                                            <td colspan="6"></td>
                                                            <td class="text-end"><b>{{ $taxName }}</b></td>
                                                            <td class="text-end">
                                                                {{ Utility::priceFormat($company_setting, $taxPrice) }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="blue-text text-end"><b>{{ __('Total') }}</b></td>
                                                    <td class="blue-text text-end">
                                                        {{ Utility::priceFormat($company_setting, $retainer->getTotal()) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Paid') }}</b></td>
                                                    <td class="text-end">
                                                        {{ Utility::priceFormat($company_setting, $retainer->getTotal() - $retainer->getDue()) }}
                                                    </td>
                                                </tr>
                                                {{--    <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Credit Note') }}</b></td>
                                                    <td class="text-end">
                                                        {{ Utility::priceFormat($company_setting, $retainer->retainerTotalCreditNote()) }}
                                                    </td>
                                            </tr> --}}
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Due') }}</b></td>
                                                    <td class="text-end">
                                                        {{ Utility::priceFormat($company_setting, $retainer->getDue()) }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-10">
            <h5 class="h4 d-inline-block font-weight-400 mb-4">{{ __('Receipt Summary') }}</h5>
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table ">
                            <tr>
                                <th class="text-dark">{{ __('Date') }}</th>
                                <th class="text-dark">{{ __('Amount') }}</th>
                                <th class="text-dark">{{ __('Payment Type') }}</th>
                                <th class="text-dark">{{ __('Account') }}</th>
                                <th class="text-dark">{{ __('Reference') }}</th>
                                <th class="text-dark">{{ __('Description') }}</th>
                                <th class="text-dark">{{ __('Receipt') }}</th>
                                <th class="text-dark">{{ __('OrderId') }}</th>
                                @can('delete payment invoice')
                                    <th class="text-dark">{{ __('Action') }}</th>
                                @endcan
                            </tr>
                            @php
                                $path = \App\Models\Utility::get_file('/uploads/bank_receipt');
                            @endphp
                            @foreach ($retainer->payments as $key => $payment)
                                <tr>
                                    <td>{{ Utility::dateFormat($company_setting, $payment->date) }}</td>
                                    <td>{{ Utility::priceFormat($company_setting, $payment->amount) }}</td>
                                    <td>{{ $payment->payment_type }}</td>
                                    <td>{{ !empty($payment->bankAccount) ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : '--' }}
                                    </td>
                                    <td>{{ !empty($payment->reference) ? $payment->reference : '--' }}</td>
                                    <td>{{ !empty($payment->description) ? $payment->description : '--' }}</td>
                                    <td>
                                        @if (!empty($payment->receipt))
                                            <a href="{{ $path . '/' . $payment->receipt }}" target="_blank">
                                                <i class="ti ti-file"></i></a>
                                        @elseif(!empty($payment->add_receipt))
                                            <a href="{{ $payment->add_receipt }}" target="_blank">
                                                <i class="ti ti-file"></i></a>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>{{ !empty($payment->order_id) ? $payment->order_id : '--' }}</td>
                                    @can('delete invoice product')
                                        <td>
                                            <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                data-bs-toggle="tooltip" data-original-title="{{ __('Delete') }}"
                                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                data-confirm-yes="document.getElementById('delete-form-{{ $payment->id }}').submit();">
                                                <i class="ti ti-trash text-white"></i>
                                            </a>
                                            {!! Form::open([
                                                'method' => 'post',
                                                'route' => ['retainer.payment.destroy', $retainer->id, $payment->id],
                                                'id' => 'delete-form-' . $payment->id,
                                            ]) !!}
                                            {!! Form::close() !!}
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                            @php
                                $path = \App\Models\Utility::get_file('/uploads/bank_receipt');
                            @endphp
                            @foreach ($retainer->bankpayment as $key => $bankpayment)
                                <tr>
                                    <td>{{ Utility::dateFormat($company_setting, $bankpayment->created_at) }}</td>
                                    <td>{{ Utility::priceFormat($company_setting, $bankpayment->amount) }}</td>
                                    <td>{{ 'Bank Transfer' }}</td>
                                    <td>{{ !empty($bankpayment->bankAccount) ? $bankpayment->bankAccount->bank_name . ' ' . $bankpayment->bankAccount->holder_name : '--' }}
                                    </td>
                                    <td>{{ !empty($bankpayment->reference) ? $bankpayment->reference : '--' }}</td>
                                    <td>{{ !empty($bankpayment->description) ? $bankpayment->description : '--' }}</td>
                                    <td>
                                        @if (!empty($bankpayment->receipt))
                                            <a href="{{ $path . '/' . $bankpayment->receipt }}" target="_blank"> <i
                                                    class="ti ti-file"></i></a>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>{{ !empty($bankpayment->order_id) ? $bankpayment->order_id : '--' }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
        {{--  <div class="col-10">
            <h5 class="h4 d-inline-block font-weight-400 mb-4">{{ __('Credit Note Summary') }}</h5>
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table ">
                            <tr>
                                <th class="text-dark">{{ __('Date') }}</th>
                                <th class="text-dark" class="">{{ __('Amount') }}</th>
                                <th class="text-dark" class="">{{ __('Description') }}</th>
                                @if (Gate::check('edit credit note') || Gate::check('delete credit note'))
                                    <th class="text-dark">{{ __('Action') }}</th>
                                @endif
                            </tr>
                            @forelse($retainer->creditNote as $key =>$creditNote)
                                <tr>
                                    <td>{{ Utility::dateFormat($company_setting, $creditNote->date) }}</td>
                                    <td class="">
                                        {{ Utility::priceFormat($company_setting, $creditNote->amount) }}</td>
                                    <td class="">{{ $creditNote->description }}</td>
                                    <td>
                                        @can('edit credit note')
                                            <a data-url="{{ route('retainer.edit.credit.note', [$creditNote->retainer, $creditNote->id]) }}"
                                                data-ajax-popup="true" data-title="{{ __('Add Credit Note') }}"
                                                data-bs-toggle="tooltip" data-original-title="{{ __('Credit Note') }}" href="#"
                                                class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip"
                                                data-original-title="{{ __('Edit') }}">
                                                <i class="ti ti-pencil text-white"></i>
                                            </a>
                                        @endcan
                                        @can('delete credit note')
                                            <a href="#" class="mx-3 btn btn-sm align-items-center " data-bs-toggle="tooltip"
                                                data-original-title="{{ __('Delete') }}"
                                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                data-confirm-yes="document.getElementById('delete-form-{{ $creditNote->id }}').submit();">
                                                <i class="ti ti-trash text-white"></i>
                                            </a>
                                            {!! Form::open(['method' => 'DELETE', 'route' => ['retainer.delete.credit.note', $creditNote->retainer, $creditNote->id], 'id' => 'delete-form-' . $creditNote->id]) !!}
                                            {!! Form::close() !!}
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <p class="text-dark">{{ __('No Data Found') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div> --}}
    </div>
    {{-- {{ dd($company_payment_setting) }} --}}
    {{-- @auth('customer') --}}

    @if ($retainer->getDue() > 0)
        <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalLabel">{{ __('Add Payment') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card bg-none card-box">
                            <section class="">

                                @if (
                                    (isset($company_payment_setting['is_stripe_enabled']) && $company_payment_setting['is_stripe_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_paypal_enabled']) &&
                                            $company_payment_setting['is_paypal_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_paystack_enabled']) &&
                                            $company_payment_setting['is_paystack_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_flutterwave_enabled']) &&
                                            $company_payment_setting['is_flutterwave_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_razorpay_enabled']) &&
                                            $company_payment_setting['is_razorpay_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_mercado_enabled']) &&
                                            $company_payment_setting['is_mercado_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_paytm_enabled']) && $company_payment_setting['is_paytm_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_mollie_enabled']) &&
                                            $company_payment_setting['is_mollie_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_skrill_enabled']) &&
                                            $company_payment_setting['is_skrill_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_coingate_enabled']) &&
                                            $company_payment_setting['is_coingate_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_paymentwall_enabled']) &&
                                            $company_payment_setting['is_paymentwall_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_toyyibpay_enabled']) &&
                                            $company_payment_setting['is_toyyibpay_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_payfast_enabled']) &&
                                            $company_payment_setting['is_payfast_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_bank_enabled']) && $company_payment_setting['is_bank_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_iyzipay_enabled']) &&
                                            $company_payment_setting['is_iyzipay_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_sspay_enabled']) && $company_payment_setting['is_sspay_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_paytab_enabled']) &&
                                            $company_payment_setting['is_paytab_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_benefit_enabled']) &&
                                            $company_payment_setting['is_benefit_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_cashfree_enabled']) &&
                                            $company_payment_setting['is_cashfree_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_aamarpay_enabled']) &&
                                            $company_payment_setting['is_aamarpay_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_paytr_enabled']) && $company_payment_setting['is_paytr_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_yookassa_enabled']) &&
                                            $company_payment_setting['is_yookassa_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_xendit_enabled']) &&
                                            $company_payment_setting['is_xendit_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_midtrans_enabled']) &&
                                            $company_payment_setting['is_midtrans_enabled'] == 'on') ||
                                        (isset($company_payment_setting['is_tap_enabled']) &&
                                            $company_payment_setting['is_tap_enabled'] == 'on'))
                                    <ul class="nav nav-pills  mb-3" id="pills-tab" role="tablist">
                                        @if ($company_payment_setting['is_bank_enabled'] == 'on' && !empty($company_payment_setting['bank_detail']))
                                            <li class="nav-item mb-2">
                                                <button class="active btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#banktransfer-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Bank Transfer') }}</button>
                                            </li>
                                        @endif

                                        @if (
                                            $company_payment_setting['is_stripe_enabled'] == 'on' &&
                                                !empty($company_payment_setting['stripe_key']) &&
                                                !empty($company_payment_setting['stripe_secret']))
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#stripe-payment" type="button"
                                                    role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Stripe') }}</button>
                                            </li>
                                        @endif
                                        @if (
                                            $company_payment_setting['is_paypal_enabled'] == 'on' &&
                                                !empty($company_payment_setting['paypal_client_id']) &&
                                                !empty($company_payment_setting['paypal_secret_key']))
                                            <li class="nav-item mb-2">
                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#paypal-payment" type="button"
                                                    role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Paypal') }}</button>
                                            </li>
                                        @endif

                                        @if (
                                            $company_payment_setting['is_paystack_enabled'] == 'on' &&
                                                !empty($company_payment_setting['paystack_public_key']) &&
                                                !empty($company_payment_setting['paystack_secret_key']))
                                            <li class="nav-item mb-2">

                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#paystack-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Paystack') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_flutterwave_enabled']) &&
                                                $company_payment_setting['is_flutterwave_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#flutterwave-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Flutterwave') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_razorpay_enabled']) && $company_payment_setting['is_razorpay_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#razorpay-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Razorpay') }}</button>
                                            </li>
                                        @endif


                                        @if (isset($company_payment_setting['is_mercado_enabled']) && $company_payment_setting['is_mercado_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#mercado-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Mercado') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_paytm_enabled']) && $company_payment_setting['is_paytm_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#paytm-payment" type="button"
                                                    role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Paytm') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_mollie_enabled']) && $company_payment_setting['is_mollie_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#mollie-payment" type="button"
                                                    role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Mollie') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_skrill_enabled']) && $company_payment_setting['is_skrill_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#skrill-payment" type="button"
                                                    role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Skrill') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_coingate_enabled']) && $company_payment_setting['is_coingate_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#coingate-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Coingate') }}</button>
                                            </li>
                                        @endif
                                        @if (
                                            $company_payment_setting['is_paymentwall_enabled'] == 'on' &&
                                                !empty($company_payment_setting['paymentwall_public_key']) &&
                                                !empty($company_payment_setting['paymentwall_secret_key']))
                                            <li class="nav-item mb-2">

                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#paymentwall-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('PaymentWall') }}</button>
                                            </li>
                                        @endif
                                        @if (
                                            $company_payment_setting['is_toyyibpay_enabled'] == 'on' &&
                                                !empty($company_payment_setting['toyyibpay_secret_key']) &&
                                                !empty($company_payment_setting['category_code']))
                                            <li class="nav-item mb-2">

                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#toyyibpay-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Toyyibpay') }}</button>
                                            </li>
                                        @endif

                                        @if (
                                            $company_payment_setting['is_payfast_enabled'] == 'on' &&
                                                !empty($company_payment_setting['payfast_merchant_id']) &&
                                                !empty($company_payment_setting['payfast_merchant_key']))
                                            <li class="nav-item mb-2">

                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#payfast-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true"
                                                    onclick=get_payfast_status()>{{ __('Payfast') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_iyzipay_enabled']) && $company_payment_setting['is_iyzipay_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class=" btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#iyzipay-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('IyziPay') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_sspay_enabled']) && $company_payment_setting['is_sspay_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#sspay-payment" type="button"
                                                    role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Sspay') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_paytab_enabled']) && $company_payment_setting['is_paytab_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#paytab-payment" type="button"
                                                    role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Paytab') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_benefit_enabled']) && $company_payment_setting['is_benefit_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#benefit-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Benefit') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_cashfree_enabled']) && $company_payment_setting['is_cashfree_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#cashfree-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Cashfree') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_aamarpay_enabled']) && $company_payment_setting['is_aamarpay_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#aamarpay-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Aamarpay') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_paytr_enabled']) && $company_payment_setting['is_paytr_enabled'] == 'on')
                                            <li class="nav-item mb-2">

                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#paytr-payment" type="button"
                                                    role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('PayTR') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_yookassa_enabled']) && $company_payment_setting['is_yookassa_enabled'] == 'on')
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#yookassa-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('YooKassa') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_xendit_enabled']) && $company_payment_setting['is_xendit_enabled'] == 'on')
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#xendit-payment" type="button"
                                                    role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Xendit') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_midtrans_enabled']) && $company_payment_setting['is_midtrans_enabled'] == 'on')
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#midtrans-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Midtrans') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_paiementpro_enabled']) && $company_payment_setting['is_paiementpro_enabled'] == 'on')
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#paiementpro-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Paiementpro') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_nepalste_enabled']) && $company_payment_setting['is_nepalste_enabled'] == 'on')
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#nepalste-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Nepalste') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_cinetpay_enabled']) && $company_payment_setting['is_cinetpay_enabled'] == 'on')
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#cinetpay-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Cinetpay') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_fedapay_enabled']) && $company_payment_setting['is_fedapay_enabled'] == 'on')
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#fedapay-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Fedapay') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_payhere_enabled']) && $company_payment_setting['is_payhere_enabled'] == 'on')
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#payhere-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Payhere') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_tap_enabled']) && $company_payment_setting['is_tap_enabled'] == 'on')
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#tap-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Tap') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_authorizenet_enabled']) && $company_payment_setting['is_authorizenet_enabled'] == 'on')
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#authorizenet-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('AuthorizeNet') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_khalti_enabled']) && $company_payment_setting['is_khalti_enabled'] == 'on')
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#khalti-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Khalti') }}</button>
                                            </li>
                                        @endif

                                        @if (isset($company_payment_setting['is_ozow_enabled']) && $company_payment_setting['is_ozow_enabled'] == 'on')
                                            <li class="nav-item mb-2">
                                                <button class="btn btn-outline-success btn-sm" id="pills-home-tab"
                                                    data-bs-toggle="pill" data-bs-target="#ozow-payment"
                                                    type="button" role="tab" aria-controls="pills-home"
                                                    aria-selected="true">{{ __('Ozow') }}</button>
                                            </li>
                                        @endif

                                    </ul>
                                @endif
                                <div class="tab-content" id="pills-tabContent">

                                    @if (
                                        !empty($company_payment_setting) &&
                                            ($company_payment_setting['is_bank_enabled'] == 'on' && !empty($company_payment_setting['bank_detail'])))
                                        <div class="tab-pane fade active show" id="banktransfer-payment" role="tabpanel"
                                            aria-labelledby="banktransfer-payment">
                                            <form method="post"
                                                action="{{ route('retainer.with.banktransfer', $retainer->id) }}"
                                                class="require-validation" enctype="multipart/form-data">
                                                @csrf
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="custom-radio">
                                                            <h6 class="font-16 font-weight-bold">
                                                                {{ __('Bank Details :') }}</h6>
                                                        </div>
                                                        <p class="mb-0 pt-1 text-sm">
                                                            @if (isset($company_payment_setting['bank_detail']) && !empty($company_payment_setting['bank_detail']))
                                                            @endif
                                                            {!! $company_payment_setting['bank_detail'] !!}
                                                        </p>
                                                    </div>
                                                    <div class="col-6">
                                                        {{ Form::label('payment_receipt', __('Payment Receipt'), ['class' => 'form-label']) }}
                                                        <div class="choose-file form-group">
                                                            <input type="file" name="receipt" id="image" class="form-control file-validate" data-filename="upload_file" enctype="multipart/form-data" required="required">
                                                            <span id="" class="file-error text-danger"></span>
                                                        </div>
                                                        <p class="upload_file"></p>
                                                    </div>

                                                </div>
                                                <div class="row">
                                                    <div class="form-group col-md-12">
                                                        <br>
                                                        <label for="amount">{{ __('Amount') }}</label>
                                                        <div class="input-group">
                                                            <span class="input-group-prepend"><span
                                                                    class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}</span></span>
                                                            <input class="form-control" required="required"
                                                                min="0" name="amount" type="number"
                                                                value="{{ $retainer->getDue() }}" min="0"
                                                                step="0.01" max="{{ $retainer->getDue() }}"
                                                                id="amount">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <button class="btn btn-sm btn-primary m-r-10"
                                                        type="submit">{{ __('Make Payment') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            ($company_payment_setting['is_stripe_enabled'] == 'on' &&
                                                !empty($company_payment_setting['stripe_key']) &&
                                                !empty($company_payment_setting['stripe_secret'])))
                                        <div class="tab-pane fade" id="stripe-payment" role="tabpanel"
                                            aria-labelledby="stripe-payment">
                                            <form method="post" action="{{ route('retainer.payment', $retainer->id) }}"
                                                class="require-validation" id="payment-form">
                                                @csrf
                                                <div class="row">
                                                    <div class="col-sm-8">
                                                        <div class="custom-radio">
                                                            <label
                                                                class="font-16 font-weight-bold">{{ __('Credit / Debit Card') }}</label>
                                                        </div>
                                                        <p class="mb-0 pt-1 text-sm">
                                                            {{ __('Safe money transfer using your bank account. We support Mastercard, Visa, Discover and American express.') }}
                                                        </p>
                                                    </div>

                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label for="card-name-on">{{ __('Name on card') }}</label>
                                                            <input type="text" name="name" id="card-name-on"
                                                                class="form-control required"
                                                                placeholder="{{ $retainer->customer->name }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div id="card-element">

                                                        </div>
                                                        <div id="card-errors" role="alert"></div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="form-group col-md-12">
                                                        <br>
                                                        <label for="amount">{{ __('Amount') }}</label>
                                                        <div class="input-group">
                                                            <span class="input-group-prepend"><span
                                                                    class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}</span></span>
                                                            <input class="form-control" required="required"
                                                                min="0" name="amount" type="number"
                                                                value="{{ $retainer->getDue() }}" min="0"
                                                                step="0.01" max="{{ $retainer->getDue() }}"
                                                                id="amount">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="error" style="display: none;">
                                                            <div class='alert-danger alert'>
                                                                {{ __('Please correct the errors and try again.') }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <button class="btn btn-sm btn-primary m-r-10"
                                                        type="submit">{{ __('Make Payment') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            ($company_payment_setting['is_paypal_enabled'] == 'on' &&
                                                !empty($company_payment_setting['paypal_client_id']) &&
                                                !empty($company_payment_setting['paypal_secret_key'])))
                                        <div class="tab-pane fade " id="paypal-payment" role="tabpanel"
                                            aria-labelledby="paypal-payment">
                                            <form class="w3-container w3-display-middle w3-card-4 " method="POST"
                                                id="payment-form"
                                                action="{{ route('customer.pay.with.paypal', $retainer->id) }}">
                                                @csrf
                                                <div class="row">
                                                    <div class="form-group col-md-12">
                                                        <label for="amount">{{ __('Amount') }}</label>
                                                        <div class="input-group">
                                                            <span class="input-group-prepend"><span
                                                                    class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}</span></span>
                                                            <input class="form-control" required="required"
                                                                min="0" name="amount" type="number"
                                                                value="{{ $retainer->getDue() }}" min="0"
                                                                step="0.01" max="{{ $retainer->getDue() }}"
                                                                id="amount">
                                                            @error('amount')
                                                                <span class="invalid-amount" role="alert">
                                                                    <strong>{{ $message }}</strong>
                                                                </span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <button class="btn btn-sm btn-primary m-r-10" name="submit"
                                                        type="submit">{{ __('Make Payment') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_paystack_enabled']) &&
                                            $company_payment_setting['is_paystack_enabled'] == 'on' &&
                                            !empty($company_payment_setting['paystack_public_key']) &&
                                            !empty($company_payment_setting['paystack_secret_key']))
                                        <div class="tab-pane fade " id="paystack-payment" role="tabpanel"
                                            aria-labelledby="paypal-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="paystack-payment-form"
                                                action="{{ route('customer.retainer.pay.with.paystack') }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend"><span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}</span></span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">

                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_paystack"
                                                        type="button" value="{{ __('Make Payment') }}">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_flutterwave_enabled']) &&
                                            $company_payment_setting['is_flutterwave_enabled'] == 'on' &&
                                            !empty($company_payment_setting['paystack_public_key']) &&
                                            !empty($company_payment_setting['paystack_secret_key']))
                                        <div class="tab-pane fade " id="flutterwave-payment" role="tabpanel"
                                            aria-labelledby="paypal-payment">
                                            <form role="form"
                                                action="{{ route('customer.retainer.pay.with.flaterwave') }}"
                                                method="post" class="require-validation" id="flaterwave-payment-form">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend"><span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}</span></span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">

                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_flaterwave"
                                                        type="button" value="{{ __('Make Payment') }}">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_razorpay_enabled']) &&
                                            $company_payment_setting['is_razorpay_enabled'] == 'on')
                                        <div class="tab-pane fade " id="razorpay-payment" role="tabpanel"
                                            aria-labelledby="paypal-payment">
                                            <form role="form"
                                                action="{{ route('customer.retainer.pay.with.razorpay') }}"
                                                method="post" class="require-validation" id="razorpay-payment-form">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend"><span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}</span></span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">

                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_razorpay"
                                                        type="button" value="{{ __('Make Payment') }}">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_mercado_enabled']) &&
                                            $company_payment_setting['is_mercado_enabled'] == 'on')
                                        <div class="tab-pane fade " id="mercado-payment" role="tabpanel"
                                            aria-labelledby="mercado-payment">
                                            <form role="form"
                                                action="{{ route('customer.retainer.pay.with.mercado') }}"
                                                method="post" class="require-validation" id="mercado-payment-form">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend"><span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}</span></span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">

                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input type="submit" id="pay_with_mercado"
                                                        value="{{ __('Make Payment') }}"
                                                        class="btn btn-sm btn-primary m-r-10">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_paytm_enabled']) &&
                                            $company_payment_setting['is_paytm_enabled'] == 'on')
                                        <div class="tab-pane fade" id="paytm-payment" role="tabpanel"
                                            aria-labelledby="paytm-payment">
                                            <form role="form"
                                                action="{{ route('customer.retainer.pay.with.paytm') }}" method="post"
                                                class="require-validation" id="paytm-payment-form">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend"><span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}</span></span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">

                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="flaterwave_coupon"
                                                            class=" text-dark">{{ __('Mobile Number') }}</label>
                                                        <input type="text" id="mobile" name="mobile"
                                                            class="form-control mobile" data-from="mobile"
                                                            placeholder="{{ __('Enter Mobile Number') }}" required>
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input type="submit" id="pay_with_paytm"
                                                        value="{{ __('Make Payment') }}"
                                                        class="btn btn-sm btn-primary m-r-10">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_mollie_enabled']) &&
                                            $company_payment_setting['is_mollie_enabled'] == 'on')
                                        <div class="tab-pane fade " id="mollie-payment" role="tabpanel"
                                            aria-labelledby="mollie-payment">
                                            <form role="form"
                                                action="{{ route('customer.retainer.pay.with.mollie') }}" method="post"
                                                class="require-validation" id="mollie-payment-form">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend"><span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}</span></span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">

                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input type="submit" id="pay_with_mollie"
                                                        value="{{ __('Make Payment') }}"
                                                        class="btn btn-sm btn-primary m-r-10">
                                                </div>
                                            </form>
                                        </div>
                                    @endif


                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_skrill_enabled']) &&
                                            $company_payment_setting['is_skrill_enabled'] == 'on')
                                        <div class="tab-pane fade " id="skrill-payment" role="tabpanel"
                                            aria-labelledby="skrill-payment">
                                            <form role="form"
                                                action="{{ route('customer.retainer.pay.with.skrill') }}" method="post"
                                                class="require-validation" id="skrill-payment-form">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend"><span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}</span></span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">

                                                    </div>
                                                </div>
                                                @php
                                                    $skrill_data = [
                                                        'transaction_id' => md5(date('Y-m-d') . strtotime('Y-m-d H:i:s') . 'user_id'),
                                                        'user_id' => 'user_id',
                                                        'amount' => 'amount',
                                                        'currency' => 'currency',
                                                    ];
                                                    session()->put('skrill_data', $skrill_data);

                                                @endphp
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input type="submit" id="pay_with_skrill"
                                                        value="{{ __('Make Payment') }}"
                                                        class="btn btn-sm btn-primary m-r-10">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_coingate_enabled']) &&
                                            $company_payment_setting['is_coingate_enabled'] == 'on')
                                        <div class="tab-pane fade " id="coingate-payment" role="tabpanel"
                                            aria-labelledby="coingate-payment">
                                            <form role="form"
                                                action="{{ route('customer.retainer.pay.with.coingate') }}"
                                                method="post" class="require-validation" id="coingate-payment-form">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend"><span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}</span></span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">

                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input type="submit" id="pay_with_coingate"
                                                        value="{{ __('Make Payment') }}"
                                                        class="btn btn-sm btn-primary m-r-10">
                                                </div>

                                            </form>
                                        </div>
                                    @endif


                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_paymentwall_enabled']) &&
                                            $company_payment_setting['is_paymentwall_enabled'] == 'on' &&
                                            !empty($company_payment_setting['paymentwall_public_key']) &&
                                            !empty($company_payment_setting['paymentwall_secret_key']))
                                        <div class="tab-pane fade " id="paymentwall-payment" role="tabpanel"
                                            aria-labelledby="paypal-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="paymentwall-payment-form"
                                                action="{{ route('retainer.paymentwallpayment') }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_paymentwall"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>

                                            </form>
                                        </div>
                                    @endif
                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_toyyibpay_enabled']) &&
                                            $company_payment_setting['is_toyyibpay_enabled'] == 'on' &&
                                            !empty($company_payment_setting['toyyibpay_secret_key']) &&
                                            !empty($company_payment_setting['category_code']))
                                        <div class="tab-pane fade " id="toyyibpay-payment" role="tabpanel"
                                            aria-labelledby="toyyibpay-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="toyyibpay-payment-form"
                                                action="{{ route('pay.with.toyyibpay', $retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_toyyibpay"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_payfast_enabled']) &&
                                            $company_payment_setting['is_payfast_enabled'] == 'on' &&
                                            !empty($company_payment_setting['payfast_merchant_id']))
                                        <div class="tab-pane fade " id="payfast-payment" role="tabpanel"
                                            aria-labelledby="payfast-payment">
                                            @php
                                                $pfHost = $company_payment_setting['payfast_mode'] == 'sandbox' ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
                                            @endphp
                                            <form role="form" action={{ 'https://' . $pfHost . '/eng/process' }}
                                                method="post" id="payfast-payment-form">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="pay_fast_amount" onchange=get_payfast_status()>
                                                    </div>
                                                </div>
                                                <div id="get-payfast-inputs"></div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input type="hidden" name="retainer_id" id="retainer_id"
                                                        value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_payfast"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            ($company_payment_setting['is_iyzipay_enabled'] == 'on' &&
                                                !empty($company_payment_setting['iyzipay_private_key']) &&
                                                !empty($company_payment_setting['iyzipay_secret_key'])))
                                        <div class="tab-pane fade " id="iyzipay-payment" role="tabpanel"
                                            aria-labelledby="iyzipay-payment">
                                            <form class="w3-container w3-display-middle w3-card-4 " method="POST"
                                                id="payment-form"
                                                action="{{ route('retainer.with.iyzipay', $retainer->id) }}">

                                                @csrf
                                                <div class="row">
                                                    <div class="form-group col-md-12">
                                                        <label for="amount">{{ __('Amount') }}</label>
                                                        <div class="input-group">
                                                            <span class="input-group-prepend"><span
                                                                    class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}</span></span>
                                                            <input class="form-control" required="required"
                                                                min="0" name="amount" type="number"
                                                                value="{{ $retainer->getDue() }}" min="0"
                                                                step="0.01" max="{{ $retainer->getDue() }}"
                                                                id="amount">
                                                            @error('amount')
                                                                <span class="invalid-amount" role="alert">
                                                                    <strong>{{ $message }}</strong>
                                                                </span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <button class="btn btn-sm btn-primary m-r-10" name="submit"
                                                        type="submit">{{ __('Make Payment') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_sspay_enabled']) &&
                                            $company_payment_setting['is_sspay_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_sspay_enabled']))
                                        <div class="tab-pane fade " id="sspay-payment" role="tabpanel"
                                            aria-labelledby="sspay-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="sspay-payment-form"
                                                action="{{ route('retainer.pay.with.sspay', $retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_sspay"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_paytab_enabled']) &&
                                            $company_payment_setting['is_paytab_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_paytab_enabled']))
                                        <div class="tab-pane fade " id="paytab-payment" role="tabpanel"
                                            aria-labelledby="paytab-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="paytab-payment-form"
                                                action="{{ route('retainer.pay.with.paytab', $retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_paytab"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_benefit_enabled']) &&
                                            $company_payment_setting['is_benefit_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_benefit_enabled']))
                                        <div class="tab-pane fade" id="benefit-payment" role="tabpanel"
                                            aria-labelledby="benefit-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="benefit-payment-form"
                                                action="{{ route('retainer.pay.with.benefit', $retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_benefit"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_cashfree_enabled']) &&
                                            $company_payment_setting['is_cashfree_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_cashfree_enabled']))
                                        <div class="tab-pane fade" id="cashfree-payment" role="tabpanel"
                                            aria-labelledby="cashfree-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="cashfree-payment-form"
                                                action="{{ route('retainer.pay.with.cashfree', $retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_cashfree"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_aamarpay_enabled']) &&
                                            $company_payment_setting['is_aamarpay_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_aamarpay_enabled']))
                                        <div class="tab-pane fade" id="aamarpay-payment" role="tabpanel"
                                            aria-labelledby="aamarpay-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="aamarpay-payment-form"
                                                action="{{ route('retainer.pay.aamarpay.payment', $retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_aarampay"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>

                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_paytr_enabled']) &&
                                            $company_payment_setting['is_paytr_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_paytr_enabled']))
                                        <div class="tab-pane fade" id="paytr-payment" role="tabpanel"
                                            aria-labelledby="paytr-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="paytr-payment-form"
                                                action="{{ route('retainer.pay.paytr.payment', $retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">

                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ App\Models\Utility::getValByName('site_currency') }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_paytr"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_yookassa_enabled']) &&
                                            $company_payment_setting['is_yookassa_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_yookassa_enabled']))
                                        <div class="tab-pane fade" id="yookassa-payment" role="tabpanel"
                                            aria-labelledby="yookassa-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="yookassa-payment-form"
                                                action="{{ route('retainer.with.yookassa', $retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ $setting['site_currency'] }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_yookassa"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_xendit_enabled']) &&
                                            $company_payment_setting['is_xendit_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_xendit_enabled']))
                                        <div class="tab-pane fade" id="xendit-payment" role="tabpanel"
                                            aria-labelledby="xendit-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="xendit-payment-form"
                                                action="{{ route('retainer.with.xendit', $retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ $setting['site_currency'] }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_xendit"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_midtrans_enabled']) &&
                                            $company_payment_setting['is_midtrans_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_midtrans_enabled']))
                                        <div class="tab-pane fade" id="midtrans-payment" role="tabpanel"
                                            aria-labelledby="midtrans-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="midtrans-payment-form"
                                                action="{{ route('retainer.with.midtrans', $retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ $setting['site_currency'] }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_midtrans"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_paiementpro_enabled']) &&
                                            $company_payment_setting['is_paiementpro_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_paiementpro_enabled']))
                                        <div class="tab-pane fade" id="paiementpro-payment" role="tabpanel"
                                            aria-labelledby="paiementpro-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="paiementpro-payment-form"
                                                action="{{ route('retainer.with.paiementpro', $retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                <div class="col-md-12 mt-4 row">
                                                    <div class="form-group col-md-6">
                                                        {{Form::label('mobile_number',__('Mobile Number'),['class'=>'form-label'])}}
                                                        <input type="text" name="mobile_number" class="form-control font-style mobile_number">
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        {{Form::label('channel',__('Channel'),['class'=>'form-label'])}}
                                                        <input type="text" name="channel" class="form-control font-style channel">
                                                        <small class="text-danger">Example : OMCIV2,MOMO,CARD,FLOOZ ,PAYPAL</small>
                                                    </div>
                                                </div>
                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ $setting['site_currency'] }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_paiementpro"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_nepalste_enabled']) &&
                                            $company_payment_setting['is_nepalste_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_nepalste_enabled']))
                                        <div class="tab-pane fade" id="nepalste-payment" role="tabpanel"
                                            aria-labelledby="nepalste-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="nepalste-payment-form"
                                                action="{{ route('retainer.with.nepalste',$retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ $setting['site_currency'] }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_nepalste"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_cinetpay_enabled']) &&
                                            $company_payment_setting['is_cinetpay_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_cinetpay_enabled']))
                                        <div class="tab-pane fade" id="cinetpay-payment" role="tabpanel"
                                            aria-labelledby="cinetpay-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="cinetpay-payment-form"
                                                action="{{ route('retainer.with.cinetpay',$retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ $setting['site_currency'] }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_cinetpay"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_fedapay_enabled']) &&
                                            $company_payment_setting['is_fedapay_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_fedapay_enabled']))
                                        <div class="tab-pane fade" id="fedapay-payment" role="tabpanel"
                                            aria-labelledby="fedapay-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="fedapay-payment-form"
                                                action="{{ route('retainer.with.fedapay',$retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ $setting['site_currency'] }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_fedapay"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_payhere_enabled']) &&
                                            $company_payment_setting['is_payhere_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_payhere_enabled']))
                                        <div class="tab-pane fade" id="payhere-payment" role="tabpanel"
                                            aria-labelledby="payhere-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="payhere-payment-form"
                                                action="{{ route('retainer.with.payhere',$retainer->id) }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ $setting['site_currency'] }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_payhere"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>
                                            </form>
                                        </div>
                                    @endif


                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_tap_enabled']) &&
                                            $company_payment_setting['is_tap_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_tap_enabled']))
                                        <div class="tab-pane fade" id="tap-payment" role="tabpanel"
                                            aria-labelledby="tap-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="tap-payment-form"
                                                action="{{ route('retainer.with.tap') }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ $setting['site_currency'] }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_payhere"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_authorizenet_enabled']) &&
                                            $company_payment_setting['is_authorizenet_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_authorizenet_enabled']))
                                        <div class="tab-pane fade" id="authorizenet-payment" role="tabpanel"
                                            aria-labelledby="authorizenet-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="authorizenet-payment-form"
                                                action="{{ route('retainer.with.authorizenet') }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ $setting['site_currency'] }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <input class="btn btn-sm btn-primary m-r-10" id="pay_with_payhere"
                                                        type="submit" value="{{ __('Make Payment') }}">
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_khalti_enabled']) &&
                                            $company_payment_setting['is_khalti_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_khalti_enabled']))
                                        <div class="tab-pane fade" id="khalti-payment" role="tabpanel"
                                            aria-labelledby="khalti-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="khalti-payment-form"
                                                action="{{ route('retainer.with.khalti') }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id" class="khalti_retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ $setting['site_currency'] }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control khalti_amount" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <!-- <input class="btn btn-sm btn-primary m-r-10" id="pay_with_khalti"
                                                        type="button" value="{{ __('Make Payment') }}"> -->
                                                    <button class="btn btn-sm btn-primary m-r-10" type="submit" id="pay_with_khalti">
                                                        {{ __('Make Payment') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    {{-- ozow --}}
                                    @if (
                                        !empty($company_payment_setting) &&
                                            isset($company_payment_setting['is_ozow_enabled']) &&
                                            $company_payment_setting['is_ozow_enabled'] == 'on' &&
                                            !empty($company_payment_setting['is_ozow_enabled']))
                                        <div class="tab-pane fade" id="ozow-payment" role="tabpanel"
                                            aria-labelledby="ozow-payment">
                                            <form class="w3-container w3-display-middle w3-card-4" method="POST"
                                                id="ozow-payment-form"
                                                action="{{ route('retainer.with.ozow') }}">
                                                @csrf
                                                <input type="hidden" name="retainer_id" class="ozow_retainer_id"
                                                    value="{{ \Illuminate\Support\Facades\Crypt::encrypt($retainer->id) }}">
                                                <div class="form-group col-md-12">
                                                    <label for="amount">{{ __('Amount') }}</label>
                                                    <div class="input-group">
                                                        <span class="input-group-prepend">
                                                            <span
                                                                class="input-group-text">{{ $setting['site_currency'] }}
                                                            </span>
                                                        </span>
                                                        <input class="form-control ozow_amount" required="required" min="0"
                                                            name="amount" type="number"
                                                            value="{{ $retainer->getDue() }}" min="0"
                                                            step="0.01" max="{{ $retainer->getDue() }}"
                                                            id="amount">
                                                    </div>
                                                </div>
                                                <div class="col-12 form-group mt-3 text-end">
                                                    <button class="btn btn-sm btn-primary m-r-10" type="submit" id="pay_with_ozow">
                                                        {{ __('Make Payment') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif


                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    {{-- @endauth --}}

@endsection
