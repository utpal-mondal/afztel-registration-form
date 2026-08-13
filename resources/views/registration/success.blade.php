@extends('layouts.app')

@section('title', 'Application received')

@php
    $adminEmail = is_array(config('registration.admin_email'))
        ? config('registration.admin_email')[0]
        : config('registration.admin_email');
@endphp

@section('content')
<main class="wrap">
    <div class="hero">
        <span class="kicker">Application received</span>
    </div>

    <div class="success">
        <span class="success-icon">✓</span>

        <h1>Thank you — we have your application</h1>
        <p>
            A confirmation is on its way to <strong>{{ $email }}</strong>.
            Our compliance desk reviews new partners within two business days.
        </p>

        <div class="ref-box">
            <p class="ref-label">Your reference</p>
            <p class="ref-value">{{ $reference }}</p>
        </div>

        <p>
            Quote this reference in any correspondence. Missing documents can be sent to
            <a class="link-gold" href="mailto:{{ $adminEmail }}">{{ $adminEmail }}</a>.
        </p>

        <p style="margin-top:1.75rem">
            <a href="{{ url('/') }}" class="btn btn-primary" style="display:inline-block">Back to home</a>
        </p>
    </div>
</main>
@endsection
