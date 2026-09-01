<!DOCTYPE html>
<html lang="{{ filled($page->en_content) || blank($page->bn_content) ? 'en' : 'bn' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#146a67">
    <meta name="description" content="{{ $page->sub_title ?: $page->name }}">

    <title>{{ $page->name }} | {{ $siteName }}</title>

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">

    <style>
        :root {
            --brand: #146a67;
            --brand-deep: #0d4f4d;
            --brand-soft: #e6f1ef;
            --accent: #87985b;
            --ink: #172321;
            --muted: #63706d;
            --line: #dce7e4;
            --surface: #ffffff;
            --canvas: #f4f8f7;
            --display-font: Georgia, "Times New Roman", serif;
            --body-font: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-width: 320px;
            background: var(--canvas);
            color: var(--ink);
            font-family: var(--body-font);
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: var(--brand);
        }

        a:focus-visible,
        button:focus-visible {
            outline: 3px solid color-mix(in srgb, var(--accent) 75%, white);
            outline-offset: 3px;
        }

        .skip-link {
            position: fixed;
            top: 0.75rem;
            left: 0.75rem;
            z-index: 20;
            padding: 0.65rem 1rem;
            border-radius: 0.4rem;
            background: var(--surface);
            color: var(--brand-deep);
            font-weight: 700;
            transform: translateY(-160%);
            transition: transform 160ms ease;
        }

        .skip-link:focus {
            transform: translateY(0);
        }

        .site-header {
            position: relative;
            z-index: 5;
            border-bottom: 1px solid rgba(20, 106, 103, 0.12);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
        }

        .header-inner,
        .hero-inner,
        .content-shell,
        .footer-inner {
            width: min(1120px, calc(100% - 2.5rem));
            margin-inline: auto;
        }

        .header-inner {
            min-height: 78px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }

        .brand-link {
            display: inline-flex;
            align-items: center;
            min-width: 0;
            text-decoration: none;
        }

        .brand-logo {
            display: block;
            width: auto;
            max-width: min(190px, 52vw);
            height: 44px;
            object-fit: contain;
            object-position: left center;
        }

        .header-label {
            margin: 0;
            color: var(--muted);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .hero {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            background:
                radial-gradient(circle at 12% 12%, rgba(135, 152, 91, 0.26), transparent 34%),
                radial-gradient(circle at 88% 85%, rgba(20, 106, 103, 0.36), transparent 32%),
                linear-gradient(135deg, #0b3f3d 0%, var(--brand) 56%, #1c7772 100%);
            color: #fff;
        }

        .hero::before,
        .hero::after {
            content: "";
            position: absolute;
            z-index: -1;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero::before {
            width: 330px;
            height: 330px;
            top: -210px;
            right: 7%;
        }

        .hero::after {
            width: 220px;
            height: 220px;
            right: 13%;
            bottom: -160px;
        }

        .hero-inner {
            padding-block: clamp(3.5rem, 8vw, 6.5rem);
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin: 0 0 1.25rem;
            color: rgba(255, 255, 255, 0.76);
            font-size: 0.84rem;
            font-weight: 650;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .breadcrumb a {
            color: inherit;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: #fff;
        }

        .breadcrumb-separator {
            color: rgba(255, 255, 255, 0.48);
        }

        .hero h1 {
            max-width: 820px;
            margin: 0;
            font-family: var(--display-font);
            font-size: clamp(2.5rem, 7vw, 5.4rem);
            font-weight: 600;
            letter-spacing: -0.045em;
            line-height: 0.98;
            text-wrap: balance;
        }

        .hero-subtitle {
            max-width: 680px;
            margin: 1.25rem 0 0;
            color: rgba(255, 255, 255, 0.82);
            font-size: clamp(1rem, 2vw, 1.2rem);
            line-height: 1.65;
            text-wrap: pretty;
        }

        .content-shell {
            position: relative;
            z-index: 2;
            margin-top: -2rem;
            padding-bottom: clamp(3.5rem, 7vw, 6rem);
        }

        .content-card {
            overflow: hidden;
            border: 1px solid rgba(20, 106, 103, 0.12);
            border-radius: 1.25rem;
            background: var(--surface);
            box-shadow: 0 24px 70px rgba(13, 79, 77, 0.11);
        }

        .content-toolbar {
            min-height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.8rem clamp(1.25rem, 4vw, 2.75rem);
            border-bottom: 1px solid var(--line);
            background: #fbfdfc;
        }

        .updated-at {
            margin: 0;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .language-switcher {
            display: inline-flex;
            gap: 0.25rem;
            padding: 0.25rem;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--brand-soft);
        }

        .language-button {
            min-width: 88px;
            padding: 0.5rem 0.9rem;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: var(--brand-deep);
            font: inherit;
            font-size: 0.82rem;
            font-weight: 750;
            cursor: pointer;
            transition: background-color 160ms ease, color 160ms ease, box-shadow 160ms ease;
        }

        .language-button[aria-selected="true"] {
            background: var(--brand);
            color: #fff;
            box-shadow: 0 5px 15px rgba(20, 106, 103, 0.2);
        }

        .page-copy {
            padding: clamp(2rem, 6vw, 4.5rem);
            color: #2b3936;
            font-size: clamp(1rem, 1.6vw, 1.08rem);
            overflow-wrap: anywhere;
        }

        .page-copy > :first-child {
            margin-top: 0;
        }

        .page-copy > :last-child {
            margin-bottom: 0;
        }

        .page-copy h1,
        .page-copy h2,
        .page-copy h3,
        .page-copy h4,
        .page-copy h5,
        .page-copy h6 {
            margin: 2em 0 0.65em;
            color: var(--ink);
            font-family: var(--display-font);
            font-weight: 600;
            letter-spacing: -0.025em;
            line-height: 1.2;
            text-wrap: balance;
        }

        .page-copy h1 { font-size: clamp(2rem, 4vw, 3rem); }
        .page-copy h2 { font-size: clamp(1.65rem, 3vw, 2.25rem); }
        .page-copy h3 { font-size: clamp(1.35rem, 2.4vw, 1.75rem); }

        .page-copy p,
        .page-copy ul,
        .page-copy ol,
        .page-copy blockquote,
        .page-copy table {
            margin: 0 0 1.25rem;
        }

        .page-copy ul,
        .page-copy ol {
            padding-inline-start: 1.4rem;
        }

        .page-copy li + li {
            margin-top: 0.45rem;
        }

        .page-copy a {
            font-weight: 650;
            text-underline-offset: 0.2em;
        }

        .page-copy img,
        .page-copy video,
        .page-copy iframe {
            max-width: 100%;
            border-radius: 0.85rem;
        }

        .page-copy blockquote {
            padding: 1.2rem 1.4rem;
            border-left: 4px solid var(--accent);
            border-radius: 0 0.65rem 0.65rem 0;
            background: var(--brand-soft);
            color: var(--brand-deep);
        }

        .page-copy table {
            width: 100%;
            border-collapse: collapse;
            display: block;
            overflow-x: auto;
        }

        .page-copy th,
        .page-copy td {
            min-width: 140px;
            padding: 0.8rem 1rem;
            border: 1px solid var(--line);
            text-align: left;
        }

        .page-copy th {
            background: var(--brand-soft);
            color: var(--brand-deep);
        }

        .empty-copy {
            margin: 0;
            color: var(--muted);
            text-align: center;
        }

        .site-footer {
            background: #0c2d2c;
            color: rgba(255, 255, 255, 0.76);
        }

        .footer-inner {
            min-height: 130px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
            padding-block: 2rem;
        }

        .footer-copy p {
            max-width: 600px;
            margin: 0;
            font-size: 0.9rem;
        }

        .footer-copy p + p {
            margin-top: 0.3rem;
        }

        .footer-contact {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.5rem 1.25rem;
        }

        .footer-contact a {
            color: #fff;
            font-size: 0.9rem;
            font-weight: 650;
            text-decoration: none;
        }

        [hidden] {
            display: none !important;
        }

        @media (max-width: 700px) {
            .header-inner,
            .hero-inner,
            .content-shell,
            .footer-inner {
                width: min(100% - 1.5rem, 1120px);
            }

            .header-label {
                display: none;
            }

            .hero-inner {
                padding-block: 3.5rem 4.5rem;
            }

            .content-shell {
                margin-top: -1.25rem;
            }

            .content-card {
                border-radius: 0.85rem;
            }

            .content-toolbar {
                align-items: flex-start;
                flex-direction: column-reverse;
                padding-block: 1rem;
            }

            .language-switcher {
                width: 100%;
            }

            .language-button {
                flex: 1;
            }

            .footer-inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .footer-contact {
                justify-content: flex-start;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .skip-link,
            .site-header,
            .site-footer,
            .language-switcher {
                display: none !important;
            }

            .hero {
                background: none;
                color: #000;
            }

            .hero-inner {
                padding-block: 1.5rem;
            }

            .breadcrumb,
            .hero-subtitle {
                color: #444;
            }

            .content-shell {
                width: 100%;
                margin-top: 0;
                padding-bottom: 0;
            }

            .content-card {
                border: 0;
                box-shadow: none;
            }

            .page-copy[hidden] {
                display: block !important;
                border-top: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    @php
        $hasEnglish = filled($page->en_content);
        $hasBengali = filled($page->bn_content);
        $defaultLanguage = $hasEnglish || ! $hasBengali ? 'en' : 'bn';
        $phoneLink = preg_replace('/[^0-9+]/', '', (string) $contactPhone);
    @endphp

    <a class="skip-link" href="#page-content">Skip to content</a>

    <header class="site-header">
        <div class="header-inner">
            <a class="brand-link" href="{{ route('home') }}" aria-label="{{ $siteName }} home">
                <img class="brand-logo" src="{{ $logoUrl }}" alt="{{ $siteName }}">
            </a>
            <p class="header-label">Public information</p>
        </div>
    </header>

    <main id="page-content">
        <section class="hero" aria-labelledby="page-title">
            <div class="hero-inner">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}">Home</a>
                    <span class="breadcrumb-separator" aria-hidden="true">/</span>
                    <span aria-current="page">{{ $page->name }}</span>
                </nav>
                <h1 id="page-title">{{ $page->name }}</h1>
                @if(filled($page->sub_title))
                    <p class="hero-subtitle">{{ $page->sub_title }}</p>
                @endif
            </div>
        </section>

        <div class="content-shell">
            <article class="content-card">
                <div class="content-toolbar">
                    <p class="updated-at">
                        @if($page->updated_at)
                            Last updated <time datetime="{{ $page->updated_at->toDateString() }}">{{ $page->updated_at->format('F j, Y') }}</time>
                        @else
                            Published by {{ $siteName }}
                        @endif
                    </p>

                    @if($hasEnglish && $hasBengali)
                        <div class="language-switcher" role="tablist" aria-label="Page language">
                            <button
                                class="language-button"
                                type="button"
                                role="tab"
                                id="language-en"
                                aria-controls="content-en"
                                aria-selected="true"
                                data-language-button="en"
                            >
                                English
                            </button>
                            <button
                                class="language-button"
                                type="button"
                                role="tab"
                                id="language-bn"
                                aria-controls="content-bn"
                                aria-selected="false"
                                data-language-button="bn"
                            >
                                বাংলা
                            </button>
                        </div>
                    @endif
                </div>

                @if($hasEnglish)
                    <section
                        class="page-copy"
                        id="content-en"
                        lang="en"
                        @if($hasEnglish && $hasBengali) role="tabpanel" aria-labelledby="language-en" @endif
                        @if($defaultLanguage !== 'en') hidden @endif
                    >
        {!! $enContent !!}
                    </section>
                @endif

                @if($hasBengali)
                    <section
                        class="page-copy"
                        id="content-bn"
                        lang="bn"
                        @if($hasEnglish && $hasBengali) role="tabpanel" aria-labelledby="language-bn" @endif
                        @if($defaultLanguage !== 'bn') hidden @endif
                    >
                        {!! $bnContent !!}
                    </section>
                @endif

                @unless($hasEnglish || $hasBengali)
                    <div class="page-copy">
                        <p class="empty-copy">Content for this page will be available soon.</p>
                    </div>
                @endunless
            </article>
        </div>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-copy">
                @if(filled($footerText))
                    <p>{{ $footerText }}</p>
                @endif
                <p>&copy; {{ now()->year }} {{ $siteName }}. All rights reserved.</p>
            </div>

            @if(filled($contactEmail) || filled($contactPhone))
                <div class="footer-contact" aria-label="Contact information">
                    @if(filled($contactPhone))
                        <a href="tel:{{ $phoneLink }}">{{ $contactPhone }}</a>
                    @endif
                    @if(filled($contactEmail))
                        <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                    @endif
                </div>
            @endif
        </div>
    </footer>

    @if($hasEnglish && $hasBengali)
        <script>
            document.querySelectorAll('[data-language-button]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var language = button.dataset.languageButton;

                    document.querySelectorAll('[data-language-button]').forEach(function (candidate) {
                        candidate.setAttribute('aria-selected', candidate === button ? 'true' : 'false');
                    });

                    document.getElementById('content-en').hidden = language !== 'en';
                    document.getElementById('content-bn').hidden = language !== 'bn';
                    document.documentElement.lang = language;
                });
            });
        </script>
    @endif
</body>
</html>
