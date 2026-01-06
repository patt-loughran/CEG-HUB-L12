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
<div class="flex flex-col lg:grid lg:grid-cols-[360px_1fr] h-screen p-6 gap-6 max-w-[1840px] mx-auto">

    {{-- LEFT COLUMN --}}
    <div class="flex flex-col gap-6 min-w-0 lg:min-h-0">
        <x-finance.payroll.control-panel :dateRanges="$dateRanges" />
        <x-finance.payroll.pay-period-quick-list />
    </div>

    {{-- RIGHT COLUMN --}}
    <div class="flex flex-col gap-6 min-w-0 lg:min-h-0">

        {{-- Stat Tiles --}}
        <div class="flex flex-col sm:flex-row flex-wrap gap-6">
            
            <x-general.simple-stat 
                title="Total Hours"
                dataKey="totalHours"
                format="hours"
                iconClasses="bg-blue-100 text-blue-700"
                iconName="simpleClock"
                eventPrefix="payroll"
            />
            
            <x-general.simple-stat 
                title="Billable Percentage"
                dataKey="averageBillablePercentage"
                format="percentage"
                iconClasses="bg-green-100 text-green-700"
                iconName="pieChart"
                eventPrefix="payroll"
            />

            <x-general.simple-stat 
                title="Total Overtime"
                dataKey="totalOvertime"
                format="hours"
                iconClasses="bg-orange-100 text-orange-700"
                iconName="warning"
                eventPrefix="payroll"
            />

        </div>

        {{-- Payroll Table --}}
        <x-finance.payroll.payroll-table />
        
    </div>
</div>

{{-- modal export component --}}
<x-general.export-table />

{{-- This component is invisible --}}
<x-finance.payroll.data-bridge />

</body>
@stack('scripts')
</html>