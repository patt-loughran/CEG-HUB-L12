<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">

{{-- Main Page Container - Full viewport height, flex row layout --}}
<div class="flex h-screen">

    {{-- Left Sidebar: User Input Component --}}
    <x-time.employee-time.user-input 
        :payPeriodData="$payPeriodData" 
        :startDateCutOff="$startDateCutOff" 
        :endDateCutOff="$endDateCutOff" 
    />

    {{-- Main Content Area --}}
    <div class="flex-1 flex flex-col p-6 gap-6 overflow-auto">

        <div class="mb-6">
            <nav class="inline-flex bg-white rounded-xl shadow-lg p-1.5 gap-1" aria-label="Tabs">
                <a href="#" class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-slate-700 text-white shadow-md transition-all">Historian</a>
                <a href="#" class="px-5 py-2.5 rounded-lg text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-all">Aggregated</a>
                <a href="#" class="px-5 py-2.5 rounded-lg text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-all">Charts</a>
            </nav>
        </div>



    </div>

    {{-- Headless Components --}}
    <x-time.employee-time.data-bridge />
    <x-general.error-modal />

    @stack('scripts')
</div>
</body>
</html>