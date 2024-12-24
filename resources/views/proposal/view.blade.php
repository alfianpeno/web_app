@extends('layouts.admin')
@section('page-title')
    {{ __('Proposal Detail') }}
@endsection
@php
    $settings = App\Models\Utility::settings();
@endphp
@push('script-page')
    <script>
        $(document).on('change', '.status_change', function() {
            var status = this.value;
            var url = $(this).data('url');
            $.ajax({
                url: url + '?status=' + status,
                type: 'GET',
                cache: false,
                success: function(data) {
                    location.reload();
                },
            });
        });

        $('.cp_link').on('click', function() {
            var value = $(this).attr('data-link');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(value).select();
            document.execCommand("copy");
            $temp.remove();
            show_toastr('success', '{{ __('Link Copy on Clipboard') }}')
        });
    </script>
@endpush
@section('breadcrumb')
    @if (\Auth::guard('customer')->check())
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">{{ __('Dashboard') }}</a></li>
    @else
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    @endif
    @if (\Auth::user()->type == 'company')
        <li class="breadcrumb-item"><a href="{{ route('proposal.index') }}">{{ __('Proposal') }}</a></li>
    @else
        <li class="breadcrumb-item"><a href="{{ route('customer.proposal') }}">{{ __('Proposal') }}</a></li>
    @endif
    <li class="breadcrumb-item">{{ AUth::user()->proposalNumberFormat($proposal->proposal_id) }}</li>
@endsection

@section('action-btn')
    <div class="d-flex">


        @if ($proposal->is_convert == 0)
            @if ($proposal->converted_invoice_id == 0)
                @can('convert retainer proposal')
                    <div class="action-btn me-2">
                        {!! Form::open([
                            'method' => 'get',
                            'class' => ' btn btn-sm btn-primary align-items-center',
                            'route' => ['proposal.convert', $proposal->id],
                            'id' => 'proposal-form-' . $proposal->id,
                        ]) !!}
                        <a href="#" class="bs-pass-para bg-success" data-bs-toggle="tooltip"
                            title="{{ __('Convert into Retainer') }}" data-original-title="{{ __('Convert to Retainer') }}"
                            data-original-title="{{ __('Delete') }}"
                            data-confirm="{{ __('You want to confirm convert to invoice. Press Yes to continue or Cancel to go back') }}"
                            data-confirm-yes="document.getElementById('proposal-form-{{ $proposal->id }}').submit();">
                            <i class="ti ti-exchange text-white"></i>
                            {!! Form::close() !!}
                        </a>
                    </div>
                @endcan
            @endif
        @else
            @if ($proposal->converted_invoice_id == 0)
                @can('convert retainer proposal')
                    <div class="action-btn me-2">
                        <a href="{{ route('retainer.show', \Crypt::encrypt($proposal->converted_retainer_id)) }}"
                            class="btn btn-sm btn-primary align-items-center bg-success" data-bs-toggle="tooltip"
                            title="{{ __('Already convert to Retainer') }}">
                            <i class="ti ti-eye text-white"></i>
                        </a>
                    </div>
                @endcan
            @endif
        @endif



        @if ($proposal->converted_invoice_id == 0)
            @if ($proposal->is_convert == 0)
                @can('convert retainer proposal')
                    <div class="action-btn me-2">
                        {!! Form::open([
                            'method' => 'get',
                            'class' => ' btn btn-sm btn-warning align-items-center',
                            'route' => ['proposal.convertinvoice', $proposal->id],
                            'id' => 'proposal-form-' . $proposal->id,
                        ]) !!}
                        <a href="#" class="bs-pass-para bg-warning" data-bs-toggle="tooltip"
                            title="{{ __('Convert into Invoice') }}" data-original-title="{{ __('Convert to Retainer') }}"
                            data-original-title="{{ __('Delete') }}"
                            data-confirm="{{ __('You want to confirm convert to invoice. Press Yes to continue or Cancel to go back') }}"
                            data-confirm-yes="document.getElementById('proposal-form-{{ $proposal->id }}').submit();">
                            <i class="ti ti-exchange text-white"></i>
                            {!! Form::close() !!}
                        </a>
                    </div>
                @endcan
            @endif
        @else
            @can('show invoice')
                <div class="action-btn me-2">
                    <a href="{{ route('invoice.show', \Crypt::encrypt($proposal->converted_invoice_id)) }}"
                        class="btn btn-sm btn-warning align-items-center bg-warning " data-bs-toggle="tooltip"
                        title="{{ __('Already convert to Invoice') }}">
                        <i class="ti ti-eye text-white"></i>
                    </a>
                </div>
            @endcan
        @endif


        <a href="#" class="btn btn-sm btn-primary align-items-center cp_link"
            data-link="{{ route('pay.proposalpay', \Illuminate\Support\Facades\Crypt::encrypt($proposal->id)) }}"
            data-bs-toggle="tooltip" title="{{ __('Copy proposal') }}"
            data-original-title="{{ __('Click to copy invoice link') }}">
            <span class="btn-inner--icon text-white"><i class="ti ti-file"></i></span>
        </a>
    </div>
@endsection

@section('content')

    @can('send proposal')
        @if ($proposal->status != 4)
            <div class="row">
                <div class="col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row timeline-wrapper">
                                <div class="col-md-6 col-lg-4 col-xl-4">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-plus text-primary"></i>
                                    </div>
                                    <h6 class="text-primary my-3">{{ __('Create Proposal') }}</h6>
                                    <p class="text-muted text-sm mb-3"><i
                                            class="ti ti-clock mr-2"></i>{{ __('Created on ') }}{{ \Auth::user()->dateFormat($proposal->issue_date) }}
                                    </p>
                                    @can('edit proposal')
                                        <a href="{{ route('proposal.edit', \Crypt::encrypt($proposal->id)) }}"
                                            class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                            data-original-title="{{ __('Edit') }}"><i
                                                class="ti ti-pencil mr-2"></i>{{ __('Edit') }}</a>
                                    @endcan
                                </div>
                                <div class="col-md-6 col-lg-4 col-xl-4">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-mail text-warning"></i>
                                    </div>
                                    <h6 class="text-warning my-3">{{ __('Send Proposal') }}</h6>
                                    <p class="text-muted text-sm mb-3">
                                        @if ($proposal->status != 0)
                                            <i class="ti ti-clock mr-2"></i>{{ __('Sent on') }}
                                            {{ \Auth::user()->dateFormat($proposal->send_date) }}
                                        @else
                                            @can('send proposal')
                                                <small>{{ __('Status') }} : {{ __('Not Sent') }}</small>
                                            @endcan
                                        @endif
                                    </p>

                                    @if ($proposal->status == 0)
                                        @can('send proposal')
                                            <a href="{{ route('proposal.sent', $proposal->id) }}" class="btn btn-sm btn-warning"
                                                data-bs-toggle="tooltip" data-original-title="{{ __('Mark Sent') }}"><i
                                                    class="ti ti-send mr-2"></i>{{ __('Send') }}</a>
                                        @endcan
                                    @endif
                                </div>
                                <div class="col-md-6 col-lg-4 col-xl-4">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-report-money text-info"></i>
                                    </div>
                                    <h6 class="text-info my-3">{{ __('Proposal Status') }}</h6>
                                    <small>
                                        @if ($proposal->status == 0)
                                            <span
                                                class="badge fix_badge bg-primary p-2 px-3 ">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @elseif($proposal->status == 1)
                                            <span
                                                class="badge fix_badge bg-info p-2 px-3 ">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @elseif($proposal->status == 2)
                                            <span
                                                class="badge fix_badge bg-secondary p-2 px-3 ">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @elseif($proposal->status == 3)
                                            <span
                                                class="badge fix_badge bg-warning p-2 px-3 ">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @elseif($proposal->status == 4)
                                            <span
                                                class="badge fix_badge bg-danger p-2 px-3 ">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @endif
                                    </small>
                                    <br>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    @if (\Auth::user()->type == 'company')
        @if ($proposal->status == 0)
            <div class="row col-12 d-flex justify-content-md-end mb-2 ">
                <div class="float-right a col-md-2 float-end ml-5" data-bs-toggle="tooltip"
                    data-original-title="{{ __('Click to change status') }}">
                    <select class="form-control status_change" name="status"
                        data-url="{{ route('proposal.status.change', $proposal->id) }}">
                        @foreach ($status as $k => $val)
                            <option value="{{ $k }}" {{ $proposal->status == $k ? 'selected' : '' }}>
                                {{ $val }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            <div class="float-right col-md-2 float-end ml-5" data-bs-toggle="tooltip"
                data-original-title="{{ __('Click to change status') }}">
                <select class="form-control status_change" name="status"
                    data-url="{{ route('proposal.status.change', $proposal->id) }}">
                    @foreach ($status as $k => $val)
                        <option value="{{ $k }}" {{ $proposal->status == $k ? 'selected' : '' }}>
                            {{ $val }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($proposal->status != 0)
            <div class="row justify-content-between align-items-center mb-3">
                <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                    <div class="all-button-box mx-2">
                        <a href="{{ route('proposal.resent', $proposal->id) }}"
                            class="btn btn-xs btn-primary btn-icon-only width-auto">{{ __('Resend Proposal') }}</a>
                    </div>
                    <div class="all-button-box">
                        <a href="{{ route('proposal.pdf', Crypt::encrypt($proposal->id)) }}"
                            class="btn btn-xs btn-primary btn-icon-only width-auto"
                            target="_blank">{{ __('Download') }}</a>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="row  justify-content-between align-items-center mb-3">
            <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                <div class="all-button-box">
                    <a href="{{ route('proposal.pdf', Crypt::encrypt($proposal->id)) }}"
                        class="btn btn-xs btn-primary btn-icon-only width-auto" target="_blank">{{ __('Download') }}</a>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h2>{{ __('Proposal') }}</h2>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h3 class="invoice-number">
                                        {{ Auth::user()->proposalNumberFormat($proposal->proposal_id) }}</h3>
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
                                                {{ \Auth::user()->dateFormat($proposal->issue_date) }}<br><br>
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
                                            {{!empty($customer->billing_name)?$customer->billing_name:''}}<br>
                                            {{!empty($customer->billing_address)?$customer->billing_address:''}}<br>
                                            {{!empty($customer->billing_city)?$customer->billing_city:'' .', '}}, {{!empty($customer->billing_state)?$customer->billing_state:'',', '}} {{!empty($customer->billing_zip)?$customer->billing_zip:''}}<br>
                                            {{!empty($customer->billing_country)?$customer->billing_country:''}}<br>
                                            {{!empty($customer->billing_phone)?$customer->billing_phone:''}}<br>
                                            @if (App\Models\Utility::getValByName('tax_number') == 'on')
                                                <strong>{{ __('Tax Number ') }} :
                                                </strong>{{ !empty($customer->tax_number) ? $customer->tax_number : '' }}<br>
                                            @endif
                                            @if(!empty($settings['tax_type']) && !empty($settings['vat_number'])){{$settings['tax_type'].' '. __('Number')}} : {{$settings['vat_number']}} <br>@endif
                                        </small>
                                    </div>
                                @endif


                                @if (App\Models\Utility::getValByName('shipping_display') == 'on')
                                    <div class="col">
                                        <small>
                                            <strong>{{ __('Shipped To') }} :</strong><br>
                                            {{!empty($customer->shipping_name)?$customer->shipping_name:''}}<br>
                                            {{!empty($customer->shipping_address)?$customer->shipping_address:''}}<br>
                                            {{!empty($customer->shipping_city)?$customer->shipping_city:'' . ', '}}, {{!empty($customer->shipping_state)?$customer->shipping_state:'' .', '}} {{!empty($customer->shipping_zip)?$customer->shipping_zip:''}}<br>
                                            {{!empty($customer->shipping_country)?$customer->shipping_country:''}}<br>
                                            {{!empty($customer->shipping_phone)?$customer->shipping_phone:''}}<br>
                                        </small>
                                    </div>
                                @endif
                                <div class="col">
                                    <div class="float-end mt-3">
                                        @if ($settings['proposal_qr_display'] == 'on')
                                        {!! DNS2D::getBarcodeHTML(
                                            route('pay.proposalpay', \Illuminate\Support\Facades\Crypt::encrypt($proposal->id)),
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
                                        @if ($proposal->status == 0)
                                            <span
                                                class="badge fix_badge bg-primary p-2 px-3 ">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @elseif($proposal->status == 1)
                                            <span
                                                class="badge fix_badge bg-info p-2 px-3 ">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @elseif($proposal->status == 2)
                                            <span
                                                class="badge fix_badge bg-secondary p-2 px-3 ">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @elseif($proposal->status == 3)
                                            <span
                                                class="badge fix_badge bg-warning p-2 px-3 ">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @elseif($proposal->status == 4)
                                            <span
                                                class="badge fix_badge bg-danger p-2 px-3 ">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @endif
                                    </small>
                                </div>


                            </div>

                            @if (!empty($customFields) && count($proposal->customField) > 0)
                                @foreach ($customFields as $field)
                                    <div class="col text-end">
                                        <small>
                                            <strong>{{ $field->name }} :</strong><br>
                                            {{ !empty($proposal->customField) ? $proposal->customField[$field->id] : '-' }}
                                            <br><br>
                                        </small>
                                    </div>
                                @endforeach
                            @endif
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-weight-bold">{{ __('Product Summary') }}</div>
                                    <small>{{ __('All items here cannot be deleted.') }}</small>
                                    <div class="table-responsive mt-2">
                                        <table class="table mb-0 ">
                                            <tr>
                                                <th class="text-dark" data-width="40">#</th>
                                                <th class="text-dark">{{ __('Product') }}</th>
                                                <th class="text-dark">{{ __('Quantity') }}</th>
                                                <th class="text-dark">{{ __('Rate') }}</th>
                                                <th class="text-dark"> {{ __('Discount') }}</th>
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
                                                    <td>{{ $iteam->quantity }} ({{ $iteam->product->unit->name }})</td>
                                                    <td>{{ \Auth::user()->priceFormat($iteam->price) }}</td>
                                                    <td>
                                                        {{ \Auth::user()->priceFormat($iteam->discount) }}
                                                    </td>

                                                    <td>
                                                        @if (!empty($iteam->tax))
                                                            <table>
                                                                @php $totalTaxRate = 0;@endphp
                                                                @foreach ($taxes as $tax)
                                                                    @php
                                                                        $taxPrice = App\Models\Utility::taxRate($tax->rate, $iteam->price, $iteam->quantity, $iteam->discount);
                                                                        $totalTaxPrice += $taxPrice;
                                                                    @endphp
                                                                    <tr>
                                                                        <td>{{ $tax->name . ' (' . $tax->rate . '%)' }}
                                                                        </td>
                                                                        <td>{{ \Auth::user()->priceFormat($taxPrice) }}
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
                                                        {{ \Auth::user()->priceFormat($iteam->price * $iteam->quantity) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tfoot>
                                                <tr>

                                                    <td></td>
                                                    <td><b>{{ __('Total') }}</b></td>
                                                    <td><b>{{ $totalQuantity }}</b></td>
                                                    <td><b>{{ \Auth::user()->priceFormat($totalRate) }}</b></td>
                                                    <td><b>{{ \Auth::user()->priceFormat($totalDiscount) }}</b>
                                                    <td><b>{{ \Auth::user()->priceFormat($totalTaxPrice) }}</b></td>


                                                    {{-- <td></td>
                                                <td><b>{{__('Total')}}</b></td>
                                                <td><b>{{$totalQuantity}}</b></td>
                                                <td><b>{{\Auth::user()->priceFormat($totalRate)}}</b></td>
                                                <td><b>{{\Auth::user()->priceFormat($totalTaxPrice)}}</b></td> --}}
                                                    {{-- <td>

                                                        <b>{{\Auth::user()->priceFormat($totalDiscount)}}</b>

                                                </td> --}}
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Sub Total') }}</b></td>
                                                    <td class="text-end">
                                                        {{ \Auth::user()->priceFormat($proposal->getSubTotal()) }}</td>
                                                </tr>

                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{ __('Discount') }}</b></td>
                                                    <td class="text-end">
                                                        {{ \Auth::user()->priceFormat($proposal->getTotalDiscount()) }}
                                                    </td>
                                                </tr>

                                                @if (!empty($taxesData))
                                                    @php $totalTaxRate = 0;@endphp
                                                    @foreach ($taxes as $tax)
                                                        @php
                                                            $taxPrice = App\Models\Utility::taxRate($tax->rate, $iteam->price, $iteam->quantity, $iteam->discount);
                                                            $totalTaxPrice += $taxPrice;
                                                        @endphp
                                                        <tr>
                                                            <td colspan="6"></td>
                                                            <td class="text-end"><b>{{ $tax->name }}</b></td>
                                                            <td class="text-end">
                                                                {{ \Auth::user()->priceFormat($taxPrice) }}</td>
                                                            {{-- <td class="text-end"><b>{{$taxName}}</b></td>
                                                            <td class="text-end">{{ \Auth::user()->priceFormat($taxPrice) }}</td> --}}
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                {{-- @if (!empty($taxesData))
                                                    @foreach ($taxesData as $taxName => $taxPrice)
                                                        <tr>
                                                            <td colspan="6"></td>
                                                            <td class="text-end"><b>{{$taxName}}</b></td>
                                                            <td class="text-end">{{ \Auth::user()->priceFormat($taxPrice) }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif --}}
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="blue-text text-end"><b>{{ __('Total') }}</b></td>
                                                    <td class="blue-text text-end">
                                                        {{ \Auth::user()->priceFormat($proposal->getTotal()) }}</td>
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
    </div>

@endsection
