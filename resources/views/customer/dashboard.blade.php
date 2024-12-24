@extends('layouts.admin')
@section('page-title')
    {{__('Dashboard')}}
@endsection
{{--{{dd($invoiceChartData['data'])}}--}}
@push('script-page')
    <script>
        (function () {
            var chartBarOptions = {
                series: [
                    {
                        name: "{{__('Unpaid')}}",
                        data: {!! json_encode($invoiceChartData['data']['unpaid']) !!}
                    }, {
                        name: "{{__('Paid')}}",
                        data: {!! json_encode($invoiceChartData['data']['paid']) !!}
                    }, {
                        name: "{{__('Partial Paid')}}",
                        data: {!! json_encode($invoiceChartData['data']['partial']) !!}
                    }, {
                        name: "{{__('Due')}}",
                        data: {!! json_encode($invoiceChartData['data']['due']) !!}
                    },

                ],

                chart: {
                    height: 300,
                    type: 'area',
                    // type: 'line',
                    dropShadow: {
                        enabled: true,
                        color: '#000',
                        top: 18,
                        left: 7,
                        blur: 10,
                        opacity: 0.2
                    },
                    toolbar: {
                        show: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },
                title: {
                    text: '',
                    align: 'left'
                },
                xaxis: {
                    categories: {!! json_encode($invoiceChartData['month']) !!},
                    title: {
                        text: '{{ __("Months") }}'
                    }
                },
                colors: ['#6fd944', '#6fd944'],

                grid: {
                    strokeDashArray: 4,
                },
                legend: {
                    show: false,
                },
                // markers: {
                //     size: 4,
                //     colors: ['#ffa21d', '#FF3A6E'],
                //     opacity: 0.9,
                //     strokeWidth: 2,
                //     hover: {
                //         size: 7,
                //     }
                // },
                yaxis: {
                    title: {
                        text: '{{ __("Amount") }}'
                    },

                }

            };
            var arChart = new ApexCharts(document.querySelector("#chart-sales"), chartBarOptions);
            arChart.render();
        })();
    </script>
@endpush
@section('breadcrumb')
    <!-- <li class="breadcrumb-item"><a href="{{route('customer.dashboard')}}">{{__('Dashboard')}}</a></li> -->
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col">
                            <div class="list-group list-group-flush">
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="flex-fill text-limit">
                                            <h6 class="progress-text mb-1 text-sm d-block text-limit"> {{number_format($invoiceChartData['progressData']['unpaidPr'], (int)App\Models\Utility::getValByName('decimal_number'), '.', '') .' %'}}</h6>
                                            <div class="progress progress-xs mb-0">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: {{$invoiceChartData['progressData']['unpaidPr']}}%;" aria-valuenow="{{$invoiceChartData['progressData']['unpaidPr']}}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="d-flex justify-content-between text-xs text-muted text-right mt-1">
                                                <div>
                                                    <span class="font-bold text-danger">{{__('Unpaid')}}</span>
                                                </div>
                                                <div>
                                                    {{$invoiceChartData['progressData']['totalInvoice'] .'/'.$invoiceChartData['progressData']['totalUnpaidInvoice']}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="col">
                            <div class="list-group list-group-flush">
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="flex-fill text-limit">
                                            <h6 class="progress-text mb-1 text-sm d-block text-limit"> {{number_format($invoiceChartData['progressData']['paidPr'], (int)App\Models\Utility::getValByName('decimal_number'), '.', '') .' %'}}</h6>
                                            <div class="progress progress-xs mb-0">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{$invoiceChartData['progressData']['paidPr']}}%;" aria-valuenow="{{$invoiceChartData['progressData']['paidPr']}}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="d-flex justify-content-between text-xs text-muted text-right mt-1">
                                                <div>
                                                    <span class="font-bold text-success">{{__('Paid')}}</span>
                                                </div>
                                                <div>
                                                    {{$invoiceChartData['progressData']['totalInvoice'] .'/'.$invoiceChartData['progressData']['totalPaidInvoice']}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="col">
                            <div class="list-group list-group-flush">
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="flex-fill text-limit">
                                            <h6 class="progress-text mb-1 text-sm d-block text-limit"> {{number_format($invoiceChartData['progressData']['partialPr'], (int)App\Models\Utility::getValByName('decimal_number'), '.', '') .'%'}}</h6>
                                            <div class="progress progress-xs mb-0">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: {{$invoiceChartData['progressData']['partialPr']}}%;" aria-valuenow="{{$invoiceChartData['progressData']['partialPr']}}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="d-flex justify-content-between text-xs text-muted text-right mt-1">
                                                <div>
                                                    <span class="font-bold text-info">{{__('Partial Paid')}}</span>
                                                </div>
                                                <div>
                                                    {{$invoiceChartData['progressData']['totalInvoice'] .'/'.$invoiceChartData['progressData']['totalPartialInvoice']}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="col">
                            <div class="list-group list-group-flush">
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="flex-fill text-limit">
                                            <h6 class="progress-text mb-1 text-sm d-block text-limit">{{number_format($invoiceChartData['progressData']['duePr'], (int)App\Models\Utility::getValByName('decimal_number'), '.', '') .'%'}}</h6>
                                            <div class="progress progress-xs mb-0">
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{$invoiceChartData['progressData']['duePr']}}%;" aria-valuenow="{{$invoiceChartData['progressData']['duePr']}}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="d-flex justify-content-between text-xs text-muted text-right mt-1">
                                                <div>
                                                    <span class="font-bold text-warning">{{__('Due')}}</span>
                                                </div>
                                                <div>
                                                    {{$invoiceChartData['progressData']['totalInvoice'] .'/'.$invoiceChartData['progressData']['totalDueInvoice']}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-12 mt-4">
            <h4 class="h4 font-400">{{__('Current year').' - '.date('Y')}}</h4>
            <div class="card mt-3">
                <div class="chart mt-3">
                    <div id="chart-sales" data-color="primary" data-height="280" class="p-3"></div>
                </div>
            </div>
        </div>

    </div>
@endsection


