
@extends('layouts.landing-master')

@section('styles')



@endsection

@section('content')
	
                <!-- Start:: Landing Banner -->
                <div class="landing-banner" id="home">
                    <div class="banner-image-container">
                        <img src="{{asset('build/assets/images/media/backgrounds/5.png')}}" alt="">
                    </div>
                    <div class="container">
                        <div class="row">
                            <div class="col-xl-6 my-auto">
                                <div class="d-inline-flex align-items-center gap-2 text-default badge bg-white border fs-13 rounded-pill"><span class="avatar avatar-xs avatar-rounded bg-warning"><i class="ri-flashlight-fill fs-14"></i></span>Seamless Integration</div>
                                <h1 class="fw-semibold mt-3 landing-banner-heading">Track key metrics <br> and <span class="text-primary">optimize</span> your business</h1>
                                <span class="d-block fs-18">Keep track of important business metrics and optimize processes to drive growth and improve efficiency.</span>
                                <div class="btn-list banner-buttons">
                                    <a href="{{url('index')}}" class="btn btn-primary btn-lg rounded-pill btn-w-lg">Get Started for free</a>
                                    <a class="btn btn-lg btn-light border rounded-pill btn-w-lg" href="javascript:void(0);">View Demo</a>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="banner-main-img text-end d-xl-block d-none">
                                    <img src="{{asset('build/assets/images/media/backgrounds/7.png')}}" alt="" class="img-fluid">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End:: Landing Banner -->

                <!-- Start:: Section-1 -->
                <section class="section">
                    <div class="container">
                        <div class="heading-section">
                            <div class="heading-subtitle">Clients</div>
                            <div class="heading-title">Trusted by Leading Companies Worldwide</div>
                            <div class="heading-description">
                                Thousands of businesses trust our solutions <br> to optimize their operations and drive growth.
                            </div>
                        </div>
                        <div class="swiper trusted-clients">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide">
                                    <div class="card custom-card trusted-clients-container mb-0 border border-dashed">
                                        <div class="card-body">
                                            <img src="{{asset('build/assets/images/company-logos/13.png')}}" alt="" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card trusted-clients-container mb-0 border border-dashed">
                                        <div class="card-body">
                                            <img src="{{asset('build/assets/images/company-logos/14.png')}}" alt="" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card trusted-clients-container mb-0 border border-dashed">
                                        <div class="card-body">
                                            <img src="{{asset('build/assets/images/company-logos/15.png')}}" alt="" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card trusted-clients-container mb-0 border border-dashed">
                                        <div class="card-body">
                                            <img src="{{asset('build/assets/images/company-logos/16.png')}}" alt="" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card trusted-clients-container mb-0 border border-dashed">
                                        <div class="card-body">
                                            <img src="{{asset('build/assets/images/company-logos/17.png')}}" alt="" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card trusted-clients-container mb-0 border border-dashed">
                                        <div class="card-body">
                                            <img src="{{asset('build/assets/images/company-logos/18.png')}}" alt="" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card trusted-clients-container mb-0 border border-dashed">
                                        <div class="card-body">
                                            <img src="{{asset('build/assets/images/company-logos/19.png')}}" alt="" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card trusted-clients-container mb-0 border border-dashed">
                                        <div class="card-body">
                                            <img src="{{asset('build/assets/images/company-logos/20.png')}}" alt="" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card trusted-clients-container mb-0 border border-dashed">
                                        <div class="card-body">
                                            <img src="{{asset('build/assets/images/company-logos/12.png')}}" alt="" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- End:: Section-1 -->

                <!-- Start:: Section-2 -->
                <section class="section" id="feature">
                    <div class="container">
                        <div class="heading-section">
                            <div class="heading-subtitle">Features</div>
                            <div class="heading-title">Powerful Features to Streamline Your Workflow</div>
                            <div class="heading-description">
                                Boost productivity and simplify management with our powerful, real-time features.
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-4 col-md-6">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="lh-1 mb-3">
                                            <span class="avatar avatar-lg avatar-rounded bg-primary-transparent svg-primary">   
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M104,208V104H32v96a8,8,0,0,0,8,8H96" opacity="0.2"/><line x1="32" y1="104" x2="224" y2="104" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="104" y1="104" x2="104" y2="208" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><rect x="32" y="48" width="192" height="160" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                            </span>
                                        </div>
                                        <h5 class="fw-semibold">Dashboard Customization</h5>
                                        <span class="fs-15 text-muted">
                                            Easily customize the layout and widgets of your dashboard for a personalized admin experience.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="lh-1 mb-3">
                                            <span class="avatar avatar-lg avatar-rounded bg-secondary-transparent svg-secondary">   
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M32,48H208a16,16,0,0,1,16,16V208a0,0,0,0,1,0,0H32a0,0,0,0,1,0,0V48A0,0,0,0,1,32,48Z" opacity="0.2"/><polyline points="224 208 32 208 32 48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="224 96 160 152 96 104 32 160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                            </span>
                                        </div>
                                        <h5 class="fw-semibold">Interactive Charts & Graphs</h5>
                                        <span class="fs-15 text-muted">
                                            Display data dynamically with fully customizable, interactive charts and graphs.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="lh-1 mb-3">
                                            <span class="avatar avatar-lg avatar-rounded bg-success-transparent svg-success">   
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,129.09,32.7,76.93a8,8,0,0,0-.7,3.25v95.64a8,8,0,0,0,4.16,7l88,48.18a8,8,0,0,0,3.84,1Z" opacity="0.2"/><polyline points="32.7 76.92 128 129.08 223.3 76.92" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="129.09" x2="128" y2="231.97" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M219.84,182.84l-88,48.18a8,8,0,0,1-7.68,0l-88-48.18a8,8,0,0,1-4.16-7V80.18a8,8,0,0,1,4.16-7l88-48.18a8,8,0,0,1,7.68,0l88,48.18a8,8,0,0,1,4.16,7v95.64A8,8,0,0,1,219.84,182.84Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="81.56 48.31 176 100 176 152" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                            </span>
                                        </div>
                                        <h5 class="fw-semibold">User Interface Components</h5>
                                        <span class="fs-15 text-muted">
                                            Access a wide range of pre-built UI components to quickly create a clean and consistent interface.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="lh-1 mb-3">
                                            <span class="avatar avatar-lg avatar-rounded bg-warning-transparent svg-warning">   
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><rect x="64" y="56" width="128" height="144" opacity="0.2"/><rect x="64" y="24" width="128" height="208" rx="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="56" x2="192" y2="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="200" x2="192" y2="200" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                            </span>
                                        </div>
                                        <h5 class="fw-semibold">Responsive Design</h5>
                                        <span class="fs-15 text-muted">
                                            Ensure your admin panel looks great on all devices with a fully responsive design.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="lh-1 mb-3">
                                            <span class="avatar avatar-lg avatar-rounded bg-info-transparent svg-info">   
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><rect x="32" y="104" width="56" height="96" opacity="0.2"/><path d="M32,56H224a0,0,0,0,1,0,0V192a8,8,0,0,1-8,8H40a8,8,0,0,1-8-8V56A0,0,0,0,1,32,56Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="104" x2="224" y2="104" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="32" y1="152" x2="224" y2="152" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="88" y1="104" x2="88" y2="200" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                            </span>
                                        </div>
                                        <h5 class="fw-semibold">Table Management</h5>
                                        <span class="fs-15 text-muted">
                                            Manage and display large datasets with advanced table components, filters, and pagination.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="lh-1 mb-3">
                                            <span class="avatar avatar-lg avatar-rounded bg-danger-transparent svg-danger">   
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M192,120,136,64l29.66-29.66a8,8,0,0,1,11.31,0L221.66,79a8,8,0,0,1,0,11.31Z" opacity="0.2"/><path d="M92.69,216H48a8,8,0,0,1-8-8V163.31a8,8,0,0,1,2.34-5.65L165.66,34.34a8,8,0,0,1,11.31,0L221.66,79a8,8,0,0,1,0,11.31L98.34,213.66A8,8,0,0,1,92.69,216Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="136" y1="64" x2="192" y2="120" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="164" y1="92" x2="68" y2="188" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="95.49" y1="215.49" x2="40.51" y2="160.51" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                            </span>
                                        </div>
                                        <h5 class="fw-semibold">Forms and Validation</h5>
                                        <span class="fs-15 text-muted">
                                            Create robust forms with validation features for collecting and processing user data.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="lh-1 mb-3">
                                            <span class="avatar avatar-lg avatar-rounded bg-teal-transparent svg-teal">   
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M108.11,28.11A96.09,96.09,0,0,0,227.89,147.89,96,96,0,1,1,108.11,28.11Z" opacity="0.2"/><path d="M108.11,28.11A96.09,96.09,0,0,0,227.89,147.89,96,96,0,1,1,108.11,28.11Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                            </span>
                                        </div>
                                        <h5 class="fw-semibold">Dark Mode / Light Mode</h5>
                                        <span class="fs-15 text-muted">
                                            Switch between dark and light modes to suit user preferences and improve readability.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="lh-1 mb-3">
                                            <span class="avatar avatar-lg avatar-rounded bg-orange-transparent svg-orange">   
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M56,104a72,72,0,0,1,144,0c0,35.82,8.3,64.6,14.9,76A8,8,0,0,1,208,192H48a8,8,0,0,1-6.88-12C47.71,168.6,56,139.81,56,104Z" opacity="0.2"/><path d="M96,192a32,32,0,0,0,64,0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M56,104a72,72,0,0,1,144,0c0,35.82,8.3,64.6,14.9,76A8,8,0,0,1,208,192H48a8,8,0,0,1-6.88-12C47.71,168.6,56,139.81,56,104Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                            </span>
                                        </div>
                                        <h5 class="fw-semibold">Notifications and Alerts</h5>
                                        <span class="fs-15 text-muted">
                                            Set up real-time notifications and alerts to keep users informed of important events and updates.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="lh-1 mb-3">
                                            <span class="avatar avatar-lg avatar-rounded bg-purple-transparent svg-purple">   
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><polygon points="152 32 152 88 208 88 152 32" opacity="0.2"/><path d="M200,224H56a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8h96l56,56V216A8,8,0,0,1,200,224Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="152 32 152 88 208 88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                            </span>
                                        </div>
                                        <h5 class="fw-semibold">Page Templates</h5>
                                        <span class="fs-15 text-muted">
                                            Choose from a variety of pre-built page templates to save time on development.
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- End:: Section-2 -->

                <!-- Start:: Section-3 -->
                <section class="section" id="service">
                    <div class="container">
                        <div class="heading-section">
                            <div class="heading-subtitle">Services</div>
                            <div class="heading-title">Comprehensive Solutions for Your Business Needs</div>
                            <div class="heading-description">
                                Discover our services to streamline operations, boost productivity, <br> and drive growth, from analytics to automation.
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-7">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="card custom-card landing-services-card primary">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start gap-3">
                                                    <div class="lh-1">
                                                        <span class="avatar avatar-lg avatar-rounded bg-primary-transparent svg-primary">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M104,208V104H32v96a8,8,0,0,0,8,8H96" opacity="0.2"/><line x1="32" y1="104" x2="224" y2="104" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="104" y1="104" x2="104" y2="208" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><rect x="32" y="48" width="192" height="160" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="d-block fw-semibold">Customizable Dashboards</h6>
                                                        <span class="d-block text-muted">Personalize your dashboard with customizable widgets & modules.</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card custom-card landing-services-card secondary">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start gap-3">
                                                    <div class="lh-1">
                                                        <span class="avatar avatar-lg avatar-rounded bg-secondary-transparent svg-secondary">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M32,48H208a16,16,0,0,1,16,16V208a0,0,0,0,1,0,0H32a0,0,0,0,1,0,0V48A0,0,0,0,1,32,48Z" opacity="0.2"/><polyline points="224 208 32 208 32 48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="224 96 160 152 96 104 32 160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="d-block fw-semibold">Real-Time Analytics</h6>
                                                        <span class="d-block text-muted">Access real-time data to drive fast, informed decisions.</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card custom-card landing-services-card warning">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start gap-3">
                                                    <div class="lh-1">
                                                        <span class="avatar avatar-lg avatar-rounded bg-warning-transparent svg-warning">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="96" r="64" opacity="0.2"/><circle cx="128" cy="96" r="64" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M32,216c19.37-33.47,54.55-56,96-56s76.63,22.53,96,56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="d-block fw-semibold">User Management</h6>
                                                        <span class="d-block text-muted">Efficiently manage roles, permissions, and team access.</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card custom-card landing-services-card success">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start gap-3">
                                                    <div class="lh-1">
                                                        <span class="avatar avatar-lg avatar-rounded bg-success-transparent svg-success">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M212,132l-58.63,58.63a32,32,0,0,1-45.25,0L65.37,147.88a32,32,0,0,1,0-45.25L124,44Z" opacity="0.2"/><line x1="144" y1="64" x2="184" y2="24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="232" y1="72" x2="192" y2="112" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="224" y1="144" x2="112" y2="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M212,132l-58.63,58.63a32,32,0,0,1-45.25,0L65.37,147.88a32,32,0,0,1,0-45.25L124,44" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="86.75" y1="169.25" x2="32" y2="224" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="d-block fw-semibold">Seamless Integration</h6>
                                                        <span class="d-block text-muted">Integrate effortlessly with third-party tools and services.</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-5 my-auto">
                                <div class="services-image-container text-end d-xl-block d-none">
                                    <img src="{{asset('build/assets/images/media/media-67.png')}}" alt="" class="img-fluid">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- End:: Section-3 -->

                <!-- Start:: Buy Now Section -->
                <section class="section section-md section-primary text-fixed-white py-5 buy-now-section">
                    <div class="testimonials-background-container">
                        <img src="{{asset('build/assets/images/media/backgrounds/1.png')}}" alt="">
                    </div>
                    <div class="container">
                        <div class="d-flex align-items-center gap-2 justify-content-between flex-wrap">
                            <div>
                                <h4 class="fw-semibold text-fixed-white">Transform Your Workflow Today</h4>
                                <span class="d-block fs-16 op-8">Unlock all the powerful features of our admin template. <br> Purchase now or try the demo to see it in action!</span>
                            </div>
                            <div class="btn-list">
                                <a href="{{url('index')}}" class="btn btn-danger btn-lg btn-w-md d-inline-flex align-items-center">View Demo<i class="ti ti-arrow-narrow-right ms-2"></i></a>
                                <button class="btn btn-success btn-lg btn-w-md d-inline-flex align-items-center">Buy Now<i class="ti ti-shopping-cart ms-2"></i></button>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- End:: Buy Now Section -->

                <!-- Start:: Section-4 -->
                <section class="section" id="price">
                    <div class="container">
                        <div class="heading-section">
                            <div class="heading-subtitle">Our Pricing</div>
                            <div class="heading-title">Choose the Plan That Fits Your Needs</div>
                            <div class="heading-description mb-3">
                                Select the right fit and enjoy seamless access to all features.
                            </div>
                            <div class="tab-style-1 border bg-white rounded-0 d-inline-block"> 
                                <ul class="nav nav-pills" role="tablist"> 
                                    <li class="nav-item" role="presentation"> 
                                        <button type="button" class="nav-link rounded-0 fw-medium active" data-bs-toggle="pill" data-bs-target="#pricing1-monthly" aria-selected="true" role="tab">Monthly</button> 
                                    </li> 
                                    <li class="nav-item" role="presentation"> 
                                        <button type="button" class="nav-link rounded-0 fw-medium" data-bs-toggle="pill" data-bs-target="#pricing1-yearly" aria-selected="false" role="tab" tabindex="-1">Yearly</button> 
                                    </li> 
                                </ul> 
                            </div>
                        </div>
                        <div class="tab-content">
                            <div class="tab-pane show active p-0 border-0" id="pricing1-monthly" role="tabpanel">
                                <div class="row">
                                    <div class="col-xl-4">
                                        <div class="card custom-card">
                                            <div class="card-body p-4 text-center">
                                                <h5 class="fw-semibold mb-3">Basic</h5>
                                                <div class="d-flex align-items-end justify-content-center gap-1 mb-4">
                                                    <h2 class="fw-semibold mb-0 lh-1 text-primary">$15</h2>
                                                    <span class="fs-13 text-muted">/ Month</span>
                                                </div>
                                                <ul class="list-unstyled pricing-features-list-1 mx-5 text-start mb-4">
                                                    <li>
                                                        Access to core features
                                                    </li>
                                                    <li>
                                                        5GB storage
                                                    </li>
                                                    <li>
                                                        Basic customer support
                                                    </li>
                                                    <li>
                                                        1 user access
                                                    </li>
                                                    <li>
                                                        Email notifications
                                                    </li>
                                                </ul>
                                                <div>
                                                    <span class="badge bg-secondary-transparent py-1 px-3 fs-13 rounded-pill fw-normal">25% Off</span>
                                                    <div class="d-grid mt-3">
                                                        <button class="btn btn-primary btn-lg">Choose Plan</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-4">
                                        <div class="card custom-card card-bg-primary">
                                            <div class="card-body p-4 text-center">
                                                <h5 class="fw-semibold mb-3 text-fixed-white">Pro</h5>
                                                <div class="d-flex align-items-end justify-content-center gap-1 mb-4">
                                                    <h2 class="fw-semibold mb-0 lh-1 text-fixed-white">$45</h2>
                                                    <span class="fs-13">/ Month</span>
                                                </div>
                                                <ul class="list-unstyled pricing-features-list-1 mx-5 text-start mb-4">
                                                    <li>
                                                        All Basic Plan Features
                                                    </li>
                                                    <li>
                                                        50GB storage
                                                    </li>
                                                    <li>
                                                        Priority customer support
                                                    </li>
                                                    <li>
                                                        5 user access
                                                    </li>
                                                    <li>
                                                        Advanced analytics
                                                    </li>
                                                </ul>
                                                <div>
                                                    <span class="badge bg-white-transparent py-1 px-3 fs-13 rounded-pill fw-normal">40% Off</span>
                                                    <div class="d-grid mt-3">
                                                        <button class="btn btn-light btn-lg">Choose Plan</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-4">
                                        <div class="card custom-card">
                                            <div class="card-body p-4 text-center">
                                                <h5 class="fw-semibold mb-3">Enterprise</h5>
                                                <div class="d-flex align-items-end justify-content-center gap-1 mb-4">
                                                    <h2 class="fw-semibold mb-0 lh-1 text-primary">$99</h2>
                                                    <span class="fs-13 text-muted">/ Month</span>
                                                </div>
                                                <ul class="list-unstyled pricing-features-list-1 mx-5 text-start mb-4">
                                                    <li>
                                                        All Pro Plan Features
                                                    </li>
                                                    <li>
                                                        Unlimited storage
                                                    </li>
                                                    <li>
                                                        Dedicated account manager
                                                    </li>
                                                    <li>
                                                        20 user access
                                                    </li>
                                                    <li>
                                                        Customizable workflows
                                                    </li>
                                                </ul>
                                                <div>
                                                    <span class="badge bg-secondary-transparent py-1 px-3 fs-13 rounded-pill fw-normal">50% Off</span>
                                                    <div class="d-grid mt-3">
                                                        <button class="btn btn-primary btn-lg">Choose Plan</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane p-0 border-0" id="pricing1-yearly" role="tabpanel">
                                <div class="row">
                                    <div class="col-xl-4">
                                        <div class="card custom-card">
                                            <div class="card-body p-4 text-center">
                                                <h5 class="fw-semibold mb-3">Basic</h5>
                                                <div class="d-flex align-items-end justify-content-center gap-1 mb-4">
                                                    <h2 class="fw-semibold mb-0 lh-1 text-primary">$150</h2>
                                                    <span class="fs-13 text-muted">/ Year</span>
                                                </div>
                                                <ul class="list-unstyled pricing-features-list-1 mx-5 text-start mb-4">
                                                    <li>
                                                        Access to core features
                                                    </li>
                                                    <li>
                                                        5GB storage
                                                    </li>
                                                    <li>
                                                        Basic customer support
                                                    </li>
                                                    <li>
                                                        1 user access
                                                    </li>
                                                    <li>
                                                        Email notifications
                                                    </li>
                                                </ul>
                                                <div>
                                                    <span class="badge bg-secondary-transparent py-1 px-3 fs-13 rounded-pill fw-normal">25% Off</span>
                                                    <div class="d-grid mt-3">
                                                        <button class="btn btn-primary btn-lg">Choose Plan</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-4">
                                        <div class="card custom-card card-bg-primary">
                                            <div class="card-body p-4 text-center">
                                                <h5 class="fw-semibold mb-3 text-fixed-white">Pro</h5>
                                                <div class="d-flex align-items-end justify-content-center gap-1 mb-4">
                                                    <h2 class="fw-semibold mb-0 lh-1 text-fixed-white">$450</h2>
                                                    <span class="fs-13">/ Year</span>
                                                </div>
                                                <ul class="list-unstyled pricing-features-list-1 mx-5 text-start mb-4">
                                                    <li>
                                                        All Basic Plan Features
                                                    </li>
                                                    <li>
                                                        50GB storage
                                                    </li>
                                                    <li>
                                                        Priority customer support
                                                    </li>
                                                    <li>
                                                        5 user access
                                                    </li>
                                                    <li>
                                                        Advanced analytics
                                                    </li>
                                                </ul>
                                                <div>
                                                    <span class="badge bg-white-transparent py-1 px-3 fs-13 rounded-pill fw-normal">40% Off</span>
                                                    <div class="d-grid mt-3">
                                                        <button class="btn btn-light btn-lg">Choose Plan</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-4">
                                        <div class="card custom-card">
                                            <div class="card-body p-4 text-center">
                                                <h5 class="fw-semibold mb-3">Enterprise</h5>
                                                <div class="d-flex align-items-end justify-content-center gap-1 mb-4">
                                                    <h2 class="fw-semibold mb-0 lh-1 text-primary">$990</h2>
                                                    <span class="fs-13 text-muted">/ Year</span>
                                                </div>
                                                <ul class="list-unstyled pricing-features-list-1 mx-5 text-start mb-4">
                                                    <li>
                                                        All Pro Plan Features
                                                    </li>
                                                    <li>
                                                        Unlimited storage
                                                    </li>
                                                    <li>
                                                        Dedicated account manager
                                                    </li>
                                                    <li>
                                                        20 user access
                                                    </li>
                                                    <li>
                                                        Customizable workflows
                                                    </li>
                                                </ul>
                                                <div>
                                                    <span class="badge bg-secondary-transparent py-1 px-3 fs-13 rounded-pill fw-normal">50% Off</span>
                                                    <div class="d-grid mt-3">
                                                        <button class="btn btn-primary btn-lg">Choose Plan</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- End:: Section-4 -->

                <!-- Start:: Section-5 -->
                <section class="section">
                    <div class="container">
                        <div class="heading-section">
                            <div class="heading-subtitle">FAQ's</div>
                            <div class="heading-title">Need Help? Find Your Answers Here</div>
                            <div class="heading-description">
                                Browse through common questions to get quick solutions. We're here to help!
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="accordion faq-accordion accordions-items-seperate" id="accordionFAQ3">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingcustomicon2Eleven">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapsecustomicon2Eleven" aria-expanded="true" aria-controls="collapsecustomicon2Eleven">
                                                <i class="ri-layout-4-line fw-medium avatar avatar-sm avatar-rounded bg-primary-transparent fs-5 me-2 text-primary flex-shrink-0"></i>How do I customize my dashboard layout?
                                            </button>
                                        </h2>
                                        <div id="collapsecustomicon2Eleven" class="accordion-collapse collapse show" aria-labelledby="headingcustomicon2Eleven" data-bs-parent="#accordionFAQ3">
                                            <div class="accordion-body">
                                                You can easily customize the dashboard by dragging and dropping widgets. Go to the settings menu and select 'Customize Dashboard' to rearrange the layout.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingcustomicon2Twelve">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsecustomicon2Twelve" aria-expanded="false" aria-controls="collapsecustomicon2Twelve">
                                                <i class="ri-plug-line fw-medium avatar avatar-sm avatar-rounded bg-primary-transparent fs-5 me-2 text-primary flex-shrink-0"></i>Can I integrate third-party apps with the admin template?
                                            </button>
                                        </h2>
                                        <div id="collapsecustomicon2Twelve" class="accordion-collapse collapse" aria-labelledby="headingcustomicon2Twelve" data-bs-parent="#accordionFAQ3">
                                            <div class="accordion-body">
                                                Yes! Our admin template supports seamless integrations with third-party apps. You can easily connect tools like Google Analytics, CRM software, and more.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingcustomicon2Thirteen">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsecustomicon2Thirteen" aria-expanded="false" aria-controls="collapsecustomicon2Thirteen">
                                                <i class="ri-phone-line fw-medium avatar avatar-sm avatar-rounded bg-primary-transparent fs-5 me-2 text-primary flex-shrink-0"></i>Is this admin template mobile responsive?
                                            </button>
                                        </h2>
                                        <div id="collapsecustomicon2Thirteen" class="accordion-collapse collapse" aria-labelledby="headingcustomicon2Thirteen" data-bs-parent="#accordionFAQ3">
                                            <div class="accordion-body">
                                                Absolutely! The admin template is fully responsive and optimized for mobile, ensuring that it works perfectly on smartphones and tablets.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingcustomicon2Fourteen">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsecustomicon2Fourteen" aria-expanded="false" aria-controls="collapsecustomicon2Fourteen">
                                                <i class="ri-user-settings-line fw-medium avatar avatar-sm avatar-rounded bg-primary-transparent fs-5 me-2 text-primary flex-shrink-0"></i>How do I manage user roles and permissions?
                                            </button>
                                        </h2>
                                        <div id="collapsecustomicon2Fourteen" class="accordion-collapse collapse" aria-labelledby="headingcustomicon2Fourteen" data-bs-parent="#accordionFAQ3">
                                            <div class="accordion-body">
                                                You can manage user roles and permissions under the 'User Management' section. Simply assign roles like Admin, Manager, or Viewer to control access.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingcustomicon2Fifteen">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsecustomicon2Fifteen" aria-expanded="false" aria-controls="collapsecustomicon2Fifteen">
                                                <i class="ri-file-excel-line fw-medium avatar avatar-sm avatar-rounded bg-primary-transparent fs-5 me-2 text-primary flex-shrink-0"></i>Can I export data from the reports section?
                                            </button>
                                        </h2>
                                        <div id="collapsecustomicon2Fifteen" class="accordion-collapse collapse" aria-labelledby="headingcustomicon2Fifteen" data-bs-parent="#accordionFAQ3">
                                            <div class="accordion-body">
                                                Yes, you can easily export reports as CSV, PDF, or Excel files. Just click the export button at the top of the reports page."
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingcustomicon2Sixteen">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsecustomicon2Sixteen" aria-expanded="false" aria-controls="collapsecustomicon2Sixteen">
                                                <i class="ri-notification-line fw-medium avatar avatar-sm avatar-rounded bg-primary-transparent fs-5 me-2 text-primary flex-shrink-0"></i>How do I enable notifications for updates?
                                            </button>
                                        </h2>
                                        <div id="collapsecustomicon2Sixteen" class="accordion-collapse collapse" aria-labelledby="headingcustomicon2Sixteen" data-bs-parent="#accordionFAQ3">
                                            <div class="accordion-body">
                                                Notifications can be enabled under the 'Settings' section. You can choose to receive real-time alerts via email or in-app for important updates.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- End:: Section-5 -->

                <!-- Start:: Section-6 -->
                <section class="section section-primary testimonials-section">
                    <div class="testimonials-background-container">
                        <img src="{{asset('build/assets/images/media/backgrounds/2.png')}}" alt="">
                    </div>
                    <div class="container">
                        <div class="heading-section">
                            <div class="heading-subtitle">Testimonials</div>
                            <div class="heading-title">See What Our Customers Say</div>
                            <div class="heading-description">
                                Read real testimonials from customers who’ve <br> improved their business with our platform.
                            </div>
                        </div>
                        <div class="swiper pagination-dynamic testimonialSwiperService testimonials-swiper-2">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide">
                                    <div class="card custom-card testimonial-style-2-card primary border-0">
                                        <div class="card-body p-5">
                                            <h5 class="fw-semibold mb-3">Our team’s productivity skyrocketed!</h5>
                                            <div class="mb-3 text-muted">
                                                The customizable dashboard helped us track key metrics in real-time. It’s user-friendly and has made managing our operations so much easier.
                                            </div>
                                            <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                                <div class="lh-1">
                                                    <span class="avatar rounded-circle me-2">
                                                        <img src="{{asset('build/assets/images/faces/1.jpg')}}" alt="" class="img-fluid rounded-circle">
                                                    </span>
                                                </div>
                                                <div class="flex-fill">
                                                    <span class="d-block fw-semibold">Sarah Johnson</span>
                                                    <span>Operations Manager</span>
                                                </div>
                                                <div class="text-warning d-block ms-1">
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-half-line"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card testimonial-style-2-card success border-0">
                                        <div class="card-body p-5">
                                            <h5 class="fw-semibold mb-3">A game-changer for our workflow.</h5>
                                            <div class="mb-3 text-muted">
                                                With seamless integrations and powerful analytics, we can make faster decisions. This admin template has truly optimized our business processes.
                                            </div>
                                            <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                                <div class="lh-1">
                                                    <span class="avatar rounded-circle me-2">
                                                        <img src="{{asset('build/assets/images/faces/10.jpg')}}" alt="" class="img-fluid rounded-circle">
                                                    </span>
                                                </div>
                                                <div class="flex-fill">
                                                    <span class="d-block fw-semibold">James Lee</span>
                                                    <span>Product Director</span>
                                                </div>
                                                <div class="text-warning d-block ms-1">
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-half-line"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card testimonial-style-2-card warning border-0">
                                        <div class="card-body p-5">
                                            <h5 class="fw-semibold mb-3">Simple, powerful, and efficient!</h5>
                                            <div class="mb-3 text-muted">
                                                We implemented the template across multiple teams. The ease of use and flexibility have improved how we manage data and projects daily.
                                            </div>
                                            <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                                <div class="lh-1">
                                                    <span class="avatar rounded-circle me-2">
                                                        <img src="{{asset('build/assets/images/faces/9.jpg')}}" alt="" class="img-fluid rounded-circle">
                                                    </span>
                                                </div>
                                                <div class="flex-fill">
                                                    <span class="d-block fw-semibold">Alex Smith</span>
                                                    <span>Project Lead</span>
                                                </div>
                                                <div class="text-warning d-block ms-1">
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-half-line"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card testimonial-style-2-card info border-0">
                                        <div class="card-body p-5">
                                            <h5 class="fw-semibold mb-3">Effortless reporting and data management.</h5>
                                            <div class="mb-3 text-muted">
                                                The real-time analytics have allowed us to monitor key performance indicators at a glance. A perfect solution for data-driven teams!
                                            </div>
                                            <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                                <div class="lh-1">
                                                    <span class="avatar rounded-circle me-2">
                                                        <img src="{{asset('build/assets/images/faces/5.jpg')}}" alt="" class="img-fluid rounded-circle">
                                                    </span>
                                                </div>
                                                <div class="flex-fill">
                                                    <span class="d-block fw-semibold">David Thompson</span>
                                                    <span>Marketing Director</span>
                                                </div>
                                                <div class="text-warning d-block ms-1">
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-half-line"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card testimonial-style-2-card danger border-0">
                                        <div class="card-body p-5">
                                            <h5 class="fw-semibold mb-3">The best decision for our company.</h5>
                                            <div class="mb-3 text-muted">
                                                "After adopting this admin template, everything is much more organized. We now have full control and visibility over our business operations.
                                            </div>
                                            <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                                <div class="lh-1">
                                                    <span class="avatar rounded-circle me-2">
                                                        <img src="{{asset('build/assets/images/faces/14.jpg')}}" alt="" class="img-fluid rounded-circle">
                                                    </span>
                                                </div>
                                                <div class="flex-fill">
                                                    <span class="d-block fw-semibold">Michael Brown</span>
                                                    <span>CEO</span>
                                                </div>
                                                <div class="text-warning d-block ms-1">
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-half-line"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="card custom-card testimonial-style-2-card teal border-0">
                                        <div class="card-body p-5">
                                            <h5 class="fw-semibold mb-3">Made managing our business a breeze.</h5>
                                            <div class="mb-3 text-muted">
                                                The intuitive interface and powerful features have simplified our admin tasks. We can now monitor sales, track progress, and generate reports effortlessly.
                                            </div>
                                            <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                                <div class="lh-1">
                                                    <span class="avatar rounded-circle me-2">
                                                        <img src="{{asset('build/assets/images/faces/3.jpg')}}" alt="" class="img-fluid rounded-circle">
                                                    </span>
                                                </div>
                                                <div class="flex-fill">
                                                    <span class="d-block fw-semibold">Emily Clark</span>
                                                    <span>Sales Manager</span>
                                                </div>
                                                <div class="text-warning d-block ms-1">
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-fill"></i>
                                                    <i class="ri-star-half-line"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- End:: Section-6 -->

                <!-- Start:: Setion-7 -->
                <section class="section bg-light py-5">
                    <div class="container">
                        <div class="row gy-4">
                            <div class="col-lg-3 col-6">
                                <div class="text-center stats-point one">
                                    <h4 class="fw-semibold mb-1">12,345</h4>
                                    <div class="text-muted fs-16">Customers</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="text-center stats-point two">
                                    <h4 class="fw-semibold mb-1">56,789</h4>
                                    <div class="text-muted fs-16">Products Sold</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="text-center stats-point three">
                                    <h4 class="fw-semibold mb-1">1,234</h4>
                                    <div class="text-muted fs-16">Projects</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="text-center stats-point four">
                                    <h4 class="fw-semibold mb-1">98%</h4>
                                    <div class="text-muted fs-16">Client Satisfaction Rate</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- End:: Setion-7 -->

                <!-- Start:: Section-8 -->
                <section class="section">
                    <div class="container">
                        <div class="heading-section">
                            <div class="heading-subtitle">Workflow</div>
                            <div class="heading-title">Streamlined Workflow for Efficient Results</div>
                            <div class="heading-description">
                                Discover how our structured workflow drives productivity, <br> ensuring seamless execution from start to finish.
                            </div>
                        </div>
                        <div class="row justify-content-between">
                            <div class="col-lg-3">
                                <div class="card custom-card border-0 shadow-none">
                                    <div class="card-body p-4 text-center">
                                        <div class="step-arrow-container d-lg-block d-none">
                                            <img src="{{asset('build/assets/images/media/backgrounds/3.png')}}" alt="" class="img-fluid">
                                        </div>
                                        <div class="lh-1 mb-3">
                                            <span class="avatar avatar-lg svg-primary text-primary workflow-icon-container">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M104,208V104H32v96a8,8,0,0,0,8,8H96" opacity="0.2"/><line x1="32" y1="104" x2="224" y2="104" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="104" y1="104" x2="104" y2="208" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><rect x="32" y="48" width="192" height="160" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                            </span>
                                        </div>
                                        <h5 class="fw-semibold">Dashboard Setup</h5>
                                        <span class="d-block text-muted">Quickly configure your dashboard with widgets, charts, and modules to track essential metrics.</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card custom-card border-0 shadow-none">
                                    <div class="card-body p-4 text-center">
                                        <div class="step-arrow-container d-lg-block d-none">
                                            <img src="{{asset('build/assets/images/media/backgrounds/4.png')}}" alt="" class="img-fluid">
                                        </div>
                                        <div class="lh-1 mb-3">
                                            <span class="avatar avatar-lg svg-warning text-warning workflow-icon-container">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="84" cy="108" r="52" opacity="0.2"/><path d="M10.23,200a88,88,0,0,1,147.54,0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M172,160a87.93,87.93,0,0,1,73.77,40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="84" cy="108" r="52" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M152.69,59.7A52,52,0,1,1,172,160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                            </span>
                                        </div>
                                        <h5 class="fw-semibold">User Management</h5>
                                        <span class="d-block text-muted">Easily manage user roles, permissions, and access levels to keep your team organized.</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card custom-card border-0 shadow-none">
                                    <div class="card-body p-4 text-center">
                                        <div class="lh-1 mb-3">
                                            <span class="avatar avatar-lg svg-success text-success workflow-icon-container">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M32,48H208a16,16,0,0,1,16,16V208a0,0,0,0,1,0,0H32a0,0,0,0,1,0,0V48A0,0,0,0,1,32,48Z" opacity="0.2"/><polyline points="224 208 32 208 32 48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="224 96 160 152 96 104 32 160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                            </span>
                                        </div>
                                        <h5 class="fw-semibold">Data Analytics</h5>
                                        <span class="d-block text-muted">Monitor real-time data and generate insightful reports to make informed business decisions.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- End:: Section-8 -->

                <!-- Start:: Section-9 -->
                <section class="section">
                    <div class="container">
                        <div class="heading-section">
                            <div class="heading-subtitle">Our Team</div>
                            <div class="heading-title">Meet Our Expert Team</div>
                            <div class="heading-description">
                                Get to know the talented individuals behind our success, <br> working together to deliver exceptional results.
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-3 col-md-6">
                                <div class="card custom-card">
                                    <img src="{{asset('build/assets/images/faces/team/1.png')}}" class="card-img-top" alt="...">
                                    <div class="card-body text-center">
                                        <h6 class="fw-semibold mb-0">
                                            John Smith
                                        </h6>
                                        <span class="text-muted fs-13">Senior Developer</span>
                                        <div class="btn-list mt-3">
                                            <button class="btn btn-light btn-icon rounded-circle border">
                                                <i class="ri-facebook-circle-fill lh-1"></i>
                                            </button>
                                            <button class="btn btn-light btn-icon rounded-circle border">
                                                <i class="ri-twitter-x-fill lh-1"></i>
                                            </button>
                                            <button class="btn btn-light btn-icon rounded-circle border">
                                                <i class="ri-linkedin-box-fill lh-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="card custom-card">
                                    <img src="{{asset('build/assets/images/faces/team/2.png')}}" class="card-img-top" alt="...">
                                    <div class="card-body text-center">
                                        <h6 class="fw-semibold mb-0">
                                            Emily Johnson
                                        </h6>
                                        <span class="text-muted fs-13">Product Manager</span>
                                        <div class="btn-list mt-3">
                                            <button class="btn btn-light btn-icon rounded-circle border">
                                                <i class="ri-facebook-circle-fill lh-1"></i>
                                            </button>
                                            <button class="btn btn-light btn-icon rounded-circle border">
                                                <i class="ri-twitter-x-fill lh-1"></i>
                                            </button>
                                            <button class="btn btn-light btn-icon rounded-circle border">
                                                <i class="ri-linkedin-box-fill lh-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="card custom-card">
                                    <img src="{{asset('build/assets/images/faces/team/3.png')}}" class="card-img-top" alt="...">
                                    <div class="card-body text-center">
                                        <h6 class="fw-semibold mb-0">
                                            Sarah Davis
                                        </h6>
                                        <span class="text-muted fs-13">Marketing Specialist</span>
                                        <div class="btn-list mt-3">
                                            <button class="btn btn-light btn-icon rounded-circle border">
                                                <i class="ri-facebook-circle-fill lh-1"></i>
                                            </button>
                                            <button class="btn btn-light btn-icon rounded-circle border">
                                                <i class="ri-twitter-x-fill lh-1"></i>
                                            </button>
                                            <button class="btn btn-light btn-icon rounded-circle border">
                                                <i class="ri-linkedin-box-fill lh-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="card custom-card">
                                    <img src="{{asset('build/assets/images/faces/team/4.png')}}" class="card-img-top" alt="...">
                                    <div class="card-body text-center">
                                        <h6 class="fw-semibold mb-0">
                                            Michael Brown
                                        </h6>
                                        <span class="text-muted fs-13">Lead Designer</span>
                                        <div class="btn-list mt-3">
                                            <button class="btn btn-light btn-icon rounded-circle border">
                                                <i class="ri-facebook-circle-fill lh-1"></i>
                                            </button>
                                            <button class="btn btn-light btn-icon rounded-circle border">
                                                <i class="ri-twitter-x-fill lh-1"></i>
                                            </button>
                                            <button class="btn btn-light btn-icon rounded-circle border">
                                                <i class="ri-linkedin-box-fill lh-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- End:: Section-9 -->

                <!-- Start:: Section-10 -->
                <section class="section" id="contactus">
                    <div class="container">
                        <div class="heading-section">
                            <div class="heading-subtitle">Contact Us</div>
                            <div class="heading-title">Get in Touch With Us</div>
                            <div class="heading-description">
                                Have questions or need assistance? Our team is here to help. <br> Reach out to us anytime for support or inquiries.
                            </div>
                        </div>
                        <div class="row gy-4 justify-content-between">
                            <div class="col-xl-6">
                                <h6 class="fw-semibold mb-4">Get In Touch !</h6>
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="contact-address-firstname" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="contact-address-firstname" placeholder="Enter Name">
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="contact-address-lastname" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="contact-address-lastname" placeholder="Enter Name">
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="contact-address-email" class="form-label">Email Id</label>
                                        <input type="email" class="form-control" id="contact-address-email" placeholder="Enter Email Id">
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="contact-address-phone" class="form-label">Phone No</label>
                                        <input type="text" class="form-control" id="contact-address-phone" placeholder="Enter Phone No">
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="contact-mail-message" class="form-label">Message</label>
                                        <textarea class="form-control" id="contact-mail-message" rows="4" placeholder="Enter Your Query ?"></textarea>
                                    </div>
                                </div>
                                <div class="d-grid mt-3">
                                    <button class="btn btn-primary">Submit<i class="ti ti-arrow-narrow-right ms-1 align-middle"></i></button>
                                </div>
                            </div>
                            <div class="col-xl-5">
                                <div class="row gy-5">
                                    <div class="col-xl-12">
                                        <div class="d-flex align-items-start gap-3">
                                            <div>
                                                <span class="avatar avatar-lg bg-primary-transparent svg-primary">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><path d="M0 0h24v24H0z" fill="none"></path><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"></path></svg>
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="fs-13 text-muted text-uppercase">Address :</h5>
                                                <span class="d-block fs-12 text-muted mb-2">Visit us in person From Mon-Fri 9:00am - 6:00pm</span>
                                                <div class="fw-semibold">123 Health Street, Suite 456
                                                    <br>Wellness City, HC 78910<br>
                                                    Country Name</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-12">
                                        <div class="d-flex align-items-start gap-3">
                                            <div>
                                                <span class="avatar avatar-lg bg-primary-transparent svg-primary">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><path d="M0 0h24v24H0z" fill="none"></path><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56-.35-.12-.74-.03-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"></path></svg>
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="fs-13 text-muted text-uppercase">Phone Number :</h5>
                                                <span class="d-block fs-12 text-muted mb-2">Call our team Mon-Fri 9:00am - 6:00pm</span>
                                                <div class="fw-semibold">+1 (555) 123-4567</div>
                                            </div>
                                        </div>   
                                    </div>
                                    <div class="col-xl-12">
                                        <div class="d-flex align-items-start gap-3">
                                            <div>
                                                <span class="avatar avatar-lg bg-primary-transparent svg-primary">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#5f6368"><path d="M0 0h24v24H0z" fill="none"></path><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"></path></svg>
                                                </span>    
                                            </div>
                                            <div>
                                                <h5 class="fs-13 text-muted text-uppercase">Email ID :</h5>
                                                <div class="fw-semibold lh-1">contact@healthclinic.com</div>
                                            </div>
                                        </div>   
                                    </div>
                                    <div class="col-xl-12">
                                        <div class="d-flex align-items-start gap-3">
                                            <div>
                                                <span class="avatar avatar-lg bg-primary-transparent svg-primary">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 5.589 2 10c0 2.908 1.897 5.516 5 6.934V22l5.34-4.004C17.697 17.852 22 14.32 22 10c0-4.411-4.486-8-10-8zm-2.5 9a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"></path></svg>
                                                </span>    
                                            </div>
                                            <div>
                                                <h5 class="fs-13 text-muted text-uppercase">Chat With Us :</h5>
                                                <div class="fw-semibold lh-1 chat-platforms">
                                                    <a href="https://www.facebook.com" target="_blank" class="d-block">
                                                        Chat on Facebook
                                                    </a>
                                                    <a href="https://www.twitter.com" target="_blank" class="d-block">
                                                        Message Us On Twitter
                                                    </a>
                                                    <a href="javascript:void(0);" target="_blank">
                                                        Start a Live Chat
                                                    </a>
                                                </div>
                                            </div>
                                        </div>   
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- End:: Section-10 -->

                <!-- Start:: Buy Now Section -->
                <section class="section section-md section-primary text-fixed-white py-5 buy-now-section">
                    <div class="testimonials-background-container">
                        <img src="{{asset('build/assets/images/media/backgrounds/1.png')}}" alt="">
                    </div>
                    <div class="container">
                        <div class="d-flex align-items-center gap-2 justify-content-between">
                            <div>
                                <h4 class="fw-semibold text-fixed-white">Build and Manage with Ease</h4>
                                <span class="d-block fs-16 op-8">Create, manage, and optimize effortlessly with our admin template.</span>
                            </div>
                            <button class="btn btn-secondary btn-lg btn-w-md d-inline-flex align-items-center">Buy Now<i class="ti ti-shopping-cart ms-2"></i></button>
                        </div>
                    </div>
                </section>
                <!-- End:: Buy Now Section -->

@endsection

@section('scripts')
	


@endsection
