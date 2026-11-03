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
	@vite(['resources/sass/app.scss'])
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
	
	.padding-label{
		padding-top: 2px !important;
	}
</style>
</head>
<body class="main-bg">

<div class="auth-wrapper">
    <div class="auth-card-register">
		<span class="ribbon-3 top-right ribbon-warning">
			<span><i class="fe fe-umbrella"></i></span>
		</span>
		<div style="text-align: center; margin: 0px 0px 20px 0px;">
            <a href="{{ url('/') }}" aria-label="Go to Home"><img src="{{asset('build/assets/images/se_logo.png')}}" alt="Go Digital Logo" style="max-height: 48px;" /></a>
        </div>
        <div class="text-center">
            <h2 class="auth-title">Create your account</h2>
            <p class="auth-subtitle color-black">Enter your details to register</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
			
			<div class="row g-3">
				<div class="col-12 col-md-4">
					<div class="form-floating">
						<input class="form-control @error('name') is-invalid @enderror" id="name" type="text" name="name" value="{{ old('name') }}" placeholder="" required autocomplete="organization" />
						<label for="name">Business Name <span>*</span></label>
						@error('name')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				
				<div class="col-12 col-md-4">
					<div class="form-floating">
						<input class="form-control @error('first_name') is-invalid @enderror" id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" placeholder="" required autocomplete="given-name" />
						<label for="first_name">First Name</label>
						@error('first_name')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-12 col-md-4">
					<div class="form-floating">
						<input class="form-control @error('last_name') is-invalid @enderror" id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" placeholder="" required autocomplete="family-name" />
						<label for="last_name">Last Name</label>
						@error('last_name')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-12 col-md-4">
					<div class="form-floating">
						<input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="" required autocomplete="username" />
						<label for="email">Email Address</label>
						@error('email')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-12 col-md-4">
					<div class="form-floating">
						<input class="form-control @error('city') is-invalid @enderror" id="city" type="text" name="city" value="{{ old('city') }}" placeholder="" autocomplete="address-level2" />
						<label for="city">City</label>
						@error('city')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-12 col-md-4">
					<div class="form-floating">
						<input class="form-control @error('mobile_no') is-invalid @enderror" id="mobile_no" type="text" name="mobile_no" value="{{ old('mobile_no') }}" placeholder="" required autocomplete="tel" maxlength="15" inputmode="tel" />
						<label for="mobile_no">Mobile No <span>*</span></label>
						@error('mobile_no')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-12 mt-4 text-center">
					<button type="submit" class="btn-regiser" id="registerSubmitBtn" style="min-width: 200px;">
						{{ __('Register') }}
					</button>
				</div>
			</div>
		</form>

        <div class="divider mt-4 mb-2">
            <span style="color: #4b5563; font-weight: 600;">OR</span>
        </div>

        <div class="text-center mt-3">
            <small class="fs-md color-black">Already have an account? 
                <a href="{{ route('login') }}" class="text-primary fw-bold">Sign In</a>
            </small>
        </div>

    </div>
</div>

</body>
</html>


