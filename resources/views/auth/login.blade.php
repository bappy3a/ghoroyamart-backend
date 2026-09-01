@extends('layouts.master-without-nav')
@section('title', 'Sign in')

@section('css')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Sora:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    :root {
        --login-theme: oklch(51.1% .086 186.4);
        --login-theme-soft: oklch(72% .07 186.4);
        --login-theme-deep: oklch(38% .075 186.4);
        --login-ink: oklch(14% 0.01 186.4);
        --login-surface: oklch(18% 0.012 186.4);
        --login-panel: oklch(20% 0.014 186.4);
        --login-muted: oklch(68% 0.02 186.4);
        --login-line: oklch(51.1% .086 186.4 / 0.28);
        --login-text: oklch(96% 0.01 186.4);
        --login-danger: oklch(62% 0.18 25);
        --login-radius: 4px;
        --login-font-display: "Cormorant Garamond", Georgia, serif;
        --login-font-body: "Sora", system-ui, sans-serif;
    }

    body {
        margin: 0;
        background: var(--login-ink) !important;
        color: var(--login-text);
        font-family: var(--login-font-body);
    }

    .login-shell {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        background: var(--login-ink);
        overflow: hidden;
    }

    .login-brand {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: clamp(2rem, 5vw, 4rem);
        background:
            radial-gradient(ellipse 80% 60% at 20% 90%, oklch(51.1% .086 186.4 / 0.22), transparent 55%),
            radial-gradient(ellipse 70% 50% at 85% 15%, oklch(72% .07 186.4 / 0.14), transparent 50%),
            linear-gradient(160deg, oklch(18% 0.015 186.4) 0%, var(--login-ink) 45%, oklch(17% 0.012 186.4) 100%);
        isolation: isolate;
    }

    .login-brand::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(oklch(51.1% .086 186.4 / 0.06) 1px, transparent 1px),
            linear-gradient(90deg, oklch(51.1% .086 186.4 / 0.06) 1px, transparent 1px);
        background-size: 48px 48px;
        mask-image: radial-gradient(ellipse 70% 70% at 40% 50%, black, transparent);
        pointer-events: none;
        z-index: 0;
        animation: gridDrift 28s linear infinite;
    }

    .login-brand::after {
        content: "";
        position: absolute;
        width: min(70vw, 520px);
        height: min(70vw, 520px);
        border: 1px solid oklch(51.1% .086 186.4 / 0.28);
        border-radius: 50%;
        top: 50%;
        left: 42%;
        transform: translate(-50%, -50%);
        z-index: 0;
        animation: orbitPulse 8s ease-in-out infinite;
        pointer-events: none;
    }

    .login-brand__top,
    .login-brand__hero,
    .login-brand__foot {
        position: relative;
        z-index: 1;
    }

    .login-brand__mark {
        display: inline-flex;
        align-items: center;
        text-decoration: none;
    }

    .login-brand__mark img {
        height: 52px;
        width: auto;
        max-width: min(280px, 70vw);
        object-fit: contain;
        filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.45));
        animation: markRise 0.9s ease both;
    }

    .login-brand__hero {
        max-width: 34rem;
        padding: 2rem 0;
        animation: heroRise 1s 0.15s ease both;
    }

    .login-brand__eyebrow {
        display: inline-block;
        margin: 0 0 1.25rem;
        font-size: 0.72rem;
        font-weight: 500;
        letter-spacing: 0.28em;
        text-transform: uppercase;
        color: var(--login-theme-soft);
    }

    .login-brand__title {
        margin: 0;
        font-family: var(--login-font-display);
        font-size: clamp(2.75rem, 6vw, 4.75rem);
        font-weight: 600;
        line-height: 0.95;
        letter-spacing: -0.02em;
        color: var(--login-text);
    }

    .login-brand__title em {
        font-style: italic;
        color: var(--login-theme-soft);
        font-weight: 500;
    }

    .login-brand__copy {
        margin: 1.35rem 0 0;
        max-width: 26rem;
        font-size: 0.95rem;
        font-weight: 300;
        line-height: 1.65;
        color: var(--login-muted);
    }

    .login-brand__foot {
        font-size: 0.75rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(154, 154, 154, 0.75);
        animation: heroRise 1s 0.3s ease both;
    }

    .login-panel {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: clamp(1.5rem, 4vw, 3rem);
        background:
            linear-gradient(180deg, oklch(51.1% .086 186.4 / 0.06), transparent 28%),
            var(--login-panel);
        border-left: 1px solid var(--login-line);
        animation: panelSlide 0.85s ease both;
    }

    .login-form-wrap {
        width: 100%;
        max-width: 380px;
    }

    .login-form-wrap__mobile-logo {
        display: none;
        margin-bottom: 1.75rem;
        text-align: center;
    }

    .login-form-wrap__mobile-logo img {
        height: 40px;
        width: auto;
        max-width: 220px;
        object-fit: contain;
    }

    .login-form-wrap h1 {
        margin: 0;
        font-family: var(--login-font-display);
        font-size: clamp(2rem, 4vw, 2.6rem);
        font-weight: 600;
        line-height: 1.1;
        color: var(--login-text);
    }

    .login-form-wrap > p {
        margin: 0.65rem 0 0;
        font-size: 0.9rem;
        font-weight: 300;
        color: var(--login-muted);
    }

    .login-form {
        margin-top: 2.25rem;
    }

    .login-field {
        margin-bottom: 1.15rem;
    }

    .login-field label {
        display: block;
        margin-bottom: 0.45rem;
        font-size: 0.72rem;
        font-weight: 500;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--login-theme-soft);
    }

    .login-field .req {
        color: var(--login-danger);
        margin-left: 0.15rem;
    }

    .login-input,
    .login-input:focus {
        width: 100%;
        height: 48px;
        padding: 0 0.95rem;
        border: 1px solid oklch(96% 0.01 186.4 / 0.12);
        border-radius: var(--login-radius);
        background: oklch(100% 0 0 / 0.03);
        color: var(--login-text);
        font-family: var(--login-font-body);
        font-size: 0.92rem;
        outline: none;
        transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
        box-shadow: none;
    }

    .login-input::placeholder {
        color: rgba(154, 154, 154, 0.7);
    }

    .login-input:hover {
        border-color: oklch(51.1% .086 186.4 / 0.45);
    }

    .login-input:focus {
        border-color: var(--login-theme);
        background: oklch(51.1% .086 186.4 / 0.08);
        box-shadow: 0 0 0 3px oklch(51.1% .086 186.4 / 0.18);
    }

    .login-input.is-invalid {
        border-color: var(--login-danger);
    }

    .login-pass {
        position: relative;
    }

    .login-pass .login-input {
        padding-right: 2.75rem;
    }

    .login-pass-toggle {
        position: absolute;
        top: 0;
        right: 0;
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        background: transparent;
        color: var(--login-muted);
        cursor: pointer;
        transition: color 0.2s ease;
    }

    .login-pass-toggle:hover,
    .login-pass-toggle:focus {
        color: var(--login-theme-soft);
        outline: none;
    }

    .login-error {
        display: block;
        margin-top: 0.4rem;
        font-size: 0.8rem;
        color: var(--login-danger);
    }

    .login-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin: 0.25rem 0 1.75rem;
    }

    .login-check {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        margin: 0;
        font-size: 0.85rem;
        color: var(--login-muted);
        cursor: pointer;
        user-select: none;
    }

    .login-check input {
        width: 16px;
        height: 16px;
        margin: 0;
        accent-color: var(--login-theme);
        cursor: pointer;
    }

    .login-submit {
        width: 100%;
        height: 50px;
        border: 0;
        border-radius: var(--login-radius);
        background: linear-gradient(115deg, var(--login-theme-deep) 0%, var(--login-theme) 48%, var(--login-theme-soft) 100%);
        color: oklch(98% 0.01 186.4);
        font-family: var(--login-font-body);
        font-size: 0.82rem;
        font-weight: 600;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        cursor: pointer;
        transition: transform 0.2s ease, filter 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 10px 28px oklch(51.1% .086 186.4 / 0.28);
    }

    .login-submit:hover {
        filter: brightness(1.06);
        transform: translateY(-1px);
        box-shadow: 0 14px 32px oklch(51.1% .086 186.4 / 0.38);
    }

    .login-submit:active {
        transform: translateY(0);
    }

    .login-form-foot {
        margin-top: 2rem;
        text-align: center;
        font-size: 0.75rem;
        color: rgba(154, 154, 154, 0.7);
    }

    @keyframes markRise {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes heroRise {
        from { opacity: 0; transform: translateY(18px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes panelSlide {
        from { opacity: 0; transform: translateX(18px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @keyframes orbitPulse {
        0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.55; }
        50% { transform: translate(-50%, -50%) scale(1.06); opacity: 0.9; }
    }

    @keyframes gridDrift {
        from { background-position: 0 0, 0 0; }
        to { background-position: 48px 48px, 48px 48px; }
    }

    @media (max-width: 991.98px) {
        .login-shell {
            grid-template-columns: 1fr;
            min-height: 100vh;
        }

        .login-brand {
            min-height: auto;
            padding: 1.75rem 1.5rem 2rem;
        }

        .login-brand::after {
            width: 280px;
            height: 280px;
            left: 70%;
            top: 30%;
        }

        .login-brand__hero {
            padding: 1.25rem 0 0.5rem;
        }

        .login-brand__title {
            font-size: clamp(2.2rem, 10vw, 3rem);
        }

        .login-brand__foot {
            display: none;
        }

        .login-panel {
            border-left: 0;
            border-top: 1px solid var(--login-line);
            align-items: flex-start;
            padding-top: 2rem;
            padding-bottom: 3rem;
            animation: heroRise 0.85s ease both;
        }

        .login-form-wrap__mobile-logo {
            display: none;
        }
    }

    @media (max-width: 575.98px) {
        .login-brand__mark img {
            height: 40px;
        }

        .login-brand__copy {
            font-size: 0.88rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .login-brand::before,
        .login-brand::after,
        .login-brand__mark img,
        .login-brand__hero,
        .login-brand__foot,
        .login-panel {
            animation: none !important;
        }
    }
</style>
@endsection

@section('body')
@include('layouts.body')
@endsection

@section('content')
<div class="login-shell">
    <aside class="login-brand" aria-label="{{ config('app.name') }} brand">
        <div class="login-brand__top">
            <a href="{{ url('/') }}" class="login-brand__mark">
                <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}">
            </a>
        </div>

        <div class="login-brand__hero">
            <p class="login-brand__eyebrow">Admin access</p>
            <h2 class="login-brand__title">
                Welcome<br>
                <em>back</em>
            </h2>
            <p class="login-brand__copy">
                Sign in to manage orders, products, and the day-to-day of {{ config('app.name') }}.
            </p>
        </div>

        <p class="login-brand__foot">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </p>
    </aside>

    <main class="login-panel">
        <div class="login-form-wrap">
            <div class="login-form-wrap__mobile-logo">
                <img src="{{ asset('logo.php') }}" alt="{{ config('app.name') }}">
            </div>

            <h1>Sign in</h1>
            <p>Use your admin email and password to continue.</p>

            <form class="login-form" action="{{ route('login.process') }}" method="POST" novalidate>
                @csrf

                <div class="login-field">
                    <label for="username">Email <span class="req">*</span></label>
                    <input
                        type="email"
                        id="username"
                        name="email"
                        value="{{ old('email') }}"
                        class="login-input @error('email') is-invalid @enderror"
                        placeholder="you@example.com"
                        autocomplete="username"
                        required
                        autofocus
                    >
                    @error('email')
                        <span class="login-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="login-field">
                    <label for="password-input">Password <span class="req">*</span></label>
                    <div class="login-pass">
                        <input
                            type="password"
                            id="password-input"
                            name="password"
                            class="login-input password-input @error('password') is-invalid @enderror"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <button class="login-pass-toggle password-addon" type="button" id="password-addon" aria-label="Show password">
                            <i class="ri-eye-fill align-middle"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="login-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="login-row">
                    <label class="login-check" for="auth-remember-check">
                        <input type="checkbox" value="1" id="auth-remember-check" name="remember">
                        Remember me
                    </label>
                </div>

                <button class="login-submit" type="submit">Sign In</button>
            </form>

            <p class="login-form-foot d-lg-none mb-0">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </p>
        </div>
    </main>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/js/pages/password-addon.init.js') }}"></script>
@endsection
