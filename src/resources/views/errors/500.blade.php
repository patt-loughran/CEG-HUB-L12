<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans antialiased">
    <div class="flex min-h-screen items-center justify-center px-4">
       
        <div class="mx-auto w-full max-w-lg rounded-lg border border-slate-200 bg-white p-8 text-center shadow-md">
           
            <!-- Icon -->
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-red-100">
                <x-general.icon name="warning" class="h-12 w-12 text-red-600" />
            </div>
            
            <!-- Heading -->
            <h1 class="mt-6 text-5xl font-bold tracking-tight text-slate-800">500</h1>
            <h2 class="mt-2 text-2xl font-semibold text-slate-700">Internal Server Error</h2>
            
            <!-- Technical Details -->
            <div class="mt-6">
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4 text-left">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600 mb-2">Error Details</p>
                    <div class="max-h-64 overflow-y-auto">
                        <p class="text-sm text-slate-700 font-mono break-words whitespace-pre-wrap">{{ $error_message ?? 'No additional technical information available.' }}</p>
                    </div>
                </div>
            </div>
            
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