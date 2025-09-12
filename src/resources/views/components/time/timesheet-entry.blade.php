<div x-data='timeTimesheetEntryLogic()'
     @timesheet-data-loading.window="handleDataLoading()"
     @timesheet-data-updated.window="handleDataUpdate($event.detail)"

     class="flex flex-col h-full w-full rounded-lg border border-slate-300 bg-white p-6 font-sans shadow-sm">
    
    <!-- Component Header -->
    <div class="mb-4 flex items-center justify-between">
        <!-- Left: Current Date Context - This remains as it is display data -->
        <div>
            <p class="text-lg font-bold text-slate-700">
                <span x-text="headerInfo.weekLabel || 'Loading...'"></span>
                <span x-text="headerInfo.payPeriodLabel" class="font-normal text-slate-500"></span>
            </p>
        </div>

        <div>
            {{ $slot }}  <!-- date-navigator is injected here (user Input Component) -->
        </div>
    </div>

    <!-- Timesheet Table Wrapper -->
    <div class="overflow-x-auto rounded-lg border border-slate-200 flex-grow">
        <table class="min-w-full border-collapse text-sm">
            <thead class="bg-slate-700 text-white sticky top-0">
                <tr class="divide-x divide-slate-600">
                    <th class="w-10 p-2 text-center">
                        <button @click="pinAll()" title="Pin All Rows" class="p-2 rounded-lg hover:bg-slate-500">
                            <x-general.icon name="thumbPin" class="w-4 h-4 text-white" />
                        </button>
                    </th>
                    <th class="w-10 p-2 text-center">
                        <button @click="deleteAll()" title="Delete All Rows" class="p-2 rounded-lg hover:bg-slate-500">
                            <x-general.icon name="trash" class="w-4 h-4 text-white" />
                        </button>
                    </th>
                    <th class="px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap uppercase">Project Code</th>
                    <th class="px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap uppercase">Sub-Code</th>
                    <th class="px-3 py-3 text-left text-xs font-semibold tracking-wider whitespace-nowrap uppercase">Activity Code</th>
                    <template x-for="(header, index) in dateHeaders" :key="index">
                        <th class="w-20 px-2 py-3 text-center text-xs font-semibold tracking-wider uppercase" x-bind:class="{ 'bg-slate-600': header.isWeekend }">
                            <span x-text="header.day"></span> <br /> <span x-text="header.date"></span>
                        </th>
                    </template>
                    <th class="w-24 px-3 py-3 text-right text-xs font-semibold tracking-wider uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                 <!-- Loading Skeleton -->
                <template x-if="isLoading">
                    <template x-for="i in 5" :key="i">
                        <tr class="divide-x divide-slate-200">
                        <td colspan="5"><div class="h-8 bg-slate-200 rounded animate-pulse m-2"></div></td>
                        <td><div class="h-8 bg-slate-200 rounded animate-pulse m-2"></div></td>
                        <td><div class="h-8 bg-slate-200 rounded animate-pulse m-2"></div></td>
                        <td><div class="h-8 bg-slate-200 rounded animate-pulse m-2"></div></td>
                        <td><div class="h-8 bg-slate-200 rounded animate-pulse m-2"></div></td>
                        <td><div class="h-8 bg-slate-200 rounded animate-pulse m-2"></div></td>
                        <td><div class="h-8 bg-slate-200 rounded animate-pulse m-2"></div></td>
                        <td><div class="h-8 bg-slate-200 rounded animate-pulse m-2"></div></td>
                        <td><div class="h-8 bg-slate-200 rounded animate-pulse m-2"></div></td>
                        </tr>
                    </template>
                </template>

                <!-- Data Rows -->
           <template x-if="!isLoading">
                <template x-for="(row, rowIndex) in timesheetRows" :key="row.rowId">
                    <tr class="divide-x divide-slate-200 hover:bg-slate-50">
                        <td class="p-0 text-center align-middle"></td>
                        <td class="p-0 text-center align-middle">
                            <button @click="removeRow(rowIndex)" title="Remove Row" class="p-2 text-slate-400 text-slate-400 hover:text-red-600">
                                <x-general.icon name="trash" class="w-4 h-4" />
                            </button>
                        </td>
                        <td class="p-0 align-middle"><input type="text" x-model="row.project_code" class="h-full w-full border-0 bg-transparent px-3 py-2 text-slate-900 focus:ring-2 focus:ring-slate-400 focus:outline-none focus:ring-inset" /></td>
                        <td class="p-0 align-middle"><input type="text" x-model="row.sub_project" class="h-full w-full border-0 bg-transparent px-3 py-2 text-slate-900 focus:ring-2 focus:ring-slate-400 focus:outline-none focus:ring-inset" /></td>
                        <td class="p-0 align-middle"><input type="text" x-model="row.activity_code" class="h-full w-full border-0 bg-transparent px-3 py-2 text-slate-900 focus:ring-2 focus:ring-slate-400 focus:outline-none focus:ring-inset" /></td>
                        
                        <!-- Day 1 -->
                        <td class="p-0 align-middle" x-bind:class="{ 'bg-slate-50/75': row.hours[0].isWeekend }">
                            <input type="text" x-model.lazy="row.hours[0].value" @change="validateHourInput(rowIndex, 0)" class="h-full w-full border-0 bg-transparent px-1 py-2 text-center text-slate-800 focus:bg-slate-100 focus:outline-none" />
                        </td>
                        <!-- Day 2 -->
                        <td class="p-0 align-middle" x-bind:class="{ 'bg-slate-50/75': row.hours[1].isWeekend }">
                            <input type="text" x-model.lazy="row.hours[1].value" @change="validateHourInput(rowIndex, 1)" class="h-full w-full border-0 bg-transparent px-1 py-2 text-center text-slate-800 focus:bg-slate-100 focus:outline-none" />
                        </td>
                        <!-- Day 3 -->
                        <td class="p-0 align-middle" x-bind:class="{ 'bg-slate-50/75': row.hours[2].isWeekend }">
                            <input type="text" x-model.lazy="row.hours[2].value" @change="validateHourInput(rowIndex, 2)" class="h-full w-full border-0 bg-transparent px-1 py-2 text-center text-slate-800 focus:bg-slate-100 focus:outline-none" />
                        </td>
                        <!-- Day 4 -->
                        <td class="p-0 align-middle" x-bind:class="{ 'bg-slate-50/75': row.hours[3].isWeekend }">
                            <input type="text" x-model.lazy="row.hours[3].value" @change="validateHourInput(rowIndex, 3)" class="h-full w-full border-0 bg-transparent px-1 py-2 text-center text-slate-800 focus:bg-slate-100 focus:outline-none" />
                        </td>
                        <!-- Day 5 -->
                        <td class="p-0 align-middle" x-bind:class="{ 'bg-slate-50/75': row.hours[4].isWeekend }">
                            <input type="text" x-model.lazy="row.hours[4].value" @change="validateHourInput(rowIndex, 4)" class="h-full w-full border-0 bg-transparent px-1 py-2 text-center text-slate-800 focus:bg-slate-100 focus:outline-none" />
                        </td>
                        <!-- Day 6 -->
                        <td class="p-0 align-middle" x-bind:class="{ 'bg-slate-50/75': row.hours[5].isWeekend }">
                            <input type="text" x-model.lazy="row.hours[5].value" @change="validateHourInput(rowIndex, 5)" class="h-full w-full border-0 bg-transparent px-1 py-2 text-center text-slate-800 focus:bg-slate-100 focus:outline-none" />
                        </td>
                        <!-- Day 7 -->
                        <td class="p-0 align-middle" x-bind:class="{ 'bg-slate-50/75': row.hours[6].isWeekend }">
                            <input type="text" x-model.lazy="row.hours[6].value" @change="validateHourInput(rowIndex, 6)" class="h-full w-full border-0 bg-transparent px-1 py-2 text-center text-slate-800 focus:bg-slate-100 focus:outline-none" />
                        </td>
                        
                        <td class="p-0 align-middle"><div class="bg-slate-100 px-3 py-2 text-right font-semibold text-slate-800" x-text="calculateRowTotal(rowIndex)"></div></td>
                    </tr>
                </template>
            </template>
            </tbody>
            <tfoot class="bg-slate-100 sticky bottom-0">
                {{-- The table footer (tfoot) remains exactly the same --}}
                <tr class="divide-x divide-slate-200">
                    <th colspan="5" scope="row" class="px-3 py-2 text-right text-sm font-bold text-slate-600">Daily Totals</th>
                    <td class="p-2 text-center font-semibold text-slate-800" x-text="footerTotals.dailyTotals[0]?.toFixed(1) || '0.0'"></td>
                    <td class="p-2 text-center font-semibold text-slate-800" x-text="footerTotals.dailyTotals[1]?.toFixed(1) || '0.0'"></td>
                    <td class="p-2 text-center font-semibold text-slate-800" x-text="footerTotals.dailyTotals[2]?.toFixed(1) || '0.0'"></td>
                    <td class="p-2 text-center font-semibold text-slate-800" x-text="footerTotals.dailyTotals[3]?.toFixed(1) || '0.0'"></td>
                    <td class="p-2 text-center font-semibold text-slate-800" x-text="footerTotals.dailyTotals[4]?.toFixed(1) || '0.0'"></td>
                    <td class="p-2 text-center font-semibold text-slate-800" x-text="footerTotals.dailyTotals[5]?.toFixed(1) || '0.0'"></td>
                    <td class="p-2 text-center font-semibold text-slate-800" x-text="footerTotals.dailyTotals[6]?.toFixed(1) || '0.0'"></td>
                    <th scope="row" class="px-3 py-2 text-right font-bold text-slate-800" x-text="footerTotals.weeklyTotal.toFixed(1)"></th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- Component Footer with Actions -->
    <div class="mt-6 flex items-center justify-between">
        {{-- The component footer remains exactly the same --}}
        <div class="flex items-center gap-2">
            <button @click="addNewRow()" class="flex items-center gap-2 whitespace-nowrap rounded-md bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300">
                <span>Add New Row</span>
                <x-general.icon name="add" class="w-5 h-5" />
            </button>
            
            <!-- Actions Dropdown -->
            <div x-data="{ showActionsMenu: false }" class="relative">
                <button @click="showActionsMenu = !showActionsMenu" class="flex items-center gap-2 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <span>More</span>
                    <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                
                <div x-show="showActionsMenu" 
                     @click.away="showActionsMenu = false"
                     x-transition
                     class="absolute bottom-full z-10 mb-2 w-max min-w-full rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                     style="display: none;">
                    
                    <button @click="revertChanges(); showActionsMenu = false;" class="block w-full whitespace-nowrap px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                        Revert Changes
                    </button>
                    <button @click="loadFromLastWeek(); showActionsMenu = false;" class="block w-full whitespace-nowrap px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                        Load from last week
                    </button>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <div class="text-right">
                <span class="text-sm font-medium text-slate-600">Pay Period Hours:</span>
                <span class="block text-lg font-semibold text-slate-800" x-text="payPeriodTotal.toFixed(1)"></span>
            </div>
            <button class="rounded-md bg-slate-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Save Timesheet</button>
        </div>
    </div>
</div>
  
@push('scripts')
<script>
    function timeTimesheetEntryLogic() {
        return {
            // Data properties for display
            headerInfo: null,
            dateHeaders: null,
            timesheetRows: null,
            pristineTimesheetRows: null, // Stores a clean copy to check for changes
            footerTotals: null,
            payPeriodTotal: null,

            // State management
            isLoading: null,
            error: null,
            hasUnsavedChanges: null,

            init() {
                // Initialize with empty/loading states
                this.headerInfo = { weekLabel: '', payPeriodLabel: '' };
                this.dateHeaders = Array(7).fill({ day: '', date: '', isWeekend: false });
                this.timesheetRows = [];
                this.pristineTimesheetRows = [];
                this.footerTotals = { dailyTotals: Array(7).fill(0), weeklyTotal: 0 };
                this.payPeriodTotal = 0;
                
                // Set initial loading state to true
                this.isLoading = true;
                this.error = null;
                this.hasUnsavedChanges = false;
                
                // Watch for user edits to flag unsaved changes
                this.$watch('timesheetRows', (newValue) => {
                    // Only compare if not in the middle of loading new data
                    if (!this.isLoading) {
                        this.hasUnsavedChanges = JSON.stringify(newValue) !== JSON.stringify(this.pristineTimesheetRows);
                    }
                }, { deep: true });

                // Warn user if they try to leave with unsaved changes
                window.addEventListener('beforeunload', (event) => {
                    if (this.hasUnsavedChanges) {
                        event.preventDefault();
                        event.returnValue = ''; // Required for legacy browsers
                    }
                });
            },

            // Data calculation methods (These are unchanged)
            calculateRowTotal(rowIndex) {
                let total = this.timesheetRows[rowIndex].hours.reduce((sum, hour) => sum + parseFloat(hour.value || 0), 0);
                this.timesheetRows[rowIndex].rowTotal = total;
                return total.toFixed(1);
            },
            
            recalculateAllTotals() {
                const newDailyTotals = Array(7).fill(0);
                this.timesheetRows.forEach(row => {
                    row.hours.forEach((hour, index) => {
                        newDailyTotals[index] += parseFloat(hour.value || 0);
                    });
                });
                this.footerTotals.dailyTotals = newDailyTotals;
                this.footerTotals.weeklyTotal = newDailyTotals.reduce((sum, total) => sum + total, 0);
            },

            validateHourInput(rowIndex, hourIndex) {
                let input = this.timesheetRows[rowIndex].hours[hourIndex].value;
                let numericValue = parseFloat(input);

                if (isNaN(numericValue) || numericValue < 0) {
                    this.timesheetRows[rowIndex].hours[hourIndex].value = 0;
                } else {
                    this.timesheetRows[rowIndex].hours[hourIndex].value = (Math.round(numericValue * 4) / 4).toFixed(2);
                }
                this.recalculateAllTotals();
            },

            // Row management methods (These are unchanged)
            addNewRow() {
                // Create a default hours array matching the current week's weekend structure
                const newHours = this.dateHeaders.map(header => ({ value: 0, isWeekend: header.isWeekend }));

                this.timesheetRows.push({
                    rowId: `new_${Date.now()}`,
                    project_code: '',
                    sub_project: '',
                    activity_code: '',
                    is_pinned: false,
                    hours: newHours,
                    rowTotal: 0,
                });
            },

            removeRow(rowIndex) {
                this.timesheetRows.splice(rowIndex, 1);
                this.recalculateAllTotals();
            },

            revertChanges() {
                if (confirm('Are you sure you want to discard all changes?')) {
                    this.timesheetRows = JSON.parse(JSON.stringify(this.pristineTimesheetRows));
                    this.recalculateAllTotals();
                    this.hasUnsavedChanges = false;
                }
            },

            loadFromLastWeek() {
                alert('"Load from last week" functionality is not yet implemented.');
            },

            // Event Handlers for data-bridge communication
            handleDataLoading() {
                this.isLoading = true;
                this.error = null;
                // Clear old data to prevent stale information from showing
                this.headerInfo = { weekLabel: '', payPeriodLabel: '' };
                this.hasUnsavedChanges = false;
            },

            handleDataUpdate(response) {
                const data = response.timesheetData; 

                this.headerInfo = data.headerInfo;
                this.dateHeaders = data.dateHeaders;
                this.timesheetRows = data.timesheetRows;
                this.footerTotals = data.footerTotals;
                this.payPeriodTotal = data.payPeriodTotal;
                
                // Create a deep copy for pristine state AFTER data has loaded
                this.pristineTimesheetRows = JSON.parse(JSON.stringify(data.timesheetRows));
                
                this.hasUnsavedChanges = false;
                this.isLoading = false;
            },

            handleInputError(detail) {
                this.error = detail.message;
                this.isLoading = false;
                alert(`Error fetching timesheet data: ${this.error}`);
            }
        }
    }
</script>
@endpush