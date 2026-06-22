{{-- Shared <head> primitives: meta, favicon, app assets, anti-flicker CSS.
     $storeFaviconUrl is injected once per request by the View::composer in
     AppServiceProvider and is itself cached for 1h in StoreSettingsService,
     so this never triggers a DB lookup or a changing URL per request. --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@auth
    <meta name="user-id" content="{{ auth()->id() }}">
@endauth

<link rel="icon" href="{{ $storeFaviconUrl ?? asset('images/logo.png') }}">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('styles')

<style>
    body { visibility: visible; opacity: 1; transition: opacity .1s ease-in; }
</style>
