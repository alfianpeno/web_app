@php
use App\Models\Utility;
$company_small_logo = App\Models\Utility::getValByName('company_small_logo');
$mode_setting = \App\Models\Utility::mode_layout();
$logo = \App\Models\Utility::get_file('uploads/logo/');
$company_logo = Utility::get_company_logo();
$emailTemplate = App\Models\EmailTemplate::first();
$SITE_RTL = !empty($setting['SITE_RTL']) ? $setting['SITE_RTL'] : 'off';

@endphp

<nav
    class="dash-sidebar light-sidebar {{ isset($mode_setting['cust_theme_bg']) && $mode_setting['cust_theme_bg'] == 'on' ? 'transprent-bg' : '' }}">
    <div class="navbar-wrapper">
        <div class="m-header main-logo">
            <a href="" class="b-brand">
                <img src="{{ $logo . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png') . '?' . time() }}"
                    alt="{{ config('app.name', 'AccountGo') }}" class="logo logo-lg">
            </a>
        </div>

        <div class="navbar-content">
            <ul class="dash-navbar">
                {{-- -------  Dashboard ---------- --}}
                <li class="dash-item ">
                    @if (\Auth::guard('customer')->check())
                    <a href="{{ route('customer.dashboard') }}"
                        class="dash-link {{ Request::route()->getName() == 'customer.dashboard' ? ' active' : '' }}">
                        <span class="dash-micon"><i class="ti ti-home"></i></span>
                        <span class="dash-mtext">{{ __('Dashboard') }}</span>
                    </a>
                    @elseif(\Auth::guard('vender')->check())
                    <a href="{{ route('vender.dashboard') }}"
                        class="dash-link {{ Request::route()->getName() == 'vender.dashboard' ? ' active' : '' }}">
                        <span class="dash-micon"><i class="ti ti-home"></i></span>
                        <span class="dash-mtext">{{ __('Dashboard') }}</span>
                    </a>
                    @else
                    <a href="{{ route('dashboard') }}"
                        class="dash-link {{ Request::route()->getName() == 'dashboard' ? ' active' : '' }}">
                        <span class="dash-micon"><i class="ti ti-home"></i></span>
                        <span class="dash-mtext">{{ __('Dashboard') }}</span>
                    </a>
                    @endif
                </li>

                @if (Gate::check('manage customer proposal'))
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'customer.proposal' || Request::segment(1) == 'customer.retainer' ? ' active dash-trigger' : '' }}">
                    <a href="#!" class="dash-link "><span class="dash-micon"><i
                                class="ti ti-building-bank"></i></span><span
                            class="dash-mtext">{{ __('Presale') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul
                        class="dash-submenu {{ Request::segment(1) == 'customer.proposal' || Request::segment(1) == 'customer.retainer' ? 'show' : '' }}">
                        @can('manage customer proposal')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'customer.proposal' || Request::route()->getName() == 'customer.proposal.show' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('customer.proposal') }}">{{ __('Proposal') }}</a>
                        </li>
                        @endcan
                        @can('manage customer proposal')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'customer.retainer' || Request::route()->getName() == 'customer.retainer.show' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('customer.retainer') }}">{{ __('Retainers') }}</a>

                        </li>
                        @endcan
                    </ul>
                </li>
                @endif


                {{-- -------  Customer Proposal ---------- --}}
                <!-- @can('manage customer proposal')
    <li class="dash-item {{ Request::route()->getName() == 'customer.proposal' || Request::route()->getName() == 'customer.proposal.show' ? ' active' : '' }} ">
                                                        <a href="{{ route('customer.proposal') }}" class="dash-link ">
                                                            <span class="dash-micon"><i class="ti ti-receipt"></i></span>
                                                            <span class="dash-mtext">{{ __('Proposal') }}</span>
                                                        </a>
                                                    </li>
@endcan -->

                {{-- -------  Customer Invoice ---------- --}}
                @can('manage customer invoice')
                <li
                    class="dash-item {{ Request::route()->getName() == 'customer.invoice' || Request::route()->getName() == 'customer.invoice.show' ? ' active' : '' }} ">
                    <a href="{{ route('customer.invoice') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-file-invoice"></i></span>
                        <span class="dash-mtext">{{ __('Invoice') }}</span>
                    </a>
                </li>
                @endcan

                {{-- -------  Customer Payment ---------- --}}
                @can('manage customer payment')
                <li class="dash-item {{ Request::route()->getName() == 'customer.payment' ? ' active' : '' }} ">
                    <a href="{{ route('customer.payment') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-report-money"></i></span>
                        <span class="dash-mtext">{{ __('Payment') }}</span>
                    </a>
                </li>
                @endcan

                {{-- -------  Customer Transaction ---------- --}}
                @can('manage customer transaction')
                <li class="dash-item {{ Request::route()->getName() == 'customer.transaction' ? ' active' : '' }}">
                    <a href="{{ route('customer.transaction') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-history"></i></span>
                        <span class="dash-mtext">{{ __('Transaction') }}</span>
                    </a>
                </li>
                @endcan

                {{-- -------  Vendor Bill ---------- --}}
                @can('manage vender bill')
                <li
                    class="dash-item {{ Request::route()->getName() == 'vender.bill' || Request::route()->getName() == 'vender.bill.show' ? ' active' : '' }}">
                    <a href="{{ route('vender.bill') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-file-invoice"></i></span>
                        <span class="dash-mtext">{{ __('Bill') }}</span>
                    </a>
                </li>
                @endcan
                {{-- -------  Vendor Payment ---------- --}}
                @can('manage vender payment')
                <li class="dash-item {{ Request::route()->getName() == 'vender.payment' ? ' active' : '' }} ">
                    <a href="{{ route('vender.payment') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-report-money"></i></span>
                        <span class="dash-mtext">{{ __('Payment') }}</span>
                    </a>
                </li>
                @endcan

                {{-- -------  Vendor Transaction ---------- --}}
                @can('manage vender transaction')
                <li class="dash-item {{ Request::route()->getName() == 'vender.transaction' ? ' active' : '' }}">
                    <a href="{{ route('vender.transaction') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-history"></i></span>
                        <span class="dash-mtext">{{ __('Transaction') }}</span>
                    </a>
                </li>
                @endcan



                {{-- -------  Staff ---------- --}}
                {{-- @if (\Auth::user()->type == 'company')
                    @can('manage user')
                        <li class="dash-item">
                            <a href="{{ route('users.index') }}"
                class="dash-link {{ Request::route()->getName() == 'users.index' || Request::route()->getName() == 'users.create' || Request::route()->getName() == 'users.edit' ? ' active' : '' }}">
                <span class="dash-micon"><i class="ti ti-users"></i></span>
                <span class="dash-mtext">{{ __('User') }}</span>
                </a>
                </li>
                @endcan
                @else --}}
                @if (Gate::check('manage user') || Gate::check('manage role'))
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'users' || Request::segment(1) == 'roles' || Request::segment(1) == 'permissions' ? ' active dash-trigger' : '' }}">
                    <a href="#!" class="dash-link "><span class="dash-micon"><i
                                class="ti ti-users"></i></span><span class="dash-mtext">{{ __('Staff') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul
                        class="dash-submenu {{ Request::segment(1) == 'users' || Request::segment(1) == 'roles' || Request::segment(1) == 'permissions' ? 'show' : '' }}">
                        @can('manage user')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'users.index' || Request::route()->getName() == 'users.create' || Request::route()->getName() == 'users.edit' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('users.index') }}">{{ __('User') }}</a>
                        </li>
                        @endcan
                        @can('manage role')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'roles.index' || Request::route()->getName() == 'roles.create' || Request::route()->getName() == 'roles.edit' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('roles.index') }}">{{ __('Role') }}</a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endif
                {{-- @endif --}}

                {{-- -------  Product & Service ---------- --}}
                @if (Gate::check('manage product & service'))
                <li class="dash-item {{ Request::segment(1) == 'productservice' ? 'active' : '' }} ">
                    <a href="{{ route('productservice.index') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-shopping-cart"></i></span>
                        <span class="dash-mtext">{{ __('Product & Services') }}</span>
                    </a>
                </li>
                @endif

                {{-- -------  Product & Stock ---------- --}}
                @if (Gate::check('manage product & service'))
                <li class="dash-item {{ Request::segment(1) == 'productstock' ? 'active' : '' }}">
                    <a href="{{ route('productstock.index') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-box"></i></span>
                        <span class="dash-mtext">{{ __('Product Stock') }}</span>
                    </a>
                </li>
                @endif

                {{-- -------  Customer ---------- --}}
                @if (Gate::check('manage customer'))
                <li class="dash-item {{ Request::segment(1) == 'customer' ? 'active' : '' }}">
                    <a href="{{ route('customer.index') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-user-plus"></i></span>
                        <span class="dash-mtext">{{ __('Customer') }}</span>
                    </a>
                </li>
                @endif

                {{-- -------  Vendor ---------- --}}
                @if (Gate::check('manage vender'))
                <li class="dash-item {{ Request::segment(1) == 'vender' ? 'active' : '' }}">
                    <a href="{{ route('vender.index') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-note"></i></span>
                        <span class="dash-mtext">{{ __('Vendor') }}</span>
                    </a>
                </li>
                @endif

                {{-- -------  Proposal ---------- --}}
                <!-- @if (Gate::check('manage proposal'))
<li class="dash-item {{ Request::segment(1) == 'proposal' ? 'active' : '' }}">
                        <a href="{{ route('proposal.index') }}" class="dash-link ">
                            <span class="dash-micon"><i class="ti ti-receipt"></i></span>
                            <span class="dash-mtext">{{ __('Proposal') }}</span>
                        </a>
                    </li>
@endif -->

                {{-- -------  Presale ---------- --}}

                @if (Gate::check('manage proposal') || Gate::check('manage retainer'))
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'proposal' || Request::segment(1) == 'retainer' ? ' active dash-trigger' : '' }}">
                    <a href="#!" class="dash-link "><span class="dash-micon"><i
                                class="ti ti-building-bank"></i></span><span
                            class="dash-mtext">{{ __('Presale') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul
                        class="dash-submenu {{ Request::segment(1) == 'proposal' || Request::segment(1) == 'retainer' ? 'show' : '' }}">
                        @can('manage proposal')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'proposal.index' || Request::route()->getName() == 'proposal.create' || Request::route()->getName() == 'proposal.edit' || Request::route()->getName() == 'proposal.show' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('proposal.index') }}">{{ __('Proposal') }}</a>
                        </li>
                        @endcan
                        @can('manage retainer')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'retainer.index' || Request::route()->getName() == 'retainer.create' || Request::route()->getName() == 'retainer.edit' || Request::route()->getName() == 'retainer.show' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('retainer.index') }}">{{ __('Retainers') }}</a>

                        </li>
                        @endcan
                    </ul>
                </li>
                @endif


                {{-- -------  Banking ---------- --}}
                @if (Gate::check('manage bank account') || Gate::check('manage transfer'))
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'bank-account' || Request::segment(1) == 'transfer' ? ' active dash-trigger' : '' }}">
                    <a href="#!" class="dash-link "><span class="dash-micon"><i
                                class="ti ti-building-bank"></i></span><span
                            class="dash-mtext">{{ __('Banking') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul
                        class="dash-submenu {{ Request::segment(1) == 'bank-account' || Request::segment(1) == 'transfer' ? 'show' : '' }}">
                        @can('manage bank account')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'bank-account.index' || Request::route()->getName() == 'bank-account.create' || Request::route()->getName() == 'bank-account.edit' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('bank-account.index') }}">{{ __('Account') }}</a>
                        </li>
                        @endcan
                        @can('manage transfer')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'transfer.index' || Request::route()->getName() == 'transfer.create' || Request::route()->getName() == 'transfer.edit' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('transfer.index') }}">{{ __('Transfer') }}</a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endif

                {{-- -------  Income ---------- --}}
                @if (Gate::check('manage invoice') || Gate::check('manage revenue') || Gate::check('manage credit note'))
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'invoice' || Request::segment(1) == 'revenue' || Request::segment(1) == 'credit-note' ? ' active dash-trigger' : '' }}">
                    <a href="#!" class="dash-link "><span class="dash-micon"><i
                                class="ti ti-file-invoice"></i></span><span
                            class="dash-mtext">{{ __('Income') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul
                        class="dash-submenu {{ Request::segment(1) == 'invoice' || Request::segment(1) == 'revenue' || Request::segment(1) == 'credit-note' ? 'show' : '' }}">
                        @can('manage invoice')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'invoice.index' || Request::route()->getName() == 'invoice.create' || Request::route()->getName() == 'invoice.edit' || Request::route()->getName() == 'invoice.show' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('invoice.index') }}">{{ __('Invoice') }}</a>
                        </li>
                        @endcan
                        @can('manage revenue')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'revenue.index' || Request::route()->getName() == 'revenue.create' || Request::route()->getName() == 'revenue.edit' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('revenue.index') }}">{{ __('Revenue') }}</a>
                        </li>
                        @endcan
                        @can('manage credit note')
                        <li class="dash-item {{ Request::route()->getName() == 'credit.note' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('credit.note') }}">{{ __('Credit Note') }}</a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endif

                {{-- -------  Expense ---------- --}}
                @if (Gate::check('manage bill') || Gate::check('manage payment') || Gate::check('manage debit note'))
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'bill' || Request::segment(1) == 'payment' || Request::segment(1) == 'debit-note' ? ' active dash-trigger' : '' }}">
                    <a href="#!" class="dash-link "><span class="dash-micon"><i
                                class="ti ti-report-money"></i></span><span
                            class="dash-mtext">{{ __('Expense') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul
                        class="dash-submenu  {{ Request::segment(1) == 'bill' || Request::segment(1) == 'payment' || Request::segment(1) == 'debit-note' ? 'show' : '' }}">
                        @can('manage bill')
                        <li
                            class="dash-item  {{ Request::route()->getName() == 'bill.index' || Request::route()->getName() == 'bill.create' || Request::route()->getName() == 'bill.edit' || Request::route()->getName() == 'bill.show' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('bill.index') }}">{{ __('Bill') }}</a>
                        </li>
                        @endcan
                        @can('manage payment')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'payment.index' || Request::route()->getName() == 'payment.create' || Request::route()->getName() == 'payment.edit' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('payment.index') }}">{{ __('Payment') }}</a>
                        </li>
                        @endcan
                        @can('manage debit note')
                        <li class="dash-item {{ Request::route()->getName() == 'debit.note' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('debit.note') }}">{{ __('Debit Note') }}</a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endif

                {{-- -------  Double Entry ---------- --}}
                @if (Gate::check('manage chart of account') ||
                Gate::check('manage journal entry') ||
                Gate::check('balance sheet report') ||
                Gate::check('ledger report') ||
                Gate::check('trial balance report'))
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'chart-of-account' || Request::segment(1) == 'journal-entry' || Request::segment(2) == 'ledger' || Request::segment(2) == 'balance-sheet' || Request::segment(2) == 'trial-balance' ? ' active dash-trigger' : '' }}">
                    <a href="#!" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-scale"></i></span><span
                            class="dash-mtext">{{ __('Double Entry') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul
                        class="dash-submenu {{ Request::segment(1) == 'chart-of-account' || Request::segment(1) == 'journal-entry' || Request::segment(2) == 'ledger' || Request::segment(2) == 'balance-sheet' || Request::segment(2) == 'trial-balance' ? 'show' : '' }}">
                        @can('manage chart of account')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'chart-of-account.index' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('chart-of-account.index') }}">{{ __('Chart of Accounts') }}</a>
                        </li>
                        @endcan
                        @can('manage journal entry')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'journal-entry.index' || Request::route()->getName() == 'journal-entry.show' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('journal-entry.index') }}">{{ __('Journal Account') }}</a>
                        </li>
                        @endcan
                        @can('ledger report')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'report.ledger' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('report.ledger') }}">{{ __('Ledger Summary') }}</a>
                        </li>
                        @endcan
                        @can('balance sheet report')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'report.balance.sheet' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('report.balance.sheet') }}">{{ __('Balance Sheet') }}</a>
                        </li>
                        @endcan
                        @can('trial balance report')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'trial.balance' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('trial.balance') }}">{{ __('Trial Balance') }}</a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endif

                {{-- -------  Budget Planner ---------- --}}
                @if (\Auth::user()->type == 'company')
                <li class="dash-item {{ Request::segment(1) == 'budget' ? 'active' : '' }}">
                    <a href="{{ route('budget.index') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-businessplan"></i></span>
                        <span class="dash-mtext">{{ __('Budget Planner') }}</span>
                    </a>
                </li>
                @endif

                {{-- -------  Contract ---------- --}}

                @if (Gate::check('manage contract'))
                <li class="dash-item {{ Request::segment(1) == 'contract' ? 'active' : '' }}">
                    <a href="{{ route('contract.index') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-device-floppy"></i></span>
                        <span class="dash-mtext">{{ __('Contract') }}</span>
                    </a>
                </li>
                @endif

                @can('manage customer contract')
                <li class="dash-item {{ Request::segment(2) == 'contract' ? 'active' : '' }}">
                    <a href="{{ route('customer.contract.index') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-device-floppy"></i></span>
                        <span class="dash-mtext">{{ __('Contract') }}</span>
                    </a>
                </li>
                @endcan

<!-- 
                @if (\Auth::user()->type == 'company')
                <li class="dash-item {{ Request::segment(1) == 'email_template_lang' ? 'active' : '' }}">
                    <a href="{{ route('email_template.index') }}"
                        class="dash-link"><span class="dash-micon"><i
                                class="ti ti-clipboard-check"></i></span><span
                            class="dash-mtext">{{ __('Email Template') }}</span></a>
                </li>
                @endif

                @if (\Auth::user()->type == 'company')
                <li class="dash-item {{ Request::segment(1) == 'Notifications' ? 'active' : '' }}">
                    <a href="{{ route('notification-templates.index') }}" class="dash-link"><span
                            class="dash-micon"><i class="ti ti-bell"></i></span><span
                            class="dash-mtext">{{ __('Notification Template') }}</span></a>
                </li>
                @endif -->


               



                {{-- -------  Goal---------- --}}
                @if (Gate::check('manage goal'))
                <li class="dash-item {{ Request::segment(1) == 'goal' ? 'active' : '' }}">
                    <a href="{{ route('goal.index') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-target"></i></span>
                        <span class="dash-mtext">{{ __('Goal') }}</span>
                    </a>
                </li>
                @endif

                {{-- -------  Asset ---------- --}}
                @if (Gate::check('manage assets'))
                <li class="dash-item {{ Request::segment(1) == 'account-assets' ? 'active' : '' }}">
                    <a href="{{ route('account-assets.index') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-calculator"></i></span>
                        <span class="dash-mtext">{{ __('Assets') }}</span>
                    </a>
                </li>
                @endif


                 <!-- templates -->
                 @if (\Auth::user()->type == 'company')
                <li class="dash-item dash-hasmenu">
                    <a href="#" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-template"></i></span><span
                            class="dash-mtext">{{ __('Templates') }}</span><span class="dash-arrow"><i
                                data-feather="chevron-right"></i></span></a>
                    <ul class="dash-submenu">
                        @if (\Auth::user()->type == 'company')
                        <li class="dash-item {{ Request::segment(1) == 'email_template_lang' ? 'active' : '' }}">
                            <a href="{{ route('email_template.index') }}"
                                class="dash-link"><span
                                    class="dash-mtext">{{ __('Email Template') }}</span></a>
                        </li>
                        @endif
                        @if (\Auth::user()->type == 'company')
                        <li class="dash-item {{ Request::segment(1) == 'Notifications' ? 'active' : '' }}">
                            <a href="{{ route('notification-templates.index') }}" class="dash-link"><span
                                    class="dash-mtext">{{ __('Notification Template') }}</span></a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif

                {{-- -------  Report ---------- --}}
                @if (Gate::check('income report') ||
                Gate::check('expense report') ||
                Gate::check('income vs expense report') ||
                Gate::check('tax report') ||
                Gate::check('loss & profit report') ||
                Gate::check('invoice report') ||
                Gate::check('bill report') ||
                Gate::check('invoice report') ||
                Gate::check('manage transaction') ||
                Gate::check('statement report'))
                <li
                    class="dash-item dash-hasmenu {{ (Request::segment(1) == 'report' || Request::segment(1) == 'transaction') && Request::segment(2) != 'ledger' && Request::segment(2) != 'balance-sheet' && Request::segment(2) != 'trial-balance' ? ' active dash-trigger' : '' }}">
                    <a href="#!" class="dash-link "><span class="dash-micon"><i
                                class="ti ti-chart-line"></i></span><span
                            class="dash-mtext">{{ __('Report') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul
                        class="dash-submenu {{ (Request::segment(1) == 'report' || Request::segment(1) == 'transaction') && Request::segment(2) != 'ledger' && Request::segment(2) != 'balance-sheet' && Request::segment(2) != 'trial-balance' ? 'show' : '' }}">
                        @can('manage transaction')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'transaction.index' || Request::route()->getName() == 'transfer.create' || Request::route()->getName() == 'transaction.edit' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('transaction.index') }}">{{ __('Transaction') }}</a>
                        </li>
                        @endcan
                        @can('statement report')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'report.account.statement' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('report.account.statement') }}">{{ __('Account Statement') }}</a>
                        </li>
                        @endcan
                        @can('income report')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'report.income.summary' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('report.income.summary') }}">{{ __('Income Summary') }}</a>
                        </li>
                        @endcan
                        @can('expense report')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'report.expense.summary' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('report.expense.summary') }}">{{ __('Expense Summary') }}</a>
                        </li>
                        @endcan
                        @can('income vs expense report')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'report.income.vs.expense.summary' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('report.income.vs.expense.summary') }}">{{ __('Income VS Expense') }}</a>
                        </li>
                        @endcan
                        @can('tax report')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'report.tax.summary' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('report.tax.summary') }}">{{ __('Tax Summary') }}</a>
                        </li>
                        @endcan
                        @can('loss & profit report')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'report.monthly.cashflow' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('report.monthly.cashflow') }}">{{ __('Cash Flow') }}</a>
                        </li>
                        @endcan
                        @can('invoice report')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'report.invoice.summary' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('report.invoice.summary') }}">{{ __('Invoice Summary') }}</a>
                        </li>
                        @endcan
                        @can('bill report')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'report.bill.summary' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('report.bill.summary') }}">{{ __('Bill Summary') }}</a>
                        </li>
                        @endcan
                        @can('stock report')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'report.product.stock.report' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('report.product.stock.report') }}">{{ __('Product Stock') }}</a>
                        </li>
                        @endcan


                    </ul>
                </li>
                @endif

                {{-- -------  Constant ---------- --}}
                @if (Gate::check('manage constant tax') ||
                Gate::check('manage constant category') ||
                Gate::check('manage constant unit') ||
                Gate::check('manage constant payment method') ||
                Gate::check('manage constant custom field') ||
                Gate::check('manage constant contract type') ||
                Gate::check('manage constant chart of account'))
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'taxes' || Request::segment(1) == 'product-category' || Request::segment(1) == 'product-unit' || Request::segment(1) == 'payment-method' || Request::segment(1) == 'custom-field' || Request::segment(1) == 'chart-of-account-type' ? ' active dash-trigger' : '' }} ">
                    <a href="#!" class="dash-link"><span class="dash-micon"><i
                                class="ti ti-chart-arcs"></i></span><span
                            class="dash-mtext">{{ __('Constant') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul
                        class="dash-submenu {{ Request::segment(1) == 'taxes' || Request::segment(1) == 'product-category' || Request::segment(1) == 'product-unit' || Request::segment(1) == 'payment-method' || Request::segment(1) == 'custom-field' || Request::segment(1) == 'chart-of-account-type' ? 'show' : '' }}">
                        @can('manage constant tax')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'taxes.index' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('taxes.index') }}">{{ __('Taxes') }}</a>
                        </li>
                        @endcan
                        @can('manage constant category')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'product-category.index' ? 'active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('product-category.index') }}">{{ __('Category') }}</a>
                        </li>
                        @endcan
                        @can('manage constant unit')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'product-unit.index' ? ' active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('product-unit.index') }}">{{ __('Unit') }}</a>
                        </li>
                        @endcan
                        @can('manage constant custom field')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'custom-field.index' ? 'active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('custom-field.index') }}">{{ __('Custom Field') }}</a>
                        </li>
                        @endcan
                        @can('manage constant contract type')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'contractType.index' ? 'active' : '' }}">
                            <a class="dash-link"
                                href="{{ route('contractType.index') }}">{{ __('Contract Type') }}</a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endif

                @if (\Auth::user()->type == 'company')
                <li
                    class="dash-item {{ Request::route()->getName() == 'landingpage.index' ||
                        Request::route()->getName() == 'custom_page.index' ||
                        Request::route()->getName() == 'homesection.index' ||
                        Request::route()->getName() == 'features.index' ||
                        Request::route()->getName() == 'discover.index' ||
                        Request::route()->getName() == 'screenshots.index' ||
                        Request::route()->getName() == 'pricing_plan.index' ||
                        Request::route()->getName() == 'faq.index' ||
                        Request::route()->getName() == 'testimonials.index' ||
                        Request::route()->getName() == 'join_us.index'
                            ? ' active'
                            : '' }}">
                    <a href="{{ route('landingpage.index') }}" class="dash-link">
                        <span class="dash-micon"><i class="ti ti-license"></i></span><span
                            class="dash-mtext">{{ __('Landing Page') }}</span>
                    </a>
                </li>
                @endif


                {{-- -------  Company Setting ---------- --}}
                @if (Gate::check('manage company settings'))
                <li class="dash-item ">
                    <a href="{{ route('company.setting') }}" class="dash-link ">
                        <span class="dash-micon"><i class="ti ti-settings"></i></span>
                        <span class="dash-mtext">{{ __('Settings') }}</span>
                    </a>
                </li>
                @endif


            </ul>
        </div>
    </div>
</nav>