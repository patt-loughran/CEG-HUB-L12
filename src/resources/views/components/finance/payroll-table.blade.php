{{-- Define the properties (props) that this Blade component accepts --}}
@props(['rows', 'timePeriod'])

{{-- Main component container with styling for a card-like appearance --}}
<div class="bg-white rounded-lg shadow-sm border border-slate-200 flex flex-col flex-1 min-h-[320px] max-w-[1400px] overflow-hidden">
      
    {{-- Component header section --}}
    <header class="p-4 border-b border-slate-200">
        <div class="flex items-center justify-between">
            
            {{-- Left side of the header containing the title and the selected time period --}}
            <div>
                <h2 class="font-bold text-slate-800 text-lg">Employee Hours Summary</h2>
                <p class="text-sm text-slate-500 -mt-0.5">Data for {{ $timePeriod['str_representation'] }}</p>
            </div>

            {{-- Right side of the header, showing an "Export" button only if there is data to export --}}
            @if ($rows && $rows->isNotEmpty())
                <button class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-200 text-slate-800 text-sm font-medium rounded-lg border border-slate-200 hover:bg-slate-700 hover:text-white hover:border-slate-300 hover:cursor-pointer transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export
                </button>
            @endif
        </div>
    </header>

    {{-- Check if there are any rows of data to display in the table --}}
    @if ($rows && $rows->isNotEmpty())
        {{-- Container for the table that allows for horizontal and vertical scrolling --}}
        <div class="overflow-x-auto lg:flex-1 lg:overflow-y-auto">
            <table class="w-full text-sm">
                {{-- Table header with sticky positioning to keep it visible during scroll --}}
                <thead class="bg-slate-50 sticky top-0">
                    <tr>
                        <th scope="col" class="py-4 px-0 xl:px-1 2xl:px-2 text-left text-xs font-semibold text-slate-500 xl::uppercase tracking-wider whitespace-nowrap">Employee</th>
                        <th scope="col" class="py-4 px-0 xl:px-1 2xl:px-2 text-left text-xs font-semibold text-slate-500 xl:uppercase tracking-wider whitespace-nowrap">PTO</th>
                        <th scope="col" class="py-4 px-0 xl:px-1 2xl:px-2 text-left text-xs font-semibold text-slate-500 xl:uppercase tracking-wider whitespace-nowrap">Holiday</th>
                        <th scope="col" class="py-4 px-0 xl:px-1 2xl:px-2 text-left text-xs font-semibold text-slate-500 xl:uppercase tracking-wider whitespace-nowrap">Other 200</th>
                        <th scope="col" class="py-4 px-0 xl:px-1 2xl:px-2 text-left text-xs font-semibold text-slate-500 xl:uppercase tracking-wider whitespace-nowrap">Other NB</th>
                        <th scope="col" class="py-4 px-0 xl:px-1 2xl:px-2 text-left text-xs font-semibold text-slate-500 xl:uppercase tracking-wider whitespace-nowrap">Total NB</th>
                        <th scope="col" class="py-4 px-0 xl:px-1 2xl:px-2 text-left text-xs font-semibold text-slate-500 xl:uppercase tracking-wider whitespace-nowrap">Billable</th>
                        <th scope="col" class="py-4 px-0 xl:px-1 2xl:px-2 text-left text-xs font-semibold text-slate-500 xl:uppercase tracking-wider whitespace-nowrap">Total Hours</th>
                        <th scope="col" class="py-4 px-0 xl:px-1 2xl:px-2 text-left text-xs font-semibold text-slate-500 xl:uppercase tracking-wider whitespace-nowrap">Billable %</th>
                        <th scope="col" class="py-4 px-0 xl:px-1 2xl:px-2 text-left text-xs font-semibold text-slate-500 xl:uppercase tracking-wider whitespace-nowrap">Overtime</th>
                        <th scope="col" class="py-4 px-0 xl:px-1 2xl:px-2 text-right text-xs font-semibold text-slate-500 xl:uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                {{-- Table body which will be populated with data rows --}}
                <tbody class="divide-y divide-slate-200">
                    {{-- Loop through each row of data to create a table row for each employee --}}
                    @foreach ($rows as $row)
                        <tr class="hover:bg-slate-50">
                            {{-- Employee information cell with avatar, name, and ID --}}
                            <td class="py-4 px-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    @php
                                        $initials = help_generate_initials($row['name']);
                                        $colors = help_get_avatar_colors($initials);
                                    @endphp
                                    <!-- Hidden by default, visible from md breakpoint up -->
                                    <div class="hidden xl:flex flex-shrink-0 h-9 w-9 rounded-full items-center justify-center text-sm font-bold {{ $colors['background'] }} {{ $colors['text'] }}">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-900">{{ $row['name'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $row['emp_id'] }}</div>
                                    </div>
                                </div>
                            </td>
                            {{-- Cells for various time categories, formatted to one decimal place --}}
                            <td class="py-4 px-0 xl:px-1 2xl:px-2 text-slate-700 whitespace-nowrap">{{ number_format($row['pto'], 1) }}</td>
                            <td class="py-4 px-0 xl:px-1 2xl:px-2 text-slate-700 whitespace-nowrap">{{ number_format($row['holiday'], 1) }}</td>
                            <td class="py-4 px-0 xl:px-1 2xl:px-2 text-slate-700 whitespace-nowrap">{{ number_format($row['other_200'], 1) }}</td>
                            <td class="py-4 px-0 xl:px-1 2xl:px-2 text-slate-700 whitespace-nowrap">{{ number_format($row['other_nb'], 1) }}</td>
                            <td class="py-4 px-0 xl:px-1 2xl:px-2 font-medium text-slate-800 whitespace-nowrap">{{ number_format($row['total_non_billable'], 1) }}</td>
                            <td class="py-4 px-0 xl:px-1 2xl:px-2 font-medium text-slate-800 whitespace-nowrap">{{ number_format($row['billable'], 1) }}</td>
                            
                            {{-- Total hours cell with conditional styling based on the value --}}
                            <td class="py-4 px-0 xl:px-1 2xl:px-2 whitespace-nowrap">
                                @if ($row['total_hours'] >= 80)
                                    <span class="inline-flex items-center px-2.5 py-1 text-sm font-semibold text-green-800 bg-green-100 rounded-full">{{ number_format($row['total_hours'], 1) }}</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 text-sm font-semibold text-amber-800 bg-amber-100 rounded-full">{{ number_format($row['total_hours'], 1) }}</span>
                                @endif
                            </td>

                            {{-- Billable percentage cell, including a visual progress bar on larger screens --}}
                            <td class="py-4 px-0 xl:px-1 2xl:px-2 text-slate-700 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span>{{ number_format($row['billable_percent'], 0) }}%</span>
                                    <div class="hidden min-[1800px]:block w-20 h-1.5 bg-slate-200 rounded-full">
                                        <div class="bg-slate-400 h-1.5 rounded-full" style="width: {{ $row['billable_percent'] }}%"></div>
                                    </div>
                                </div>
                            </td>
                            
                            {{-- Overtime cell, conditionally displays a badge if overtime is greater than zero --}}
                            <td class="py-4 px-0 xl:px-1 2xl:px-2 whitespace-nowrap">
                                @if ($row['overtime'] > 0)
                                    <span class="inline-flex items-center px-2.5 py-1 text-sm font-semibold text-orange-800 bg-orange-100 rounded-full">{{ number_format($row['overtime'], 1) }}</span>
                                @else
                                    <span class="font-medium text-slate-500">0.0</span>
                                @endif
                            </td>

                            {{-- Actions cell with a "more options" button --}}
                            <td class="py-4 px-0 xl:px-1 2xl:px-2 text-right whitespace-nowrap">
                                <button class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 rounded-full">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        {{-- This block is displayed if there is no data to show --}}
        <div class="text-center p-16">
            <svg class="mx-auto h-16 w-16 text-slate-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75l-2.489-2.489m0 0a3.375 3.375 0 10-4.773-4.773 3.375 3.375 0 004.774 4.774zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-slate-800">No Results Found</h3>
            <p class="mt-2 text-sm text-slate-500">There is no employee timesheet data to display for the selected criteria.</p>
        </div>
    @endif
</div>