<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Luxury Login</title>

<link id="style" href="{{asset('build/assets/libs/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet" >
<!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">-->
<link rel="stylesheet" href="{{asset('build/assets/themecss/custom-static.css')}}">
<!--<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">-->

</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card-register">

        <div class="text-center">
            <h2 class="auth-title">Sign in to your account</h2>
            <p class="auth-subtitle">Enter your credentials to continue</p>
        </div>

        <form method="POST" action="{{ route('register') }}">
            @csrf
			
			<div class="row g-3">
				<div class="col-md-12">
					<div class="form-floating">
						<input class="form-control form-control-sm" id="name" type="text" name="name" placeholder="" required autocomplete="name" />
						<label for="name">Business Name</label>
					</div>
				</div>
				
				<div class="col-md-4">
					<div class="form-floating">
						<input class="form-control" id="first-name" type="text" name="first-name" placeholder="" required autocomplete="first-name" />
						<label for="first-name">First Name</label>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-floating">
						<input class="form-control" id="last-name" type="text" name="last-name" placeholder="" required autocomplete="last-name" />
						<label for="last-name">Last Name</label>
					</div>
				</div>							
				<div class="col-md-4">
					<div class="form-floating">
						<input class="form-control" id="mobile" type="text" name="mobile" placeholder="" required autofocus autocomplete="mobile" />
						<label for="mobile">Mobile <span>*</span></label>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-floating">
						<input class="form-control" id="email" type="email" name="email" placeholder="" autocomplete="username" />
						<label for="email">Email Address</label>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-floating position-relative">
						<input class="form-control" id="password" type="password" name="password" placeholder="" required autocomplete="current-password" >
						<label for="password">Password</label>
						<span class="toggle-password" onclick="togglePassword()">Show</span>
						<x-input-error :messages="$errors->get('password')" class="mt-2" />
					</div>
				</div>	
				
				<div class="col-md-4">
					<button class="btn btn-smart btn-lg text-white w-100">{{ __('Register') }}</button>
				</div>	
			</div>
			
            
	
		</form>

        <div class="divider">
            <span>OR</span>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">Have an account already? 
                <a href="{{ url('login') }}" class="text-primary fw-semibold">Create one</a>
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
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
