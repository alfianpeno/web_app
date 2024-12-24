@php
    use App\Models\Utility;
    $logo = asset(Storage::url('uploads/logo/'));
    $company_favicon = Utility::getValByName('company_favicon');
    $SITE_RTL = env('SITE_RTL');
    $setting = \App\Models\Utility::colorset();
    // $color = 'theme-3';
    // if (!empty($setting['color'])) {
    //     $color = $setting['color'];
    // }

    $settings = \App\Models\Utility::settings();

    $color = !empty($settings['color']) ? $settings['color'] : 'theme-3';

    if(isset($settings['color_flag']) && $settings['color_flag'] == 'true')
    {
        $themeColor = 'custom-color';
    }
    else {
        $themeColor = $color;
    }
    
    $SITE_RTL = 'theme-3';
    if (!empty($setting['SITE_RTL'])) {
        $SITE_RTL = $setting['SITE_RTL'];
    }
    
    $mode_setting = \App\Models\Utility::mode_layout();
    $seo_setting = App\Models\Utility::getSeoSetting();
    $set_cookie = Utility::cookies();
@endphp

<!DOCTYPE html>
<html lang="en" dir="{{ $SITE_RTL == 'on' ? 'rtl' : '' }}">

<head>
    <title>
        {{ Utility::getValByName('title_text') ? Utility::getValByName('title_text') : config('app.name', 'ERPGO') }}
        - @yield('page-title')</title>
    {{-- <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script> --}}
    <script src="{{ asset('js/html5shiv.js') }}"></script>
    <script src="{{ asset('js/respond.min.js') }}"></script>

    <!-- Meta -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    {{-- <meta name="url" content="{{ url('') . '/' . config('chatify.path') }}" data-user="{{ Auth::user()->id }}"> --}}

    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- Primary Meta Tags -->
    <meta name="title" content={{ $seo_setting['meta_keywords'] }}>
    <meta name="description" content={{ $seo_setting['meta_description'] }}>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content={{ env('APP_URL') }}>
    <meta property="og:title" content={{ $seo_setting['meta_keywords'] }}>
    <meta property="og:description" content={{ $seo_setting['meta_description'] }}>
    <meta property="og:image" content={{ asset('/' . $seo_setting['meta_image']) }}>

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content={{ env('APP_URL') }}>
    <meta property="twitter:title" content={{ $seo_setting['meta_keywords'] }}>
    <meta property="twitter:description" content={{ $seo_setting['meta_description'] }}>
    <meta property="twitter:image"
        content={{ asset(Storage::url('uploads/metaevent/' . $seo_setting['meta_image'])) }}>

    <!-- Favicon icon -->
    <link rel="icon"
        href="{{ $logo . '/' . (isset($company_favicon) && !empty($company_favicon) ? $company_favicon : 'favicon.png') }}"
        type="image" sizes="16x16">

    <link rel="stylesheet" href="{{ asset('assets/css/plugins/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/animate.min.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/plugins/datepicker-bs5.min.css') }}">

    <!--bootstrap switch-->
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/bootstrap-switch-button.min.css') }}">


    <!-- font css -->
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/material.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/main.css') }}">
    <!-- vendor css -->

    <link rel="stylesheet" href="{{ asset('assets/css/plugins/flatpickr.min.css') }}">

    @if ($SITE_RTL == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}">
    @endif
    @if ($setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-dark.css') }}" id="style">
    @else
        <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="style">
    @endif

    <link rel="stylesheet" href="{{ asset('assets/css/plugins/dropzone.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/customizer.css') }}">


    <link rel="stylesheet" href="{{ asset('css/custom.css') }}" id="main-style-link">
    @if ($setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('css/custom-dark.css') }}" id="style">
    @endif

    @if ($SITE_RTL == 'on')
        <link rel="stylesheet" href="{{ asset('css/custom-rtl.css') }}">
    @endif

    <style>
        :root {
            --color-customColor: <?= $color ?>;    
        }
    </style>

    <link rel="stylesheet" href="{{ asset('css/custom-color.css') }}">

    @stack('css-page')
    <style>
        [dir="rtl"] .dash-sidebar {
            left: auto !important;
        }

        [dir="rtl"] .dash-header {
            left: 0;
            right: 280px;
        }

        [dir="rtl"] .dash-header:not(.transprent-bg) .header-wrapper {
            padding: 0 0 0 30px;
        }

        [dir="rtl"] .dash-header:not(.transprent-bg):not(.dash-mob-header)~.dash-container {
            margin-left: 0px !important;
        }

        [dir="rtl"] .me-auto.dash-mob-drp {
            margin-right: 10px !important;
        }

        [dir="rtl"] .me-auto {
            margin-left: 10px !important;
        }
    </style>
</head>

<body class="{{ $themeColor }}">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    @include('partials.admin.menu')
    <!-- [ navigation menu ] end -->
    <!-- [ Header ] start -->
    @include('partials.admin.header')

    <!-- Modal -->
    <div class="modal notification-modal fade" id="notification-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="btn-close float-end" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                    <h6 class="mt-2">
                        <i data-feather="monitor" class="me-2"></i>Desktop settings
                    </h6>
                    <hr />
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting1" checked />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting1">Allow desktop
                            notification</label>
                    </div>
                    <p class="text-muted ms-5">
                        you get lettest content at a time when data will updated
                    </p>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting2" />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting2">Store Cookie</label>
                    </div>
                    <h6 class="mb-0 mt-5">
                        <i data-feather="save" class="me-2"></i>Application settings
                    </h6>
                    <hr />
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting3" />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting3">Backup Storage</label>
                    </div>
                    <p class="text-muted mb-4 ms-5">
                        Automaticaly take backup as par schedule
                    </p>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting4" />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting4">Allow guest to print
                            file</label>
                    </div>
                    <h6 class="mb-0 mt-5">
                        <i data-feather="cpu" class="me-2"></i>System settings
                    </h6>
                    <hr />
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting5" checked />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting5">View other user chat</label>
                    </div>
                    <p class="text-muted ms-5">Allow to show public user message</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-danger btn-sm" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="button" class="btn btn-light-primary btn-sm">
                        Save changes
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Header ] end -->


    <!-- [ Main Content ] start -->
    <div class="dash-container">
        <div class="dash-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <div class="page-header-title">
                                <h4 class="m-b-10">@yield('page-title')</h4>
                            </div>
                            <ul class="breadcrumb">
                                @yield('breadcrumb')
                            </ul>
                        </div>
                        <div class="col-6 d-flex justify-content-end">
                            @yield('action-btn')
                        </div>
                    </div>
                </div>
            </div>
            @yield('content')
            <!-- [ Main Content ] end -->
        </div>
    </div>
    <div class="modal fade" id="commonModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="body">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="commonModalOver" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
            </div>
        </div>
    </div>


    <div class="position-fixed top-0 end-0 p-3" style="z-index: 99999">
        <div id="liveToast" class="toast text-white  fade" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>
    @include('partials.admin.footer')
</body>
@if ($set_cookie['enable_cookie'] == 'on')
    @include('layouts.cookie_consent')
@endif

</html>
