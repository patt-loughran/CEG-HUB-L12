@props([
    'payPeriodData' => [],
    'startDateCutOff' => null,
    'endDateCutOff' => null
])

<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">

    {{-- Main Container --}}
    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: true }">

        {{-- Left Sidebar: User Input Component --}}
        <x-time.employee-time-summary.user-input 
            :payPeriodData="$payPeriodData" 
            :startDateCutOff="$startDateCutOff" 
            :endDateCutOff="$endDateCutOff"
            :showScopes="false"
        />

        {{-- Main Content Area --}}
        <div class="flex-1 flex flex-col p-6 gap-6 overflow-hidden relative transition-all duration-300">

            {{-- Content Area — Single Display Component --}}
            <div class="flex-1 flex flex-col overflow-hidden relative">
                <x-time.employee-time-summary.summary-table />
            </div>

            {{-- Export Modal --}}
            <x-general.export-table />

            {{-- Headless Components --}}
            <x-time.employee-time-summary.data-bridge />
        </div>
    </div>

    @stack('scripts')
</body>
</html>