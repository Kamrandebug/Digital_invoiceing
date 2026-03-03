@extends('layouts.landing-master')

@section('styles')
    <link rel="stylesheet" href="{{ asset('build/assets/css/welcome-new-effects.css') }}">
    <style>
        /* Local Overrides & Specific Page Styles */
        .hero-section {
            min-height: 100vh;
            background: var(--surface-sunken);
            position: relative;
            overflow: hidden;
        }

        .hero-mesh {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #6c5ffc;
            background-image:
                radial-gradient(at 0% 0%, hsla(253, 100%, 70%, 0.8) 0, transparent 50%),
                radial-gradient(at 50% 0%, hsla(225, 100%, 70%, 0.8) 0, transparent 50%),
                radial-gradient(at 100% 0%, hsla(280, 100%, 70%, 0.8) 0, transparent 50%);
            opacity: 0.15;
            z-index: 1;
        }

        .hero-container {
            position: relative;
            z-index: 2;
            padding-top: var(--space-16);
        }

        .hero-title span {
            background: linear-gradient(90deg, #6c5ffc, #a461ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-mockup-wrapper {
            position: relative;
        }

        .hero-mockup-wrapper::before {
            content: '';
            position: absolute;
            width: 120%;
            height: 120%;
            top: -10%;
            left: -10%;
            background: radial-gradient(circle, rgba(var(--primary-rgb), 0.15) 0%, transparent 70%);
            z-index: -1;
        }
    </style>
@endsection

@section('content')
    <!-- Hero Section -->
    <section class="hero-section d-flex align-items-center" aria-label="Hero Section">
        <div class="hero-mesh"></div>
        <div class="container hero-container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="reveal-on-scroll">
                        <span
                            class="badge bg-primary-soft text-primary mb-3 px-3 py-2 rounded-pill fw-semibold border border-primary-glow">
                            🚀 New v3.0 Era
                        </span>
                        <h1 class="hero-title mb-4">
                            Smart Digital Invoicing for <span>Modern Businesses</span>
                        </h1>
                        <div class="typing-container mb-5">
                            <p class="hero-subtitle lead text-muted typing-text"
                                data-type-text="Streamline your billing process, track payments, and manage finances with a high-performance digital invoicing solution built for 2026.">
                            </p>
                        </div>
                        <div class="d-flex gap-3 flex-wrap hero-cta-group">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/dashboard') }}"
                                        class="btn btn-primary btn-premium btn-lg px-4 py-3 rounded-3 shadow-lg">
                                        <i class="ri-dashboard-line me-2"></i> Go to Dashboard
                                    </a>
                                @else
                                    <a href="{{ route('login') }}"
                                        class="btn btn-primary btn-premium btn-lg px-4 py-3 rounded-3 shadow-lg">
                                        <i class="ri-login-circle-line me-2"></i> Get Started Free
                                    </a>
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}"
                                            class="btn btn-outline-primary btn-lg px-4 py-3 rounded-3 border-2">
                                            Live Demo <i class="ri-play-circle-line ms-2"></i>
                                        </a>
                                    @endif
                                @endauth
                            @endif
                        </div>
                        <div class="mt-5 d-flex align-items-center gap-4 opacity-75">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ri-checkbox-circle-fill text-primary fs-5"></i> <span class="small fw-medium">No
                                    credit card</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="ri-checkbox-circle-fill text-primary fs-5"></i> <span
                                    class="small fw-medium">Instant Setup</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block text-center mt-5 mt-lg-0">
                    <div class="hero-mockup-wrapper floating">
                        <img src="{{ asset('build/assets/images/custom/hero-mockup.png') }}"
                            alt="Invoicing Dashboard Preview"
                            class="img-fluid rounded-4 shadow-xl hero-mockup glow-on-hover"
                            style="max-height: 550px; transform: perspective(1200px) rotateY(-15deg) rotateX(8deg) scale(1.05);">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="section-padding bg-white border-bottom position-relative" aria-label="Statistics">
        <div class="container">
            <div class="row text-center justify-content-center g-4">
                <div class="col-6 col-md-3">
                    <div class="reveal-on-scroll">
                        <h3 class="fw-bold display-4 text-primary mb-1"><span class="stat-counter"
                                data-target="500">0</span>+</h3>
                        <p class="text-muted fw-medium mb-0">Happy Clients</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="reveal-on-scroll" style="transition-delay: 100ms;">
                        <h3 class="fw-bold display-4 text-primary mb-1"><span class="stat-counter"
                                data-target="10">0</span>k+</h3>
                        <p class="text-muted fw-medium mb-0">Invoices Sent</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="reveal-on-scroll" style="transition-delay: 200ms;">
                        <h3 class="fw-bold display-4 text-primary mb-1">$<span class="stat-counter"
                                data-target="2">0</span>M+</h3>
                        <p class="text-muted fw-medium mb-0">Processed</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="reveal-on-scroll" style="transition-delay: 300ms;">
                        <h3 class="fw-bold display-4 text-primary mb-1"><span class="stat-counter"
                                data-target="99">0</span>.9%</h3>
                        <p class="text-muted fw-medium mb-0">Uptime</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section-padding bg-light" id="features" aria-label="Features">
        <div class="container">
            <div class="text-center mb-5 mx-auto reveal-on-scroll" style="max-width: 800px;">
                <h6 class="text-primary fw-bold text-uppercase ls-2 mb-3">Enterprise Capabilities</h6>
                <h2 class="mb-4">Everything you need to manage <span>your billing</span></h2>
                <p class="lead text-muted">Powerful features designed to help you get paid faster and manage your business
                    finances with ease.</p>
            </div>

            <div class="row g-4">
                @php
                    $features = [
                        [
                            'icon' => 'ri-file-list-3-line',
                            'title' => 'Easy Invoicing',
                            'desc' => 'Create professional invoices in seconds. Customize templates with your logo.',
                            'color' => 'primary',
                        ],
                        [
                            'icon' => 'ri-secure-payment-line',
                            'title' => 'Secure Payments',
                            'desc' => 'Accept payments securely via credit card, PayPal, and Stripe integration.',
                            'color' => 'info',
                        ],
                        [
                            'icon' => 'ri-bar-chart-box-line',
                            'title' => 'Real-time Analytics',
                            'desc' => 'Gain deep insights into your revenue and financial health with live dashboards.',
                            'color' => 'success',
                        ],
                        [
                            'icon' => 'ri-global-line',
                            'title' => 'Global Billing',
                            'desc' => 'Bill clients globally in their local currency with automatic exchange rates.',
                            'color' => 'warning',
                        ],
                        [
                            'icon' => 'ri-smartphone-line',
                            'title' => 'Mobile Optimized',
                            'desc' => 'Manage your business on the go. Send and track invoices from any device.',
                            'color' => 'danger',
                        ],
                        [
                            'icon' => 'ri-customer-service-2-line',
                            'title' => 'Expert Support',
                            'desc' => 'Our dedicated team is available 24/7 to help you scale your business.',
                            'color' => 'secondary',
                        ],
                    ];
                @endphp

                @foreach ($features as $index => $feature)
                    <div class="col-md-6 col-lg-4">
                        <div class="flip-card reveal-on-scroll" style="transition-delay: {{ $index * 50 }}ms;">
                            <div class="flip-card-inner">
                                <div class="flip-card-front">
                                    <div class="avatar-icon bg-{{ $feature['color'] }}-transparent text-{{ $feature['color'] }} mb-4"
                                        style="width: 64px; height: 64px; font-size: 1.75rem;">
                                        <i class="{{ $feature['icon'] }}"></i>
                                    </div>
                                    <h4 class="fw-bold mb-3">{{ $feature['title'] }}</h4>
                                    <p class="text-muted small mb-0">{{ $feature['desc'] }}</p>
                                    <div class="mt-4 text-primary fw-semibold small">Hover to see more <i
                                            class="ri-arrow-right-line ms-1"></i></div>
                                </div>
                                <div class="flip-card-back">
                                    <h4 class="fw-bold mb-3">Core Advantage</h4>
                                    <p class="mb-4">{{ $feature['desc'] }}</p>
                                    <button class="btn btn-light btn-sm rounded-pill px-4">Learn More</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section-padding">
        <div class="container">
            <div
                class="card bg-primary text-white overflow-hidden border-0 rounded-4 shadow-xl position-relative reveal-on-scroll">
                <div class="hero-mesh"></div>
                <div class="card-body p-5 p-lg-12 text-center position-relative z-1">
                    <h2 class="fw-bold mb-4 display-4">Ready to streamline your invoicing?</h2>
                    <p class="lead mb-5 opacity-90 mx-auto" style="max-width: 650px;">Join thousands of businesses that
                        trust Digital Invoicing for their financial needs. Start your free trial today.</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="btn btn-light text-primary btn-lg fw-bold px-5 py-3 rounded-pill shadow-lg btn-premium">
                                Create Free Account
                            </a>
                        @endif
                        <a href="#features" class="btn btn-outline-light btn-lg fw-bold px-5 py-3 rounded-pill">
                            Explore Features
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <!-- GSAP CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <!-- New Era Motion JS -->
    <script src="{{ asset('build/assets/js/motion.js') }}"></script>
@endsection
