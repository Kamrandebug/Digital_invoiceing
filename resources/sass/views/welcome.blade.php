@extends('layouts.landing-master')

@section('styles')
<style>
    .hero-section {
        background: linear-gradient(135deg, #6c5ffc 0%, #a461ff 100%);
        padding: 120px 0 80px;
        position: relative;
        overflow: hidden;
    }
    .hero-content {
        z-index: 2;
        position: relative;
    }
    .hero-title {
        font-size: 3.5rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 1.5rem;
    }
    .hero-subtitle {
        font-size: 1.25rem;
        opacity: 0.9;
        margin-bottom: 2.5rem;
        font-weight: 400;
    }
    .feature-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.05);
        border-radius: 1rem;
        height: 100%;
    }
    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 1rem 3rem rgba(0,0,0,0.1);
    }
    .avatar-icon {
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin: 0 auto 1.5rem;
        font-size: 2rem;
    }
    .animate-up-down {
        animation: upDown 3s infinite alternate;
    }
    @keyframes upDown {
        0% { transform: translateY(0); }
        100% { transform: translateY(-20px); }
    }
    /* Accessibility improvements */
    .btn:focus {
        box-shadow: 0 0 0 0.25rem rgba(108, 95, 252, 0.5);
    }
</style>
@endsection

@section('content')
    <!-- Hero Section -->
    <section class="hero-section d-flex align-items-center text-white" aria-label="Hero Section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <span class="badge bg-white text-primary mb-3 px-3 py-2 rounded-pill fw-semibold">New v2.0 Released</span>
                    <h1 class="hero-title">Smart Digital Invoicing for Modern Businesses</h1>
                    <p class="hero-subtitle">Streamline your billing process, track payments, and manage your finances with our all-in-one digital invoicing solution.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn btn-light btn-lg text-primary fw-bold px-4 py-3 rounded-3 shadow-sm">
                                    <i class="ri-dashboard-line me-2"></i> Go to Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-light btn-lg text-primary fw-bold px-4 py-3 rounded-3 shadow-sm">
                                    <i class="ri-login-circle-line me-2"></i> Login
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg fw-bold px-4 py-3 rounded-3">
                                        Get Started <i class="ri-arrow-right-line ms-2"></i>
                                    </a>
                                @endif
                            @endauth
                        @endif
                    </div>
                    <div class="mt-5 d-flex align-items-center gap-4 opacity-75">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri-checkbox-circle-fill fs-5"></i> <span>No credit card required</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri-checkbox-circle-fill fs-5"></i> <span>14-day free trial</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block text-center">
                    <img src="{{ asset('build/assets/images/media/backgrounds/2.png') }}" alt="Invoicing Dashboard Preview" class="img-fluid rounded-4 shadow-lg animate-up-down" style="max-height: 500px; transform: perspective(1000px) rotateY(-10deg) rotateX(5deg);">
                </div>
            </div>
        </div>
        <!-- Decorative Elements -->
        <div class="position-absolute top-0 end-0 p-5 opacity-10">
            <i class="ri-money-dollar-circle-fill" style="font-size: 10rem;"></i>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="section py-5 bg-white border-bottom" aria-label="Statistics">
        <div class="container">
            <div class="row text-center justify-content-center">
                <div class="col-6 col-md-3 mb-4 mb-md-0">
                    <h3 class="fw-bold display-6 text-primary mb-1">500+</h3>
                    <p class="text-muted mb-0">Happy Clients</p>
                </div>
                <div class="col-6 col-md-3 mb-4 mb-md-0">
                    <h3 class="fw-bold display-6 text-primary mb-1">10k+</h3>
                    <p class="text-muted mb-0">Invoices Sent</p>
                </div>
                <div class="col-6 col-md-3 mb-4 mb-md-0">
                    <h3 class="fw-bold display-6 text-primary mb-1">$2M+</h3>
                    <p class="text-muted mb-0">Transactions Processed</p>
                </div>
                <div class="col-6 col-md-3">
                    <h3 class="fw-bold display-6 text-primary mb-1">99.9%</h3>
                    <p class="text-muted mb-0">Uptime</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section py-5 bg-light" id="features" aria-label="Features">
        <div class="container py-5">
            <div class="text-center mb-5 mx-auto" style="max-width: 700px;">
                <h6 class="text-primary fw-bold text-uppercase ls-1 mb-2">Why Choose Us</h6>
                <h2 class="fw-bold display-6 mb-3">Everything you need to manage your billing</h2>
                <p class="text-muted fs-5">Powerful features designed to help you get paid faster and manage your business finances with ease.</p>
            </div>

            <div class="row g-4">
                <!-- Feature 1 -->
                <div class="col-md-4">
                    <div class="card feature-card border-0 bg-white">
                        <div class="card-body p-4 text-center">
                            <div class="avatar-icon bg-primary-transparent text-primary">
                                <i class="ri-file-list-3-line"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Easy Invoicing</h4>
                            <p class="text-muted">Create professional invoices in seconds. Customize templates with your logo and brand colors to look professional.</p>
                        </div>
                    </div>
                </div>
                <!-- Feature 2 -->
                <div class="col-md-4">
                    <div class="card feature-card border-0 bg-white">
                        <div class="card-body p-4 text-center">
                            <div class="avatar-icon bg-info-transparent text-info">
                                <i class="ri-secure-payment-line"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Secure Payments</h4>
                            <p class="text-muted">Accept payments securely online via credit card, PayPal, and Stripe. Get paid faster with automated reminders.</p>
                        </div>
                    </div>
                </div>
                <!-- Feature 3 -->
                <div class="col-md-4">
                    <div class="card feature-card border-0 bg-white">
                        <div class="card-body p-4 text-center">
                            <div class="avatar-icon bg-success-transparent text-success">
                                <i class="ri-bar-chart-box-line"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Detailed Reporting</h4>
                            <p class="text-muted">Gain insights into your revenue, outstanding invoices, and financial health with real-time analytics dashboards.</p>
                        </div>
                    </div>
                </div>
                <!-- Feature 4 -->
                <div class="col-md-4">
                    <div class="card feature-card border-0 bg-white">
                        <div class="card-body p-4 text-center">
                            <div class="avatar-icon bg-warning-transparent text-warning">
                                <i class="ri-global-line"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Multi-Currency Support</h4>
                            <p class="text-muted">Bill clients globally in their local currency. Automatic exchange rate conversion keeps your books balanced.</p>
                        </div>
                    </div>
                </div>
                <!-- Feature 5 -->
                <div class="col-md-4">
                    <div class="card feature-card border-0 bg-white">
                        <div class="card-body p-4 text-center">
                            <div class="avatar-icon bg-danger-transparent text-danger">
                                <i class="ri-smartphone-line"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Mobile Friendly</h4>
                            <p class="text-muted">Manage your business on the go. Send invoices and track payments from your smartphone or tablet.</p>
                        </div>
                    </div>
                </div>
                <!-- Feature 6 -->
                <div class="col-md-4">
                    <div class="card feature-card border-0 bg-white">
                        <div class="card-body p-4 text-center">
                            <div class="avatar-icon bg-secondary-transparent text-secondary">
                                <i class="ri-customer-service-2-line"></i>
                            </div>
                            <h4 class="fw-bold mb-3">24/7 Support</h4>
                            <p class="text-muted">Our dedicated support team is available around the clock to help you with any questions or issues.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section py-5">
        <div class="container py-4">
            <div class="card bg-primary text-white overflow-hidden border-0 rounded-4 shadow-lg position-relative">
                <div class="card-body p-5 text-center position-relative z-1">
                    <h2 class="fw-bold mb-4 display-6">Ready to streamline your invoicing?</h2>
                    <p class="lead mb-5 opacity-90 mx-auto" style="max-width: 600px;">Join thousands of businesses that trust Digital Invoicing for their financial needs. Start your free trial today.</p>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-light text-primary btn-lg fw-bold px-5 py-3 rounded-pill shadow-sm hover-scale">
                            Create Free Account
                        </a>
                    @endif
                </div>
                <!-- Decorative Background -->
                <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-white opacity-10" style="width: 400px; height: 400px;"></div>
                <div class="position-absolute bottom-0 end-0 translate-middle rounded-circle bg-white opacity-10" style="width: 300px; height: 300px;"></div>
            </div>
        </div>
    </section>
@endsection
