
@extends('layouts.custom-master')

@php
// Passing the bodyClass variable from the view to the layout
$bodyClass = 'authentication-background';
@endphp

@section('styles')



@endsection

@section('content')
	
        <div class="authentication-basic-background">
            <img src="{{asset('build/assets/images/media/backgrounds/9.png')}}" alt="">
        </div>

        <div class="container">
            <div class="row justify-content-center align-items-center authentication authentication-basic h-100">
                <div class="col-xxl-4 col-xl-5 col-lg-6 col-md-6 col-sm-8 col-12">
                    <div class="card custom-card border-0 my-4">
                        <div class="card-body p-5">
                            <div class="mb-4"> 
                                <a href="{{url('index')}}"> 
                                    <img src="{{asset('build/assets/images/brand-logos/toggle-logo.png')}}" alt="logo" class="desktop-dark"> 
                                </a> 
                            </div>
                            <p class="h4 mb-2 fw-semibold">Hello Tom Phillip</p>
                            <p class="mb-3 text-muted fw-normal">Welcome Back</p>
                            <div class="d-flex gap-2 align-items-center mb-3">
                                <div class="lh-1">
                                    <span class="avatar avatar-sm avatar-rounded">
                                        <img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="">
                                    </span>
                                </div>
                                <div>
                                    <p class="mb-0 text-dark fw-medium">tomphillip32@gmail.com</p>
                                </div>
                            </div>
                            <div class="row gy-3">
                                <div class="col-xl-12 mb-2">
                                    <label for="lockscreen-password" class="form-label text-default">Password</label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" id="lockscreen-password" placeholder="password">
                                        <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('lockscreen-password',this)" id="button-addon2"><i class="ri-eye-off-line align-middle"></i></a>
                                    </div>
                                </div>
                                <div class="col-xl-12 d-grid mt-2">
                                    <a href="{{url('index')}}" class="btn btn-lg btn-primary">Unlock</a>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="fw-medium mt-3 mb-0">Try unlock with <a class="text-success" href="javascript:void(0);"><u>Finger print</u></a> / <a class="text-success" href="javascript:void(0);"><u>Face Id</u></a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

@endsection

@section('scripts')
	
        <!-- Show Password JS -->
        <script src="{{asset('build/assets/show-password.js')}}"></script>

@endsection
