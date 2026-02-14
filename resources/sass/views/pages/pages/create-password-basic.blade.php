
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
                            <div>
                                <h4 class="mb-1 fw-semibold">Create Password</h4>
                                <p class="mb-4 text-muted fw-normal">Set your new password</p>
                            </div>
                            <div class="row gy-3">
                                <div class="col-xl-12">
                                    <label for="create-password" class="form-label text-default">Password</label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control form-control-lg" id="create-password" placeholder="password">
                                        <a href="javascript:void(0);" class="show-password-button text-muted"  onclick="createpassword('create-password',this)"><i class="ri-eye-off-line align-middle"></i></a>
                                    </div>
                                </div>
                                <div class="col-xl-12">
                                    <label for="create-confirmpassword" class="form-label text-default">Confirm Password</label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control form-control-lg" id="create-confirmpassword" placeholder="password">
                                        <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('create-confirmpassword',this)" ><i class="ri-eye-off-line align-middle"></i></a>
                                    </div>
                                    <div class="mt-2">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" checked>
                                            <label class="form-check-label" for="defaultCheck1">
                                                Remember password ?
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid mt-3">
                                <a href="{{url('index')}}" class="btn btn-primary">Create Password</a>
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
                                Dont have an account? <a href="{{url('sign-up-basic')}}" class="text-primary">Register Here</a>
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
