@php
    $seo_setting = App\Models\Utility::getSeoSetting();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

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

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Styles -->
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">

        <!-- Scripts -->
        <script src="{{ asset('js/app.js') }}" defer></script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
