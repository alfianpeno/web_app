@extends('layouts.admin')

@section('page-title')
    {{ __('Customer Statement') }}
@endsection

@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        var filename = $('#filename').val();

        function saveAsPDF() {
            var element = document.getElementById('printableArea');
            var opt = {
                margin: 0.3,
                filename: filename,
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 4,
                    dpi: 72,
                    letterRendering: true
                },
                jsPDF: {
                    unit: 'in',
                    format: 'A4'
                }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customer.index') }}">{{ __('Customer') }}</a></li>
    <li class="breadcrumb-item"><a
            href="{{ route('customer.show', \Crypt::encrypt($customer['id'])) }}">{{ $customer['name'] }}</a></li>
    <li class="breadcrumb-item">{{ __('Customer Statement') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()" data-bs-toggle="tooltip"
            title="{{ __('Download') }}">
            <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12 col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                {{ Form::model($customerDetail, ['route' => ['customer.statement', $customer->id], 'method' => 'post']) }}
                                <div class="row">
                                    <div class=" d-flex align-items-end justify-content-start">
                                        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                            <div class="btn-box">
                                                {{ Form::label('from_date', __('From Date'), ['class' => 'form-label']) }}<span
                                                    class="text-danger">*</span>
                                                {{ Form::date('from_date', isset($data['from_date']) ? $data['from_date'] : null, ['class' => 'form-control', 'required' => 'required']) }}
                                            </div>
                                        </div>
                                        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                            <div class="btn-box">
                                                {{ Form::label('until_date', __('Until Date'), ['class' => 'form-label']) }}<span
                                                    class="text-danger">*</span>
                                                {{ Form::date('until_date', isset($data['until_date']) ? $data['until_date'] : null, ['class' => 'form-control', 'required' => 'required']) }}
                                            </div>
                                        </div>

                                        <div class="col-xl-auto d-flex align-items-center justify-content-between col-lg-3 col-md-6 col-sm-12 col-12 mr-2"
                                            style="max-width: 980px; width:100%;">
                                            <div class="btn-box ">
                                                <input type="submit" value="{{ __('Apply') }}"
                                                    class="btn ms-2 btn btn-primary">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{ Form::close() }}
                            </div>
                            <span id="printableArea">
                                <div class="col-12 text-center mt-4">
                                    <strong>
                                        <h5>{{ __('Customer Statement for Aaron Clark') }}</h5>
                                    </strong>
                                    <strong>{{ $data['from_date'] . '  ' . 'to' . '  ' . $data['until_date'] }}</strong>
                                </div>
                                <div class="col-12">
                                    <hr>
                                </div>

                                <div class="row">
                                    <div class="col-md-8">
                                        <img src="{{ $img }}" style="max-width: 250px" />
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <strong class="invoice-number">{{ $settings['company_name'] ?? '' }}</strong><br>
                                        <strong class="invoice-number">{{ $settings['company_email'] ?? '' }}</strong><br>
                                        <strong
                                            class="invoice-number">{{ $settings['company_address'] ?? '' }}</strong><br>
                                        <strong class="invoice-number">{{ $settings['company_city'] ?? '' }}</strong>,
                                        <strong class="invoice-number">{{ $settings['company_state'] ?? '' }}</strong><br>
                                        <strong class="invoice-number">{{ $settings['company_zipcode'] ?? '' }}</strong>,
                                        <strong
                                            class="invoice-number">{{ $settings['company_country'] ?? '' }}</strong><br>
                                        <strong
                                            class="invoice-number">{{ $settings['company_telephone'] ?? '' }}</strong><br>
                                    </div>
                                </div><br>
                                <div class="row justify-content-end">
                                    <div class="col-md-auto text-end">
                                        <strong>
                                            <h5>{{ __('Statement of Accounts') }}</h5>
                                            <hr class="text-dark text-end my-2">
                                        </strong>
                                        <strong>{{ $data['from_date'] . '  ' . 'to' . '  ' . $data['until_date'] }}</strong>
                                        <hr class="text-dark my-2">
                                    </div>
                                </div><br><br>
                                <div class="row">
                                    @if (!empty($customer->billing_name))
                                        <div class="col-md-4">
                                            <small class="font-style">
                                                <strong>{{ __('Billed To') }} :</strong><br>
                                                {{ !empty($customer->billing_name) ? $customer->billing_name : '' }}<br>
                                                {{ !empty($customer->billing_address) ? $customer->billing_address : '' }}<br>
                                                {{ !empty($customer->billing_city) ? $customer->billing_city : '' . ', ' }},
                                                {{ !empty($customer->billing_state) ? $customer->billing_state : '', ', ' }}
                                                {{ !empty($customer->billing_zip) ? $customer->billing_zip : '' }}<br>
                                                {{ !empty($customer->billing_country) ? $customer->billing_country : '' }}<br>
                                                {{ !empty($customer->billing_phone) ? $customer->billing_phone : '' }}<br>
                                                @if (App\Models\Utility::getValByName('tax_number') == 'on')
                                                    <strong>{{ __('Tax Number ') }} :
                                                    </strong>{{ !empty($customer->tax_number) ? $customer->tax_number : '' }}
                                                @endif

                                            </small>
                                        </div>
                                    @endif
                                    @if (\App\Models\Utility::getValByName('shipping_display') == 'on')
                                        <div class="col-md-4 ">
                                            <small>
                                                <strong>{{ __('Shipped To') }} :</strong><br>
                                                {{ !empty($customer->shipping_name) ? $customer->shipping_name : '' }}<br>
                                                {{ !empty($customer->shipping_address) ? $customer->shipping_address : '' }}<br>
                                                {{ !empty($customer->shipping_city) ? $customer->shipping_city : '' . ', ' }},
                                                {{ !empty($customer->shipping_state) ? $customer->shipping_state : '' . ', ' }}
                                                {{ !empty($customer->shipping_zip) ? $customer->shipping_zip : '' }}<br>
                                                {{ !empty($customer->shipping_country) ? $customer->shipping_country : '' }}<br>
                                                {{ !empty($customer->shipping_phone) ? $customer->shipping_phone : '' }}<br>
                                                @if (App\Models\Utility::getValByName('tax_number') == 'on')
                                                    <strong>{{ __('Tax Number ') }} :
                                                    </strong>{{ !empty($customer->tax_number) ? $customer->tax_number : '' }}
                                                @endif
                                            </small>
                                        </div>
                                    @endif
                                    @php
                                        $total = 0;
                                        $total1 = 0;
                                        $total3 = 0;
                                    @endphp
                                    @foreach ($invoice_payment as $key => $payment)
                                        @php
                                            $total += $payment->amount;
                                            $total1 += $invoice_total->getTotal();
                                            $total3 = $total1 + $total;
                                        @endphp
                                    @endforeach
                                    <div class="col-md-4">
                                        <div class="table-responsive">
                                            <table class="table table_header">
                                                <thead>
                                                    <tr>
                                                        <th>{{ 'Account Summary' }}</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="list">
                                                    <tr>
                                                        <td>{{ 'Invoiced Amount' }}</td>
                                                        <td class="text-end">{{ \Auth::user()->priceFormat($total1) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{ 'Amount Paid' }}</td>
                                                        <td class="text-end">{{ \Auth::user()->priceFormat($total) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{ 'Balance Due' }}</td>
                                                        <td class="text-end">{{ \Auth::user()->priceFormat($total3) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mt-4" style="box-shadow: none">
                                    <div class="card-body table-border-styletable-border-style">
                                        <div class="table-responsive">
                                            <table class="table align-items-center table_header">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">{{ __('Date') }}</th>
                                                        <th scope="col">{{ __('Invoice') }}</th>
                                                        <th scope="col">{{ __('Payment Type') }}</th>
                                                        <th scope="col">{{ __('Invoice Total') }}</th>
                                                        <th scope="col">{{ __('Amount') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="list">
                                                    @php
                                                        $total = 0;
                                                        $total1 = 0;
                                                    @endphp
                                                    @forelse($invoice_payment as $payment)
                                                        <tr>
                                                            <td>{{ \Auth::user()->dateFormat($payment->date) }} </td>
                                                            <td>{{ \Auth::user()->invoiceNumberFormat($payment->id) }}
                                                            </td>
                                                            <td>{{ $payment->payment_type }} </td>
                                                            <td>{{ \Auth::user()->priceFormat($invoice_total->getTotal()) }}
                                                            </td>
                                                            <td> {{ \Auth::user()->priceFormat($payment->amount) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="text-center text-dark">
                                                                <p>{{ __('No Data Found') }}</p>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                    <tr class="total">
                                                        <td class="light_blue">
                                                            <span></span><strong>{{ __('TOTAL :') }}</strong>
                                                        </td>
                                                        <td class="light_blue"></td>
                                                        <td class="light_blue"></td>
                                                        @foreach ($invoice_payment as $key => $payment)
                                                            @php
                                                                $total += $payment->amount;
                                                                $total1 += $invoice_total->getTotal();
                                                            @endphp
                                                        @endforeach
                                                        <td class="light_blue">
                                                            <span></span><strong>{{ \Auth::user()->priceFormat($total1) }}</strong>
                                                        </td>
                                                        <td class="light_blue">
                                                            <span></span><strong>{{ \Auth::user()->priceFormat($total) }}</strong>
                                                        </td>
                                                    </tr>
                                                    </tfoot>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
