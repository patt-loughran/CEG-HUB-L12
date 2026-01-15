<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">

{{-- Main Page Container --}}
<div class="flex flex-col h-screen p-6 gap-6 max-w-[1840px] mx-auto">

    {{-- TOP ROW: Stat Tiles (No changes here) --}}
    <div class="flex flex-col sm:flex-row flex-wrap gap-6">
        
        <x-general.simple-stat
            title="Prev Pay-Period Status"
            dataKey="prevPayPeriodStatus"
            format="string"
            iconClasses="bg-green-100 text-green-700"
            failClasses="bg-red-100 text-red-700"
            iconName="pastClock"
            eventPrefix="timesheet"
            :successCondition="json_encode(['type' => 'equals', 'value' => 'complete'])"
            
        />
        
        <x-general.simple-stat 
            title="Days Left in Pay-Period"
            dataKey="daysLeftInPayPeriod"
            format="number"
            iconClasses="bg-blue-100 text-blue-700"
            iconName="calendar"
            eventPrefix="timesheet"
        />

        <x-general.simple-stat 
            title="Current Pay-Period Hours"
            dataKey="currentPayPeriodHours"
            format="hours"
            iconClasses="bg-purple-100 text-purple-700"
            iconName="simpleClock"
            eventPrefix="timesheet"
        />

    </div>

    {{-- BOTTOM ROW: Main Timesheet Entry Component --}}
    <div class="flex-grow min-h-0">
        <x-time.timesheet-entry>
            <x-slot name="slot">
                <x-time.date-navigator :date-navigator-data="$dateNavigatorData" />
            </x-slot>
        </x-time.timesheet-entry>
    </div>

    {{-- This component is headless and handles all data fetching for the page (No changes here) --}}
    <x-time.data-bridge />

    {{-- Add the error modal component here --}}
    <x-general.error-modal />

 <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('timesheetPageRegistry', {
                checks: [],
                registerDirtyCheck(callbackFunction) {
                    this.checks.push(callbackFunction);
                },
                isPageDirty() {
                    return this.checks.some(checkFn => checkFn());
                }
            });
        });
    </script>

    @stack('scripts')

</body>

</html>