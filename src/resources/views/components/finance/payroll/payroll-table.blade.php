{{--
    This is a "Display Component".
    It is responsible for displaying the main payroll summary table.

    It listens for events from the data-bridge component to update its state.
    - @payroll-data-loading.window: Shows a loading skeleton.
    - @payroll-data-updated.window: Receives data and displays the table.
    - @payroll-data-error.window: Shows an error message.

    This component handles its own client-side sorting.
    It now includes a responsive design, showing a card-based view on mobile (screens < lg)
    and a full table on larger screens (screens >= lg).
--}}

<div x-data="payrollTableLogic()" 
    @payroll-data-loading.window="handlePayrollFetchInitiated()"
    @payroll-data-updated.window="handlePayrollDataUpdate($event)"
    @payroll-data-error.window="handlePayrollError($event)"
    @process-export.window="handleExport($event)"
    class="bg-white rounded-lg shadow-sm border border-slate-200 flex flex-col flex-1 min-h-[320px] max-w-full overflow-hidden">
      
    {{-- Component Header --}}
    <header class="p-4 border-b border-slate-200 flex-shrink-0">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-slate-800 text-lg">Employee Hours Summary</h2>
                <p class="text-sm text-slate-500 -mt-0.5 min-h-[1.25rem]">
                    <span x-show="!isLoading && !error && payPeriodIdentifier" x-text="`Data for ${payPeriodIdentifier}`"></span>
                    <span x-show="isLoading">Fetching the latest data...</span>
                    <span x-show="!isLoading && error">Data could not be loaded</span>
                </p>
            </div>
            <template x-if="!isLoading && !error && tableData.length > 0">
                <button x-on:click="openExportModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-200 text-slate-800 text-sm font-medium rounded-lg border border-slate-200 hover:bg-slate-700 hover:text-white hover:border-slate-300 hover:cursor-pointer transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Export
                </button>
            </template>
        </div>
    </header>

    {{-- Main Content Area: Switches between Skeleton, Error, No Results, and Data Table --}}
    <div class="overflow-x-auto lg:flex-1 lg:overflow-y-auto relative">
        
        {{-- Loading Skeleton State --}}
        <template x-if="isLoading">
            <div class="w-full animate-pulse">
                {{-- Skeleton - Desktop View --}}
                <div class="hidden lg:block">
                    <div class="bg-slate-50 sticky top-0 grid grid-cols-11 gap-4 py-4 px-4 border-b border-slate-200">
                        <div class="h-4 bg-slate-200 rounded col-span-2"></div>
                        <template x-for="i in 9"><div class="h-4 bg-slate-200 rounded col-span-1"></div></template>
                    </div>
                    <div class="p-4 space-y-4">
                        <template x-for="i in 10" :key="i">
                            <div class="grid grid-cols-11 gap-4 items-center">
                                <div class="col-span-2 flex items-center gap-3"><div class="h-9 w-9 bg-slate-200 rounded-full flex-shrink-0"></div><div class="space-y-2 flex-1"><div class="h-3 bg-slate-200 rounded w-3/4"></div><div class="h-2 bg-slate-200 rounded w-1/2"></div></div></div>
                                <template x-for="i in 9"><div class="h-4 bg-slate-200 rounded col-span-1"></div></template>
                            </div>
                        </template>
                    </div>
                </div>
                {{-- Skeleton - Mobile View --}}
                <div class="lg:hidden p-4 space-y-4">
                     <template x-for="i in 5" :key="i">
                        <div class="border border-slate-200 rounded-lg p-4 space-y-4">
                            <div class="flex items-center gap-3"><div class="h-10 w-10 bg-slate-200 rounded-full"></div><div class="flex-1 space-y-2"><div class="h-4 bg-slate-200 rounded w-3/4"></div><div class="h-3 bg-slate-200 rounded w-1/2"></div></div></div>
                            <div class="grid grid-cols-3 gap-4"><div class="h-8 bg-slate-200 rounded-lg"></div><div class="h-8 bg-slate-200 rounded-lg"></div><div class="h-8 bg-slate-200 rounded-lg"></div></div>
                        </div>
                     </template>
                </div>
            </div>
        </template>

        {{-- Error State --}}
        <template x-if="!isLoading && error">
             <div class="text-center p-8 lg:p-16 flex flex-col items-center justify-center h-full">
                <svg class="mx-auto h-16 w-16 text-red-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                <h3 class="mt-4 text-lg font-semibold text-red-800">An Error Occurred</h3>
                <p class="mt-2 text-sm text-slate-600" x-text="error.message || 'The server could not process the request.'"></p>
                <p class="mt-1 text-xs text-slate-400">Try changing a filter to fetch the data again.</p>
            </div>
        </template>

        {{-- No Results State --}}
        <template x-if="!isLoading && !error && tableData.length === 0">
             <div class="text-center p-8 lg:p-16 flex flex-col items-center justify-center h-full">
                <svg class="mx-auto h-16 w-16 text-slate-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75l-2.489-2.489m0 0a3.375 3.375 0 10-4.773-4.773 3.375 3.375 0 004.774 4.774zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <h3 class="mt-4 text-lg font-semibold text-slate-800">No Results Found</h3>
                <p class="mt-2 text-sm text-slate-500">There is no employee timesheet data to display for the selected criteria.</p>
            </div>
        </template>

        {{-- Data Display --}}
        <template x-if="!isLoading && !error && tableData.length > 0">
            <div>
                {{-- DESKTOP: Traditional Table View --}}
                <table class="hidden lg:table w-full text-sm">
                    <thead class="bg-slate-50 sticky top-0 z-10">
                        <tr>
                            <th x-on:click="sortBy('employee_name')" scope="col" class="py-4 px-4 text-left text-xs font-semibold text-slate-800 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-slate-100"><div class="flex items-center gap-2"><span>Employee</span><span x-show="sortColumn === 'employee_name'" x-bind:class="sortDirection === 'asc' ? 'rotate-180' : ''" class="transition-transform"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg></span></div></th>
                            <th x-on:click="sortBy('pto')" scope="col" class="py-4 px-4 text-right text-xs font-semibold text-slate-800 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-slate-100"><div class="flex items-center justify-end gap-2"><span>PTO</span><span x-show="sortColumn === 'pto'" x-bind:class="sortDirection === 'asc' ? 'rotate-180' : ''" class="transition-transform"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg></span></div></th>
                            <th x-on:click="sortBy('holiday')" scope="col" class="py-4 px-4 text-right text-xs font-semibold text-slate-800 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-slate-100"><div class="flex items-center justify-end gap-2"><span>Holiday</span><span x-show="sortColumn === 'holiday'" x-bind:class="sortDirection === 'asc' ? 'rotate-180' : ''" class="transition-transform"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg></span></div></th>
                            <th x-on:click="sortBy('other_200')" scope="col" class="py-4 px-4 text-right text-xs font-semibold text-slate-800 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-slate-100"><div class="flex items-center justify-end gap-2"><span>Other 200</span><span x-show="sortColumn === 'other_200'" x-bind:class="sortDirection === 'asc' ? 'rotate-180' : ''" class="transition-transform"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg></span></div></th>
                            <th x-on:click="sortBy('other_nb')" scope="col" class="py-4 px-4 text-right text-xs font-semibold text-slate-800 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-slate-100"><div class="flex items-center justify-end gap-2"><span>Other NB</span><span x-show="sortColumn === 'other_nb'" x-bind:class="sortDirection === 'asc' ? 'rotate-180' : ''" class="transition-transform"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg></span></div></th>
                            <th x-on:click="sortBy('total_nb')" scope="col" class="py-4 px-4 text-right text-xs font-semibold text-slate-800 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-slate-100"><div class="flex items-center justify-end gap-2"><span>Total NB</span><span x-show="sortColumn === 'total_nb'" x-bind:class="sortDirection === 'asc' ? 'rotate-180' : ''" class="transition-transform"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg></span></div></th>
                            <th x-on:click="sortBy('billable')" scope="col" class="py-4 px-4 text-right text-xs font-semibold text-slate-800 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-slate-100"><div class="flex items-center justify-end gap-2"><span>Billable</span><span x-show="sortColumn === 'billable'" x-bind:class="sortDirection === 'asc' ? 'rotate-180' : ''" class="transition-transform"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg></span></div></th>
                            <th x-on:click="sortBy('billable_percentage')" scope="col" class="py-4 px-4 text-right text-xs font-semibold text-slate-800 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-slate-100"><div class="flex items-center justify-end gap-2"><span>Billable %</span><span x-show="sortColumn === 'billable_percentage'" x-bind:class="sortDirection === 'asc' ? 'rotate-180' : ''" class="transition-transform"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg></span></div></th>
                            <th x-on:click="sortBy('total_hours')" scope="col" class="py-4 px-4 text-center text-xs font-semibold text-slate-800 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-slate-100"><div class="flex items-center justify-center gap-2"><span>Total Hours</span><span x-show="sortColumn === 'total_hours'" x-bind:class="sortDirection === 'asc' ? 'rotate-180' : ''" class="transition-transform"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg></span></div></th>
                            <th x-on:click="sortBy('overtime')" scope="col" class="py-4 px-4 text-center text-xs font-semibold text-slate-800 uppercase tracking-wider whitespace-nowrap cursor-pointer hover:bg-slate-100"><div class="flex items-center justify-center gap-2"><span>Overtime</span><span x-show="sortColumn === 'overtime'" x-bind:class="sortDirection === 'asc' ? 'rotate-180' : ''" class="transition-transform"><svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.69l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V3.75A.75.75 0 0110 3z" clip-rule="evenodd" /></svg></span></div></th>
                            <th scope="col" class="py-4 px-4 text-right text-xs font-semibold text-slate-800 uppercase tracking-wider whitespace-nowrap"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <template x-for="row in sortedData()" :key="row.id">
                            <tr class="hover:bg-slate-50">
                                <td class="py-4 px-4 whitespace-nowrap"><div class="flex items-center gap-3"><div x-bind:class="`hidden xl:flex flex-shrink-0 h-9 w-9 rounded-full items-center justify-center text-sm font-bold ${getAvatarColors(generateInitials(row.employee_name)).background} ${getAvatarColors(generateInitials(row.employee_name)).text}`"><span x-text="generateInitials(row.employee_name)"></span></div><div><div class="font-medium text-slate-900" x-text="row.employee_name"></div><div class="text-xs text-slate-500" x-text="`ID: ${row.employee_id}`"></div></div></div></td>
                                <td class="py-4 px-4 whitespace-nowrap text-right"><span x-bind:class="row.pto == 0 ? 'text-slate-300' : 'text-slate-900 '" x-text="row.pto.toFixed(2)"></span></td>
                                <td class="py-4 px-4 whitespace-nowrap text-right"><span x-bind:class="row.holiday == 0 ? 'text-slate-300' : 'text-slate-900'" x-text="row.holiday.toFixed(2)"></span></td>
                                <td class="py-4 px-4 whitespace-nowrap text-right"><span x-bind:class="row.other_200 == 0 ? 'text-slate-300' : 'text-slate-900'" x-text="row.other_200.toFixed(2)"></span></td>
                                <td class="py-4 px-4 whitespace-nowrap text-right"><span x-bind:class="row.other_nb == 0 ? 'text-slate-300' : 'text-slate-900'" x-text="row.other_nb.toFixed(2)"></span></td>
                                <td class="py-4 px-4 whitespace-nowrap text-right font-medium"><span x-bind:class="row.total_nb == 0 ? 'text-slate-300' : 'text-slate-900'" x-text="row.total_nb.toFixed(2)"></span></td>
                                <td class="py-4 px-4 whitespace-nowrap text-right font-medium"><span x-bind:class="row.billable == 0 ? 'text-slate-300' : 'text-slate-900'" x-text="row.billable.toFixed(2)"></span></td>
                                <td class="py-4 px-4 text-slate-700 whitespace-nowrap"><div class="flex items-center justify-end gap-2"><span x-bind:class="row.billable_percentage == 0 ? 'text-slate-300' : 'text-slate-900'" x-text="`${row.billable_percentage.toFixed(0)}%`"></span><div class="hidden min-[1800px]:block w-20 h-1.5 bg-slate-200 rounded-full"><div class="bg-slate-400 h-1.5 rounded-full" x-bind:style="`width: ${row.billable_percentage}%`"></div></div></div></td>
                                <td class="py-4 px-4 whitespace-nowrap text-center"><span x-bind:class="{'inline-flex items-center px-2.5 py-1 text-md font-semibold rounded-full': true, 'text-green-800 bg-green-100': row.total_hours >= 80, 'text-amber-800 bg-amber-100': row.total_hours < 80}" x-text="row.total_hours.toFixed(2)"></span></td>
                                <td class="py-4 px-4 whitespace-nowrap text-center"><template x-if="row.overtime > 0"><span class="inline-flex items-center px-2.5 py-1 text-md font-semibold text-orange-800 bg-orange-100 rounded-full" x-text="row.overtime.toFixed(2)"></span></template><template x-if="row.overtime <= 0"><span class="text-slate-400">0.0</span></template></td>
                                <td class="py-4 px-4 text-right whitespace-nowrap"><button class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 rounded-full"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" /></svg></button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                
                {{-- MOBILE: Card-based View --}}
                <div class="lg:hidden">
                    <div class="divide-y divide-slate-200">
                        <template x-for="row in sortedData()" :key="row.id">
                            <div class="p-4">
                                {{-- Card Header --}}
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div x-bind:class="`flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center text-base font-bold ${getAvatarColors(generateInitials(row.employee_name)).background} ${getAvatarColors(generateInitials(row.employee_name)).text}`">
                                            <span x-text="generateInitials(row.employee_name)"></span>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-900" x-text="row.employee_name"></div>
                                            <div class="text-xs text-slate-500" x-text="`ID: ${row.employee_id}`"></div>
                                        </div>
                                    </div>
                                    <button class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-full"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" /></svg></button>
                                </div>
                                {{-- Card Body: Key Metrics --}}
                                <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div class="text-xs text-slate-500">Total Hours</div>
                                        <div x-bind:class="{'font-bold text-lg rounded-full mt-1': true, 'text-green-700': row.total_hours >= 80, 'text-amber-700': row.total_hours < 80}" x-text="row.total_hours.toFixed(1)"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-slate-500">Billable</div>
                                        <div class="font-bold text-lg text-slate-800 mt-1" x-text="`${row.billable_percentage.toFixed(0)}%`"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-slate-500">Overtime</div>
                                        <div class="font-bold text-lg mt-1" x-bind:class="row.overtime > 0 ? 'text-orange-600' : 'text-slate-400'" x-text="row.overtime.toFixed(1)"></div>
                                    </div>
                                </div>
                                 {{-- Card Footer: Breakdown --}}
                                <div class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                    <div class="flex justify-between"><span class="text-slate-600">Billable Hrs:</span> <span class="font-medium text-slate-800" x-text="row.billable.toFixed(1)"></span></div>
                                    <div class="flex justify-between"><span class="text-slate-600">Non-Billable:</span> <span class="font-medium text-slate-800" x-text="row.total_nb.toFixed(1)"></span></div>
                                    <div class="flex justify-between pl-4"><span class="text-slate-500">PTO:</span> <span class="font-medium text-slate-600" x-text="row.pto.toFixed(1)"></span></div>
                                    <div class="flex justify-between pl-4"><span class="text-slate-500">Holiday:</span> <span class="font-medium text-slate-600" x-text="row.holiday.toFixed(1)"></span></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
    function payrollTableLogic() {
        return {
            // Component State
            tableData: null,
            payPeriodIdentifier: null,
            isLoading: null,
            error: null,
            sortColumn: null,
            sortDirection: null,

            init() {
                // Initialize state variables
                this.tableData = [];
                this.payPeriodIdentifier = '';
                this.isLoading = true; // Show skeleton on initial page load
                this.error = null;
                this.sortColumn = 'employee_name'; // Default sort matches controller
                this.sortDirection = 'asc';
            },

            // --- Client-Side Sorting ---
            sortBy(column) {
                if (this.sortColumn === column) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortColumn = column;
                    this.sortDirection = 'asc';
                }
            },

            sortedData() {
                if (!this.tableData) return [];
                return [...this.tableData].sort((a, b) => {
                    const aValue = a[this.sortColumn];
                    const bValue = b[this.sortColumn];
                    const direction = this.sortDirection === 'asc' ? 1 : -1;
                    if (typeof aValue === 'string') return aValue.localeCompare(bValue) * direction;
                    if (typeof aValue === 'number') return (aValue - bValue) * direction;
                    return 0;
                });
            },

            // --- Helper functions for rendering ---
            generateInitials(name) { return help_generate_initials(name); },
            getAvatarColors(initials) { return help_get_avatar_colors(initials); },

            // --- Event Handlers from Data-Bridge ---
            handlePayrollFetchInitiated() { this.isLoading = true; this.error = null; },
            handlePayrollDataUpdate(event) {
                const data = event.detail;
                this.tableData = data.tableData;
                this.payPeriodIdentifier = data.payPeriodIdentifier;
                this.isLoading = false;
                this.error = null;
            },
            handlePayrollError(event) {
                this.error = event.detail;
                this.tableData = [];
                this.isLoading = false;
            },
            // --- Export Functionality ---
            openExportModal() {
                // Generate a default filename with date
                const date = new Date().toISOString().slice(0, 10);
                const defaultName = `payroll-export-${this.payPeriodIdentifier || date}`.replace(/\s+/g, '-').toLowerCase();
                this.$dispatch('open-export-modal', { defaultName: defaultName });
            },

            handleExport(event) {
                const fileName = event.detail.name || 'payroll-export';
                this.exportToCSV(fileName);
            },

            exportToCSV(fileName) {
                const data = this.sortedData();
                if (!data || data.length === 0) return;

                // Define CSV headers and corresponding data keys
                const columns = [
                    { header: 'Employee Name', key: 'employee_name' },
                    { header: 'Employee ID', key: 'employee_id' },
                    { header: 'PTO', key: 'pto' },
                    { header: 'Holiday', key: 'holiday' },
                    { header: 'Other 200', key: 'other_200' },
                    { header: 'Other NB', key: 'other_nb' },
                    { header: 'Total NB', key: 'total_nb' },
                    { header: 'Billable', key: 'billable' },
                    { header: 'Billable %', key: 'billable_percentage' },
                    { header: 'Total Hours', key: 'total_hours' },
                    { header: 'Overtime', key: 'overtime' }
                ];

                // Build CSV content
                const headers = columns.map(col => col.header);
                const rows = data.map(row => 
                    columns.map(col => {
                        let value = row[col.key];
                        // Handle numeric formatting
                        if (typeof value === 'number') {
                            value = col.key === 'billable_percentage' ? value.toFixed(0) : value.toFixed(1);
                        }
                        // Escape values containing commas or quotes
                        if (typeof value === 'string' && (value.includes(',') || value.includes('"'))) {
                            value = `"${value.replace(/"/g, '""')}"`;
                        }
                        return value;
                    })
                );

                const csvContent = [
                    headers.join(','),
                    ...rows.map(row => row.join(','))
                ].join('\n');

                // Create and trigger download
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                
                link.setAttribute('href', url);
                link.setAttribute('download', `${fileName}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }
        }
    }
</script>
@endpush