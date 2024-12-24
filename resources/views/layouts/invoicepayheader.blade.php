@php
    $logo = asset(Storage::url('uploads/logo/'));
    $company_favicon = \App\Models\Utility::getValByName('company_favicon');
    $seo_setting = App\Models\Utility::getSeoSetting();
    $SITE_RTL = env('SITE_RTL');
    $setting = \App\Models\Utility::colorset();
    $settings = \App\Models\Utility::settings();
    $color = !empty($settings['color']) ? $settings['color'] : 'theme-3';

    if (isset($settings['color_flag']) && $settings['color_flag'] == 'true') {
        $themeColor = 'custom-color';
    } else {
        $themeColor = $color;
    }

    $SITE_RTL = 'theme-3';
    if (!empty($setting['SITE_RTL'])) {
        $SITE_RTL = $setting['SITE_RTL'];
    }

    $set_cookie = App\Models\Utility::cookies();
    $setting_arr = App\Models\Utility::file_validate();
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ $SITE_RTL == 'on' ? 'rtl' : '' }}">
<meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">

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
    <meta property="twitter:image" content={{ asset(Storage::url('uploads/metaevent/' . $seo_setting['meta_image'])) }}>

    <title>
        {{ \App\Models\Utility::getValByName('title_text') ? App\Models\Utility::getValByName('title_text') : config('app.name', 'AccountGo') }}
        - @yield('page-title')</title>
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
        {{-- <link rel="stylesheet" href="{{ asset('css/bootstrap-rtl.css') }}"> --}}
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}">
    @endif
    @if ($setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-dark.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    @endif

    <link rel="stylesheet" href="{{ asset('assets/css/customizer.css') }}">

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}" id="main-style-link">
</head>

{{-- <body class="application application-offset"> --}}

<body class="{{ $themeColor }}">
    <div class="container-fluid container-application">
        <div class="main-content position-relative">
            <div class="page-content">
                <div class="page-title">
                    <div class="row justify-content-between align-items-center">
                        <div
                            class="col-xl-4 col-lg-4 col-md-4 d-flex align-items-center justify-content-between justify-content-md-start mb-3 mb-md-0">
                            <div class="d-inline-block">
                                <h5 class="h4 d-inline-block font-weight-400 mb-0 ">@yield('page-title')</h5>
                            </div>
                        </div>
                        <div
                            class="col-xl-8 col-lg-8 col-md-8 d-flex align-items-center justify-content-between justify-content-md-end">
                            @yield('action-btn')
                        </div>
                    </div>
                </div>
                @yield('content')
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


    <!-- Required Js -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/jquery.form.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>
    {{-- <script src="{{ asset('assets/js/dash.js') }}"></script> --}}

    <script src="{{ asset('assets/js/plugins/datepicker-full.min.js') }}"></script>

    <script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>

    <script src="{{ asset('assets/js/plugins/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/simple-datatables.js') }}"></script>

    <!-- sweet alert Js -->
    {{-- <script src="{{ asset('assets/js/plugins/sweetalert.min.js') }}"></script> --}}


    <!--Botstrap switch-->
    <script src="{{ asset('assets/js/plugins/bootstrap-switch-button.min.js') }}"></script>


    <!-- Apex Chart -->
    <script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/main.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/flatpickr.min.js') }}"></script>
    @stack('theme-script')
    @stack('scripts')


    <script>
        var toster_pos = "{{ env('SITE_RTL') == 'on' ? 'left' : 'right' }}";
    </script>
    {{-- @if (\App\Models\Utility::getValByName1('gdpr_cookie') == 'on')
    <script type="text/javascript">
        var defaults = {
            'messageLocales': {
                /*'en': 'We use cookies to make sure you can have the best experience on our website. If you continue to use this site we assume that you will be happy with it.'*/
                'en': "{{\App\Models\Utility::getValByName1('cookie_text')}}"
            },
            'buttonLocales': {
                'en': 'Ok'
            },
            'cookieNoticePosition': 'bottom',
            'learnMoreLinkEnabled': false,
            'learnMoreLinkHref': '/cookie-banner-information.html',
            'learnMoreLinkText': {
                'it': 'Saperne di più',
                'en': 'Learn more',
                'de': 'Mehr erfahren',
                'fr': 'En savoir plus'
            },
            'buttonLocales': {
                'en': 'Ok'
            },
            'expiresIn': 30,
            'buttonBgColor': '#d35400',
            'buttonTextColor': '#fff',
            'noticeBgColor': '#051c4b',
            'noticeTextColor': '#fff',
            'linkColor': '#009fdd'
        };
    </script>
    <script src="{{ asset('assets/js/cookie.notice.js')}}"></script>
@endif --}}

<script>
    var file_size = "{{ $setting_arr['max_size'] }}";
    var file_types = "{{ $setting_arr['types'] }}"; 
    var type_err = "{{ __('Invalid file type. Please select a valid file ') }}" + "({{ $setting_arr['types'] }}).";
    var size_err = "{{ __('File size exceeds the maximum limit of ') }}" + " {{ $setting_arr['max_size'] / 1024 }} MB.";
</script>

<script src="{{ asset('js/custom.js') }}"></script>

    @if ($message = Session::get('success'))
        <script>
            show_toastr('Success', '{!! $message !!}', 'success');
        </script>
    @endif
    @if ($message = Session::get('error'))
        <script>
            show_toastr('Error', '{!! $message !!}', 'error');
        </script>
    @endif
    @stack('script-page')
</body>
@if ($set_cookie['enable_cookie'] == 'on')
    @include('layouts.cookie_consent')
@endif

</html>
