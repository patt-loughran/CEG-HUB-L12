<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">

{{-- Main Container 
--}}
<div class="flex h-screen overflow-hidden" x-data="{ activeTab: 'historian', sidebarOpen: true }">

    {{-- Left Sidebar: User Input Component --}}
    {{-- We don't need to pass sidebarOpen as a prop; Alpine scopes inherit automatically --}}
    <x-time.employee-time.user-input 
        :payPeriodData="$payPeriodData" 
        :startDateCutOff="$startDateCutOff" 
        :endDateCutOff="$endDateCutOff" 
    />

    {{-- Main Content Area --}}
    {{-- Flex-1 allows it to fill the remaining space as the sidebar shrinks --}}
    <div class="flex-1 flex flex-col p-6 gap-6 overflow-hidden relative transition-all duration-300">

        {{-- Tab Navigation --}}
        <div class="shrink-0">
            <nav class="inline-flex bg-white rounded-xl shadow-lg p-1.5 gap-1" aria-label="Tabs">
                <button 
                    @click="activeTab = 'historian'"
                    class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all"
                    :class="activeTab === 'historian' 
                        ? 'bg-slate-700 text-white shadow-md' 
                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700 font-medium'"
                >
                    Historian
                </button>
                <button 
                    @click="activeTab = 'aggregated'"
                    class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all"
                    :class="activeTab === 'aggregated' 
                        ? 'bg-slate-700 text-white shadow-md' 
                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700 font-medium'"
                >
                    Aggregated
                </button>
                <button 
                    @click="activeTab = 'charts'"
                    class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all"
                    :class="activeTab === 'charts' 
                        ? 'bg-slate-700 text-white shadow-md' 
                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700 font-medium'"
                >
                    Charts
                </button>
            </nav>
        </div>

        {{-- Tabbed Content Area --}}
        {{-- CHANGE 1: Remove 'overflow-auto'. Add 'flex flex-col overflow-hidden relative'. 
            This forces this container to be exactly the size of the remaining screen space. --}}
        <div class="flex-1 flex flex-col overflow-hidden relative">
            
            {{-- CHANGE 2: Add 'h-full' to the wrapper div. 
                This ensures the Alpine x-show div takes up 100% of the parent's flex space. --}}
            <div x-show="activeTab === 'historian'" x-cloak class="h-full">
                {{-- CHANGE 3: Ensure the component itself accepts the height. 
                    (Your component already has 'h-full', so this is just a safety check) --}}
                <x-time.employee-time.historian-table class="h-full" />
            </div>

            <div x-show="activeTab === 'aggregated'" x-cloak class="h-full">
                <x-time.employee-time.aggregated-table class="h-full" />
            </div>

            <div x-show="activeTab === 'charts'" x-cloak class="h-full">
                {{-- Charts usually need a specific overflow strategy, but h-full here is safe --}}
                <div class="bg-white rounded-xl shadow-lg p-12 text-center h-full overflow-auto">
                    <p class="text-slate-400 text-sm">Charts coming soon.</p>
                </div>
            </div>
        </div>

    {{-- modal export component --}}
    <x-general.export-table />

    {{-- Headless Components --}}
    <x-time.employee-time.data-bridge />

    @stack('scripts')
</div>
</body>
</html>