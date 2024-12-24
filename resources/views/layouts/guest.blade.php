<!DOCTYPE html>
@php
    $logo=asset(Storage::url('uploads/logo/'));
    $company_favicon=App\Models\Utility::getValByName('company_favicon');
    $seo_setting = App\Models\Utility::getSeoSetting();
@endphp
<html lang="en"  dir="{{env('SITE_RTL') == 'on'?'rtl':''}}">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">

    <!-- Primary Meta Tags -->
    <meta name="title" content={{$seo_setting['meta_keywords']}}>
    <meta name="description" content={{$seo_setting['meta_description']}}>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content={{env('APP_URL')}}>
    <meta property="og:title" content={{$seo_setting['meta_keywords']}}>
    <meta property="og:description" content={{$seo_setting['meta_description']}}>
    <meta property="og:image" content={{asset('/'.$seo_setting['meta_image'])}}>

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content={{env('APP_URL')}}>
    <meta property="twitter:title" content={{$seo_setting['meta_keywords']}}>
    <meta property="twitter:description" content={{$seo_setting['meta_description']}}>
    <meta property="twitter:image" content={{asset(Storage::url('uploads/metaevent/'.$seo_setting['meta_image']))}}>

    <title>{{(App\Models\Utility::getValByName('title_text')) ? App\Models\Utility::getValByName('title_text') : config('app.name', 'AccountGo')}} - @yield('page-title')</title>
    <link rel="icon" href="{{$logo.'/'.(isset($company_favicon) && !empty($company_favicon)?$company_favicon:'favicon.png')}}" type="image" sizes="16x16">

    <script src="{{ asset('js/app.js') }}" defer></script>
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Font Awesome 5 -->
    <link rel="stylesheet" href="{{ asset('assets/libs/@fortawesome/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/select2/dist/css/select2.min.css') }}">


    <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ac.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/stylesheet.css') }}">

    @if(env('SITE_RTL')=='on')
        <link rel="stylesheet" href="{{ asset('css/bootstrap-rtl.css') }}">
    @endif
</head>

<body>
@yield('content')

<!-- General JS Scripts -->
<script src="{{ asset('assets/libs/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('assets/libs/nicescroll/jquery.nicescroll.min.js')}} "></script>
<script src="{{ asset('assets/libs/select2/dist/js/select2.min.js') }}"></script>

<script>
    var dataTabelLang = {
        paginate: {previous: "{{__('Previous')}}", next: "{{__('Next')}}"},
        lengthMenu: "{{__('Show')}} _MENU_ {{__('entries')}}",
        zeroRecords: "{{__('No data available in table')}}",
        info: "{{__('Showing')}} _START_ {{__('to')}} _END_ {{__('of')}} _TOTAL_ {{__('entries')}}",
        infoEmpty: " ",
        search: "{{__('Search:')}}"
    }
</script>
<script src="{{ asset('assets/js/custom.js')}}"></script>
</body>
</html>
