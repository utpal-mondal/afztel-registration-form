@extends('layouts.app')

@section('title', 'Test Mail')

@section('content')
<main class="wrap" style="padding: 4rem 0; text-align: center;">
    <h1 style="font-size: 2rem; color: var(--navy);">Test Mail</h1>
    <p style="margin: 1rem 0 2rem; color: var(--muted);">Enter an email address to send a test message and verify your mail configuration.</p>

    @if (session('status'))
        <div class="alert" role="alert" style="background: #e8f5e9; color: #2e7d32; border-color: #a5d6a7;">
            <span class="icon" aria-hidden="true">✓</span>{{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert" role="alert">
            <span class="icon" aria-hidden="true">!</span>{{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('testmail.send') }}" class="card" style="max-width: 520px; margin: 0 auto; padding: 2rem;">
        @csrf

        <x-field
            name="email"
            label="Recipient email"
            type="email"
            required
            placeholder="name@company.com"
            inputmode="email"
            autocomplete="email"
            :value="old('email')"
            span="col-12"
        />

        <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">Send test mail</button>
    </form>

    <p style="margin-top: 2rem; font-size: .875rem; color: var(--muted);">
        If the test fails, the error message above will show the reason.
    </p>
</main>
@endsection
