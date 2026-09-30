<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="{{ $seo['charset'] ?? 'UTF-8' }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1216">
    <meta name="application-name" content="{{ $seo['site_name'] ?? config('app.name') }}">
    <title inertia>{{ $seo['title'] ?? config('app.name') }}</title>
    @if(!empty($seo['description']))
        <meta name="description" content="{{ $seo['description'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
    @endif
    @if(!empty($seo['keywords']))
        <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif
    <meta property="og:title" content="{{ $seo['title'] ?? config('app.name') }}">
    <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
    <meta property="og:url" content="{{ $seo['url'] ?? url()->current() }}">
    <meta property="og:site_name" content="{{ $seo['site_name'] ?? config('app.name') }}">
    <meta name="twitter:card" content="{{ !empty($seo['image']) ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seo['title'] ?? config('app.name') }}">
    @if(!empty($seo['image']))
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta name="twitter:image" content="{{ $seo['image'] }}">
    @endif
    @if(!empty($seo['favicon']))
        <link rel="icon" href="{{ $seo['favicon'] }}">
    @endif
    @vite('resources/js/app.ts')
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
