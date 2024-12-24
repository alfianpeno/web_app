<!DOCTYPE html>
@php
    use App\Models\Utility;
    // $logo=asset(Storage::url('uploads/logo/'));
    $logo = \App\Models\Utility::get_file('uploads/logo/');
    $company_logo = Utility::get_company_logo();
    $company_favicon = Utility::getValByName('company_favicon');
    $seo_setting = App\Models\Utility::getSeoSetting();
    // $logo = Utility::get_superadmin_logo();
    // $settings = \App\Models\Utility::getLayoutsSetting();

    $setting = \App\Models\Utility::colorset();
    if ($lang == 'ar' || $lang == 'he') {
        $setting['SITE_RTL'] = 'on';
    }
    $settings = \App\Models\Utility::settings();

    $color_image = 'theme-3';

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
    $mode_setting = \App\Models\Utility::mode_layout();
    $set_cookie = Utility::cookies();

@endphp
<html lang="en" dir="{{ $SITE_RTL == 'on' ? 'rtl' : '' }}">

<head>
    <title>
        {{ Utility::getValByName('title_text') ? Utility::getValByName('title_text') : config('app.name', 'Accountgo') }}
        - @yield('page-title')</title>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Dashboard Template Description" />
    <meta name="keywords" content="Dashboard Template" />
    <meta name="author" content="Workdo" />

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

    <style>
        :root {
            --color-customColor: <?=$color ?>;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/custom-color.css') }}">

    <!-- Favicon icon -->
    <link rel="icon"
        href="{{ $logo . (isset($company_favicon) && !empty($company_favicon) ? $company_favicon : 'favicon.png') }}"
        type="image" sizes="16x16">

    @if ($setting['cust_darklayout'] == 'on')
        @if (isset($setting['SITE_RTL']) && $setting['SITE_RTL'] == 'on')
            <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}" id="main-style-link">
        @endif
        <link rel="stylesheet" href="{{ asset('assets/css/style-dark.css') }}">
    @else
        @if (isset($setting['SITE_RTL']) && $setting['SITE_RTL'] == 'on')
            <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}" id="main-style-link">
        @else
            <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">
        @endif
    @endif
    @if (isset($setting['SITE_RTL']) && $setting['SITE_RTL'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/custom-login-rtl.css') }}" id="main-style-link">
    @else
        <link rel="stylesheet" href="{{ asset('assets/css/custom-login.css') }}" id="main-style-link">
    @endif
    @if ($setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/custom-login-dark.css') }}" id="main-style-link">
        <script>
            document.addEventListener('DOMContentLoaded', (event) => {
                const recaptcha = document.querySelector('.g-recaptcha');
                recaptcha.setAttribute("data-theme", "dark");
            });
        </script>
    @endif

</head>

<body class="{{ $themeColor }}">
    <!-- [custom-login] start -->
    <div class="custom-login">
        <div class="login-bg-img">
            <img src="{{ asset('assets/images/auth/' . $color_image . '.svg') }}" class="login-bg-1">
            <img src="{{ asset('assets/images/auth/common.svg') }}" class="login-bg-2">
        </div>
        <div class="bg-login bg-primary"></div>
        <div class="custom-login-inner">
            <header class="dash-header">
                <nav class="navbar navbar-expand-md default">

                    <div class="container-fluid pe-2">
                        <div class="navbar-brand">
                            <a href="">
                                <img src="{{ $logo . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png') . '?' . time() }}"
                                    class="logo" alt="{{ config('app.name', 'AccountGo') }}" class="logo logo-lg"
                                    loading="lazy" style="height: 40px" />
                            </a>
                        </div>
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                            data-bs-target="#navbarlogin">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarlogin">
                            <ul class="navbar-nav align-items-center ms-auto mb-2 mb-lg-0">
                                @include('landingpage::layouts.buttons')
                                @yield('auth-lang')
                            </ul>
                        </div>
                    </div>
                </nav>
            </header>
            <main class="custom-wrapper">
                <div class="custom-row">
                    <div class="card">
                        <div class="card-body">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </main>
            <footer>
                <div class="auth-footer">
                    <div class="container">
                        <div class="row">
                            <div class="col-12">
                                <span>{{ __('©') }} {{ date('Y') }}
                                    {{ Utility::getValByName('footer_text') ? Utility::getValByName('footer_text') : config('app.name', 'AccountGo') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <!-- [custom-login] end -->

    @if ($set_cookie['enable_cookie'] == 'on')
        @include('layouts.cookie_consent')
    @endif

    <!-- Required Js -->
    <script src="{{ asset('js/custom.js') }}"></script>
    <script src="{{ asset('assets/js/vendor-all.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>

    <script>
        feather.replace();
    </script>
    <script>
        feather.replace();
        var pctoggle = document.querySelector("#pct-toggler");
        if (pctoggle) {
            pctoggle.addEventListener("click", function() {
                if (
                    !document.querySelector(".pct-customizer").classList.contains("active")
                ) {
                    document.querySelector(".pct-customizer").classList.add("active");
                } else {
                    document.querySelector(".pct-customizer").classList.remove("active");
                }
            });
        }

        var themescolors = document.querySelectorAll(".themes-color > a");
        for (var h = 0; h < themescolors.length; h++) {
            var c = themescolors[h];

            c.addEventListener("click", function(event) {
                var targetElement = event.target;
                if (targetElement.tagName == "SPAN") {
                    targetElement = targetElement.parentNode;
                }
                var temp = targetElement.getAttribute("data-value");
                removeClassByPrefix(document.querySelector("body"), "theme-");
                document.querySelector("body").classList.add(temp);
            });
        }



        var custthemebg = document.querySelector("#cust-theme-bg");
        custthemebg.addEventListener("click", function() {
            if (custthemebg.checked) {
                document.querySelector(".dash-sidebar").classList.add("transprent-bg");
                document
                    .querySelector(".dash-header:not(.dash-mob-header)")
                    .classList.add("transprent-bg");
            } else {
                document.querySelector(".dash-sidebar").classList.remove("transprent-bg");
                document
                    .querySelector(".dash-header:not(.dash-mob-header)")
                    .classList.remove("transprent-bg");
            }
        });

        var custdarklayout = document.querySelector("#cust-darklayout");
        custdarklayout.addEventListener("click", function() {
            if (custdarklayout.checked) {
                document
                    .querySelector(".m-header > .b-brand > .logo-lg")
                    .setAttribute("src", "{{ asset('assets/images/logo.svg') }}");
                document
                    .querySelector("#main-style-link")
                    .setAttribute("href", "{{ asset('assets/css/style-dark.css') }}");
            } else {
                document
                    .querySelector(".m-header > .b-brand > .logo-lg")
                    .setAttribute("src", "{{ asset('assets/images/logo-dark.svg') }}");
                document
                    .querySelector("#main-style-link")
                    .setAttribute("href", "{{ asset('assets/css/style.css') }}");
            }
        });

        function removeClassByPrefix(node, prefix) {
            for (let i = 0; i < node.classList.length; i++) {
                let value = node.classList[i];
                if (value.startsWith(prefix)) {
                    node.classList.remove(value);
                }
            }
        }
    </script>
    @stack('custom-scripts')
    <script>
        validation();
    </script>
</body>

</html>
