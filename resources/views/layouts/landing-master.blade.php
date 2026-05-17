<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
<!-- Meta Tags -->
<meta charset="utf-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Gosaas - SaaS & Software Startup Template">
<meta name="author" content="Laralink">
<!-- Favicon Icon -->
<link rel="icon" href="assets/img/favicon.png">
<!-- Site Title -->
<title>Go Digital.pk</title>
<link rel="stylesheet" href="{{ asset('build/assets/web-assets/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('build/assets/web-assets/css/fontawesome.min.css') }}">
<link rel="stylesheet" href="{{ asset('build/assets/web-assets/css/animate.css') }}">
<link rel="stylesheet" href="{{ asset('build/assets/web-assets/css/odometer.css') }}">
<link rel="stylesheet" href="{{ asset('build/assets/web-assets/css/slick.min.css') }}">
<link rel="stylesheet" href="{{ asset('build/assets/web-assets/css/style.css') }}">
</head>
<body>
<!-- Start Preloader -->
<div class="cs_preloader cs_white_bg">
  <div class="cs_preloader_in position-relative"><span></span><span></span></div>
</div>
<!-- End Preloader --> 
<!-- Start Header Section -->
<header class="cs_site_header cs_style_1 cs_sticky_header">
  <div class="cs_main_header cs_fs_18 cs_heading_color">
    <div class="container">
      <div class="cs_main_header_in">
        <div class="cs_main_header_left"> <a class="cs_site_branding" href="index.html" aria-label="Home page link"> <img src="{{ asset('build/assets/web-assets/img/se_logo.png') }}" alt="Logo"> </a> </div>
        <div class="cs_main_header_center">
          <div class="cs_nav">
            <ul class="cs_nav_list">
<!--
              <li><a href="#" aria-label="Menu link">Home</a></li>
              <li><a href="about.html" aria-label="Menu link">About</a></li>
              <li><a href="services.html" aria-label="Menu link">Services</a></li>
              <li><a href="contact.html" aria-label="Menu link">Contact</a></li>
-->
              <li class="btn-gap login-btn"><a href="{{ route('login') }}" aria-label="Menu link"><span class="btn-login">Login</span></a></li>
              <li class="reg-btn"><a href="{{ route('register') }}" aria-label="Menu link"><span class="btn-register">Register</span></a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>
<!-- End Header Section --> 
<!-- Start Hero Section -->
<section class="cs_hero cs_style_1 cs_type_3 position-relative" id="home">
  <div class="container">
    <div class="cs_hero_content position-relative">
      <div class="row align-items-center cs_gap_y_40">
        <div class="col-lg-5 order-lg-2">
          <div class="cs_hero_thumbnail cs_radius_15 position-relative wow fadeInRight">
            <div class="slider_img01"><img src="{{ asset('build/assets/web-assets/img/main_img2.jpg') }}" alt="Hero Image"></div>
            <div class="cs_hero_shape_vector position-absolute"> <img src="{{ asset('build/assets/web-assets/img/quarter-circle.svg') }}" alt="Vector Shape"> </div>
            <div class="cs_volume_report cs_white_bg cs_radius_15 position-absolute"> <img src="{{ asset('build/assets/web-assets/img/volume.jpg') }}" alt="Volume report chart"> </div>
            <div class="cs_sells_report cs_white cs_radius_20 position-absolute"> <img src="{{ asset('build/assets/web-assets/img/sells-report.jpg') }}" alt="Sells report chart"> </div>
          </div>
        </div>
        <div class="col-lg-7 order-lg-1">
          <div class="cs_hero_text position-relative z-2">
            <h1 class="cs_hero_title wow fadeInDown">Streamline Your Business Invoicing with godigital.pk <span class="position-relative"> Platform <img src="{{ asset('build/assets/web-assets/img/vector.svg') }}" alt="Vector line" class="cs_line_1 position-absolute"> <img src="{{ asset('build/assets/web-assets/img/vector-2.svg') }}" alt="Vector line" class="cs_line_2 position-absolute"> </span> </h1>
            <p class="cs_hero_subtitle">Improve your invoicing workflows and simplify your billing processes with our powerful and intuitive invoicing solution</p>
            <div class="cs_btn_group position-relative"> <a href="{{ route('register') }}" aria-label="Video open button" class="cs_btn cs_style_1 cs_purple_bg cs_white_color cs_video_open wow fadeInLeft"><span>Get a Demo</span></a> <a href="{{ route('register') }}" aria-label="Register free button" class="cs_btn cs_style_1 cs_outline cs_semibold cs_heading_color wow fadeInRight"> <span class="cs_player_text">Get Started Free</span> </a> </div>
          </div>
        </div>
      </div>
      <div class="cs_blob_5 position-absolute"></div>
    </div>
  </div>
</section>
<!-- End Hero Section --> 
<!-- Start Features Section -->
<section class="cs_slider cs_style_1 cs_slider_gap_24">
  <div class="cs_bg_shape_1 cs_gray_bg cs_radius_15 position-absolute"></div>
  <div class="cs_height_125 cs_height_lg_80"></div>
  <div class="container position-relative z-1">
    <div class="cs_section_heading cs_style_1 cs_type_1 cs_mb_60">
      <div class="cs_section_heading_left">
        <h2 class="cs_section_title cs_fs_50 mb-0 wow fadeInLeft">Core Features of Our Digital Invoicing Application</h2>
      </div>
      <div class="cs_section_heading_right wow fadeInRight">
        <p class="mb-0">Discover the essential tools and features designed to simplify your invoicing, improve payment efficiency, and enhance overall business performance</p>
      </div>
    </div>
    <div class="cs_slider_container" data-autoplay="0" data-loop="1" data-speed="800" data-center="0" data-variable-width="0" data-slides-per-view="responsive" data-xs-slides="1" data-sm-slides="2" data-md-slides="2" data-lg-slides="3" data-add-slides="3">
      <div class="cs_slider_wrapper wow fadeInUp">
        <div class="cs_slide">
          <div class="cs_iconbox cs_style_1 cs_type_1 cs_heading_bg cs_center_column cs_radius_15 text-center"> <span class="cs_iconbox_icon cs_center cs_radius_15 cs_accent_bg cs_mb_28"> <img src="{{ asset('build/assets/web-assets/img/icons/browser.svg') }}" alt="Browser icon"> </span>
            <div class="cs_iconbox_info">
              <h3 class="cs_iconbox_title cs_fs_29 cs_normal cs_white_color">Client Management</h3>
              <p class="cs_iconbox_subtitle cs_fs_18 cs_border_color cs_mb_17">Store and manage customer details </br>
                in a centralized, easily </br>
                accessible system.</p>
              <a href="features.html" aria-label="Click to visit features page" class="cs_iconbox_btn cs_semibold cs_white_color"> 
              <!--
                  <span>Learn More</span>
                  <span class="cs_btn_icon cs_center"><i class="fa-solid fa-arrow-right"></i></span>
--> 
              </a> </div>
          </div>
        </div>
        <div class="cs_slide">
          <div class="cs_iconbox cs_style_1 cs_type_1 cs_heading_bg cs_center_column cs_radius_15 text-center"> <span class="cs_iconbox_icon cs_center cs_radius_15 cs_bg_1 cs_mb_28"> <img src="{{ asset('build/assets/web-assets/img/icons/stats.svg') }}" alt="Stats icon"> </span>
            <div class="cs_iconbox_info">
              <h3 class="cs_iconbox_title cs_fs_29 cs_normal cs_white_color">FBR Integrated</h3>
              <p class="cs_iconbox_subtitle cs_fs_18 cs_border_color cs_mb_17">Create and manage invoices </br>
                with real-time FBR integration </br>
                and compliance tracking.</p>
              <a href="features.html" aria-label="Click to visit features page" class="cs_iconbox_btn cs_semibold cs_white_color"> 
              <!--
                  <span>Learn More</span>
                  <span class="cs_btn_icon cs_center"><i class="fa-solid fa-arrow-right"></i></span>
--> 
              </a> </div>
          </div>
        </div>
        <div class="cs_slide">
          <div class="cs_iconbox cs_style_1 cs_type_1 cs_heading_bg cs_center_column cs_radius_15 text-center"> <span class="cs_iconbox_icon cs_center cs_radius_15 cs_bg_2 cs_mb_28"> <img src="{{ asset('build/assets/web-assets/img/icons/point-of-sale-bill.svg') }}" alt="Point of sale bill icon"> </span>
            <div class="cs_iconbox_info">
              <h3 class="cs_iconbox_title cs_fs_29 cs_normal cs_white_color">Reporting and Analytics</h3>
              <p class="cs_iconbox_subtitle cs_fs_18 cs_border_color cs_mb_17">Generate detailed reports and <br>
                insights into sales, customer <br>
                behavior, and business health.</p>
              <a href="features.html" aria-label="Click to visit features page" class="cs_iconbox_btn cs_semibold cs_white_color"> 
              <!--
                  <span>Learn More</span>
                  <span class="cs_btn_icon cs_center"><i class="fa-solid fa-arrow-right"></i></span>
--> 
              </a> </div>
          </div>
        </div>
        <!--
            <div class="cs_slide">
              <div class="cs_iconbox cs_style_1 cs_type_1 cs_heading_bg cs_center_column cs_radius_15 text-center">
                <span class="cs_iconbox_icon cs_center cs_radius_15 cs_accent_bg cs_mb_28">
                <img src="{{ asset('build/assets/web-assets/img/icons/browser.svg') }}" alt="Browser icon">
                </span>
                <div class="cs_iconbox_info">
                  <h3 class="cs_iconbox_title cs_fs_29 cs_normal cs_white_color">Contact Management</h3>
                  <p class="cs_iconbox_subtitle cs_fs_18 cs_border_color cs_mb_17">Store and organize customer <br> information in a centralized, easily <br> accessible database.</p>
                  <a href="features.html" aria-label="Click to visit features page" class="cs_iconbox_btn cs_semibold cs_white_color">
                  <span>Learn More</span>
                  <span class="cs_btn_icon cs_center"><i class="fa-solid fa-arrow-right"></i></span>
                  </a>
                </div>
              </div>
            </div>
--> 
      </div>
      <div class="cs_height_70 cs_height_lg_50"></div>
      <div class="cs_pagination cs_style_1 wow fadeInDown"></div>
    </div>
  </div>
  <div class="cs_height_140 cs_height_lg_80"></div>
</section>
<!-- End Features Section --> 
<!-- Start Brands Section -->
<div class="cs_height_132 cs_height_lg_72"></div>
<div class="container">
  <div class="cs_center">
    <p class="cs_has_before_after cs_heading_color cs_mb_45 position-relative wow fadeInDown">Already Join 15K + Business Using Go Digital</p>
  </div>
  <!--
      <div class="cs_horizontal_slider_wrapper cs_type_1">
        <div class="cs_slider_in">
          <div class="cs_brands_list">
            <div class="cs_center">
              <img src="{{ asset('build/assets/web-assets/img/brand-1.svg') }}" alt="Brand logo">
            </div>
            <div class="cs_center">
              <img src="{{ asset('build/assets/web-assets/img/brand-2.svg') }}" alt="Brand logo">
            </div>
            <div class="cs_center">
              <img src="{{ asset('build/assets/web-assets/img/brand-3.svg') }}" alt="Brand logo">
            </div>
            <div class="cs_center">
              <img src="{{ asset('build/assets/web-assets/img/brand-4.svg') }}" alt="Brand logo">
            </div>
            <div class="cs_center">
              <img src="{{ asset('build/assets/web-assets/img/brand-5.svg') }}" alt="Brand logo">
            </div>
            <div class="cs_center">
              <img src="{{ asset('build/assets/web-assets/img/brand-6.svg') }}" alt="Brand logo">
            </div>
          </div>
          <div class="cs_brands_list">
            <div class="cs_center">
              <img src="{{ asset('build/assets/web-assets/img/brand-1.svg') }}" alt="Brand logo">
            </div>
            <div class="cs_center">
              <img src="{{ asset('build/assets/web-assets/img/brand-2.svg') }}" alt="Brand logo">
            </div>
            <div class="cs_center">
              <img src="{{ asset('build/assets/web-assets/img/brand-3.svg') }}" alt="Brand logo">
            </div>
            <div class="cs_center">
              <img src="{{ asset('build/assets/web-assets/img/brand-4.svg') }}" alt="Brand logo">
            </div>
            <div class="cs_center">
              <img src="{{ asset('build/assets/web-assets/img/brand-5.svg') }}" alt="Brand logo">
            </div>
            <div class="cs_center">
              <img src="{{ asset('build/assets/web-assets/img/brand-6.svg') }}" alt="Brand logo">
            </div>
          </div>
        </div>
      </div>
--> 
</div>
<!-- End Brands Section --> 
<!-- Start Business Solution Section -->
<section> 
  <!--      <div class="cs_height_140 cs_height_lg_80"></div>-->
  <div class="container">
    <div class="cs_card cs_style_1 cs_type_6">
      <div class="row cs_gap_y_40">
        <div class="col-xl-6 col-lg-6">
          <div class="cs_card_thumbnail cs_center position-relative wow fadeInLeft"> <img src="{{ asset('build/assets/web-assets/img/feature_9.png') }}" alt="Man image">
            <div class="cs_thumbnail_before cs_radius_100 position-absolute"> </div>
            <div class="cs_illustration_1 cs_radius_20 position-absolute"> <img src="{{ asset('build/assets/web-assets/img/Illustration_5.png') }}" alt="Analytics chart image"> </div>
            <div class="cs_illustration_2 cs_radius_20 position-absolute"> <img src="{{ asset('build/assets/web-assets/img/Illustration_6.png') }}" alt="User info image"> </div>
          </div>
        </div>
        <div class="col-xl-5 col-lg-6 offset-xl-1">
          <div class="cs_section_heading cs_style_1 cs_mb_40">
            <h2 class="cs_section_title cs_fs_50 cs_mb_19 wow fadeInDown">Upgrade Your Business with FBR-Integrated Digital Solutions</h2>
            <hr>
          </div>
          <div class="cs_iconbox_wrapper position-relative z-2">
            <div class="cs_iconbox cs_style_6 cs_mb_36 wow fadeInLeft"> <span class="cs_iconbox_icon cs_center cs_purple_bg cs_white_color cs_radius_100"> <img src="{{ asset('build/assets/web-assets/img/icons/magnet-user.svg') }}" alt="User icon"> </span>
              <div class="cs_iconbox_info">
                <h3 class="cs_fs_29 cs_normal">High-Quality Business Insights</h3>
                <p class="mb-0">Leverage GoDigital.pk to track invoices, manage clients, and optimize payments without extra effort.</p>
              </div>
            </div>
            <div class="cs_iconbox cs_style_6 cs_mb_36 wow fadeInLeft" data-wow-delay="200ms"> <span class="cs_iconbox_icon cs_center cs_purple_bg cs_white_color cs_radius_100"> <img src="{{ asset('build/assets/web-assets/img/icons/rocket-lunch.svg') }}" alt="Rocket icon"> </span>
              <div class="cs_iconbox_info">
                <h3 class="cs_fs_29 cs_normal">Accelerate Your Business</h3>
                <p class="mb-0">Close invoices quicker, manage sales, and stay fully compliant with GoDigital.pk’s cloud invoicing system.</p>
              </div>
            </div>
            <div class="cs_iconbox cs_style_6 wow fadeInLeft" data-wow-delay="400ms"> <span class="cs_iconbox_icon cs_center cs_purple_bg cs_white_color cs_radius_100"> <img src="{{ asset('build/assets/web-assets/img/icons/journey.svg') }}" alt="Journey icon"> </span>
              <div class="cs_iconbox_info">
                <h3 class="cs_fs_29 cs_normal">Seamless Invoicing Journey</h3>
                <p class="mb-0">Generate invoices, track payments, and handle taxes effortlessly with GoDigital.pk’s smart cloud-based system.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="cs_height_140 cs_height_lg_80"></div>
</section>
<!-- End Business Solution Section --> 
<!-- Start CTA Section -->
<section class="cs_cta cs_style_3 cs_purple_bg position-relative overflow-hidden">
  <div class="cs_height_125 cs_height_lg_72"></div>
  <div class="container">
    <div class="cs_cta_text cs_center_column text-center position-relative z-1">
      <h2 class="cs_fs_50 cs_white_color cs_mb_48 wow fadeInDown">Boost Sales. Build Lasting Relationships. <br>
        Achieve Faster Growth Today!</h2>
      <div class="text-center"> <a href="{{ route('register') }}" aria-label="Get started now button" class="btn-started-black"> Get Started Now! </a> </div>
    </div>
    <div class="cs_cta_shape_1 cs_accent_bg cs_radius_100 position-absolute"></div>
    <div class="cs_cta_shape_2 cs_accent_bg cs_radius_100 position-absolute"></div>
  </div>
  <div class="cs_height_140 cs_height_lg_80"></div>
</section>
<!-- End CTA Section --> 
<!-- Start Features Card Section -->
<section>
  <div class="cs_height_140 cs_height_lg_80"></div>
  <div class="container">
    <div class="cs_card cs_style_1 cs_type_4">
      <div class="row cs_gap_y_40 align-items-center position-relative">
        <div class="col-xl-5 col-lg-5 offset-xl-1 order-lg-2">
          <div class="cs_card_thumbnail cs_radius_15 position-relative wow fadeInRight"> <img src="{{ asset('build/assets/web-assets/img/feature_8.jpg') }}" alt="Feature image">
            <div class="cs_illustration cs_radius_10 position-absolute"> <img src="{{ asset('build/assets/web-assets/img/Illustration_4.png') }}" alt="Analytics chart image"> </div>
          </div>
        </div>
        <div class="col-xl-6 col-lg-7 order-lg-1">
          <div class="cs_card_content">
            <div class="cs_section_heading cs_style_1 cs_mb_30">
              <h2 class="cs_section_title cs_fs_50 cs_mb_16 wow fadeInDown">Automate Your Invoicing Process</h2>
              <p class="cs_section_text cs_fs_21 mb-0">Gain real-time insights into your invoices, sales, and tax data to make accurate, informed business decisions.</p>
            </div>
            <ul class="cs_list cs_style_1 cs_mp_0 wow fadeInLeft">
              <li> <span class="cs_list_icon cs_accent_color">
                <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g clip-path="url(#clip0_1573_697412)">
                    <path d="M27.803 6.59L23.4093 2.19625C21.9918 0.78 20.108 0 18.1055 0H11.8918C9.8893 0 8.00555 0.78 6.58805 2.19625L2.1943 6.59C0.778047 8.0075 -0.00195312 9.89125 -0.00195312 11.8938V18.1075C-0.00195312 20.11 0.778047 21.9937 2.1943 23.4113L6.58805 27.805C8.00555 29.2212 9.8893 30.0012 11.8918 30.0012H18.1055C20.108 30.0012 21.9918 29.2212 23.4093 27.805L27.803 23.4113C29.2193 21.9937 29.9993 20.11 29.9993 18.1075V11.8938C29.9993 9.89125 29.2193 8.0075 27.803 6.59ZM22.9155 12.2913L16.3755 18.8312C15.6093 19.5975 14.5905 20.02 13.5068 20.02C12.423 20.02 11.4043 19.5975 10.638 18.8312L7.1543 15.3475C6.66555 14.8587 6.66555 14.0688 7.1543 13.58C7.64305 13.0913 8.43305 13.0913 8.9218 13.58L12.4055 17.0637C12.9943 17.6525 14.0205 17.6513 14.608 17.0637L21.148 10.5237C21.6368 10.035 22.4268 10.035 22.9155 10.5237C23.4043 11.0125 23.4043 11.8025 22.9155 12.2913Z" fill="currentColor"></path>
                  </g>
                  <defs>
                    <clipPath id="clip0_1573_697413">
                      <rect width="30" height="30" fill="white"></rect>
                    </clipPath>
                  </defs>
                </svg>
                </span> <span class="cs_fs_21 cs_heading_color">Actionable Insights</span> </li>
              <li> <span class="cs_list_icon cs_accent_color">
                <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g clip-path="url(#clip0_1573_6974514)">
                    <path d="M27.803 6.59L23.4093 2.19625C21.9918 0.78 20.108 0 18.1055 0H11.8918C9.8893 0 8.00555 0.78 6.58805 2.19625L2.1943 6.59C0.778047 8.0075 -0.00195312 9.89125 -0.00195312 11.8938V18.1075C-0.00195312 20.11 0.778047 21.9937 2.1943 23.4113L6.58805 27.805C8.00555 29.2212 9.8893 30.0012 11.8918 30.0012H18.1055C20.108 30.0012 21.9918 29.2212 23.4093 27.805L27.803 23.4113C29.2193 21.9937 29.9993 20.11 29.9993 18.1075V11.8938C29.9993 9.89125 29.2193 8.0075 27.803 6.59ZM22.9155 12.2913L16.3755 18.8312C15.6093 19.5975 14.5905 20.02 13.5068 20.02C12.423 20.02 11.4043 19.5975 10.638 18.8312L7.1543 15.3475C6.66555 14.8587 6.66555 14.0688 7.1543 13.58C7.64305 13.0913 8.43305 13.0913 8.9218 13.58L12.4055 17.0637C12.9943 17.6525 14.0205 17.6513 14.608 17.0637L21.148 10.5237C21.6368 10.035 22.4268 10.035 22.9155 10.5237C23.4043 11.0125 23.4043 11.8025 22.9155 12.2913Z" fill="currentColor"></path>
                  </g>
                  <defs>
                    <clipPath id="clip0_1573_697415">
                      <rect width="30" height="30" fill="white"></rect>
                    </clipPath>
                  </defs>
                </svg>
                </span> <span class="cs_fs_21 cs_heading_color">Enhanced Decision-Making</span> </li>
              <li> <span class="cs_list_icon cs_accent_color">
                <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <g clip-path="url(#clip0_1573_6974616)">
                    <path d="M27.803 6.59L23.4093 2.19625C21.9918 0.78 20.108 0 18.1055 0H11.8918C9.8893 0 8.00555 0.78 6.58805 2.19625L2.1943 6.59C0.778047 8.0075 -0.00195312 9.89125 -0.00195312 11.8938V18.1075C-0.00195312 20.11 0.778047 21.9937 2.1943 23.4113L6.58805 27.805C8.00555 29.2212 9.8893 30.0012 11.8918 30.0012H18.1055C20.108 30.0012 21.9918 29.2212 23.4093 27.805L27.803 23.4113C29.2193 21.9937 29.9993 20.11 29.9993 18.1075V11.8938C29.9993 9.89125 29.2193 8.0075 27.803 6.59ZM22.9155 12.2913L16.3755 18.8312C15.6093 19.5975 14.5905 20.02 13.5068 20.02C12.423 20.02 11.4043 19.5975 10.638 18.8312L7.1543 15.3475C6.66555 14.8587 6.66555 14.0688 7.1543 13.58C7.64305 13.0913 8.43305 13.0913 8.9218 13.58L12.4055 17.0637C12.9943 17.6525 14.0205 17.6513 14.608 17.0637L21.148 10.5237C21.6368 10.035 22.4268 10.035 22.9155 10.5237C23.4043 11.0125 23.4043 11.8025 22.9155 12.2913Z" fill="currentColor"></path>
                  </g>
                  <defs>
                    <clipPath id="clip0_1573_697417">
                      <rect width="30" height="30" fill="white"></rect>
                    </clipPath>
                  </defs>
                </svg>
                </span> <span class="cs_fs_21 cs_heading_color">Improved Efficiency</span> </li>
            </ul>
<!--            <a href="contact.html" aria-label="Click to learn more" class="cs_btn cs_style_1 cs_purple_bg cs_accent_hover cs_fs_16 cs_white_color cs_semibold wow fadeInUp"> <span>Learn More</span> </a> </div>-->
        </div>
        <div class="cs_vector_shape_3 position-absolute"> <img src="{{ asset('build/assets/web-assets/img/vector_1.svg') }}" alt="Vector shape"> </div>
        <div class="cs_vector_shape_4 position-absolute"> <img src="{{ asset('build/assets/web-assets/img/vector_1.svg') }}" alt="Vector shape"> </div>
      </div>
    </div>
  </div>
</section>
<!-- End Features Card Section --> 
<!-- Start Testimonial Section --> 
<!--
    <section>
      <div class="cs_height_120 cs_height_lg_70"></div>
      <div class="container">
        <div class="cs_section_heading cs_style_1 text-center cs_mb_55">
          <h2 class="cs_fs_50 mb-0 wow fadeInDown">Customer Reviews and <br> Success Stories</h2>
        </div>
      </div>
      <div class="cs_slider cs_style_1 cs_slider_gap_24">
        <div class="cs_slider_container" data-autoplay="0" data-loop="1" data-speed="800" data-center="1" data-variable-width="1" data-slides-per-view="responsive" data-xs-slides="1" data-sm-slides="2" data-md-slides="2" data-lg-slides="4" data-add-slides="4">
          <div class="cs_slider_wrapper">
            <div class="cs_slide">
              <div class="cs_testimonial cs_style_1 cs_radius_15 position-relative">
                <div class="cs_avatar cs_style_1 cs_mb_41">
                  <span class="cs_avatar_icon cs_center cs_radius_100">
                  <img src="{{ asset('build/assets/web-assets/img/avatar-1.jpg') }}" alt="Avatar">
                  </span>
                  <div class="cs_avatar_info">
                    <h3 class="cs_fs_21 fw-normal">Sara Thomas</h3>
                    <p class="mb-0">Project Manager</p>
                  </div>
                </div>
                <blockquote>Gosaas has completely transformed how we manage projects at our company. The intuitive interface makes it easy for everyone to get on board, and the powerful features like subtasks and dependencies.</blockquote>
                <div class="cs_rating" data-rating="5">
                  <div class="cs_rating_percentage"></div>
                </div>
                <span class="cs_quote_icon position-absolute">                
                </span>
              </div>
            </div>
            <div class="cs_slide">
              <div class="cs_testimonial cs_style_1 cs_radius_15 position-relative">
                <div class="cs_avatar cs_style_1 cs_mb_41">
                  <span class="cs_avatar_icon cs_center cs_radius_100">
                  <img src="{{ asset('build/assets/web-assets/img/avatar-2.jpg') }}" alt="Avatar">
                  </span>
                  <div class="cs_avatar_info">
                    <h3 class="cs_fs_21 fw-normal">Wizard Bona</h3>
                    <p class="mb-0">Creative Director</p>
                  </div>
                </div>
                <blockquote>Managing a creative team comes with its own set of challenges, but Gosaas has made it so much easier. The customizable views allow us to organize tasks in a way that makes sense for our projects.</blockquote>
                <div class="cs_rating" data-rating="5">
                  <div class="cs_rating_percentage"></div>
                </div>
                <span class="cs_quote_icon position-absolute">                
                </span>
              </div>
            </div>
            <div class="cs_slide">
              <div class="cs_testimonial cs_style_1 cs_radius_15 position-relative">
                <div class="cs_avatar cs_style_1 cs_mb_41">
                  <span class="cs_avatar_icon cs_center cs_radius_100">
                  <img src="{{ asset('build/assets/web-assets/img/avatar-3.jpg') }}" alt="Avatar">
                  </span>
                  <div class="cs_avatar_info">
                    <h3 class="cs_fs_21 fw-normal">Jhon Kubera</h3>
                    <p class="mb-0">Marketing Specialist</p>
                  </div>
                </div>
                <blockquote>As a marketing specialist, staying organized and on top of deadlines is crucial. Gosaas has been a lifesaver. The platform is incredibly user-friendly, and I love how I can customize task lists to fit my workflow.</blockquote>
                <div class="cs_rating" data-rating="4.5">
                  <div class="cs_rating_percentage"></div>
                </div>
                <span class="cs_quote_icon position-absolute">                
                </span>
              </div>
            </div>
            <div class="cs_slide">
              <div class="cs_testimonial cs_style_1 cs_radius_15 position-relative">
                <div class="cs_avatar cs_style_1 cs_mb_41">
                  <span class="cs_avatar_icon cs_center cs_radius_100">
                  <img src="{{ asset('build/assets/web-assets/img/avatar-4.jpg') }}" alt="Avatar">
                  </span>
                  <div class="cs_avatar_info">
                    <h3 class="cs_fs_21 fw-normal">Emily Loren</h3>
                    <p class="mb-0">Software Developer</p>
                  </div>
                </div>
                <blockquote>Our software development team has greatly benefited from using Gosaas. The ability to set task dependencies and create subtasks allows us to break down large projects into manageable pieces.</blockquote>
                <div class="cs_rating" data-rating="5">
                  <div class="cs_rating_percentage"></div>
                </div>
                <span class="cs_quote_icon position-absolute">                
                </span>
              </div>
            </div>
            <div class="cs_slide">
              <div class="cs_testimonial cs_style_1 cs_radius_15 position-relative">
                <div class="cs_avatar cs_style_1 cs_mb_41">
                  <span class="cs_avatar_icon cs_center cs_radius_100">
                  <img src="{{ asset('build/assets/web-assets/img/avatar-4.jpg') }}" alt="Avatar">
                  </span>
                  <div class="cs_avatar_info">
                    <h3 class="cs_fs_21 fw-normal">AI King</h3>
                    <p class="mb-0">Project Manager</p>
                  </div>
                </div>
                <blockquote>Gosaas has completely transformed how we manage projects at our company. The intuitive interface makes it easy for everyone to get on board, and the powerful features like subtasks and dependencies.</blockquote>
                <div class="cs_rating" data-rating="5">
                  <div class="cs_rating_percentage"></div>
                </div>
                <span class="cs_quote_icon position-absolute">                
                </span>
              </div>
            </div>
          </div>
          <div class="cs_height_70 cs_height_lg_50"></div>
          <div class="cs_pagination cs_style_1 wow fadeInUp"></div>
        </div>
      </div>
      <div class="cs_height_140 cs_height_lg_80"></div>
    </section>
--> 
<!-- End Testimonial Section --> 
<!-- Start Pricing Section -->
<!--<section class="position-relative">
  <div class="cs_bg_shape_1 cs_gray_bg cs_radius_15 position-absolute"></div>
  <div class="cs_height_125 cs_height_lg_70"></div>
  <div class="container position-relative z-1">
    <div class="cs_section_heading cs_style_1 cs_mb_42 text-center">
      <h2 class="cs_section_title cs_fs_50 mb-0 wow fadeInDown">Transparent pricing plans, find the <br>
        perfect fit for your needs</h2>
    </div>
    <ul class="cs_pricing_control cs_type_1 cs_center p-0 position-relative">
      <li class="active"><a href="monthly" aria-label="Pricing toggle button">Monthly</a><span class="cs_switch"></span></li>
      <li> <a href="yearly" aria-label="Pricing toggle button">Annually</a>
        <div class="cs_offer_info cs_type_1 cs_heading_color position-absolute">
          <svg width="64" height="27" viewBox="0 0 64 27" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M63.2728 3.31355C62.7609 2.99354 62.1574 2.63567 61.6072 2.40842C57.7794 0.50156 53.6369 -0.123714 49.2796 0.0309426C42.8353 0.300803 36.7947 1.93186 30.8527 4.36371C21.1406 8.38715 12.8326 14.7279 5.70033 22.6402C5.66202 22.7329 5.6237 22.8257 5.45538 22.9733C6.05891 23.3312 6.57075 23.6512 7.26597 24.047C6.08085 24.3175 4.98743 24.6259 3.894 24.9344C3.80231 24.8965 3.74893 24.7659 3.74893 24.7659C4.22376 23.8762 4.75197 23.1171 5.2268 22.2274C7.73785 18.487 10.2489 14.7465 12.7599 11.0061C12.8366 10.8206 12.8749 10.7278 12.9515 10.5423C12.8981 10.4117 12.8064 10.3738 12.7148 10.3359C10.9959 9.30018 10.9959 9.30018 9.67942 10.9281C7.0233 14.5 4.32887 18.1647 1.67275 21.7366C1.27455 22.4408 0.929723 23.2756 0.714902 24.0556C0.423455 25.021 0.782037 25.712 1.66064 26.1835C2.68432 26.8235 3.82294 27.1853 5.17638 26.7671C8.71667 25.732 12.3103 24.8276 15.9423 23.8304C16.3324 23.6658 16.7224 23.5011 17.1658 23.4671C17.2041 23.3743 17.1124 23.3365 17.1124 23.3365C15.6384 21.9675 14.0181 21.7326 12.2514 22.6316C11.5631 22.9987 10.768 23.1047 9.80453 23.3582C9.88116 23.1727 9.91947 23.0799 10.0495 23.025C15.9872 17.2254 22.3218 12.024 29.6336 8.09485C37.1137 4.0181 45.2111 1.82507 53.819 1.25449C56.441 1.14314 59.0015 1.4407 61.3623 2.74158C61.9658 3.09945 62.6843 3.17906 63.2344 3.4063L63.2728 3.31355Z" fill="#6C6C6C"/>
          </svg>
          <span>Save 20%</span> </div>
      </li>
    </ul>
    <div class="cs_pricing_table_wrapper_2">
      <div class="cs_pricing_table cs_style_1 cs_type_3 cs_white_bg cs_radius_20 position-relative wow fadeInLeft">
        <div class="cs_pricing_head cs_mb_14">
          <h2 class="cs_fs_38 mb-0">Free</h2>
          <p class="cs_mb_18">Free for small team</p>
          <hr>
        </div>
        <h3 class="cs_fs_38 cs_mb_17">$<span class="monthlyPrice">0</span><span class="yearlyPrice">0</span> <small class="cs_fs_18 cs_normal cs_gray2_color">/ <span class="monthlyText">month</span> <span class="yearlyText">year</span> </small> </h3>
        <div class="cs_mb_36">
          <ul class="cs_pricing_feature_list cs_mp_0">
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Unlimited cards </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Up to 10 boards per </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Unlimited Power-Ups per board </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-xmark"></i></span> Unlimited storage (10MB/file) </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-xmark"></i></span> Unlimited activity log </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-xmark"></i></span> Assignee and due dates </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-xmark"></i></span> Assignee and due dates </li>
          </ul>
        </div>
        <a href="contact.html" aria-label="Buy service button" class="cs_pricing_btn cs_center cs_fs_16 cs_semibold">Choose Plan</a> </div>
      <div class="cs_pricing_table cs_style_1 cs_type_3 cs_white_bg cs_radius_20 active position-relative overflow-hidden wow fadeInDown"> <span class="cs_pricing_badge cs_fs_16 position-absolute">Most Popular</span>
        <div class="cs_pricing_head cs_mb_14">
          <h2 class="cs_fs_38 mb-0">Standard</h2>
          <p class="cs_mb_18">Most popular deal</p>
          <hr>
        </div>
        <h3 class="cs_fs_38 cs_mb_17">$<span class="monthlyPrice">10</span><span class="yearlyPrice">80</span> <small class="cs_fs_18 cs_normal cs_gray_color">/ <span class="monthlyText">month</span> <span class="yearlyText">year</span> </small> </h3>
        <div class="cs_mb_36">
          <ul class="cs_pricing_feature_list cs_mp_0">
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Unlimited cards </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Up to 10 boards per </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Unlimited Power-Ups per board </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Unlimited storage (10MB/file) </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Unlimited activity log </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-xmark"></i></span> Assignee and due dates </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-xmark"></i></span> Assignee and due dates </li>
          </ul>
        </div>
        <a href="contact.html" aria-label="Buy service button" class="cs_pricing_btn cs_center cs_fs_16 cs_semibold">Choose Plan</a> </div>
      <div class="cs_pricing_table cs_style_1 cs_type_3 cs_white_bg cs_radius_20 position-relative wow fadeInRight">
        <div class="cs_pricing_head cs_mb_14">
          <h2 class="cs_fs_38 mb-0">Premium</h2>
          <p class="cs_mb_18">For your large team</p>
          <hr>
        </div>
        <h3 class="cs_fs_38 cs_mb_17">$<span class="monthlyPrice">20</span><span class="yearlyPrice">90</span> <small class="cs_fs_18 cs_normal cs_gray2_color">/ <span class="monthlyText">month</span> <span class="yearlyText">year</span> </small> </h3>
        <div class="cs_mb_36">
          <ul class="cs_pricing_feature_list cs_mp_0">
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Unlimited cards </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Up to 10 boards per </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Unlimited Power-Ups per board </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Unlimited storage (10MB/file) </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Unlimited activity log </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Assignee and due dates </li>
            <li> <span class="cs_feature_icon cs_center cs_radius_100 cs_accent_bg cs_white_color"><i class="fa-solid fa-check"></i></span> Assignee and due dates </li>
          </ul>
        </div>
        <a href="contact.html" aria-label="Buy service button" class="cs_pricing_btn cs_center cs_fs_16 cs_semibold">Choose Plan</a> </div>
    </div>
  </div>
  <div class="cs_height_140 cs_height_lg_80"></div>
</section>-->
<!-- End Pricing Section --> 
<!-- Start FAQ Section -->
<section class="position-relative">
  <div class="cs_height_130 cs_height_lg_80"></div>
  <div class="container">
    <div class="cs_section_heading cs_style_1 cs_mb_60 text-center">
      <h2 class="cs_section_title cs_fs_50 mb-0 wow fadeInDown">Answers to Your Frequently <br>
        Asked Questions</h2>
    </div>
    <div class="row cs_gap_y_24 position-relative z-1">
      <div class="col-xl-6 wow fadeInLeft">
        <div class="cs_accordian cs_radius_15 cs_white_bg cs_type_2 position-relative">
          <div class="cs_accordian_head cs_fs_21 cs_heading_color"> How does GoDigital.pk help with FBR integration? <span class="cs_accordian_toggle position-absolute"></span> </div>
          <div class="cs_accordian_body"> Our system connects directly with FBR to generate compliant invoices and report transactions in real time. It simplifies tax filing and reduces the risk of errors or penalties. </div>
        </div>
        <div class="cs_accordian cs_radius_15 cs_white_bg cs_type_2 position-relative">
          <div class="cs_accordian_head cs_fs_21 cs_heading_color"> Can I manage my POS sales with this software? <span class="cs_accordian_toggle position-absolute"></span> </div>
          <div class="cs_accordian_body"> Yes, GoDigital.pk allows you to handle POS sales, generate invoices, and track transactions seamlessly. It ensures all sales are properly recorded and aligned with tax requirements. </div>
        </div>
        <div class="cs_accordian cs_radius_15 cs_white_bg cs_type_2 position-relative">
          <div class="cs_accordian_head cs_fs_21 cs_heading_color"> Is my business data secure on the cloud? <span class="cs_accordian_toggle position-absolute"></span> </div>
          <div class="cs_accordian_body"> We use advanced security protocols to keep your data safe and protected at all times. Your information is stored securely and accessible only to authorized users. </div>
        </div>
        <div class="cs_accordian cs_radius_15 cs_white_bg cs_type_2 position-relative">
          <div class="cs_accordian_head cs_fs_21 cs_heading_color"> Does the system automatically calculate taxes? <span class="cs_accordian_toggle position-absolute"></span> </div>
          <div class="cs_accordian_body"> Yes, the software automatically calculates applicable taxes based on FBR rules. This reduces manual effort and ensures accurate and compliant tax handling. </div>
        </div>
      </div>
      <div class="col-xl-6 wow fadeInRight">
        <div class="cs_accordian cs_radius_15 cs_white_bg cs_type_2 position-relative">
          <div class="cs_accordian_head cs_fs_21 cs_heading_color"> Can I track payments and outstanding invoices? <span class="cs_accordian_toggle position-absolute"></span> </div>
          <div class="cs_accordian_body"> You can easily monitor paid and unpaid invoices in real time through the dashboard. This helps you manage cash flow and follow up with clients efficiently. </div>
        </div>
        <div class="cs_accordian cs_radius_15 cs_white_bg cs_type_2 position-relative">
          <div class="cs_accordian_head cs_fs_21 cs_heading_color"> Is GoDigital.pk suitable for small businesses? <span class="cs_accordian_toggle position-absolute"></span> </div>
          <div class="cs_accordian_body"> Absolutely, it is designed to support businesses of all sizes, including startups and SMEs. The system is simple, scalable, and easy to use without technical expertise. </div>
        </div>
        <div class="cs_accordian cs_radius_15 cs_white_bg cs_type_2 position-relative">
          <div class="cs_accordian_head cs_fs_21 cs_heading_color"> Can I access the system from anywhere? <span class="cs_accordian_toggle position-absolute"></span> </div>
          <div class="cs_accordian_body"> Since it is cloud-based, you can access your data anytime from any device with internet access. This gives you full control over your business operations on the go. </div>
        </div>
        <div class="cs_accordian cs_radius_15 cs_white_bg cs_type_2 position-relative">
          <div class="cs_accordian_head cs_fs_21 cs_heading_color"> Does it support reporting and analytics? <span class="cs_accordian_toggle position-absolute"></span> </div>
          <div class="cs_accordian_body"> Yes, GoDigital.pk provides detailed reports on sales, invoices, and taxes. These insights help you make better business decisions with accurate real-time data. </div>
        </div>
      </div>
    </div>
    <div class="cs_faq_shape_1 cs_purple_bg cs_radius_100 position-absolute"></div>
    <div class="cs_faq_shape_2 cs_accent_bg cs_radius_100 position-absolute"></div>
  </div>
</section>
<!-- End FAQ Section --> 
<!-- Start CTA Section -->
<section>
  <div class="cs_height_134 cs_height_lg_80"></div>
  <div class="container">
    <div class="cs_cta cs_style_2 cs_type_1 cs_heading_bg cs_bg_filed cs_radius_10" data-src="assets/img/counter_bg.svg">
      <div class="cs_center_column text-center position-relative z-1">
        <h2 class="cs_contet_title cs_fs_70 cs_white_color cs_mb_55 wow fadeInDown"> Ready to enhance your sales & <br>
          customer satisfaction?</h2>
        <form action="#" class="cs_newsletter_form cs_type_3 cs_mb_21 position-relative wow fadeInUp">
          <input type="text" class="cs_newsletter_input cs_heading_bg cs_radius_50 text-capitalize" placeholder="Enter your email address">
          <a href="{{ route('register') }}" type="submit" aria-label="Sign up button" class="cs_btn cs_style_1 cs_purple_bg cs_accent_hover cs_fs_16 cs_semibold cs_white_color"> <span>Sign Up Free</span> </a>
        </form>
        <ul class="cs_list cs_style_2 cs_mp_0">
          <li class="cs_border_color"> <i class="fa-solid fa-check cs_accent_color"></i> Sign Up and Start Immediately </li>
          <li class="cs_border_color"> <i class="fa-solid fa-check cs_accent_color"></i> 24/7 Support </li>
        </ul>
      </div>
    </div>
  </div>
</section>
<!-- End CTA Section --> 
<!-- Start Footer Section -->
<footer class="cs_footer cs_style_1 cs_type_3">
  <div class="cs_height_130 cs_height_lg_80"></div>
  <div class="container">
    <div class="cs_footer_main">
      <div class="row cs_gap_y_30">
        <div class="col-xl-5 col-lg-4 col-md-7">
          <div class="cs_footer_widget cs_text_widget">
            <div class="cs_brand cs_mb_10 wow fadeInUp"> <img src="{{ asset('build/assets/web-assets/img/se_logo.png') }}" alt="Logo"> </div>
            <p class="mb-0">Our analytics application turns raw data into actionable insights, empowering businesses to make data-driven decisions and enhance performance.</p>
          </div>
        </div>
        <div class="col-xl-2 col-lg-2 col-md-4 offset-lg-0 offset-md-1">
          <div class="cs_footer_widget cs_links_widget">
            <h3 class="cs_footer_widget_title cs_fs_21 cs_mb_21">Quick Links</h3>
            <ul class="cs_footer_medu cs_mp_0">
              <li><a href="#" aria-label="Services">Services</a></li>
              <li><a href="#" aria-label="Features">Features</a></li>
<!--              <li><a href="pricing.html" aria-label="Pricing page link">Pricing</a></li>-->
<!--              <li><a href="blog.html" aria-label="Blog page link">Blog</a></li>-->
              <li><a href="#" aria-label="Contact">Contact</a></li>
            </ul>
          </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-7">
          <div class="cs_footer_widget cs_help_widget">
            <h3 class="cs_footer_widget_title cs_fs_21 cs_mb_21">Our Policies</h3>
            <ul class="cs_footer_medu cs_mp_0">
<!--              <li><a href="contact.html" aria-label="Contact page link">Contact</a></li>-->
<!--              <li><a href="#" aria-label="Help link">Help</a></li>-->
              <li><a href="#" aria-label="Refund Policy">Refund Policy</a></li>
              <li><a href="#" aria-label="Terms & Condition link">Terms & Condition</a></li>
            </ul>
          </div>
        </div>
        <div class="col-xl-3 col-lg-3  col-md-4 offset-md-1 offset-lg-0">
          <div class="cs_footer_widget cs_contact_widget">
            <h3 class="cs_footer_widget_title cs_fs_21 cs_mb_21">Contact Info</h3>
            <ul class="cs_footer_medu cs_mp_0">
              <li>24/7 Support Center</li>
              <li><a href="mailto:info@ai.godigital.pk" aria-label="Email send link">info@godigital.pk </a></li>
              <li><a href="tel:032529555" aria-label="Phone call link">0325 29 555 21</a></li>
<!--              <li>10 am - 12 pm, Everyday</li>-->
            </ul>
          </div>
        </div>
      </div>
    </div>
    <div class="cs_height_62 cs_height_lg_40"></div>
    <div class="cs_footer_bottom">
      <div class="cs_social_links cs_style_1 cs_heading_color"> <a href="#" aria-label="Social link"> <span class="cs_social_icon cs_purple_color"><i class="fa-brands fa-facebook"></i></span> <span>Facebook</span> </a> <a href="#" aria-label="Social link"> <span class="cs_social_icon cs_purple_color"><i class="fa-brands fa-youtube"></i></span> <span>YouTube</span> </a> <a href="#" aria-label="Social link"> <span class="cs_social_icon cs_purple_color"><i class="fa-brands fa-square-instagram"></i></span> <span>Instagram</span> </a> </div>
      <div class="cs_footer_text cs_heading_color">Copyright &copy; <span class="cs_getting_year"></span> Go Digital.pk</div>
    </div>
  </div>
</footer>
<!-- End Footer Section --> 
<!-- Start Scroll Up Button -->
<button type="button" aria-label="Scroll to top button" class="cs_scrollup cs_purple_bg cs_white_color cs_radius_100">
<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M0 10L1.7625 11.7625L8.75 4.7875V20H11.25V4.7875L18.225 11.775L20 10L10 0L0 10Z" fill="currentColor" />
</svg>
</button>
<!-- End Scroll Up Button --> 
<!-- Script --> 

<script src="{{ asset('build/assets/web-assets/js/jquery.min.js') }}"></script> 
<script src="{{ asset('build/assets/web-assets/js/wow.min.js') }}"></script> 
<script src="{{ asset('build/assets/web-assets/js/jquery.slick.min.js') }}"></script> 
<script src="{{ asset('build/assets/web-assets/js/odometer.js') }}"></script> 
<script src="{{ asset('build/assets/web-assets/js/main.js') }}"></script>
</body>
</html>
<?php /*?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="horizontal" data-nav-style="menu-hover" data-menu-position="fixed" data-theme-mode="light">

<head>

  <!-- Meta Data -->
  <meta charset="UTF-8">
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="Description" content="Laravel Bootstrap Responsive Admin Web Dashboard Template">
  <meta name="Author" content="Spruko Technologies Private Limited">
  <meta name="keywords" content="laravel, laravel admin panel, laravel dashboard, bootstrap dashboard, bootstrap admin panel, vite laravel, admin dashboard, admin panel in laravel, admin dashboard ui, laravel admin, admin panel template, laravel framework, dashboard, admin dashboard template, laravel template.">

  <!-- Title-->
  <title>11 Vyzor - Laravel Bootstrap 5 Premium Admin & Dashboard Template </title>

  <!-- Favicon -->
  <link rel="icon" href="{{ asset('build/assets/images/brand-logos/favicon.ico') }}" type="image/x-icon">

  <!-- Icons CSS -->
  <link href="{{ asset('build/assets/icon-fonts/icons.css') }}" rel="stylesheet">

  @include('layouts.components.landingpage.styles')

  <!-- APP CSS & APP SCSS -->
  @vite(['resources/sass/app.scss'])

  @yield('styles')

</head>

<body class="landing-body">

  <!-- Start::main-switcher -->
  @include('layouts.components.landingpage.switcher')
  <!-- End::main-switcher -->

  <div class="landing-page-wrapper">

    <!-- Start::main-header -->
    @include('layouts.components.landingpage.main-header')
    <!-- End::main-header -->

    <!-- Start::main-sidebar -->
    @include('layouts.components.landingpage.main-sidebar')
    <!-- End::main-sidebar -->

    <!-- Start::app-content -->
    <div class="main-content landing-main">

      @yield('content')

    </div>
    <!-- End::main-content -->

    <!-- Start::main-footer -->
    @include('layouts.components.landingpage.footer')
    <!-- End::main-footer -->

  </div>
  <!--app-content closed-->

  @yield('modals')

  <!-- Scripts -->
  @include('layouts.components.landingpage.scripts')

  @yield('scripts')

</body>

</html>
<?php */?>