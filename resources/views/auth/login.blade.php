<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Luxury Login</title>

<link id="style" href="{{asset('build/assets/libs/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet" >
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Inter', sans-serif;
    background: #f8fafc;
}

/* Page wrapper with breathing space */
.auth-wrapper {
    min-height: 100vh;
/*    padding: 100px 20px;*/
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Card */
.auth-card {
    width: 100%;
    max-width: 440px;
    background: #ffffff;
    border-radius: 18px;
    padding: 45px 40px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.06);
    border: 1px solid #f1f1f1;
    transition: 0.3s;
}

.auth-card:hover {
    box-shadow: 0 30px 70px rgba(0,0,0,0.08);
}

/* Heading */
.auth-title {
    font-weight: 700;
    font-size: 26px;
    color: #111827;
}

.auth-subtitle {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 30px;
}

/* Floating inputs */
.form-floating > .form-control {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.form-floating > .form-control:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.15);
}

/* Button */
.btn-smart {
    background: #4f46e5;
    border: none;
    border-radius: 12px;
    padding: 12px;
    font-weight: 600;
    transition: 0.3s;
}

.btn-smart:hover {
    background: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 10px 20px rgba(79,70,229,0.2);
}

/* Divider */
.divider {
    text-align: center;
    margin: 25px 0;
    position: relative;
}

.divider::before,
.divider::after {
    content: '';
    height: 1px;
    width: 40%;
    background: #e5e7eb;
    position: absolute;
    top: 50%;
}

.divider::before { left: 0; }
.divider::after { right: 0; }

.divider span {
    padding: 0 10px;
    font-size: 12px;
    color: #9ca3af;
}

.social-btn {
    border-radius: 12px;
    padding: 10px;
    font-weight: 500;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 14px;
    color: #6b7280;
}

a {
    text-decoration: none;
}
</style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">

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
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                    <label class="form-check-label small">Remember me</label>
                </div>
				@if (Route::has('password.request'))
                <a class="small text-primary" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
            </div>
			
            <button class="btn btn-smart w-100 text-white">{{ __('Log in') }}</button>
	
		</form>

        <div class="divider">
            <span>OR</span>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">Don't have an account? 
                <a href="#" class="text-primary fw-semibold">Create one</a>
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

<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
