@props(['dateNavigatorData' => []])

<div x-data='timeTimesheetEntryLogic(@json($dateNavigatorData))'
     @timesheet-data-loading.window="handleDataLoading()"
     @timesheet-data-updated.window="handleDataUpdate($event.detail)"

     class="flex flex-col h-full w-full rounded-lg border border-slate-300 bg-white p-6 font-sans shadow-sm">
    
    <!-- Component Header with Date Navigator -->
    <div class="mb-4 flex items-center justify-between">
        <!-- Left: Current Date Context -->
        <div>
            <p class="text-lg font-bold text-slate-700">
                <span x-text="headerInfo.weekLabel || 'Loading...'"></span>
                <span x-text="headerInfo.payPeriodLabel" class="font-normal text-slate-500"></span>
            </p>
        </div>

        <!-- Right: Date Navigator -->
        <div class="flex items-center rounded-md border border-slate-300 shadow-sm">
            <button @click="navigateWeek(-1)" title="Previous Week" class="rounded-l-md border-r border-slate-300 p-2 text-slate-500 hover:bg-slate-100">
                <x-general.icon name="leftChevron" class="w-5 h-5 text-slate-500" />
            </button>
            
            <div class="relative">
                <button @click="showWeekSelector = !showWeekSelector" class="flex w-40 items-center justify-between bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <span x-text="selectedWeek ? selectedWeek.weekLabel : 'Select Week'"></span>
                    <x-general.icon name="downChevron" class="w-5 h-5 text-slate-500" />
                </button>
                
                <div x-show="showWeekSelector" 
                     @click.away="showWeekSelector = false" 
                     class="absolute z-10 mt-1 w-full rounded-md bg-white shadow-lg border border-slate-300 h-96 overflow-y-auto">
                    
                    <!-- Loop through each Pay Period -->
                    <template x-for="(period, periodIndex) in payPeriodNavData" :key="periodIndex">
                        <!-- This div wraps the two weeks of a pay period -->
                        <div class="py-1" 
                            :class="{ 'border-b border-slate-200': periodIndex < payPeriodNavData.length - 1 }">
                            <!-- Loop through the weeks within the period -->
                            <template x-for="(week, weekIndex) in period.weeks" :key="weekIndex">
                                <a href="#" @click.prevent="selectWeek(periodIndex, weekIndex)" x-bind:class="{ 'bg-blue-100': isWeekSelected(week) }" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" x-text="week.weekLabel"></a>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <button @click="navigateWeek(1)" title="Next Week" class="rounded-r-md border-l border-slate-300 p-2 text-slate-500 hover:bg-slate-100">
                <x-general.icon name="rightChevron" class="w-5 h-5 text-slate-500" />
            </button>
        </div>
    </div>

    <!-- Timesheet Table Wrapper -->
    <div class="overflow-x-auto rounded-lg border border-slate-200 flex-grow">
        <table class="min-w-full border-collapse text-sm">
            <thead class="bg-slate-700 text-white sticky top-0">
                <tr class="divide-x divide-slate-600">
                    <th class="w-10 p-2 text-center"><!-- Pin --></th>
                    <th class="w-10 p-2 text-center"><!-- Delete --></th>
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
                        <td class="p-0 text-center align-middle"><!-- Pin Button --></td>
                        <td class="p-0 text-center align-middle">
                            <button @click="removeRow(rowIndex)" title="Remove Row" class="p-2 text-slate-400 hover:text-red-600">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor"><path d="M448,85.333H381.867A106.859,106.859,0,0,0,277.333,0H234.667A106.859,106.859,0,0,0,130.133,85.333H64A21.333,21.333,0,0,0,64,128H85.333V405.333a106.795,106.795,0,0,0,106.667,106.667h128a106.795,106.795,0,0,0,106.667-106.667V128h21.333a21.333,21.333,0,0,0,0-42.667ZM234.667,42.667h42.667a64.128,64.128,0,0,1,60.352,42.667H174.315A64.128,64.128,0,0,1,234.667,42.667ZM384,405.333a64,64,0,0,1-64,64H192a64,64,0,0,1-64-64V128H384Z M213.333,384a21.333,21.333,0,0,0,21.333-21.333V234.667a21.333,21.333,0,0,0-42.667,0v128A21.333,21.333,0,0,0,213.333,384Z M298.667,384a21.333,21.333,0,0,0,21.333-21.333V234.667a21.333,21.333,0,0,0-42.667,0v128A21.333,21.333,0,0,0,298.667,384Z"/></svg>
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
            <!-- Table Footer -->
            <tfoot class="bg-slate-100 sticky bottom-0">
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
<div class="mt-6 flex items-center justify-between border-t border-slate-200 pt-4">
    <div class="flex items-center gap-2">
        <button @click="addNewRow()" class="flex items-center gap-2 whitespace-nowrap rounded-md bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300">
            <span>Add New Row</span>
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor"><path d="m256 0a256 256 0 1 0 256 256a256.28 256.28 0 0 0 -256-256zm0 469.33a213.33 213.33 0 1 1 213.33-213.33a213.57 213.57 0 0 1 -213.33 213.33zm106.67-213.33a21.33 21.33 0 0 1 -21.33 21.33h-64v64a21.33 21.33 0 0 1 -42.67 0v-64h-64a21.33 21.33 0 0 1 0-42.67h64v-64a21.33 21.33 0 0 1 42.67 0v64h64a21.33 21.33 0 0 1 21.33 21.33z"/></svg>
        </button>
        
        <!-- Actions Dropdown -->
        <div x-data="{ showActionsMenu: false }" class="relative">
            <button @click="showActionsMenu = !showActionsMenu" class="flex items-center gap-2 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <span>More</span>
                <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
            
            <div x-show="showActionsMenu" 
                 @click.away="showActionsMenu = false"
                 x-transition
                 class="absolute bottom-full z-10 mb-2 w-max min-w-full rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                 style="display: none;">
                
                <button @click="dispatchChangeEvent(true); showActionsMenu = false;" class="block w-full whitespace-nowrap px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
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

  

@push('scripts')
<script>
    function timeTimesheetEntryLogic(dateNavigatorData) {
        return {
            // Data properties
            payPeriodNavData:null,
            headerInfo: null,
            dateHeaders: null,
            timesheetRows: null,
            pristineTimesheetRows: null, // For checking unsaved changes
            footerTotals: null,
            payPeriodTotal: null,

            // State management
            isLoading: null,
            error: null,
            selectedPayPeriodIndex: null,
            selectedWeekIndex: null,
            showWeekSelector: null,
            hasUnsavedChanges: null,

            init() {
                // Initialize data properties
                this.payPeriodNavData = dateNavigatorData;
                this.headerInfo = { weekLabel: '', payPeriodLabel: '' };
                this.dateHeaders = Array(7).fill({ day: '', date: '', isWeekend: false });
                this.timesheetRows = [];
                this.pristineTimesheetRows = [];
                this.footerTotals = { dailyTotals: Array(7).fill(0), weeklyTotal: 0 };
                this.payPeriodTotal = 0;
                
                // Initialize state
                this.isLoading = true;
                this.error = null;
                this.showWeekSelector = false;
                this.hasUnsavedChanges = false;
                
                this.setDefaultWeek();

                // Watch for changes to the timesheet data to flag unsaved changes
                this.$watch('timesheetRows', (newValue) => {
                    if (JSON.stringify(newValue) !== JSON.stringify(this.pristineTimesheetRows)) {
                        this.hasUnsavedChanges = true;
                    }
                }, { deep: true });

                window.addEventListener('beforeunload', (event) => {
                    if (this.hasUnsavedChanges) {
                        event.preventDefault();
                        event.returnValue = ''; // Required for legacy browsers
                    }
                });
                
                // Fire initial dispatch event with default week
                this.$nextTick(() => {
                    this.dispatchChangeEvent();
                });
            },

            setDefaultWeek() {
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                for (let i = 0; i < this.payPeriodNavData.length; i++) {
                    for (let j = 0; j < this.payPeriodNavData[i].weeks.length; j++) {
                        const week = this.payPeriodNavData[i].weeks[j];
                        const start = new Date(week.startDate + 'T00:00:00');
                        const end = new Date(week.endDate + 'T00:00:00');
                        if (today >= start && today <= end) {
                            this.selectedPayPeriodIndex = i;
                            console.log(i);
                            this.selectedWeekIndex = j;
                            console.log(j);
                            return;
                        }
                    }
                }
                // Fallback to the middle-most pay period if today is out of range
                this.selectedPayPeriodIndex = Math.floor(this.payPeriodNavData.length / 2);
                this.selectedWeekIndex = 0;
            },

            get selectedWeek() {
                if (this.selectedPayPeriodIndex !== null && this.selectedWeekIndex !== null) {
                    return this.payPeriodNavData[this.selectedPayPeriodIndex].weeks[this.selectedWeekIndex];
                }
                return null;
            },

            // Navigation and Selection
            navigateWeek(direction) {
                if (this.hasUnsavedChanges) {
                    if (!confirm('You have unsaved changes. Are you sure you want to discard them?')) {
                        return;
                    }
                }

                let currentWeek = this.selectedWeekIndex;
                let currentPeriod = this.selectedPayPeriodIndex;

                currentWeek += direction;

                if (currentWeek < 0) {
                    currentPeriod--;
                    currentWeek = 1; // Go to the second week of the previous period
                } else if (currentWeek > 1) {
                    currentPeriod++;
                    currentWeek = 0; // Go to the first week of the next period
                }

                if (this.payPeriodNavData[currentPeriod] && this.payPeriodNavData[currentPeriod].weeks[currentWeek]) {
                    this.selectedPayPeriodIndex = currentPeriod;
                    this.selectedWeekIndex = currentWeek;
                    this.dispatchChangeEvent();
                }
            },

            selectWeek(periodIndex, weekIndex) {
                 if (this.hasUnsavedChanges) {
                    if (!confirm('You have unsaved changes. Are you sure you want to discard them?')) {
                        this.showWeekSelector = false;
                        return;
                    }
                }
                this.selectedPayPeriodIndex = periodIndex;
                this.selectedWeekIndex = weekIndex;
                this.showWeekSelector = false;
                this.dispatchChangeEvent();
            },

            isWeekSelected(week) {
                return this.selectedWeek && this.selectedWeek.startDate === week.startDate;
            },

            // Data calculation methods
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
                    // Round to nearest 0.25
                    this.timesheetRows[rowIndex].hours[hourIndex].value = (Math.round(numericValue * 4) / 4).toFixed(2);
                }
                this.recalculateAllTotals();
            },

            // Row management
            addNewRow() {
                this.timesheetRows.push({
                    rowId: `new_${Date.now()}`,
                    project_code: '',
                    sub_project: '',
                    activity_code: '',
                    is_pinned: false,
                    hours: Array(7).fill({ value: 0, isWeekend: false }), // This needs to be smarter
                    rowTotal: 0,
                });
            },

            removeRow(rowIndex) {
                this.timesheetRows.splice(rowIndex, 1);
                this.recalculateAllTotals();
            },

            loadFromLastWeek() {
                // This is a placeholder. You'll need to implement the logic to fetch
                // and populate the timesheet with data from the previous week.
                alert('"Load from last week" functionality is not yet implemented.');
            },

            // Event Handlers & Dispatcher
            dispatchChangeEvent(isRevert = false) {
                 if (this.hasUnsavedChanges && !isRevert) {
                    if (!confirm('You have unsaved changes. Are you sure you want to discard them?')) {
                        return;
                    }
                }
                if (!this.selectedWeek) return;
                
                this.$dispatch('timesheet-date-change', {
                    startDate: this.selectedWeek.startDate,
                    endDate: this.selectedWeek.endDate
                });
            },

            handleDataLoading() {
                this.isLoading = true;
                this.error = null;
                this.headerInfo = { weekLabel: '', payPeriodLabel: '' };
            },

           handleDataUpdate(response) {
            // Access the data nested inside the 'timesheetData' key
            const data = response.timesheetData; 

            this.headerInfo = data.headerInfo;
            this.dateHeaders = data.dateHeaders;
            this.timesheetRows = data.timesheetRows;
            this.footerTotals = data.footerTotals;
            this.payPeriodTotal = data.payPeriodTotal;
            
            // Create a deep copy for pristine state
            this.pristineTimesheetRows = JSON.parse(JSON.stringify(data.timesheetRows));
            
            this.hasUnsavedChanges = false;
            this.isLoading = false;
        },
            handleInputError(detail) {
                this.error = detail.message;
                this.isLoading = false;
                // Here you would typically trigger a modal
                alert(`Error: ${this.error}`);
            }
        }
    }
</script>
@endpush