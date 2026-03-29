<header class="app-header glass-panel border-bottom-0" id="header" style="transition: all 0.4s ease;">

    <!-- Start::main-header-container -->
    <div class="main-header-container container-fluid">

        <!-- Start::header-content-left -->
        <div class="header-content-left">

            <!-- Start::header-element -->
            <div class="header-element">
                <div class="horizontal-logo">
                    <a href="{{ url('/') }}" class="header-logo">
                        <img src="{{ asset('build/assets/images/custom/logo.png') }}" alt="logo" class="desktop-logo"
                            style="height: 45px; width: auto;">
                        <img src="{{ asset('build/assets/images/custom/logo.png') }}" alt="logo" class="toggle-logo"
                            style="height: 45px; width: auto;">
                        <img src="{{ asset('build/assets/images/custom/logo.png') }}" alt="logo"
                            class="desktop-dark" style="height: 45px; width: auto;">
                        <img src="{{ asset('build/assets/images/custom/logo.png') }}" alt="logo" class="toggle-dark"
                            style="height: 45px; width: auto;">
                    </a>
                </div>
            </div>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <div class="header-element">
                <!-- Start::header-link -->
                <a href="javascript:void(0);" class="sidemenu-toggle header-link" data-bs-toggle="sidebar">
                    <span class="open-toggle">
                        <i class="ri-menu-3-line fs-20"></i>
                    </span>
                </a>
                <!-- End::header-link -->
            </div>
            <!-- End::header-element -->

        </div>
        <!-- End::header-content-left -->

        <!-- Start::header-content-right -->
        <div class="header-content-right">

            <!-- Start::header-element -->
            <div class="header-element align-items-center">
                <!-- Start::header-link|switcher-icon -->
                <div class="btn-list d-lg-none d-block gap-1">
                    @guest
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="btn btn-primary-light btn-sm">Login</a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-primary-light btn-sm">Register</a>
                        @endif
                    @else
                        <a href="{{ route('dashboard') }}" class="btn btn-primary-light btn-sm">Dashboard</a>
                    @endguest
                    <button class="btn btn-icon btn-success switcher-icon" data-bs-toggle="offcanvas"
                        data-bs-target="#switcher-canvas">
                        <i class="ri-settings-3-line"></i>
                    </button>
                </div>
                <!-- End::header-link|switcher-icon -->
            </div>
            <!-- End::header-element -->

        </div>
        <!-- End::header-content-right -->

    </div>
    <!-- End::main-header-container -->

</header>
