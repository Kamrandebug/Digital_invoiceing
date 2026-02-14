
@extends('layouts.custom-master')

@php
// Passing the bodyClass variable from the view to the layout
$bodyClass = 'coming-soon-main';
@endphp

@section('styles')



@endsection

@section('content')
	
        <div class="coming-soon-background">
            <img src="{{asset('build/assets/images/media/backgrounds/9.png')}}" alt="">
        </div>
        <div id="particles-js"></div>

        <div class="row authentication coming-soon g-0 my-4 justify-content-center">
            <div class="col-xxl-7 col-xl-7 col-lg-7 col-md-7 col-sm-7 col-11 my-auto">
                <div class="authentication-cover text-center">
                    <div class="authentication-cover-content">
                        <div class="row justify-content-center align-items-center mx-0 g-0">
                            <div class="col-xxl-7 col-xl-7 col-lg-7 col-md-12 col-sm-12 col-12">
                                <div>
                                    <div class="mb-3"> 
                                        <a href="{{url('index')}}"> 
                                            <img src="{{asset('build/assets/images/brand-logos/desktop-logo.png')}}" alt="" class="authentication-brand"> 
                                            <img src="{{asset('build/assets/images/brand-logos/desktop-dark.png')}}" alt="" class="authentication-brand dark"> 
                                        </a> 
                                    </div>
                                    <h1 class="mb-0">Under Maintenance</h1>
                                    <div class="d-flex gap-5 flex-wrap gy-xxl-0 gy-3 justify-content-center my-4 rounded p-4 timer-container" id="timer">
                                    </div>
                                    <p class="mb-4 fs-18">We're cooking up something great! Our website is getting an <br> upgrade – check back soon for a better experience!</p>
                                    <div class="input-group">
                                        <input type="email" class="form-control form-control-lg" placeholder="info@gmail.com" aria-label="info@gmail.com" aria-describedby="button-addon2">
                                        <button class="btn btn-primary btn-lg" type="button" id="button-addon2">Subscribe</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

@endsection

@section('scripts')
	
        <!-- Particles JS -->
        <script src="{{asset('build/assets/libs/particles.js/particles.js')}}"></script>

        <!-- Internal Coming Soon JS -->
        <script src="{{asset('build/assets/coming-soon.js')}}"></script>

@endsection
