<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Digital Invoicing</title>

<link id="style" href="{{asset('build/assets/libs/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet" >
<link id="style" href="{{asset('build/assets/themecss/custom-static.css')}}" rel="stylesheet" >
<!--<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">-->

</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">

        <div class="text-center">
            <h2 class="auth-title">Forgot your password?</h2>
            <p class="auth-subtitle pt-2">No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="form-floating mb-3">
                <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" placeholder="" value="{{ old('email') }}" required autofocus />
                <label for="email">Email address</label>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button class="btn btn-smart w-100 text-white"> {{ __('Email Password Reset Link') }}</button>
	
		</form>

    </div>
</div>

</body>
</html>


