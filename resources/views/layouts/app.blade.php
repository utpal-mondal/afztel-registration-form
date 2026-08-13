@php
    $adminEmail = is_array(config('registration.admin_email'))
        ? config('registration.admin_email')[0]
        : config('registration.admin_email');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#12294A">
    <title>@yield('title', 'Register') — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/registration.css') }}?v=7">
</head>
<body>

    <header class="nav">
        <div class="wrap nav-inner">
            <a href="{{ url('/') }}" class="nav-title">{{ config('app.name') }}</a>
        </div>
    </header>

    @yield('content')

    <footer class="foot">
        <div class="wrap">
            <div class="foot-cols">
                <div>
                    <h3>{{ config('app.name') }}</h3>
                    <p style="margin-top:1rem">{{ config('registration.company.address') }}<br>{{ config('registration.company.region') }}</p>
                </div>
                <div>
                    <h3>Company</h3>
                    <ul>
                        <li>NIPC {{ config('registration.company.nipc') }}</li>
                        <li>{{ config('registration.company.legal') }}</li>
                        <li><a href="mailto:{{ $adminEmail }}">{{ $adminEmail }}</a></li>
                    </ul>
                </div>
                <div>
                    <h3>Registration</h3>
                    <ul>
                        <li><a href="{{ route('register.create') }}">Become a partner</a></li>
                        <li>Reviewed in 2 business days</li>
                        <li>Documents kept private</li>
                    </ul>
                </div>
            </div>
            <div class="foot-bar">
                <span>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
                <span>{{ config('registration.company.region') }}</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
