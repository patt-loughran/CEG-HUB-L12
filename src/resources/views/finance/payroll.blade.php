<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">

{{-- Dummy Data Setup for Payroll Table --}}
@php
    $timePeriod = ['str_representation' => 'PP 24 (11/17 - 11/30)'];
    
    $rows = collect([
        [
            'name' => 'Alice Johnson', 'emp_id' => 'EMP-001', 'pto' => 8.0, 'holiday' => 0.0, 'other_200' => 4.0, 'other_nb' => 2.5, 
            'total_non_billable' => 14.5, 'billable' => 65.5, 'total_hours' => 80.0, 'billable_percent' => 81.9, 'overtime' => 0.0,
        ],
        [
            'name' => 'Bob Williams', 'emp_id' => 'EMP-002', 'pto' => 0.0, 'holiday' => 8.0, 'other_200' => 0.0, 'other_nb' => 1.0, 
            'total_non_billable' => 9.0, 'billable' => 75.0, 'total_hours' => 84.0, 'billable_percent' => 89.3, 'overtime' => 4.0,
        ],
        [
            'name' => 'Charlie Brown', 'emp_id' => 'EMP-003', 'pto' => 16.0, 'holiday' => 0.0, 'other_200' => 5.5, 'other_nb' => 0.0, 
            'total_non_billable' => 21.5, 'billable' => 58.5, 'total_hours' => 80.0, 'billable_percent' => 73.1, 'overtime' => 0.0,
        ],
        [
            'name' => 'Diana Prince', 'emp_id' => 'EMP-004', 'pto' => 0.0, 'holiday' => 0.0, 'other_200' => 10.0, 'other_nb' => 2.0, 
            'total_non_billable' => 12.0, 'billable' => 60.0, 'total_hours' => 72.0, 'billable_percent' => 83.3, 'overtime' => 0.0,
        ],
        [
            'name' => 'Ethan Hunt', 'emp_id' => 'EMP-005', 'pto' => 0.0, 'holiday' => 0.0, 'other_200' => 2.0, 'other_nb' => 3.0, 
            'total_non_billable' => 5.0, 'billable' => 85.0, 'total_hours' => 90.0, 'billable_percent' => 94.4, 'overtime' => 10.0,
        ],
        [
            'name' => 'Fiona Glenanne', 'emp_id' => 'EMP-006', 'pto' => 8.0, 'holiday' => 8.0, 'other_200' => 40.0, 'other_nb' => 14.0, 
            'total_non_billable' => 70.0, 'billable' => 10.0, 'total_hours' => 80.0, 'billable_percent' => 12.5, 'overtime' => 0.0,
        ],
        [
            'name' => 'George Costanza', 'emp_id' => 'EMP-007', 'pto' => 0.0, 'holiday' => 0.0, 'other_200' => 8.0, 'other_nb' => 4.0, 
            'total_non_billable' => 12.0, 'billable' => 68.0, 'total_hours' => 80.0, 'billable_percent' => 85.0, 'overtime' => 0.0,
        ],
        [
            'name' => 'Hannah Montana', 'emp_id' => 'EMP-008', 'pto' => 0.0, 'holiday' => 0.0, 'other_200' => 5.0, 'other_nb' => 0.0, 
            'total_non_billable' => 5.0, 'billable' => 35.0, 'total_hours' => 40.0, 'billable_percent' => 87.5, 'overtime' => 0.0,
        ],
        [
            'name' => 'Ian Malcolm', 'emp_id' => 'EMP-009', 'pto' => 0.0, 'holiday' => 0.0, 'other_200' => 1.0, 'other_nb' => 1.0, 
            'total_non_billable' => 2.0, 'billable' => 80.0, 'total_hours' => 82.0, 'billable_percent' => 97.6, 'overtime' => 2.0,
        ],
        [
            'name' => 'Jane Smith', 'emp_id' => 'EMP-010', 'pto' => 0.0, 'holiday' => 8.0, 'other_200' => 2.0, 'other_nb' => 0.0, 
            'total_non_billable' => 10.0, 'billable' => 70.0, 'total_hours' => 80.0, 'billable_percent' => 87.5, 'overtime' => 0.0,
        ],
        [
            'name' => 'Alice Johnson', 'emp_id' => 'EMP-001', 'pto' => 8.0, 'holiday' => 0.0, 'other_200' => 4.0, 'other_nb' => 2.5, 
            'total_non_billable' => 14.5, 'billable' => 65.5, 'total_hours' => 80.0, 'billable_percent' => 81.9, 'overtime' => 0.0,
        ],
        [
            'name' => 'Bob Williams', 'emp_id' => 'EMP-002', 'pto' => 0.0, 'holiday' => 8.0, 'other_200' => 0.0, 'other_nb' => 1.0, 
            'total_non_billable' => 9.0, 'billable' => 75.0, 'total_hours' => 84.0, 'billable_percent' => 89.3, 'overtime' => 4.0,
        ],
        [
            'name' => 'Charlie Brown', 'emp_id' => 'EMP-003', 'pto' => 16.0, 'holiday' => 0.0, 'other_200' => 5.5, 'other_nb' => 0.0, 
            'total_non_billable' => 21.5, 'billable' => 58.5, 'total_hours' => 80.0, 'billable_percent' => 73.1, 'overtime' => 0.0,
        ],
        [
            'name' => 'Diana Prince', 'emp_id' => 'EMP-004', 'pto' => 0.0, 'holiday' => 0.0, 'other_200' => 10.0, 'other_nb' => 2.0, 
            'total_non_billable' => 12.0, 'billable' => 60.0, 'total_hours' => 72.0, 'billable_percent' => 83.3, 'overtime' => 0.0,
        ],
        [
            'name' => 'Ethan Hunt', 'emp_id' => 'EMP-005', 'pto' => 0.0, 'holiday' => 0.0, 'other_200' => 2.0, 'other_nb' => 3.0, 
            'total_non_billable' => 5.0, 'billable' => 85.0, 'total_hours' => 90.0, 'billable_percent' => 94.4, 'overtime' => 10.0,
        ],
        [
            'name' => 'Fiona Glenanne', 'emp_id' => 'EMP-006', 'pto' => 8.0, 'holiday' => 8.0, 'other_200' => 40.0, 'other_nb' => 14.0, 
            'total_non_billable' => 70.0, 'billable' => 10.0, 'total_hours' => 80.0, 'billable_percent' => 12.5, 'overtime' => 0.0,
        ],
        [
            'name' => 'George Costanza', 'emp_id' => 'EMP-007', 'pto' => 0.0, 'holiday' => 0.0, 'other_200' => 8.0, 'other_nb' => 4.0, 
            'total_non_billable' => 12.0, 'billable' => 68.0, 'total_hours' => 80.0, 'billable_percent' => 85.0, 'overtime' => 0.0,
        ],
        [
            'name' => 'Hannah Montana', 'emp_id' => 'EMP-008', 'pto' => 0.0, 'holiday' => 0.0, 'other_200' => 5.0, 'other_nb' => 0.0, 
            'total_non_billable' => 5.0, 'billable' => 35.0, 'total_hours' => 40.0, 'billable_percent' => 87.5, 'overtime' => 0.0,
        ],
        [
            'name' => 'Ian Malcolm', 'emp_id' => 'EMP-009', 'pto' => 0.0, 'holiday' => 0.0, 'other_200' => 1.0, 'other_nb' => 1.0, 
            'total_non_billable' => 2.0, 'billable' => 80.0, 'total_hours' => 82.0, 'billable_percent' => 97.6, 'overtime' => 2.0,
        ],
        [
            'name' => 'Jane Smith', 'emp_id' => 'EMP-010', 'pto' => 0.0, 'holiday' => 8.0, 'other_200' => 2.0, 'other_nb' => 0.0, 
            'total_non_billable' => 10.0, 'billable' => 70.0, 'total_hours' => 80.0, 'billable_percent' => 87.5, 'overtime' => 0.0,
        ],
    ]);
@endphp


{{-- Main Page Container --}}
<div class="flex flex-col lg:grid lg: grid-cols-[360px_1fr] h-screen p-6 gap-6 max-w-[1840px] mx-auto">

    {{-- LEFT COLUMN --}}
    <div class="flex flex-col gap-6 min-w-0 lg:min-h-0">
        <x-finance.control-panel :dateRanges="$dateRanges" />
        <x-finance.met-hours />
    </div>

    {{-- RIGHT COLUMN --}}
    <div class="flex flex-col gap-6 min-w-0 lg:min-h-0">

        {{-- Stat Tiles --}}
        <div class="flex flex-wrap gap-6">
            
            {{-- Three identical, static stat tiles to show the layout --}}
            <x-finance.simple-stat />
            <x-finance.simple-stat />
            <x-finance.simple-stat />

        </div>

        {{-- Payroll Table --}}
        <x-finance.payroll-table :rows="$rows" :timePeriod="$timePeriod" />
        
    </div>
</div>

{{-- This component is invisible but does all the work --}}
<x-finance.data-bridge />

</body>
@stack('scripts')
</html>