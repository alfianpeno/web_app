@php
    use App\Models\Utility;
    $settings = App\Models\Utility::settingsById($bill->created_by);
@endphp
@extends('layouts.invoicepayheader')
@section('page-title')
    {{__('Bill Detail')}}
@endsection
@push('script-page')
    <script>
        $(document).on('click', '#shipping', function () {
            var url = $(this).data('url');
            var is_display = $("#shipping").is(":checked");
            $.ajax({
                url: url,
                type: 'get',
                data: {
                    'is_display': is_display,
                },
                success: function (data) {
                    // console.log(data);
                }
            });
        })
    </script>
@endpush
@section('content')
    @php
        $vendor=$bill->vender;
    @endphp

    @if(\Auth::check() && isset(\Auth::user()->type) && \Auth::user()->type=='company')
        @if($bill->status!=0)
            <div class="row justify-content-between align-items-center mb-3">
                <div class="col-10 offset-1 d-flex align-items-center justify-content-between justify-content-md-end">
                    @if(!empty($billPayment))
                        <div class="all-button-box mx-2">
                            <a href="#" data-url="{{ route('bill.debit.note',$bill->id) }}" data-ajax-popup="true" data-title="{{__('Add Debit Note')}}" class="btn btn-primary">
                                {{__('Add Debit Note')}}
                            </a>
                        </div>
                    @endif
                    <div class="all-button-box mx-2">
                        <a href="{{ route('bill.resent',$bill->id) }}" class="btn btn-primary">
                            {{__('Resend Bill')}}
                        </a>
                    </div>
                    <div class="all-button-box">
                        <a href="{{ route('bill.pdf', Crypt::encrypt($bill->id))}}" target="_blank" class="btn btn-primary">
                            {{__('Download')}}
                        </a>
                    </div>
                    {{-- @if($bill->getDue() > 0)
                    <a href="#" data-toggle="modal" data-target="#paymentModal" class="btn btn-xs btn-white btn-icon-only width-auto">
                        <span class="btn-inner--icon text-white"><i class="fa fa-credit-card"></i></span>
                        <span class="btn-inner--text text-white">{{__(' Pay Now')}}</span>
                    </a>
                    @endif --}}
                </div>
            </div>
        @endif

    @else
        <div class="row justify-content-between align-items-center mb-3">
            <div class="col-10 offset-1 d-flex align-items-center justify-content-between justify-content-md-end">
                <div class="all-button-box mx-2">
                    <a href="#" data-url="{{route('vender.bill.send',$bill->id)}}" data-ajax-popup="true" data-title="{{__('Send Bill')}}" class="btn btn-xs btn-success btn-icon-only width-auto">
                        {{__('Send Mail')}}
                    </a>
                </div>
                <div class="all-button-box mx-2">
                    <a href="{{ route('bill.pdf', Crypt::encrypt($bill->id))}}" target="_blank" class="btn btn-xs btn-success btn-icon-only width-auto">
                        {{__('Download')}}
                    </a>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-10 offset-1">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h2>{{__('Bill')}}</h2>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h3 class="invoice-number">{{ Utility::billNumberFormat($company_setting,$bill->bill_id) }}</h3>

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
                                                <strong>{{__('Issue Date')}} :</strong><br>
                                                @php
                                                    $user = \App\Models\User::where('id',$bill->created_by)->first();
                                                @endphp
                                                {{$user->dateFormat($bill->bill_date)}}<br><br>
                                            </small>
                                        </div>
                                        <div>
                                            <small>
                                                <strong>{{__('Due Date')}} :</strong><br>
                                                @php
                                                    $user = \App\Models\User::where('id',$bill->created_by)->first();
                                                @endphp
                                                {{$user->dateFormat($bill->due_date)}}<br><br>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                @if(!empty($vendor->billing_name))
                                    <div class="col">
                                        <small class="font-style">
                                            <strong>{{__('Billed To')}} :</strong><br>
                                            {{!empty($vendor->billing_name)?$vendor->billing_name:''}}<br>
                                            {{!empty($vendor->billing_address)?$vendor->billing_address:''}}<br>
                                            {{!empty($vendor->billing_city)?$vendor->billing_city:'' .', '}}, {{!empty($vendor->billing_state)?$vendor->billing_state:'',', '}} {{!empty($vendor->billing_zip)?$vendor->billing_zip:''}}<br>
                                            {{!empty($vendor->billing_country)?$vendor->billing_country:''}}<br>
                                            {{!empty($vendor->billing_phone)?$vendor->billing_phone:''}}<br>
                                            @if(!empty($settings['tax_type']) && !empty($settings['vat_number'])){{$settings['tax_type'].' '. __('Number')}} : {{$settings['vat_number']}} <br>@endif
                                        </small>
                                    </div>
                                @endif
                                @if(App\Models\Utility::getValByName('shipping_display')=='on')
                                    <div class="col">
                                        <small>
                                            <strong>{{__('Shipped To')}} :</strong><br>
                                            {{!empty($vendor->shipping_name)?$vendor->shipping_name:''}}<br>
                                            {{!empty($vendor->shipping_address)?$vendor->shipping_address:''}}<br>
                                            {{!empty($vendor->shipping_city)?$vendor->shipping_city:'' .', '}}, {{!empty($vendor->shipping_state)?$vendor->shipping_state:'',', '}} {{!empty($vendor->shipping_zip)?$vendor->shipping_zip:''}}<br>
                                            {{!empty($vendor->shipping_country)?$vendor->shipping_country:''}}<br>
                                            {{!empty($vendor->shipping_phone)?$vendor->shipping_phone:''}}<br>
                                        </small>
                                    </div>
                                @endif
                                     <div class="col">
                                        <div class="float-end mt-3">
                                            @if (isset($settings['bill_qr_display']) && $settings['bill_qr_display'] == 'on')
                                                <p> {!! DNS2D::getBarcodeHTML(route('pay.billpay',\Illuminate\Support\Facades\Crypt::encrypt($bill->id)), "QRCODE",2,2) !!}</p>
                                            @endif
                                        </div>
                                    </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <small>
                                        <strong>{{__('Status')}} :</strong><br>
                                        @if($bill->status == 0)
                                            <span class="badge bg-primary p-2 px-3">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 1)
                                            <span class="badge bg-warning p-2 px-3">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 2)
                                            <span class="badge bg-danger p-2 px-3">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 3)
                                            <span class="badge bg-info p-2 px-3">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 4)
                                            <span class="badge bg-success p-2 px-3">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @endif
                                    </small>
                                </div>


                                @if(!empty($customFields) && count($bill->customField)>0)
                                    @foreach($customFields as $field)
                                        <div class="col text-end">
                                            <small>
                                                <strong>{{$field->name}} :</strong><br>
                                                {{!empty($bill->customField)?$bill->customField[$field->id]:'-'}}
                                                <br><br>
                                            </small>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-weight-bold">{{__('Product Summary')}}</div>
                                    <small>{{__('All items here cannot be deleted.')}}</small>
                                    <div class="table-responsive">
                                        <table class="table ">
                                            <tr>
                                                <th class="text-dark" data-width="40">#</th>
                                                <th class="text-dark">{{__('Product')}}</th>
                                                <th class="text-dark">{{__('Quantity')}}</th>
                                                <th class="text-dark">{{__('Rate')}}</th>
                                                <th class="text-dark">
                                                        {{__('Discount')}}

                                                </th>
                                                <th class="text-dark">{{__('Tax')}}</th>
                                                <th class="text-dark">{{__('Description')}}</th>
                                                <th class="text-end text-dark" width="12%">{{__('Price')}}<br>
                                                    <small class="text-danger font-weight-bold">{{__('before tax & discount')}}</small>
                                                </th>
                                            </tr>
                                            @php
                                                $totalQuantity=0;
                                                $totalRate=0;
                                                $totalTaxPrice=0;
                                                $totalDiscount=0;
                                                $taxesData=[];
                                            @endphp

                                            @foreach($iteams as $key =>$iteam)
                                                @if(!empty($iteam->tax))
                                                    @php
                                                        $taxes=App\Models\Utility::tax($iteam->tax);
                                                        $totalQuantity+=$iteam->quantity;
                                                        $totalRate+=$iteam->price;
                                                        $totalDiscount+=$iteam->discount;
                                                        foreach($taxes as $taxe){
                                                            $taxDataPrice=App\Models\Utility::taxRate($taxe->rate,$iteam->price,$iteam->quantity);
                                                            if (array_key_exists($taxe->name,$taxesData))
                                                            {
                                                                $taxesData[$taxe->name] = $taxesData[$taxe->name]+$taxDataPrice;
                                                            }
                                                            else
                                                            {
                                                                $taxesData[$taxe->name] = $taxDataPrice;
                                                            }
                                                        }
                                                    @endphp
                                                @endif
                                                <tr>
                                                    <td>{{$key+1}}</td>
                                                    <td>{{ !empty($iteam->product) ? $iteam->product->name : '' }}</td>
                                                    <td>{{$iteam->quantity}}</td>
                                                    <td>{{utility::priceFormat($company_setting,$iteam->price)}}</td>
                                                    <td>
                                                        {{utility::priceFormat($company_setting,$iteam->discount)}}
                                                    </td>

                                                    <td>
                                                        @if(!empty($iteam->tax))
                                                            <table>
                                                                @php $totalTaxRate = 0;@endphp
                                                                @foreach($taxes as $tax)
                                                                    @php
                                                                        $taxPrice=App\Models\Utility::taxRate($tax->rate,$iteam->price,$iteam->quantity);
                                                                        $totalTaxPrice+=$taxPrice;
                                                                    @endphp
                                                                    <tr>
                                                                        <td>{{$tax->name .' ('.$tax->rate .'%)'}}</td>
                                                                        <td>{{utility::priceFormat($company_setting,$taxPrice)}}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </table>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>

                                                    <td>{{!empty($iteam->description)?$iteam->description:'-'}}</td>
                                                    <td class="text-end">{{utility::priceFormat($company_setting,($iteam->price*$iteam->quantity))}}</td>
                                                </tr>
                                            @endforeach
                                            <tfoot>
                                            <tr>
                                                <td></td>
                                                <td><b>{{__('Total')}}</b></td>
                                                <td><b>{{$totalQuantity}}</b></td>
                                                <td><b>{{utility::priceFormat($company_setting,$totalRate)}}</b></td>
                                                <td>
                                                        <b>{{utility::priceFormat($company_setting,$totalDiscount)}}</b>

                                                </td>
                                                <td><b>{{utility::priceFormat($company_setting,$totalTaxPrice)}}</b></td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-end"><b>{{__('Sub Total')}}</b></td>
                                                <td class="text-end">{{utility::priceFormat($company_setting,$bill->getSubTotal())}}</td>
                                            </tr>

                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{__('Discount')}}</b></td>
                                                    <td class="text-end">{{utility::priceFormat($company_setting,$bill->getTotalDiscount())}}</td>
                                                </tr>

                                            @if(!empty($taxesData))
                                                @foreach($taxesData as $taxName => $taxPrice)
                                                    <tr>
                                                        <td colspan="6"></td>
                                                        <td class="text-end"><b>{{$taxName}}</b></td>
                                                        <td class="text-end">{{utility::priceFormat($company_setting,$taxPrice) }}</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="blue-text text-end"><b>{{__('Total')}}</b></td>
                                                <td class="blue-text text-end">{{utility::priceFormat($company_setting,$bill->getTotal())}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-end"><b>{{__('Paid')}}</b></td>
                                                <td class="text-end">{{utility::priceFormat($company_setting,($bill->getTotal()-$bill->getDue())-($bill->billTotalDebitNote()))}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-end"><b>{{__('Debit Note')}}</b></td>
                                                <td class="text-end">{{utility::priceFormat($company_setting,($bill->billTotalDebitNote()))}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-end"><b>{{__('Due')}}</b></td>
                                                <td class="text-end">{{utility::priceFormat($company_setting,$bill->getDue())}}</td>
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

        <div class="col-10 offset-1">
            <h5 class="h4 d-inline-block font-weight-400 mb-4">{{__('Payment Summary')}}</h5>
            <div class="card">
                <div class="card-body table-border-style py-0">
                    <div class="table-responsive m-0" >
                        <table class="table ">
                            <tr>
                                <th class="text-dark">{{__('Date')}}</th>
                                <th class="text-dark">{{__('Amount')}}</th>
                                <th class="text-dark">{{__('Account')}}</th>
                                <th class="text-dark">{{__('Reference')}}</th>
                                <th class="text-dark">{{__('Description')}}</th>
                                @can('delete payment bill')
                                    <th class="text-dark">{{__('Action')}}</th>
                                @endcan
                            </tr>
                            @forelse($bill->payments as $key =>$payment)
                                <tr>
                                    <td>{{utility::dateFormat($company_setting,$payment->date)}}</td>
                                    <td>{{utility::priceFormat($company_setting,$payment->amount)}}</td>
                                    <td>{{!empty($payment->bankAccount)?$payment->bankAccount->bank_name.' '.$payment->bankAccount->holder_name:''}}</td>
                                    <td>{{$payment->reference}}</td>
                                    <td>{{$payment->description}}</td>
                                    <td class="text-dark">
                                        @can('delete bill product')
                                            <a href="#" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$payment->id}}').submit();">
                                                <i class="ti ti-trash text-white"></i>
                                            </a>
                                            {!! Form::open(['method' => 'post', 'route' => ['bill.payment.destroy',$bill->id,$payment->id],'id'=>'delete-form-'.$payment->id]) !!}
                                            {!! Form::close() !!}
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-dark"><p>{{__('No Data Found')}}</p></td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-10 offset-1">
            <h5 class="h4 d-inline-block font-weight-400 mb-4">{{__('Debit Note Summary')}}</h5>
            <div class="card">
                <div class="card-body table-border-style py-0">
                    <div class="table-responsive m-0">
                        <table class="table ">
                            <tr>
                                <th class="text-dark">{{__('Date')}}</th>
                                <th class="text-dark">{{__('Amount')}}</th>
                                <th class="text-dark">{{__('Description')}}</th>
                                @if(Gate::check('edit debit note') || Gate::check('delete debit note'))
                                    <th class="text-dark">{{__('Action')}}</th>
                                @endif
                            </tr>
                            @forelse($bill->debitNote as $key =>$debitNote)
                                <tr>
                                    <td>{{utility::dateFormat($company_setting,$debitNote->date)}}</td>
                                    <td>{{utility::priceFormat($company_setting,$debitNote->amount)}}</td>
                                    <td>{{$debitNote->description}}</td>
                                    <td>
                                        @can('edit debit note')
                                            <a data-url="{{ route('bill.edit.debit.note',[$debitNote->bill,$debitNote->id]) }}" data-ajax-popup="true" data-title="{{__('Add Debit Note')}}" href="#" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" data-original-title="{{__('Edit')}}">
                                                <i class="ti ti-pencil text-white"></i>
                                            </a>
                                        @endcan
                                        @can('delete debit note')
                                            <a href="#" class="mx-3 btn btn-sm align-items-center " data-bs-toggle="tooltip" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$debitNote->id}}').submit();">
                                                <i class="ti ti-trash text-white"></i>
                                            </a>
                                            {!! Form::open(['method' => 'DELETE', 'route' => array('bill.delete.debit.note', $debitNote->bill,$debitNote->id),'id'=>'delete-form-'.$debitNote->id]) !!}
                                            {!! Form::close() !!}
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-dark"><p>{{__('No Data Found')}}</p></td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
