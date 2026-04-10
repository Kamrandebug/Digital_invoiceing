<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Go Digital.pk</title>

<link id="style" href="{{asset('build/assets/libs/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet" >
<link id="style" href="{{asset('build/assets/themecss/custom-static.css')}}" rel="stylesheet" >
<link id="style" href="{{asset('build/assets/icon-fonts/icons.css')}}" rel="stylesheet" >
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{asset('build/assets/themecss/fontawesome/css/all.min.css')}}">
<style>
	.main-bg{
		background: url("{{asset('build/assets/images/hero-bg.svg')}}") no-repeat !important;
		background-size: cover !important;
	}
	
	body{
		font-family: inter !important;
	}
	
	.fs-md{
		font-size: 14px !important;
	}
	
	.padding-label{
		padding-top: 2px !important;
	}
	
</style>
	@vite(['resources/sass/app.scss'])
</head>
<body class="main-bg">

<div class="auth-wrapper">
    <div class="auth-card ribbon-card">
		<span class="ribbon-3 top-right ribbon-warning">
			<span><i class="fe fe-lock"></i></span>
		</span>
		<div style="text-align: center; margin: 0px 0px 20px 0px;"><img src="{{asset('build/assets/images/se_logo.png')}}" /></div>
        <div class="text-center">
            <h2 class="auth-title">Sign in to your account</h2>
            <p class="auth-subtitle">Enter your credentials to continue</p>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-floating mb-3">
                <input class="form-control" id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username">
                <label for="email">Email address</label>
				<x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
			
            <div class="form-floating mb-3 position-relative">
                <input class="form-control" id="password" type="password" name="password" required autocomplete="current-password" >
                <label for="password">Password</label>
                <span class="toggle-password" onclick="togglePassword()">Show</span>
				
				<x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
			
            <div class="d-flex justify-content-between mb-3">
                <div class="form-check fs-md">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                    <label class="form-check-label fs-md padding-label">Remember me</label>
                </div>
				@if (Route::has('password.request'))
                <a class="small color-red pt-1 fs-md" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
            </div>
			
            <button class="btn btn-lg btn-purple-gradient rounded-pill btn-wave waves-effect waves-light w-100">{{ __('Log in') }}</button>
	
		</form>

        <div class="divider mt-3 mb-1">
            <span>OR</span>
        </div>

        <div class="text-center mt-4">
            <small class="fs-md color-black">Don't have an account? 
                <a href="{{ route('register') }}" class="text-primary fw-semibold">Create one</a>
            </small>
        </div>

    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById("password");
    const toggle = document.querySelector(".toggle-password");

    if (input.type === "password") {
        input.type = "text";
        toggle.textContent = "Hide";
    } else {
        input.type = "password";
        toggle.textContent = "Show";
    }
}
</script>

</body>
</html>


