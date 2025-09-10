<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans antialiased">

    <div class="flex min-h-screen items-center justify-center">
        
        <div class="mx-auto w-full max-w-lg rounded-lg border border-slate-200 bg-white p-8 text-center shadow-md">
            
            <!-- Icon -->
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-red-100">
                <svg class="h-12 w-12 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" />
                </svg>
            </div>

            <!-- Heading -->
            <h1 class="mt-6 text-5xl font-bold tracking-tight text-slate-800">500</h1>
            <h2 class="mt-2 text-2xl font-semibold text-slate-700">Internal Server Error</h2>

            <!-- Message -->
            <p class="mt-4 text-base text-slate-500">
                {{-- This will display the custom message from the controller, with a generic fallback --}}
                {{ $message ?? 'Sorry, something went wrong on our end. We have been notified and are looking into it.' }}
            </p>

            <!-- Action Button -->
            <div class="mt-8">
                <a href="{{ url('/') }}" class="inline-block rounded-md bg-slate-700 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Go Back Home
                </a>
            </div>

        </div>

    </div>

</body>
</html>