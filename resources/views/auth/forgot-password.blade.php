<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Go Digital.pk</title>

<link id="style" href="{{asset('build/assets/libs/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet" >
<link id="style" href="{{asset('build/assets/themecss/custom-static.css')}}" rel="stylesheet" >
<link id="style" href="{{asset('build/assets/icon-fonts/icons.css')}}" rel="stylesheet" >
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{asset('build/assets/themecss/fontawesome/css/all.min.css')}}">
<style>
	.main-bg{
		background: url("{{asset('build/assets/images/hero-bg.svg')}}") no-repeat !important;
		background-size: cover !important;
		background-position: center !important;
	}
	body{
		font-family: inter !important;
	}
	.fs-md{
		font-size: 14px !important;
	}
</style>
@vite(['resources/sass/app.scss'])
</head>
<body class="main-bg">

<div class="auth-wrapper">
    <div class="auth-card ribbon-card">
		<span class="ribbon-3 top-right ribbon-warning">
			<span><i class="fe fe-key"></i></span>
		</span>
		<div style="text-align: center; margin: 0px 0px 20px 0px;">
            <a href="{{ url('/') }}" aria-label="Go to Home"><img src="{{asset('build/assets/images/se_logo.png')}}" alt="Go Digital Logo" style="max-height: 48px;" /></a>
        </div>
        <div class="text-center">
            <h2 class="auth-title">Forgot your password?</h2>
            <p class="auth-subtitle pt-2">Enter your email address and we will send you a password reset link to choose a new one.</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="form-floating mb-3">
                <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" placeholder="" value="{{ old('email') }}" required autofocus autocomplete="username" />
                <label for="email">Email address</label>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button class="btn btn-lg btn-purple-gradient rounded-pill btn-wave waves-effect waves-light w-100 text-white">
                {{ __('Email Password Reset Link') }}
            </button>
		</form>

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="text-primary fw-semibold fs-md d-inline-flex align-items-center gap-1">
                <i class="fas fa-arrow-left me-1"></i> Back to Login
            </a>
        </div>

    </div>
</div>

</body>
</html>


