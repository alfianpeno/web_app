@php
    use App\Models\Language;
    $users = \Auth::user();
    $profile = asset(Storage::url('uploads/avatar/'));
    $currantLang = $users->currentLanguage();
    $languages = Language::where('code', $currantLang)->first();
    $mode_setting = \App\Models\Utility::mode_layout();
    
@endphp
<header
    class="dash-header  {{ isset($mode_setting['cust_theme_bg']) && $mode_setting['cust_theme_bg'] == 'on' ? 'transprent-bg' : '' }}">
    <div class="header-wrapper">
        <div class="me-auto dash-mob-drp">
            <ul class="list-unstyled">
                <li class="dash-h-item mob-hamburger">
                    <a href="#!" class="dash-head-link" id="mobile-collapse">
                        <div class="hamburger hamburger--arrowturn">
                            <div class="hamburger-box">
                                <div class="hamburger-inner">
                                </div>
                            </div>
                        </div>
                    </a>
                </li>

                <li class="dropdown dash-h-item drp-company">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#"
                        role="button" aria-haspopup="false" aria-expanded="false">
                        <span class="theme-avtar">
                            @if (\Auth::guard('customer')->check())
                            <img src="{{  (isset(\Auth::user()->avatar) && !empty(\Auth::user()->avatar) ? \App\Models\Utility::get_file('uploads/avatar/'.\Auth::user()->avatar) : 'logo-dark.png') }}"
                            class="img-fluid rounded-circle">
                            @else
                                <img src="{{ !empty(\Auth::user()->avatar) ? \App\Models\Utility::get_file(\Auth::user()->avatar) : asset(Storage::url('uploads/avatar/avatar.png')) }}"
                                    class="img-fluid rounded-circle">
                            @endif

                            {{-- <img src="{{(!empty(\Auth::user()->avatar))? asset(Storage::url("uploads/avatar/".\Auth::user()->avatar)): asset(Storage::url("uploads/avatar/avatar.png"))}}" class="img-fluid rounded-circle"> --}}
                        </span>
                        <span class="hide-mob ms-2">{{ __('Hi, ') }}{{ \Auth::user()->name }}!</span>
                        <i class="ti ti-chevron-down drp-arrow nocolor hide-mob"></i>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown">

                        @if (\Auth::guard('customer')->check())
                            <a href="{{ route('customer.profile') }}" class="dropdown-item">
                                <i class="ti ti-user"></i> <span>{{ __('My Profile') }}</span>
                            </a>
                        @elseif(\Auth::guard('vender')->check())
                            <a href="{{ route('vender.profile') }}" class="dropdown-item">
                                <i class="ti ti-user"></i> <span>{{ __('My Profile') }}</span>
                            </a>
                        @else
                            <a href="{{ route('profile') }}" class="dropdown-item">
                                <i class="ti ti-user"></i> <span>{{ __('My Profile') }}</span>
                            </a>
                        @endif

                        @if (\Auth::guard('customer')->check())
                            <a href="{{ route('customer.logout') }}"
                                onclick="event.preventDefault(); document.getElementById('frm-logout').submit();"
                                class="dropdown-item">
                                <i class="ti ti-power"></i>
                                <span>{{ __('Logout') }}</span>
                            </a>
                            <form id="frm-logout" action="{{ route('customer.logout') }}" method="POST"
                                class="d-none">
                                {{ csrf_field() }}
                            </form>
                        @elseif(\Auth::guard('vender')->check())
                            <a href="{{ route('vender.logout') }}"
                                onclick="event.preventDefault(); document.getElementById('frm-logout').submit();"
                                class="dropdown-item">
                                <i class="ti ti-power"></i>
                                <span>{{ __('Logout') }}</span>
                            </a>
                            <form id="frm-logout" action="{{ route('vender.logout') }}" method="POST" class="d-none">
                                {{ csrf_field() }}
                            </form>
                        @else
                            <a href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('frm-logout').submit();"
                                class="dropdown-item">
                                <i class="ti ti-power"></i>
                                <span>{{ __('Logout') }}</span>
                            </a>
                            <form id="frm-logout" action="{{ route('logout') }}" method="POST" class="d-none">
                                {{ csrf_field() }}
                            </form>
                        @endif



                    </div>
                </li>

                @if (Gate::check('create product & service') ||
                        Gate::check('create customer') ||
                        Gate::check('create vender') ||
                        Gate::check('create proposal') ||
                        Gate::check('create invoice') ||
                        Gate::check('create bill') ||
                        Gate::check('create goal') ||
                        Gate::check('create bank account'))
                    <li class="dropdown dash-h-item ml-2">
                        <div class="dropdown notification-icon">
                            <a class="dash-head-link dropdown-toggle arrow-none ms-0" data-bs-toggle="dropdown"
                                href="#" role="button" aria-haspopup="true" aria-expanded="false">
                                <i class="ti ti-plus "></i>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="dropdownBookmark">
                                @if (Gate::check('create product & service'))
                                    <a class="dropdown-item" href="#"
                                        data-url="{{ route('productservice.create') }}" data-ajax-popup="true"
                                        data-size="lg" data-title="{{ __('Create New Product') }}"><i
                                            class="ti ti-shopping-cart"></i>{{ __('Create New Product') }}</a>
                                @endif
                                @if (Gate::check('create customer'))
                                    <a class="dropdown-item" href="#" data-size="lg" data-url="{{ route('customer.create') }}"
                                        data-ajax-popup="true" data-size="lg"
                                        data-title="{{ __('Create New Customer') }}"><i
                                            class="ti ti-user"></i>{{ __('Create New Customer') }}</a>
                                @endif
                                @if (Gate::check('create vender'))
                                    <a class="dropdown-item" href="#" data-size="lg" data-url="{{ route('vender.create') }}"
                                        data-ajax-popup="true" data-size="lg"
                                        data-title="{{ __('Create New Vendor') }}"><i
                                            class="ti ti-note"></i>{{ __('Create New Vendor') }}</a>
                                @endif
                                @if (Gate::check('create proposal'))
                                    <a class="dropdown-item" href="{{ route('proposal.create', 0) }}"><i
                                            class="ti ti-file"></i>{{ __('Create New Proposal') }}</a>
                                @endif
                                @if (Gate::check('create invoice'))
                                    <a class="dropdown-item" href="{{ route('invoice.create', 0) }}"><i
                                            class="ti ti-file-invoice"></i>{{ __('Create New Invoice') }}</a>
                                @endif
                                @if (Gate::check('create bill'))
                                    <a class="dropdown-item" href="{{ route('bill.create', 0) }}"><i
                                            class="ti ti-report-money"></i>{{ __('Create New Bill') }}</a>
                                @endif
                                @if (Gate::check('create bank account'))
                                    <a class="dropdown-item" href="#"
                                        data-url="{{ route('bank-account.create') }}" data-ajax-popup="true"
                                        data-title="{{ __('Create New Account') }}"><i
                                            class="ti ti-building-bank"></i>{{ __('Create New Account') }}</a>
                                @endif
                                @if (Gate::check('create goal'))
                                    <a class="dropdown-item " href="#" data-url="{{ route('goal.create') }}"
                                        data-ajax-popup="true" data-title="{{ __('Create New Goal') }}"><i
                                            class="ti ti-target "></i>{{ __('Create New Goal') }}</a>
                                @endif
                            </div>
                        </div>
                    </li>
                @endif
            </ul>
        </div>
        <div class="ms-auto">
            <ul class="list-unstyled">
                <li class="dropdown dash-h-item drp-language">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown"
                        href="#" role="button" aria-haspopup="false" aria-expanded="false">

                        <i class="ti ti-world nocolor"></i>
                        <span class="drp-text hide-mob">{{ ucFirst($languages->fullName ?? 'English') }}</span>
                        <i class="ti ti-chevron-down drp-arrow nocolor"></i>
                    </a>

                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end">

                        {{-- @if (\Auth::user()->type == 'super admin')
                                    <a class="dropdown-item " href="{{route('manage.language',[$currantLang])}}">{{ __('Create & Customize') }}</a>
                                @endif --}}
                        @if (\Auth::guard('customer')->check())
                            @foreach (App\Models\Utility::languages() as $code => $lang)
                                <a href="{{ route('customer.change.language', $code) }}"
                                    class="dropdown-item {{ $currantLang == $code ? 'text-primary' : '' }}">
                                    <span>{{ ucFirst($lang) }}</span>
                                </a>
                            @endforeach
                        @elseif(\Auth::guard('vender')->check())
                            @foreach (App\Models\Utility::languages() as $code => $lang)
                                <a href="{{ route('vender.change.language', $code) }}"
                                    class="dropdown-item {{ $currantLang == $code ? 'text-primary' : '' }}">
                                    <span>{{ ucFirst($lang) }}</span>
                                </a>
                            @endforeach
                        @else
                            @foreach (App\Models\Utility::languages() as $code => $lang)
                                <a href="{{ route('change.language', $code) }}"
                                    class="dropdown-item {{ $currantLang == $code ? 'text-primary' : '' }}">
                                    <span>{{ ucFirst($lang) }}</span>
                                </a>
                            @endforeach
                        @endif

                        @if (\Auth::user()->type == 'company')
                            <div class="dropdown-divider m-0"></div>
                            <a href="#" data-url="{{ route('create.language') }}"
                                class="dropdown-item text-primary" data-bs-toggle="tooltip"
                                title="{{ __('Create') }}" data-ajax-popup="true"
                                data-title="{{ __('Create New Language') }}">{{ __('Create Language') }}
                            </a>
                        @endif

                        @if (\Auth::user()->type == 'company')
                            <div class="dropdown-divider m-0"></div>
                            <a class="dropdown-item text-primary"
                                href="{{ route('manage.language', [$currantLang]) }}">{{ __('Manage Language') }}</a>
                        @endif


                    </div>

                </li>
            </ul>
        </div>

    </div>
</header>
