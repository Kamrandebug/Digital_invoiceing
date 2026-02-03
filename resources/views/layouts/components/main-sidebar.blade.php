
			<aside class="app-sidebar sticky" id="sidebar">

				<!-- Start::main-sidebar-header -->
				<div class="main-sidebar-header">
					<a href="{{url('dashboard')}}" class="header-logo">
						<img src="{{asset('build/assets/images/brand-logos/desktop-logo.png')}}" alt="logo" class="desktop-logo">
						<img src="{{asset('build/assets/images/brand-logos/toggle-dark.png')}}" alt="logo" class="toggle-dark">
						<img src="{{asset('build/assets/images/brand-logos/desktop-dark.png')}}" alt="logo" class="desktop-dark">
						<img src="{{asset('build/assets/images/brand-logos/toggle-logo.png')}}" alt="logo" class="toggle-logo">
					</a>
				</div>
				<!-- End::main-sidebar-header -->

				<!-- Start::main-sidebar -->
				<div class="main-sidebar" id="sidebar-scroll">

					<!-- Start::nav -->
					<nav class="main-menu-container nav nav-pills flex-column sub-open">
						<div class="slide-left" id="slide-left">
							<svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
						</div>
						<ul class="main-menu">
							<!-- Start::slide__category -->
							<li class="slide__category"><span class="category-name">Main</span></li>
							<!-- End::slide__category -->

							<!-- Start::slide -->
							<li class="slide has-sub">
								<a href="javascript:void(0);" class="side-menu__item active">
									<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M224,56V200a8,8,0,0,1-8,8H40a8,8,0,0,1-8-8V56a8,8,0,0,1,8-8H216A8,8,0,0,1,224,56Z" opacity="0.2"/><path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,160H40V56H216V200ZM176,88a48,48,0,0,1-96,0,8,8,0,0,1,16,0,32,32,0,0,0,64,0,8,8,0,0,1,16,0Z"></path></svg>
									<span class="side-menu__label">Ecommerce</span>
									<i class="ri-arrow-right-s-line side-menu__angle"></i>
								</a>
								<ul class="slide-menu child1" style="display: block;">
									<li class="slide">
										<a href="{{url('index2')}}" class="side-menu__item">Dashboard</a>
									</li>
									<li class="slide">
										<a href="{{url('products')}}" class="side-menu__item">Products</a>
									</li>
									<li class="slide">
										<a href="{{url('product-details')}}" class="side-menu__item">Product Details</a>
									</li>
									<li class="slide">
										<a href="{{url('cart')}}" class="side-menu__item">Cart</a>
									</li>
									<li class="slide">
										<a href="{{url('checkout')}}" class="side-menu__item">Checkout</a>
									</li>
									<li class="slide">
										<a href="{{url('customers-list')}}" class="side-menu__item">Customers</a>
									</li>
									<li class="slide">
										<a href="{{url('orders')}}" class="side-menu__item">Orders</a>
									</li>
									<li class="slide">
										<a href="{{url('orders-details')}}" class="side-menu__item">Order Details</a>
									</li>
									<li class="slide">
										<a href="{{url('add-product')}}" class="side-menu__item">Add Product</a>
									</li>
								</ul>    
							</li>
							<!-- End::slide -->
                            
                            <!-- Logout -->
                            <li class="slide__category"><span class="category-name">Account</span></li>
                            <li class="slide">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <a href="javascript:void(0);" onclick="event.preventDefault(); this.closest('form').submit();" class="side-menu__item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M48,40H208a16,16,0,0,1,16,16V200a16,16,0,0,1-16,16H48a0,0,0,0,1,0,0V40A0,0,0,0,1,48,40Z" opacity="0.2"/><polyline points="112 40 48 40 48 216 112 216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="112" y1="128" x2="224" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="184 88 224 128 184 168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                        <span class="side-menu__label">Logout</span>
                                    </a>
                                </form>
                            </li>

						</ul>
						<div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path> </svg></div>
					</nav>
					<!-- End::nav -->

				</div>
				<!-- End::main-sidebar -->

			</aside>
