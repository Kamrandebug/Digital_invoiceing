
@extends('layouts.custom-master')

@php
// Passing the bodyClass variable from the view to the layout
$bodyClass = 'bg-white';
@endphp

@section('styles')



@endsection

@section('content')
        
        <div class="row authentication authentication-cover-main mx-0">
            <div class="col-xxl-9 col-xl-9">
                <div class="row justify-content-center align-items-center h-100">
                    <div class="col-xxl-4 col-xl-5 col-lg-6 col-md-6 col-sm-8 col-12">
                        <div class="card custom-card border-0 shadow-none my-4">
                            <div class="card-body p-5">
                                <div>
                                    <h4 class="mb-1 fw-semibold">Reset Password</h4>
                                    <p class="mb-4 text-muted fw-normal">Set your new password here.</p>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-xl-12">
                                        <label for="reset-password" class="form-label text-default">Current Password</label>
                                        <div class="position-relative">
                                            <input type="password" class="form-control form-control-lg" id="reset-password" placeholder="current password">
                                            <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('reset-password',this)" id="button-addon2"><i class="ri-eye-off-line align-middle"></i></a>
                                        </div>
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="reset-newpassword" class="form-label text-default">New Password</label>
                                        <div class="position-relative">
                                            <input type="password" class="form-control form-control-lg" id="reset-newpassword" placeholder="new password">
                                            <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('reset-newpassword',this)" id="button-addon21"><i class="ri-eye-off-line align-middle"></i></a>
                                        </div>
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="reset-confirmpassword" class="form-label text-default">Confirm Password</label>
                                        <div class="position-relative">
                                            <input type="password" class="form-control form-control-lg" id="reset-confirmpassword" placeholder="confirm password">
                                            <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('reset-confirmpassword',this)" id="button-addon22"><i class="ri-eye-off-line align-middle"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-grid mt-3">
                                    <a href="{{url('index')}}" class="btn btn-primary">Reset Password</a>
                                </div>
                                <div class="text-center my-3 authentication-barrier">
                                    <span class="op-4 fs-13">OR</span>
                                </div>
                                <div class="d-grid mb-3">
                                    <button class="btn btn-white btn-w-lg border d-flex align-items-center justify-content-center flex-fill mb-3">
                                        <span class="avatar avatar-xs">
                                            <img src="{{asset('build/assets/images/media/apps/google.png')}}" alt="">
                                        </span>
                                        <span class="lh-1 ms-2 fs-13 text-default fw-medium">Signup with Google</span>
                                    </button>
                                    <button class="btn btn-white btn-w-lg border d-flex align-items-center justify-content-center flex-fill">
                                        <span class="avatar avatar-xs flex-shrink-0">
                                            <img src="{{asset('build/assets/images/media/apps/facebook.png')}}" alt="">
                                        </span>
                                        <span class="lh-1 ms-2 fs-13 text-default fw-medium">Signup with Facebook</span>
                                    </button>
                                </div>
                                <div class="text-center mt-3 fw-medium">
                                    Dont want to reset? <a href="{{url('sign-in-basic')}}" class="text-primary">login Here</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-3 col-lg-12 d-xl-block d-none px-0">
                <div class="authentication-cover overflow-hidden">
                    <div class="authentication-cover-logo">
                        <a href="{{url('index')}}">
                        <img src="{{asset('build/assets/images/brand-logos/toggle-logo.png')}}" alt="logo" class="desktop-dark">
                        </a> 
                    </div>
                    <div class="authentication-cover-background">
                        <img src="{{asset('build/assets/images/media/backgrounds/9.png')}}" alt="">
                    </div>
                    <div class="authentication-cover-content">
                        <div class="p-5">
                            <h3 class="fw-semibold lh-base">Welcome to Dashboard</h3>
                            <p class="mb-0 text-muted fw-medium">Manage your website and content with ease using our powerful admin tools.</p>
                        </div>
                        <div>
                            <img src="{{asset('build/assets/images/media/media-72.png')}}" alt="" class="img-fluid">
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
