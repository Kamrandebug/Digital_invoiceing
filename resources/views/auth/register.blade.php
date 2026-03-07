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
						<input class="form-control @error('name') is-invalid @enderror" id="name" type="text" name="name" value="{{ old('name') }}" placeholder="" required autocomplete="name" />
						<label for="name">Business Name</label>
						@error('name')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				
				<div class="col-md-4">
					<div class="form-floating">
						<input class="form-control @error('first_name') is-invalid @enderror" id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" placeholder="" required autocomplete="given-name" />
						<label for="first_name">First Name</label>
						@error('first_name')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-floating">
						<input class="form-control @error('last_name') is-invalid @enderror" id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" placeholder="" required autocomplete="family-name" />
						<label for="last_name">Last Name</label>
						@error('last_name')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>							
				<div class="col-md-4">
					<div class="form-floating">
						<input class="form-control @error('mobile') is-invalid @enderror" id="mobile" type="text" name="mobile" value="{{ old('mobile') }}" placeholder="" required autofocus autocomplete="tel" />
						<label for="mobile">Mobile <span>*</span></label>
						@error('mobile')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-floating">
						<input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="" autocomplete="username" />
						<label for="email">Email Address</label>
						@error('email')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-floating position-relative">
						<input class="form-control @error('password') is-invalid @enderror" id="password" type="password" name="password" placeholder="" required autocomplete="new-password" >
						<label for="password">Password</label>
						<span class="toggle-password" onclick="togglePassword()">Show</span>
						@error('password')
							<div class="invalid-feedback d-block">{{ $message }}</div>
						@enderror
					</div>
				</div>	
				
				<div class="col-md-4">
					<button type="submit" class="btn btn-smart btn-lg text-white w-100">{{ __('Register') }}</button>
				</div>	
			</div>
		</form>

        <div class="divider">
            <span>OR</span>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">Already have an account? 
                <a href="{{ route('login') }}" class="text-primary fw-semibold">Sign In</a>
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


