@extends('layouts.master-without-nav')
@section('title', 'Sign in')

@section('css')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --brand-green: #07843f;
        --brand-green-dark: #045f30;
        --brand-green-deep: #034b27;
        --brand-green-light: #89cf18;
        --brand-orange: #ff7900;
        --brand-orange-light: #ffad42;
        --brand-cream: #fffaf3;
        --brand-ink: #173126;
        --brand-muted: #6c7c74;
        --brand-line: #dfe9e3;
        --brand-danger: #d94141;
        --login-font: "Manrope", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    body {
        margin: 0;
        background: var(--brand-cream) !important;
        color: var(--brand-ink);
        font-family: var(--login-font);
    }

    .login-shell,
    .login-shell * {
        box-sizing: border-box;
    }

    .login-shell {
        min-height: 100vh;
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(420px, 0.92fr);
        overflow: hidden;
        background: var(--brand-cream);
    }

    .login-brand {
        position: relative;
        display: flex;
        min-height: 100vh;
        flex-direction: column;
        justify-content: space-between;
        padding: clamp(2rem, 4.5vw, 4.5rem);
        overflow: hidden;
        isolation: isolate;
        color: #fff;
        background:
            radial-gradient(circle at 14% 90%, rgba(137, 207, 24, 0.34), transparent 32%),
            radial-gradient(circle at 88% 12%, rgba(255, 121, 0, 0.24), transparent 27%),
            linear-gradient(145deg, var(--brand-green) 0%, var(--brand-green-dark) 54%, var(--brand-green-deep) 100%);
    }

    .login-brand::before {
        content: "";
        position: absolute;
        top: -11rem;
        right: -10rem;
        width: 30rem;
        height: 30rem;
        border: 5rem solid rgba(255, 255, 255, 0.055);
        border-radius: 50%;
        z-index: -1;
        animation: floatOrb 9s ease-in-out infinite;
    }

    .login-brand::after {
        content: "";
        position: absolute;
        inset: 0;
        z-index: -2;
        opacity: 0.28;
        background-image: radial-gradient(rgba(255, 255, 255, 0.42) 1px, transparent 1px);
        background-size: 24px 24px;
        mask-image: linear-gradient(145deg, transparent 15%, #000 65%, transparent 100%);
        pointer-events: none;
    }

    .login-brand__flow {
        position: absolute;
        right: -15%;
        bottom: 8%;
        width: 88%;
        height: 32%;
        z-index: -1;
        transform: rotate(-9deg);
        pointer-events: none;
    }

    .login-brand__flow span {
        position: absolute;
        inset: 0;
        border: 3px solid rgba(255, 255, 255, 0.16);
        border-right-color: transparent;
        border-bottom-color: transparent;
        border-radius: 50%;
    }

    .login-brand__flow span:last-child {
        inset: 2.2rem -2.5rem -2.2rem 2.5rem;
        border-color: rgba(255, 121, 0, 0.72);
        border-right-color: transparent;
        border-bottom-color: transparent;
    }

    .login-brand__top,
    .login-brand__hero,
    .login-brand__foot {
        position: relative;
        z-index: 1;
    }

    .login-brand__mark {
        display: inline-flex;
        width: min(100%, 420px);
        padding: 0.95rem 1.15rem;
        align-items: center;
        border: 1px solid rgba(255, 255, 255, 0.7);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 16px 46px rgba(2, 58, 29, 0.24);
        text-decoration: none;
        animation: riseIn 0.7s ease both;
    }

    .login-brand__mark img {
        display: block;
        width: 100%;
        height: auto;
        object-fit: contain;
    }

    .login-brand__hero {
        max-width: 36rem;
        padding: 3rem 0;
        animation: riseIn 0.75s 0.12s ease both;
    }

    .login-brand__eyebrow {
        display: inline-flex;
        margin: 0 0 1.35rem;
        padding: 0.55rem 0.85rem;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .login-brand__eyebrow::before {
        content: "";
        width: 8px;
        height: 8px;
        flex: 0 0 8px;
        border-radius: 50%;
        background: var(--brand-orange);
        box-shadow: 0 0 0 5px rgba(255, 121, 0, 0.18);
    }

    .login-brand__title {
        margin: 0;
        max-width: 33rem;
        color: #fff;
        font-size: clamp(2.7rem, 5vw, 4.75rem);
        font-weight: 800;
        line-height: 1.08;
        letter-spacing: -0.05em;
    }

    .login-brand__title span {
        color: var(--brand-orange-light);
    }

    .login-brand__copy {
        margin: 1.35rem 0 0;
        max-width: 29rem;
        color: rgba(255, 255, 255, 0.77);
        font-size: 1rem;
        line-height: 1.75;
    }

    .login-brand__benefits {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 2rem;
    }

    .login-brand__benefits span {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.8rem;
        font-weight: 600;
    }

    .login-brand__benefits i {
        color: var(--brand-green-light);
        font-size: 1rem;
    }

    .login-brand__foot {
        margin: 0;
        color: rgba(255, 255, 255, 0.58);
        font-size: 0.72rem;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        animation: riseIn 0.75s 0.24s ease both;
    }

    .login-panel {
        position: relative;
        display: flex;
        min-height: 100vh;
        padding: clamp(2rem, 5vw, 5rem);
        align-items: center;
        justify-content: center;
        background:
            radial-gradient(circle at 100% 0%, rgba(255, 121, 0, 0.1), transparent 23rem),
            radial-gradient(circle at 0% 100%, rgba(7, 132, 63, 0.08), transparent 22rem),
            var(--brand-cream);
    }

    .login-panel::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 5px;
        height: 100%;
        background: linear-gradient(180deg, var(--brand-orange), var(--brand-green-light) 48%, var(--brand-green));
    }

    .login-form-wrap {
        width: 100%;
        max-width: 430px;
        animation: formIn 0.75s 0.08s ease both;
    }

    .login-form__kicker {
        display: inline-flex;
        margin: 0 0 0.8rem;
        align-items: center;
        gap: 0.5rem;
        color: var(--brand-green);
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.13em;
        text-transform: uppercase;
    }

    .login-form__kicker i {
        color: var(--brand-orange);
        font-size: 1.1rem;
    }

    .login-form-wrap h1 {
        margin: 0;
        color: var(--brand-ink);
        font-size: clamp(2.15rem, 4vw, 3.15rem);
        font-weight: 800;
        line-height: 1.12;
        letter-spacing: -0.045em;
    }

    .login-form-wrap > p:not(.login-form__kicker):not(.login-form-foot) {
        margin: 0.75rem 0 0;
        color: var(--brand-muted);
        font-size: 0.92rem;
        line-height: 1.65;
    }

    .login-form {
        margin-top: 2.25rem;
    }

    .login-field {
        margin-bottom: 1.2rem;
    }

    .login-field label {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--brand-ink);
        font-size: 0.78rem;
        font-weight: 700;
    }

    .login-field .req {
        margin-left: 0.1rem;
        color: var(--brand-orange);
    }

    .login-input-wrap {
        position: relative;
    }

    .login-input-wrap > i {
        position: absolute;
        top: 50%;
        left: 1rem;
        z-index: 1;
        color: #93a39b;
        font-size: 1.15rem;
        transform: translateY(-50%);
        pointer-events: none;
        transition: color 0.2s ease;
    }

    .login-input,
    .login-input:focus {
        width: 100%;
        height: 54px;
        padding: 0 1rem 0 3rem;
        border: 1px solid var(--brand-line);
        border-radius: 12px;
        outline: none;
        background: rgba(255, 255, 255, 0.92);
        color: var(--brand-ink);
        font-family: var(--login-font);
        font-size: 0.9rem;
        box-shadow: 0 5px 20px rgba(18, 70, 43, 0.035);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .login-input::placeholder {
        color: #a1ada7;
    }

    .login-input:hover {
        border-color: #b8cec1;
    }

    .login-input:focus {
        border-color: var(--brand-green);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(7, 132, 63, 0.11);
    }

    .login-input-wrap:focus-within > i {
        color: var(--brand-green);
    }

    .login-input.is-invalid {
        border-color: var(--brand-danger);
    }

    .login-pass .login-input {
        padding-right: 3.2rem;
    }

    .login-pass-toggle {
        position: absolute;
        top: 0;
        right: 0;
        z-index: 2;
        display: inline-flex;
        width: 52px;
        height: 54px;
        padding: 0;
        align-items: center;
        justify-content: center;
        border: 0;
        outline: 0;
        background: transparent;
        color: #899991;
        cursor: pointer;
        transition: color 0.2s ease;
    }

    .login-pass-toggle:hover,
    .login-pass-toggle:focus-visible {
        color: var(--brand-green);
    }

    .login-error {
        display: block;
        margin-top: 0.45rem;
        color: var(--brand-danger);
        font-size: 0.78rem;
    }

    .login-row {
        display: flex;
        margin: 0.2rem 0 1.6rem;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .login-check {
        display: inline-flex;
        margin: 0;
        align-items: center;
        gap: 0.6rem;
        color: var(--brand-muted);
        font-size: 0.84rem;
        cursor: pointer;
        user-select: none;
    }

    .login-check input {
        width: 17px;
        height: 17px;
        margin: 0;
        accent-color: var(--brand-green);
        cursor: pointer;
    }

    .login-submit {
        display: inline-flex;
        width: 100%;
        height: 54px;
        padding: 0 0.45rem 0 1.35rem;
        align-items: center;
        justify-content: space-between;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(115deg, var(--brand-green-dark), var(--brand-green));
        color: #fff;
        font-family: var(--login-font);
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 14px 30px rgba(4, 95, 48, 0.23);
        transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
    }

    .login-submit i {
        display: inline-flex;
        width: 43px;
        height: 43px;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: var(--brand-orange);
        color: #fff;
        font-size: 1.15rem;
        transition: transform 0.2s ease;
    }

    .login-submit:hover {
        color: #fff;
        filter: brightness(1.04);
        transform: translateY(-2px);
        box-shadow: 0 18px 35px rgba(4, 95, 48, 0.3);
    }

    .login-submit:hover i {
        transform: translateX(2px);
    }

    .login-submit:active {
        transform: translateY(0);
    }

    .login-submit:focus-visible {
        outline: 3px solid rgba(255, 121, 0, 0.35);
        outline-offset: 3px;
    }

    .login-form-foot {
        margin: 2rem 0 0;
        color: #91a098;
        font-size: 0.75rem;
        text-align: center;
    }

    @keyframes riseIn {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes formIn {
        from { opacity: 0; transform: translateX(18px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @keyframes floatOrb {
        0%, 100% { transform: translate3d(0, 0, 0); }
        50% { transform: translate3d(-12px, 14px, 0); }
    }

    @media (max-width: 991.98px) {
        .login-shell {
            display: block;
            min-height: 100vh;
        }

        .login-brand {
            min-height: auto;
            padding: 1.25rem;
            background: linear-gradient(120deg, var(--brand-green-dark), var(--brand-green));
        }

        .login-brand__top {
            text-align: center;
        }

        .login-brand__mark {
            width: min(100%, 340px);
            padding: 0.7rem 0.9rem;
            border-radius: 13px;
        }

        .login-brand__hero,
        .login-brand__foot,
        .login-brand__flow {
            display: none;
        }

        .login-panel {
            min-height: calc(100vh - 115px);
            padding: 3rem 1.5rem;
        }

        .login-panel::before {
            width: 100%;
            height: 4px;
        }
    }

    @media (max-width: 575.98px) {
        .login-brand {
            padding: 0.9rem 1rem;
        }

        .login-brand__mark {
            width: min(100%, 285px);
            padding: 0.55rem 0.7rem;
            border-radius: 11px;
        }

        .login-panel {
            min-height: calc(100vh - 88px);
            padding: 2.5rem 1.25rem 3rem;
            align-items: flex-start;
        }

        .login-form-wrap h1 {
            font-size: 2.2rem;
        }

        .login-form {
            margin-top: 1.8rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .login-brand::before,
        .login-brand__mark,
        .login-brand__hero,
        .login-brand__foot,
        .login-form-wrap {
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
        <div class="login-brand__flow" aria-hidden="true">
            <span></span>
            <span></span>
        </div>

        <div class="login-brand__top">
            <a href="{{ url('/') }}" class="login-brand__mark">
                <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}">
            </a>
        </div>

        <div class="login-brand__hero">
            <p class="login-brand__eyebrow">Admin workspace</p>
            <h2 class="login-brand__title">
                Everything your store needs, <span>in one place.</span>
            </h2>
            <p class="login-brand__copy">
                Sign in to manage orders, products, customers, and the everyday work that keeps {{ config('app.name') }} moving.
            </p>
            <div class="login-brand__benefits" aria-label="Admin features">
                <span><i class="ri-checkbox-circle-fill" aria-hidden="true"></i> Orders</span>
                <span><i class="ri-checkbox-circle-fill" aria-hidden="true"></i> Products</span>
                <span><i class="ri-checkbox-circle-fill" aria-hidden="true"></i> Customers</span>
            </div>
        </div>

        <p class="login-brand__foot">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </p>
    </aside>

    <main class="login-panel">
        <div class="login-form-wrap">
            <p class="login-form__kicker"><i class="ri-shield-keyhole-line" aria-hidden="true"></i> Secure admin access</p>
            <h1>Welcome back</h1>
            <p>Enter your admin credentials to continue to the dashboard.</p>

            <form class="login-form" action="{{ route('login.process') }}" method="POST" novalidate>
                @csrf

                <div class="login-field">
                    <label for="username">Email address <span class="req">*</span></label>
                    <div class="login-input-wrap">
                        <i class="ri-mail-line" aria-hidden="true"></i>
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
                    </div>
                    @error('email')
                        <span class="login-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="login-field">
                    <label for="password-input">Password <span class="req">*</span></label>
                    <div class="login-input-wrap login-pass">
                        <i class="ri-lock-2-line" aria-hidden="true"></i>
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
                            <i class="ri-eye-fill align-middle" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="login-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="login-row">
                    <label class="login-check" for="auth-remember-check">
                        <input type="checkbox" value="1" id="auth-remember-check" name="remember" @checked(old('remember'))>
                        Remember me
                    </label>
                </div>

                <button class="login-submit" type="submit">
                    <span>Sign in to dashboard</span>
                    <i class="ri-arrow-right-line" aria-hidden="true"></i>
                </button>
            </form>

            <p class="login-form-foot d-lg-none">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </main>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/js/pages/password-addon.init.js') }}"></script>
@endsection
