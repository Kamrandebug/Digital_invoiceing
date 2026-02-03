
@extends('layouts.master')

@section('styles')

        <!-- Prism CSS -->
        <link rel="stylesheet" href="{{asset('build/assets/libs/prismjs/themes/prism-coy.min.css')}}">

@endsection

@section('content')
        
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Object Fit</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Ui Elements</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Object Fit</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start::row-1 -->
                    <div class="row">
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">Object Fit Contain</div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-contain border rounded" alt="...">
                                </div>
                                <div class="card-footer d-none border-top-0">
    <!-- Prism Code -->
    <pre class="language-html"><code class="language-html">&lt;img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-contain border rounded" alt="..."&gt;</code></pre>
    <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">Object Fit Cover</div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-cover border rounded" alt="...">
                                </div>
                                <div class="card-footer d-none border-top-0">
    <!-- Prism Code -->
    <pre class="language-html"><code class="language-html">&lt;img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-cover border rounded" alt="..."&gt;</code></pre>
    <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">Object Fit Fill</div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-fill border rounded" alt="...">
                                </div>
                                <div class="card-footer d-none border-top-0">
    <!-- Prism Code -->
    <pre class="language-html"><code class="language-html">&lt;img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-fill border rounded" alt="..."&gt;</code></pre>
    <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">Object Fit Scale Down</div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-scale border rounded" alt="...">
                                </div>
                                <div class="card-footer d-none border-top-0">
    <!-- Prism Code -->
    <pre class="language-html"><code class="language-html">&lt;img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-scale border rounded" alt="..."&gt;</code></pre>
    <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">Object Fit None</div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-none border rounded" alt="...">
                                </div>
                                <div class="card-footer d-none border-top-0">
    <!-- Prism Code -->
    <pre class="language-html"><code class="language-html">&lt;img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-none border rounded" alt="..."&gt;</code></pre>
    <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">Object Fit Contain (SM - responsive)</div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-sm-contain border rounded" alt="...">
                                </div>
                                <div class="card-footer d-none border-top-0">
        <!-- Prism Code -->
        <pre class="language-html"><code class="language-html">&lt;img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-sm-contain border rounded" alt="..."&gt;</code></pre>
        <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">Object Fit Contain (MD - responsive)</div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-md-contain border rounded" alt="...">
                                </div>
                                <div class="card-footer d-none border-top-0">
        <!-- Prism Code -->
        <pre class="language-html"><code class="language-html">&lt;img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-md-contain border rounded" alt="..."&gt;</code></pre>
        <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">Object Fit Contain (LG - responsive)</div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-lg-contain border rounded" alt="...">
                                </div>
                                <div class="card-footer d-none border-top-0">
        <!-- Prism Code -->
        <pre class="language-html"><code class="language-html">&lt;img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-lg-contain border rounded" alt="..."&gt;</code></pre>
        <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">Object Fit Contain (XL - responsive)</div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-xl-contain border rounded" alt="...">
                                </div>
                                <div class="card-footer d-none border-top-0">
    <!-- Prism Code -->
    <pre class="language-html"><code class="language-html">&lt;img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-xl-contain border rounded" alt="..."&gt;</code></pre>
    <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">Object Fit Contain (XXL - responsive)</div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-xxl-contain border rounded" alt="...">
                                </div>
                                <div class="card-footer d-none border-top-0">
    <!-- Prism Code -->
    <pre class="language-html"><code class="language-html">&lt;img src="{{asset('build/assets/images/media/media-28.jpg')}}" class="object-fit-xxl-contain border rounded" alt="..."&gt;</code></pre>
    <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Object Fit Contain Video
                                    </div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <video src="{{asset('build/assets/video/1.mp4')}}" class="object-fit-contain rounded border" autoplay loop muted></video>
                                </div>
                                <div class="card-footer d-none border-top-0">
    <!-- Prism Code -->
    <pre class="language-html"><code class="language-html">&lt;video src="{{asset('build/assets/video/1.mp4')}}" class="object-fit-contain rounded border" autoplay loop muted&gt;&lt;/video&gt;</code></pre>
    <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Object Fit Cover Video
                                    </div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <video src="{{asset('build/assets/video/1.mp4')}}" class="object-fit-cover rounded border" autoplay loop muted></video>
                                </div>
                                <div class="card-footer d-none border-top-0">
    <!-- Prism Code -->
    <pre class="language-html"><code class="language-html">&lt;video src="{{asset('build/assets/video/1.mp4')}}" class="object-fit-cover rounded border" autoplay loop muted&gt;&lt;/video&gt;</code></pre>
    <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Object Fit Fill Video
                                    </div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <video src="{{asset('build/assets/video/1.mp4')}}" class="object-fit-fill rounded border" autoplay loop muted></video>
                                </div>
                                <div class="card-footer d-none border-top-0">
    <!-- Prism Code -->
    <pre class="language-html"><code class="language-html">&lt;video src="{{asset('build/assets/video/1.mp4')}}" class="object-fit-fill rounded border" autoplay loop muted&gt;&lt;/video&gt;</code></pre>
    <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Object Fit Scale Video
                                    </div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <video src="{{asset('build/assets/video/1.mp4')}}" class="object-fit-scale rounded border" autoplay loop muted></video>
                                </div>
                                <div class="card-footer d-none border-top-0">
    <!-- Prism Code -->
    <pre class="language-html"><code class="language-html">&lt;video src="{{asset('build/assets/video/1.mp4')}}" class="object-fit-scale rounded border" autoplay loop muted&gt;&lt;/video&gt;</code></pre>
    <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Object Fit None Video
                                    </div>
                                    <div class="prism-toggle">
                                        <button class="btn btn-sm btn-primary-light">Show Code<i class="ri-code-line ms-2 d-inline-block align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="card-body object-fit-container">
                                    <video src="{{asset('build/assets/video/1.mp4')}}" class="object-fit-none rounded border" autoplay loop muted></video>
                                </div>
                                <div class="card-footer d-none border-top-0">
    <!-- Prism Code -->
    <pre class="language-html"><code class="language-html">&lt;video src="{{asset('build/assets/video/1.mp4')}}" class="object-fit-none rounded border" autoplay loop muted&gt;&lt;/video&gt;</code></pre>
    <!-- Prism Code -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End::row-1 -->

@endsection

@section('scripts')
	
    <!-- Prism JS -->
    <script src="{{asset('build/assets/libs/prismjs/prism.js')}}"></script>
    @vite('resources/assets/js/prism-custom.js')

@endsection
