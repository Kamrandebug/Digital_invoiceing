<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased">
    <div class="flex min-h-screen w-full">
        <!-- Left Side: Decorative & Branding (Hidden on mobile) -->
        <div
            class="hidden lg:flex w-1/2 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 items-center justify-center relative overflow-hidden">
            <!-- Decorative Circles/Shapes for visual interest -->
            <div class="absolute top-0 left-0 w-full h-full opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0 100 C 20 0 50 0 100 100 Z" fill="white" />
                </svg>
            </div>
            <div class="relative z-10 text-white p-12 text-center">
                <h1 class="text-5xl font-bold mb-4">Welcome Back</h1>
                <p class="text-xl opacity-90">Manage your digital invoices with ease and style.</p>
            </div>
        </div>

        <!-- Right Side: Form Area -->
        <div
            class="w-full lg:w-1/2 flex flex-col justify-center items-center bg-white dark:bg-gray-900 px-6 py-12 lg:px-20">
            <div class="w-full max-w-md">
                <!-- Logo centered above form -->
                <div class="flex justify-center mb-8">
                    <a href="/">
                        <x-application-logo class="w-24 h-24 fill-current text-indigo-600 dark:text-indigo-400" />
                    </a>
                </div>

                <!-- Content Slot -->
                <div class="w-full">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>

</html>
