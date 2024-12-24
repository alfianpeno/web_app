@extends('layouts.admin')
@section('page-title')
    {{ __('Dashboard') }}
@endsection
@php
    use App\Models\Utility;
@endphp
@push('script-page')
    <script>
        $(document).on('click', '#billing_data', function() {
            $("[name='shipping_name']").val($("[name='billing_name']").val());
            $("[name='shipping_country']").val($("[name='billing_country']").val());
            $("[name='shipping_state']").val($("[name='billing_state']").val());
            $("[name='shipping_city']").val($("[name='billing_city']").val());
            $("[name='shipping_phone']").val($("[name='billing_phone']").val());
            $("[name='shipping_zip']").val($("[name='billing_zip']").val());
            $("[name='shipping_address']").val($("[name='billing_address']").val());
        })
    </script>
    <script>
        (function() {
            var chartBarOptions = {
                series: [{
                        name: "{{ __('Income') }}",
                        data: {!! json_encode($incExpLineChartData['income']) !!}
                    },
                    {
                        name: "{{ __('Expense') }}",
                        data: {!! json_encode($incExpLineChartData['expense']) !!}
                    }
                ],

                chart: {
                    height: 250,
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
                    categories: {!! json_encode($incExpLineChartData['day']) !!},
                    title: {
                        text: '{{ __('Days') }}'
                    }
                },
                colors: ['#ffa21d', '#FF3A6E'],

                grid: {
                    strokeDashArray: 4,
                },
                legend: {
                    show: false,
                },
                yaxis: {
                    title: {
                        text: '{{ __('Amount') }}'
                    },

                }

            };
            var arChart = new ApexCharts(document.querySelector("#cash-flow"), chartBarOptions);
            arChart.render();
        })();

        (function() {
            var options = {
                chart: {
                    height: 180,
                    type: 'bar',
                    toolbar: {
                        show: false,
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },
                series: [{
                    name: "{{ __('Income') }}",
                    data: {!! json_encode($incExpBarChartData['income']) !!}
                }, {
                    name: "{{ __('Expense') }}",
                    data: {!! json_encode($incExpBarChartData['expense']) !!}
                }],
                xaxis: {
                    categories: {!! json_encode($incExpBarChartData['month']) !!},
                },
                colors: ['#3ec9d6', '#FF3A6E'],
                fill: {
                    type: 'solid',
                },
                grid: {
                    strokeDashArray: 4,
                },
                legend: {
                    show: true,
                    position: 'top',
                    horizontalAlign: 'right',
                },
                markers: {
                    size: 4,
                    colors: ['#3ec9d6', '#FF3A6E', ],
                    opacity: 0.9,
                    strokeWidth: 2,
                    hover: {
                        size: 7,
                    }
                }
            };
            var chart = new ApexCharts(document.querySelector("#incExpBarChart"), options);
            chart.render();
        })();

        (function() {
            var options = {
                chart: {
                    height: 140,
                    type: 'donut',
                },
                dataLabels: {
                    enabled: false,
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                        }
                    }
                },
                series: {!! json_encode($expenseCatAmount) !!},
                colors: {!! json_encode($expenseCategoryColor) !!},
                labels: {!! json_encode($expenseCategory) !!},
                legend: {
                    show: true
                }
            };
            var chart = new ApexCharts(document.querySelector("#expenseByCategory"), options);
            chart.render();
        })();

        (function() {
            var options = {
                chart: {
                    height: 140,
                    type: 'donut',
                },
                dataLabels: {
                    enabled: false,
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                        }
                    }
                },
                series: {!! json_encode($incomeCatAmount) !!},
                colors: {!! json_encode($incomeCategoryColor) !!},
                labels: {!! json_encode($incomeCategory) !!},
                legend: {
                    show: true
                }
            };
            var chart = new ApexCharts(document.querySelector("#incomeByCategory"), options);
            chart.render();
        })();
    </script>
@endpush
@section('breadcrumb')
    {{-- <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li> --}}
@endsection
@section('content')
    <div class="row">
        <!-- [ sample-page ] start -->
        <div class="col-sm-12">
            <div class="row">
                <div class="col-xxl-7">
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="theme-avtar bg-primary">
                                        <i class="ti ti-users"></i>
                                    </div>
                                    <p class="text-muted text-sm mt-4 mb-2">{{ __('Total') }}</p>
                                    <h6 class="mb-3 "><a href="{{ route('customer.index') }}" class="text-primary" >{{__('Customers')}}</a></h6>
                                    <h3 class="mb-0 text-primary">{{ \Auth::user()->countCustomers() }}

                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="theme-avtar bg-info">
                                        <i class="ti ti-note"></i>
                                    </div>
                                    <p class="text-muted text-sm mt-4 mb-2">{{ __('Total') }}</p>
                                    <h6 class="mb-3 "><a href="{{ route('vender.index') }}" class="text-info" >{{__('Vendors')}}</a></h6>
                                    <h3 class="mb-0 text-info">{{ \Auth::user()->countVenders() }}
                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="theme-avtar bg-warning">
                                        <i class="ti ti-file-invoice"></i>
                                    </div>
                                    <p class="text-muted text-sm mt-4 mb-2">{{ __('Total') }}</p>
                                    <h6 class="mb-3 "><a href="{{ route('invoice.index') }}" class="text-warning" >{{__('Invoices')}}</a></h6>
                                    <h3 class="mb-0 text-warning">{{ \Auth::user()->countInvoices() }} </h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="theme-avtar bg-danger">
                                        <i class="ti ti-report-money"></i>
                                    </div>
                                    <p class="text-muted text-sm mt-4 mb-2">{{ __('Total') }}</p>
                                    <h6 class="mb-3 "><a href="{{ route('bill.index') }}" class="text-danger" >{{__('Bills')}}</a></h6>
                                    <h3 class="mb-0 text-danger">{{ \Auth::user()->countBills() }} </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mt-1 mb-0">{{ __('Account Balance') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Bank') }}</th>
                                            <th>{{ __('Holder Name') }}</th>
                                            <th>{{ __('Balance') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($bankAccountDetail as $bankAccount)
                                            <tr class="font-style">
                                                <td>{{ $bankAccount->bank_name }}</td>
                                                <td>{{ $bankAccount->holder_name }}</td>
                                                <td>{{ \Auth::user()->priceFormat($bankAccount->opening_balance) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4">
                                                    <div class="text-center">
                                                        <h6>{{ __('there is no account balance') }}</h6>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-5">

                 <!-- Income Vs Expense -->
                 <div class="card">
                        <div class="card-body">
                            <h5 class="mt-1 mb-0">{{ __('Income Vs Expense') }}</h5>
                            <div class="row mt-4">

                                <div class="col-md-6 col-6">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-primary">
                                            <i class="ti ti-report-money"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted text-sm mb-1">{{ __('Income Today') }}</p>
                                            <h4 class="mb-2 text-primary">
                                                {{ \Auth::user()->priceFormat(\Auth::user()->todayIncome()) }}</h4>

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-6">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-info">
                                            <i class="ti ti-file-invoice"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted text-sm mb-1">{{ __('Expense Today') }}</p>
                                            <h4 class="mb-2 text-info">
                                                {{ \Auth::user()->priceFormat(\Auth::user()->todayExpense()) }}</h4>

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-6">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-warning">
                                            <i class="ti ti-report-money"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted text-sm mb-1">{{ __('Income This Month') }}</p>
                                            <h4 class="mb-2 text-warning">
                                                {{ \Auth::user()->priceFormat(\Auth::user()->incomeCurrentMonth()) }}</h4>

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-6">
                                    <div class="d-flex align-items-start">
                                        <div class="theme-avtar bg-danger">
                                            <i class="ti ti-file-invoice"></i>
                                        </div>
                                        <div class="ms-2">
                                            <p class="text-muted text-sm mb-1">{{ __('Expense This Month') }}</p>
                                            <h4 class="mb-2 text-danger">
                                                {{ \Auth::user()->priceFormat(\Auth::user()->expenseCurrentMonth()) }}</h4>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- cashflow -->
                    <div class="card" style="height: 415px">
                        <div class="card-header">
                            <h5 class="mt-1 mb-0">{{ __('Cashflow') }}</h5>
                        </div>
                        <div class="card-body">
                            <div id="cash-flow"></div>
                        </div>
                    </div>

                </div>
                <div class="col-xxl-7">
                    <div class="card">
                        <div class="card-header">
                            <h5>{{ __('Income & Expense') }}
                                <span class="float-end text-muted">{{ __('Current Year') . ' - ' . $currentYear }}</span>
                            </h5>

                        </div>
                        <div class="card-body">
                            <div id="incExpBarChart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-5">
                    <div class="card" style="height: 315px">
                        <div class="card-header">
                            <h5>{{ __('Income By Category') }}
                                <span class="float-end text-muted">{{ __('Year') . ' - ' . $currentYear }}</span>
                            </h5>

                        </div>
                        <div class="card-body">
                            <div id="incomeByCategory"></div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-7">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mt-1 mb-0">{{ __('Latest Income') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Date') }}</th>
                                            <th>{{ __('Customer') }}</th>
                                            <th>{{ __('Amount Due') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($latestIncome as $income)
                                            <tr>
                                                <td>{{ \Auth::user()->dateFormat($income->date) }}</td>
                                                <td>{{ !empty($income->customer) ? $income->customer->name : '-' }}</td>
                                                <td>{{ \Auth::user()->priceFormat($income->amount) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4">
                                                    <div class="text-center">
                                                        <h6>{{ __('there is no latest income') }}</h6>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-xxl-5">
                    <div class="card" style="height: 369px">
                        <div class="card-header">
                            <h5>{{ __('Expense By Category') }}
                                <span class="float-end text-muted">{{ __('Year') . ' - ' . $currentYear }}</span>
                            </h5>

                        </div>
                        <div class="card-body">
                            <div id="expenseByCategory"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mt-1 mb-0">{{ __('Latest Expense') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Date') }}</th>
                                            <th>{{ __('Customer') }}</th>
                                            <th>{{ __('Amount Due') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($latestExpense as $expense)
                                            <tr>
                                                <td>{{ \Auth::user()->dateFormat($expense->date) }}</td>
                                                <td>{{ !empty($expense->customer) ? $expense->customer->name : '-' }}</td>
                                                <td>{{ \Auth::user()->priceFormat($expense->amount) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4">
                                                    <div class="text-center">
                                                        <h6>{{ __('there is no latest expense') }}</h6>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-7">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mt-1 mb-0">{{ __('Recent Invoices') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Customer') }}</th>
                                            <th>{{ __('Issue Date') }}</th>
                                            <th>{{ __('Due Date') }}</th>
                                            <th>{{ __('Amount') }}</th>
                                            <th>{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentInvoice as $invoice)
                                            <tr>
                                                <td>{{ \Auth::user()->invoiceNumberFormat($invoice->invoice_id) }}</td>
                                                <td>{{ !empty($invoice->customer) ? $invoice->customer->name : '' }} </td>
                                                <td>{{ Auth::user()->dateFormat($invoice->issue_date) }}</td>
                                                <td>{{ Auth::user()->dateFormat($invoice->due_date) }}</td>
                                                <td>{{ \Auth::user()->priceFormat($invoice->getTotal()) }}</td>
                                                <td>
                                                    @if ($invoice->status == 0)
                                                        <span
                                                            class="p-2 px-3 fix_badges badge bg-secondary">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                                    @elseif($invoice->status == 1)
                                                        <span
                                                            class="p-2 px-3 fix_badges badge bg-warning">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                                    @elseif($invoice->status == 2)
                                                        <span
                                                            class="p-2 px-3 fix_badges badge bg-danger">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                                    @elseif($invoice->status == 3)
                                                        <span
                                                            class="p-2 px-3 fix_badges badge bg-info">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                                    @elseif($invoice->status == 4)
                                                        <span
                                                            class="p-2 px-3 fix_badges badge bg-success">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6">
                                                    <div class="text-center">
                                                        <h6>{{ __('there is no recent invoice') }}</h6>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-5">
                    <div class="card" style="height: 396px">
                        <div class="card-body">

                            <ul class="nav nav-pills mb-5" id="pills-tab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="pills-Dashboard-tab" data-bs-toggle="pill"
                                        href="#invoice_weekly_statistics" role="tab" aria-controls="pills-home"
                                        aria-selected="true">{{ __('Invoices Weekly Statistics') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="pills-profile-tab" data-bs-toggle="pill"
                                        href="#invoice_monthly_statistics" role="tab" aria-controls="pills-profile"
                                        aria-selected="false">{{ __('Invoices Monthly Statistics') }}</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="invoice_weekly_statistics" role="tabpanel"
                                    aria-labelledby="pills-home-tab">
                                    <div class="table-responsive">
                                        <table class="table align-items-center mb-0 ">
                                            <tbody class="list">
                                                <tr>
                                                    <td>
                                                        <h5 class="mb-0">{{ __('Total') }}</h5>
                                                        <p class="text-muted text-sm mb-0">{{ __('Invoice Generated') }}
                                                        </p>

                                                    </td>
                                                    <td>
                                                        <h4 class="text-muted">
                                                            {{ \Auth::user()->priceFormat($weeklyInvoice['invoiceTotal']) }}
                                                        </h4>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <h5 class="mb-0">{{ __('Total') }}</h5>
                                                        <p class="text-muted text-sm mb-0">{{ __('Paid') }}</p>
                                                    </td>
                                                    <td>
                                                        <h4 class="text-muted">
                                                            {{ \Auth::user()->priceFormat($weeklyInvoice['invoicePaid']) }}
                                                        </h4>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <h5 class="mb-0">{{ __('Total') }}</h5>
                                                        <p class="text-muted text-sm mb-0">{{ __('Due') }}</p>
                                                    </td>
                                                    <td>
                                                        <h4 class="text-muted">
                                                            {{ \Auth::user()->priceFormat($weeklyInvoice['invoiceDue']) }}
                                                        </h4>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="invoice_monthly_statistics" role="tabpanel"
                                    aria-labelledby="pills-profile-tab">
                                    <div class="table-responsive">
                                        <table class="table align-items-center mb-0 ">
                                            <tbody class="list">
                                                <tr>
                                                    <td>
                                                        <h5 class="mb-0">{{ __('Total') }}</h5>
                                                        <p class="text-muted text-sm mb-0">{{ __('Invoice Generated') }}
                                                        </p>

                                                    </td>
                                                    <td>
                                                        <h4 class="text-muted">
                                                            {{ \Auth::user()->priceFormat($monthlyInvoice['invoiceTotal']) }}
                                                        </h4>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <h5 class="mb-0">{{ __('Total') }}</h5>
                                                        <p class="text-muted text-sm mb-0">{{ __('Paid') }}</p>
                                                    </td>
                                                    <td>
                                                        <h4 class="text-muted">
                                                            {{ \Auth::user()->priceFormat($monthlyInvoice['invoicePaid']) }}
                                                        </h4>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <h5 class="mb-0">{{ __('Total') }}</h5>
                                                        <p class="text-muted text-sm mb-0">{{ __('Due') }}</p>
                                                    </td>
                                                    <td>
                                                        <h4 class="text-muted">
                                                            {{ \Auth::user()->priceFormat($monthlyInvoice['invoiceDue']) }}
                                                        </h4>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-7">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mt-1 mb-0">{{ __('Recent Bills') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Vendor') }}</th>
                                            <th>{{ __('Bill Date') }}</th>
                                            <th>{{ __('Due Date') }}</th>
                                            <th>{{ __('Amount') }}</th>
                                            <th>{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentBill as $bill)
                                            <tr>
                                                <td>{{ \Auth::user()->billNumberFormat($bill->bill_id) }}</td>
                                                <td>{{ !empty($bill->vender) ? $bill->vender->name : '' }} </td>
                                                <td>{{ Auth::user()->dateFormat($bill->bill_date) }}</td>
                                                <td>{{ Auth::user()->dateFormat($bill->due_date) }}</td>
                                                <td>{{ \Auth::user()->priceFormat($bill->getTotal()) }}</td>
                                                <td>
                                                    @if ($bill->status == 0)
                                                        <span
                                                            class="p-2 px-3 fix_badge badge bg-secondary">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                                    @elseif($bill->status == 1)
                                                        <span
                                                            class="p-2 px-3 fix_badge badge bg-warning">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                                    @elseif($bill->status == 2)
                                                        <span
                                                            class="p-2 px-3 fix_badge badge bg-danger">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                                    @elseif($bill->status == 3)
                                                        <span
                                                            class="p-2 px-3 fix_badge badge bg-info">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                                    @elseif($bill->status == 4)
                                                        <span
                                                            class="p-2 px-3 fix_badge badge bg-success">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6">
                                                    <div class="text-center">
                                                        <h6>{{ __('there is no recent bill') }}</h6>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-5">
                    <div class="card" style="height: 396px">
                        <div class="card-body">

                            <ul class="nav nav-pills mb-5" id="pills-tab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="pills-home-tab" data-bs-toggle="pill"
                                        href="#bills_weekly_statistics" role="tab" aria-controls="pills-home"
                                        aria-selected="true">{{ __('Bills Weekly Statistics') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="pills-profile-tab" data-bs-toggle="pill"
                                        href="#bills_monthly_statistics" role="tab" aria-controls="pills-profile"
                                        aria-selected="false">{{ __('Bills Monthly Statistics') }}</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="bills_weekly_statistics" role="tabpanel"
                                    aria-labelledby="pills-home-tab">
                                    <div class="table-responsive">
                                        <table class="table align-items-center mb-0 ">
                                            <tbody class="list">
                                                <tr>
                                                    <td>
                                                        <h5 class="mb-0">{{ __('Total') }}</h5>
                                                        <p class="text-muted text-sm mb-0">{{ __('Bill Generated') }}</p>

                                                    </td>
                                                    <td>
                                                        <h4 class="text-muted">
                                                            {{ \Auth::user()->priceFormat($weeklyBill['billTotal']) }}
                                                        </h4>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <h5 class="mb-0">{{ __('Total') }}</h5>
                                                        <p class="text-muted text-sm mb-0">{{ __('Paid') }}</p>
                                                    </td>
                                                    <td>
                                                        <h4 class="text-muted">
                                                            {{ \Auth::user()->priceFormat($weeklyBill['billPaid']) }}</h4>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <h5 class="mb-0">{{ __('Total') }}</h5>
                                                        <p class="text-muted text-sm mb-0">{{ __('Due') }}</p>
                                                    </td>
                                                    <td>
                                                        <h4 class="text-muted">
                                                            {{ \Auth::user()->priceFormat($weeklyBill['billDue']) }}</h4>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="bills_monthly_statistics" role="tabpanel"
                                    aria-labelledby="pills-profile-tab">
                                    <div class="table-responsive">
                                        <table class="table align-items-center mb-0 ">
                                            <tbody class="list">
                                                <tr>
                                                    <td>
                                                        <h5 class="mb-0">{{ __('Total') }}</h5>
                                                        <p class="text-muted text-sm mb-0">{{ __('Bill Generated') }}</p>

                                                    </td>
                                                    <td>
                                                        <h4 class="text-muted">
                                                            {{ \Auth::user()->priceFormat($monthlyBill['billTotal']) }}
                                                        </h4>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <h5 class="mb-0">{{ __('Total') }}</h5>
                                                        <p class="text-muted text-sm mb-0">{{ __('Paid') }}</p>
                                                    </td>
                                                    <td>
                                                        <h4 class="text-muted">
                                                            {{ \Auth::user()->priceFormat($monthlyBill['billPaid']) }}
                                                        </h4>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <h5 class="mb-0">{{ __('Total') }}</h5>
                                                        <p class="text-muted text-sm mb-0">{{ __('Due') }}</p>
                                                    </td>
                                                    <td>
                                                        <h4 class="text-muted">
                                                            {{ \Auth::user()->priceFormat($monthlyBill['billDue']) }}</h4>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header">

                            <h5>{{ __('Goal') }}</h5>
                        </div>
                        <div class="card-body">
                            @forelse($goals as $goal)
                                @php
                                    $total = $goal->target($goal->type, $goal->from, $goal->to, $goal->amount)['total'];
                                    $percentage = $goal->target($goal->type, $goal->from, $goal->to, $goal->amount)[
                                        'percentage'
                                    ];
                                @endphp
                                <div class="card border-primary border-2 border-bottom-0 border-start-0 border-end-0">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <label class="form-check-label d-block" for="customCheckdef1">
                                                <span>
                                                    <span class="row align-items-center mt-3">
                                                        <span class="col">
                                                            <span class="text-muted text-sm">{{ __('Name') }}</span>
                                                            <h6 class="text-nowrap mb-3 mb-sm-0">{{ $goal->name }}</h6>
                                                        </span>
                                                        <span class="col">
                                                            <span class="text-muted text-sm">{{ __('Type') }}</span>
                                                            <h6 class="mb-3 mb-sm-0">
                                                                {{ __(\App\Models\Goal::$goalType[$goal->type]) }}</h6>
                                                        </span>
                                                        <span class="col">
                                                            <span class="text-muted text-sm">{{ __('Duration') }}</span>
                                                            <h6 class="mb-3 mb-sm-0">
                                                                {{ $goal->from . ' To ' . $goal->to }}
                                                            </h6>
                                                        </span>
                                                        <span class="col">
                                                            <span class="text-muted text-sm">{{ __('Target') }}</span>
                                                            <h6 class="mb-3 mb-sm-0">
                                                                {{ \Auth::user()->priceFormat($total) . ' of ' . \Auth::user()->priceFormat($goal->amount) }}
                                                            </h6>
                                                        </span>
                                                        <span class="col">
                                                            <span class="text-muted text-sm">{{ __('Progress') }}</span>
                                                            <h6 class="mb-3 mb-sm-0">
                                                                {{ number_format($goal->target($goal->type, $goal->from, $goal->to, $goal->amount)['percentage'], Utility::getValByName('decimal_number'), '.', '') }}%
                                                            </h6>
                                                            <div class="progress mb-0">
                                                                <div class="progress-bar bg-primary"
                                                                    style="width: {{ number_format($goal->target($goal->type, $goal->from, $goal->to, $goal->amount)['percentage'], Utility::getValByName('decimal_number'), '.', '') }}%">
                                                                </div>
                                                            </div>
                                                        </span>
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="card pb-0">
                                    <div class="card-body text-center">
                                        <h6>{{ __('There is no goal.') }}</h6>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
