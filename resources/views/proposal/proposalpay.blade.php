@php
    use App\Models\Utility;
    $settings = App\Models\Utility::settingsById($proposal->created_by);
@endphp
@extends('layouts.invoicepayheader')
@section('page-title')
    {{__('Proposal Detail')}}
@endsection
@push('script-page')
    <script>
        $(document).on('change', '.status_change', function () {
            var status = this.value;
            var url = $(this).data('url');
            $.ajax({
                url: url + '?status=' + status,
                type: 'GET',
                cache: false,
                success: function (data) {
                },
            });
        });
    </script>
@endpush
@section('content')
    @php
        $customer=$proposal->customer;
    @endphp

    @if(\Auth::check() && isset(\Auth::user()->type) && \Auth::user()->type=='company')
        @if($proposal->status!=0)
            <div class="row justify-content-between align-items-center mb-3">
                <div class="col-10 offset-1 d-flex align-items-center justify-content-between justify-content-md-end">
                    {{-- <div class="all-button-box mx-2">
                        <a href="{{ route('proposal.resent',$proposal->id) }}" class="btn btn-xs btn-white btn-icon-only width-auto">{{__('Resend Proposal')}}</a>
                    </div> --}}
                    <div class="all-button-box">
                        <a href="{{ route('proposal.pdf', Crypt::encrypt($proposal->id))}}" class="btn btn-primary" target="_blank">{{__('Download')}}</a>
                    </div>
                </div>
            </div>
        @endif
      @else
        <div class="row justify-content-between align-items-center mb-3">
            <div class="col-10 offset-1 d-flex align-items-center justify-content-between justify-content-md-end">
                <div class="all-button-box">
                    <a href="{{ route('proposal.pdf', Crypt::encrypt($proposal->id))}}" class="btn btn-primary" target="_blank">{{__('Download')}}</a>
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
                                    <h4>{{__('Proposal')}}</h4>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h4 class="invoice-number">{{ Utility::proposalNumberFormat($company_setting,$proposal->proposal_id) }}</h4>
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
                                                @if(\Auth::check())
                                                {{\App\Models\User::dateFormat($proposal->issue_date)}}<br><br>
                                                @endif
                                            </small>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                @if(!empty($customer->billing_name))
                                    <div class="col">
                                        <small class="font-style">
                                            <strong>{{__('Billed To')}} :</strong><br>
                                            {{!empty($customer->billing_name)?$customer->billing_name:''}}<br>
                                            {{!empty($customer->billing_address)?$customer->billing_address:''}}<br>
                                            {{!empty($customer->billing_city)?$customer->billing_city:'' .', '}}, {{!empty($customer->billing_state)?$customer->billing_state:'',', '}} {{!empty($customer->billing_zip)?$customer->billing_zip:''}}<br>
                                            {{!empty($customer->billing_country)?$customer->billing_country:''}}<br>
                                            {{!empty($customer->billing_phone)?$customer->billing_phone:''}}<br>
                                            @if(!empty($settings['tax_type']) && !empty($settings['vat_number'])){{$settings['tax_type'].' '. __('Number')}} : {{$settings['vat_number']}} <br>@endif
                                        </small>
                                    </div>
                                @endif

                                @if(App\Models\Utility::getValByName('shipping_display')=='on')
                                    <div class="col">
                                        <small>
                                            <strong>{{__('Shipped To')}} :</strong><br>
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
                                            @if (isset($settings['proposal_qr_display']) && $settings['proposal_qr_display'] == 'on')
                                            <p> {!! DNS2D::getBarcodeHTML(route('pay.proposalpay',\Illuminate\Support\Facades\Crypt::encrypt($proposal->id)), "QRCODE",2,2) !!}</p>
                                            @endif
                                        </div>
                                    </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <small>
                                        <strong>{{__('Status')}} :</strong><br>
                                        @if($proposal->status == 0)
                                            <span class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @elseif($proposal->status == 1)
                                            <span class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @elseif($proposal->status == 2)
                                            <span class="badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @elseif($proposal->status == 3)
                                            <span class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @elseif($proposal->status == 4)
                                            <span class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                        @endif
                                    </small>
                                </div>



                                @if(!empty($customFields) && count($proposal->customField)>0)
                                    @foreach($customFields as $field)
                                        <div class="col text-end">
                                            <small>
                                                <strong>{{$field->name}} :</strong><br>
                                                {{!empty($proposal->customField)?$proposal->customField[$field->id]:'-'}}
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
                                    <div class="table-responsive mt-2">
                                        <table class="table mb-0 ">
                                            <tr>
                                                <th class="text-dark" data-width="40">#</th>
                                                <th class="text-dark">{{__('Product')}}</th>
                                                <th class="text-dark">{{__('Quantity')}}</th>
                                                <th class="text-dark">{{__('Rate')}}</th>
                                                <th class="text-dark"> {{__('Discount')}}</th>
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

                                            @foreach($item as $key =>$iteam)
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
                                                    <td>{{!empty($iteam->product)?$iteam->product->name:''}}</td>
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

                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-end"><b>{{__('Sub Total')}}</b></td>
                                                <td class="text-end">{{utility::priceFormat($company_setting,$proposal->getSubTotal())}}</td>
                                            </tr>

                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{__('Discount')}}</b></td>
                                                    <td class="text-end">{{utility::priceFormat($company_setting,$proposal->getTotalDiscount())}}</td>
                                                </tr>

                                            {{-- @if(!empty($taxesData))
                                                @foreach($taxesData as $taxName => $taxPrice)
                                                    <tr>
                                                        <td colspan="6"></td>
                                                        <td class="text-end"><b>{{$taxName}}</b></td>
                                                        <td class="text-end">{{utility::priceFormat($company_setting,$taxPrice) }}</td>
                                                    </tr>
                                                @endforeach
                                            @endif --}}
                                            @if(!empty($taxesData))
                                                @php $totalTaxRate = 0;@endphp
                                                @foreach($taxes as $tax)
                                                    @php
                                                        $taxPrice=App\Models\Utility::taxRate($tax->rate,$iteam->price,$iteam->quantity,$iteam->discount);
                                                        $totalTaxPrice+=$taxPrice;
                                                        $user = \App\Models\User::where('id',$tax->created_by)->first();
                                                    @endphp
                                                    <tr>
                                                        <td colspan="6"></td>
                                                        <td class="text-end"><b>{{$tax->name}}</b></td>
                                                        <td class="text-end">{{$user->priceFormat($taxPrice)}}</td>
                                                        {{-- <td class="text-end"><b>{{$taxName}}</b></td>
                                                        <td class="text-end">{{ \Auth::user()->priceFormat($taxPrice) }}</td> --}}
                                                    </tr>
                                                @endforeach
                                            @endif
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="blue-text text-end"><b>{{__('Total')}}</b></td>
                                                <td class="blue-text text-end">{{utility::priceFormat($company_setting,$proposal->getTotal())}}</td>
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
