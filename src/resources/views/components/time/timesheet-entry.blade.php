<div x-data='timeTimesheetEntryLogic()'
     @timesheet-data-loading.window="handleDataLoading()"
     @timesheet-data-updated.window="handleDataUpdate($event)"
     @timesheet-fetch-error.window="handleFetchError($event)"
     @timesheet-recent-loaded.window="handleRecentLoaded($event)"
     class="flex flex-col h-full w-full rounded-lg border border-slate-300 bg-white p-6 font-sans shadow-sm">
    
    <!-- Component Header -->
    <div class="mb-4 flex items-center justify-between">
        <div>
            <p class="text-lg font-bold text-slate-700">
                <span x-text="headerInfo.weekNum || 'Loading...'"></span>
                <span x-text="headerInfo.payPeriodLabel" class="font-normal text-slate-500"></span>
            </p>
        </div>
        <div>
            {{ $slot }}  <!-- date-navigator (User Input Component) is injected here -->
        </div>
    </div>

    <!-- Timesheet Table Wrapper -->
    <div class="overflow-x-auto rounded-lg border border-slate-200 flex-grow">
        <table class="min-w-full border-collapse text-sm">
            <thead class="bg-slate-700 text-white sticky top-0 z-10">
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
                    <template x-for="(header, index) in dateHeaders" x-bind:key="index">
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
                    <template x-for="i in 5" x-bind:key="i">
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
                <template x-for="(row, rowIndex) in timesheetRows" x-bind:key="row.rowId">
                    <tr class="divide-x divide-slate-200">
                        <td class="p-0 text-center align-middle">
                            <button @click="togglePin(rowIndex)"
                                    x-bind:title="row.is_pinned ? 'Un-Pin Row' : 'Pin Row'"
                                    class="p-2 rounded-lg transition-colors"
                                    x-bind:class="{
                                        'text-slate-700 hover:text-red-600': row.is_pinned,
                                        'text-slate-300 hover:text-slate-700': !row.is_pinned
                                    }">
                                <x-general.icon name="thumbPin" class="w-4 h-4" />
                            </button>
                        </td>
                        <td class="p-0 text-center align-middle">
                            <button @click="removeRow(rowIndex)" title="Remove Row" class="p-2 text-slate-400 hover:text-red-600">
                                <x-general.icon name="trash" class="w-4 h-4" />
                            </button>
                        </td>
                       <td class="p-0 align-middle">
                            <x-time.double-search-dropdown cellType="'project_code'" placeholder="Project Code" accessor="row.project_code"/>
                        </td>
                        <td class="p-0 align-middle">
                            <x-time.dropdown cellType="'sub_project'" placeholder="Sub-Code"  accessor="row.sub_project"/>
                        </td>
                        <td class="p-0 align-middle">
                            <x-time.dropdown cellType="'activity_code'" placeholder="Activity Code" accessor="row.activity_code"/>
                        </td>
                         <template x-for="(hour, hourIndex) in row.hours" x-bind:key="hourIndex">
                            <td class="p-0 align-middle hover:cursor-cell" x-bind:class="{ 'bg-slate-50/75': hour.isWeekend }">
                                <input type="text"
                                    x-model.lazy="hour.value"
                                    x-bind:id="`cell-${rowIndex}-${hourIndex}`"
                                    @keydown="handleHourKeydown($event, rowIndex, hourIndex)"
                                    @change="validateHourInput(rowIndex, hourIndex)"
                                    @click="onCellFocus($event)"
                                    @dblclick.prevent="onCellEdit($event)"
                                    @blur="onCellBlur($event)"
                                    class="h-full w-full border-0 bg-transparent px-1 py-2 text-center hover:cursor-cell"
                                    x-bind:class="{
                                        'text-slate-950': hour.value != 0,
                                        'text-slate-400': hour.value == 0
                                    }"/>
                            </td>
                       </template>  
                        <td class="p-0 align-middle"><div class="bg-slate-100 px-3 py-2 text-right font-semibold text-slate-800" x-text="calculateRowTotal(rowIndex)"></div></td>
                    </tr>
                </template>
            </template>
            </tbody>
            <tfoot class="bg-slate-100 sticky bottom-0 z-10">
                <tr class="divide-x divide-slate-200">
                    <th colspan="5" scope="row" class="px-3 py-2 text-right text-sm font-bold text-slate-600">Daily Totals</th>
                    <template x-for="total in footerTotals.dailyTotals">
                        <td class="p-2 text-center font-semibold text-slate-800" x-text="total.toFixed(1)"></td>
                    </template>
                    <th scope="row" class="px-3 py-2 text-right font-bold text-slate-800" x-text="footerTotals.weeklyTotal.toFixed(1)"></th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- Component Footer with Actions -->
    <div class="mt-6 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button @click="addNewRow()" class="flex items-center gap-2 whitespace-nowrap rounded-md bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300">
                <span>Add New Row</span>
                <x-general.icon name="add" class="w-5 h-5" />
            </button>
            <div x-data="{ showActionsMenu: false }" class="relative">
                <button @click="showActionsMenu = !showActionsMenu" class="flex items-center gap-2 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <span>More</span>
                    <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="showActionsMenu" @click.away="showActionsMenu = false" x-transition class="absolute bottom-full z-100 mb-2 w-max min-w-full rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5" style="display: none;">
                    <button @click="revertChanges(); showActionsMenu = false;" class="block w-full whitespace-nowrap px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">Revert Changes</button>
                    <button @click="loadRecentRows(1); showActionsMenu = false;" 
                            class="block w-full whitespace-nowrap px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                        Load from Last Week
                    </button>
                    <button @click="loadRecentRows(2); showActionsMenu = false;" 
                            class="block w-full whitespace-nowrap px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                        Load from Last 2 Weeks
                    </button>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <div class="text-right">
                <span class="text-sm font-medium text-slate-600">Pay Period Hours:</span>
                <span class="block text-lg font-semibold text-slate-800" x-text="payPeriodTotal.toFixed(1)"></span>
            </div>
            <button @click="saveTimesheet()" class="rounded-md bg-slate-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Save Timesheet</button>
        </div>
    </div>
</div>
  
    
@push('scripts')
<script>
    function timeTimesheetEntryLogic() {
        return {
            // 1. Class/Instance Variables set to null
            headerInfo: null,
            dateHeaders: null,
            timesheetRows: null,
            pristineTimesheetRows: null,
            footerTotals: null,
            payPeriodTotal: null,
            persistedPayPeriodHours: null,
            initialWeeklyTotal: null,
            dropdownData: null,
            isLoading: null,
            error: null,
            hasUnsavedChanges: null,
            isEditingCell: null,
            currentStartDate: null, 

            // 2. Init Function
            init() {
                // 2a. Initialize class/instance variables
                this.headerInfo = { weekLabel: '', payPeriodLabel: '' };
                this.dateHeaders = Array(7).fill({ day: '', date: '', isWeekend: false });
                this.timesheetRows = [];
                this.pristineTimesheetRows = [];
                this.footerTotals = { dailyTotals: Array(7).fill(0), weeklyTotal: 0 };
                this.payPeriodTotal = 0;
                this.persistedPayPeriodHours = 0;
                this.initialWeeklyTotal = 0;
                this.dropdownData = {};
                this.currentStartDate = null;
                
                // Display components SHOULD set isLoading to true to handle initial page load
                this.isLoading = true; 
                this.error = null;
                this.isEditingCell = false;

                // 2b. Define Watchers
                this.$watch('timesheetRows', (newRows, oldRows) => {
                    if (this.isLoading || !oldRows || newRows.length !== oldRows.length) return;

                    newRows.forEach((newRow, index) => {
                        const oldRow = oldRows[index];
                        if (newRow.project_code !== oldRow.project_code) {
                            this.timesheetRows[index].sub_project = '';
                            this.timesheetRows[index].activity_code = '';
                        }
                        if (newRow.sub_project !== oldRow.sub_project) {
                            this.timesheetRows[index].activity_code = '';
                        }
                    });

                }, { deep: true });

                // 2c. Define callbacks to local alpine.store
                this.$store.timesheetPageRegistry.registerDirtyCheck(() => this.hasUnsavedChanges());

            },

            // 3. Other Functions (Calculation & Interaction)

            hasUnsavedChanges() {
                if (this.isLoading) return false;
                if (!this.pristineTimesheetRows || !this.timesheetRows) return false;
                
                return JSON.stringify(this.timesheetRows) !== JSON.stringify(this.pristineTimesheetRows);
            }, 

            calculateRowTotal(rowIndex) {
                let total = this.timesheetRows[rowIndex].hours.reduce((sum, hour) => sum + parseFloat(hour.value || 0), 0);
                this.timesheetRows[rowIndex].rowTotal = total;
                return total.toFixed(1);
            },
            
            recalculateAllTotals() {
                const newDailyTotals = Array(7).fill(0);
                if (!this.timesheetRows) return;

                this.timesheetRows.forEach(row => {
                    row.hours.forEach((hour, index) => {
                        newDailyTotals[index] += parseFloat(hour.value || 0);
                    });
                });

                this.footerTotals.dailyTotals = newDailyTotals;
                const newWeeklyTotal = newDailyTotals.reduce((sum, total) => sum + total, 0);
                this.footerTotals.weeklyTotal = newWeeklyTotal;

                this.payPeriodTotal = (this.persistedPayPeriodHours - this.initialWeeklyTotal) + newWeeklyTotal;
            },

            validateHourInput(rowIndex, hourIndex) {
                let input = this.timesheetRows[rowIndex].hours[hourIndex].value;
                let numericValue = parseFloat(input);

                if (isNaN(numericValue) || numericValue < 0) {
                    this.timesheetRows[rowIndex].hours[hourIndex].value = 0;
                } else {
                    this.timesheetRows[rowIndex].hours[hourIndex].value = (Math.round(numericValue * 4) / 4);
                }
                this.recalculateAllTotals();
            },

            getCellValue(rowIndex, columnIndex) {
                if (columnIndex === 3) {
                    return this.timesheetRows[rowIndex]["sub_project"];
                }
            },

            addNewRow() {
                const newHours = this.dateHeaders.map(header => ({ value: 0, isWeekend: header.isWeekend }));
                this.timesheetRows.push({
                    rowId: this.generateRowId('new'),
                    project_code: '',
                    sub_project: '',
                    activity_code: '',
                    is_pinned: false,
                    hours: newHours,
                    rowTotal: 0,
                });
            },

            removeRow(rowIndex) {
                if (this.timesheetRows[rowIndex].is_pinned) {
                    alert("You cannot delete a pinned row.");
                    return;
                }
                this.timesheetRows.splice(rowIndex, 1);
                this.recalculateAllTotals();
            },

            deleteAll() {
                if (!confirm("Are you sure you want to delete all un-pinned rows?")) return;

                const originalLength = this.timesheetRows.length;
                this.timesheetRows = this.timesheetRows.filter(row => row.is_pinned);
                
                this.recalculateAllTotals();
            },

            togglePin(rowIndex) {
                this.timesheetRows[rowIndex].is_pinned = !this.timesheetRows[rowIndex].is_pinned;
            },
            pinAll() {
                if (this.timesheetRows.length === 0) return;
                
                const allPinned = this.timesheetRows.every(row => row.is_pinned);

                this.timesheetRows.forEach(row => {
                    row.is_pinned = !allPinned;
                });
            },

            revertChanges() {
                if (confirm('Are you sure you want to discard all changes?')) {
                    this.timesheetRows = JSON.parse(JSON.stringify(this.pristineTimesheetRows));
                    this.recalculateAllTotals();
                }
            },

            loadRecentRows(weeksBack) {
                if (!this.currentStartDate) {
                    this.handleError("Reference date missing. Please reload.");
                    return;
                }
                
                this.$dispatch('timesheet-load-recent', {
                    referenceDate: this.currentStartDate,
                    weeksBack: weeksBack
                });
            },

            handleRecentLoaded(event) {
                const responseObj = event.detail.recentRows;

                if (!responseObj) return; // Should handle error
                if (responseObj.errors) {
                    this.handleError(responseObj.errors);
                    return;
                }

                const newRowsData = responseObj.data;
                let addedCount = 0;

                // Create a Set of existing keys to prevent duplicates
                // Key format: "Project|Sub|Activity"
                const existingKeys = new Set(this.timesheetRows.map(row => 
                    `${row.project_code}|${row.sub_project}|${row.activity_code}`
                ));

                // Generate empty hour structure for new rows
                const emptyHours = this.dateHeaders.map(header => ({ value: 0, isWeekend: header.isWeekend }));

                newRowsData.forEach(item => {
                    const key = `${item.project_code}|${item.sub_project}|${item.activity_code}`;
                    
                    if (!existingKeys.has(key)) {
                        this.timesheetRows.push({
                            rowId: this.generateRowId('loaded'),
                            project_code: item.project_code,
                            sub_project: item.sub_project,
                            activity_code: item.activity_code,
                            is_pinned: false, // Default to unpinned
                            hours: JSON.parse(JSON.stringify(emptyHours)), // Deep copy
                            rowTotal: 0
                        });
                        addedCount++;
                    }
                });

                // Force UI update and recalculate
                this.isLoading = false; // Turn off loading if data-bridge turned it on
                
                if (addedCount > 0) {
                    this.recalculateAllTotals();
                    // Optional: Show a toast/notification saying "X rows added"
                } else {
                    // Optional: Show toast "No unique rows found from previous week"
                }
            },


            onCellFocus(event) {
                if (!this.isEditingCell) {
                    event.target.select();
                }
            },

            onCellEdit(event) {
                const input = event.target;
                const x = event.clientX;
                const y = event.clientY;

                let caretPosition;
                if (document.caretPositionFromPoint) {
                    caretPosition = document.caretPositionFromPoint(x, y);
                } else {
                    return;
                }

                if (caretPosition) {
                    const offset = caretPosition.offset;
                    input.setSelectionRange(offset, offset);
                }

                input.style.cursor = 'text';
                this.isEditingCell = true;
            },

            onCellBlur(event) {
                const input = event.target;
                input.style.cursor = '';
                this.isEditingCell = false;
            },

            handleHourKeydown(event, rowIndex, hourIndex) {
                let nextRowIndex = rowIndex;
                let nextHourIndex = hourIndex;

                switch (event.key) {
                    case 'ArrowUp':
                        event.preventDefault();
                        nextRowIndex = rowIndex > 0 ? rowIndex - 1 : this.timesheetRows.length - 1;
                        break;
                    case 'ArrowDown':
                        event.preventDefault();
                        nextRowIndex = rowIndex < this.timesheetRows.length - 1 ? rowIndex + 1 : 0;
                        break;
                    case 'ArrowLeft':
                        event.preventDefault();
                        nextHourIndex = hourIndex > 0 ? hourIndex - 1 : this.dateHeaders.length - 1;
                        break;
                    case 'ArrowRight':
                        event.preventDefault();
                        nextHourIndex = hourIndex < this.dateHeaders.length - 1 ? hourIndex + 1 : 0;
                        break;
                    case 'Enter':
                        event.preventDefault();
                        if (hourIndex < this.dateHeaders.length - 1) {
                            nextHourIndex = hourIndex + 1;
                        } else if (rowIndex < this.timesheetRows.length - 1) {
                            nextHourIndex = 0;
                            nextRowIndex = rowIndex + 1;
                        }
                        break;
                    default:
                        return;
                }
                
                const nextCellId = `cell-${nextRowIndex}-${nextHourIndex}`;
                this.$nextTick(() => {
                    const nextCell = document.getElementById(nextCellId);
                    if (nextCell) {
                        nextCell.focus();
                        nextCell.select();
                    }
                });
            },
            generateRowId(prefix) {
                return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
            },

            dispatchSaveEvent() {
                this.$dispatch('save-timesheet', {
                  timesheetRows: this.timesheetRows,
                  headerInfo: this.headerInfo
                });
            },

            // 4. Data Handling Functions (Loading, Update, Error)
            
            handleDataLoading() {
                this.isLoading = true;
                this.error = null;
                // Reset visual state for skeleton
                this.headerInfo = { weekLabel: 'Loading...', payPeriodLabel: '' };
                this.timesheetRows = [];
                this.payPeriodTotal = 0;
            },

            handleDataUpdate(event) {
                // 1. Extract specific payload using Controller Key (timesheetData)
                const responseObj = event.detail.timesheetData;

                // Safety check
                if (!responseObj) {
                    this.handleError("Invalid response format: timesheetData missing");
                    return;
                }

                // 2. Check for Component-Specific Error
                if (responseObj.errors) {
                    this.handleError(responseObj.errors);
                    return;
                }

                // 3. Process Success Data
                const data = responseObj.data;
                
                this.headerInfo = data.headerInfo;
                this.dateHeaders = data.dateHeaders;
                this.timesheetRows = data.timesheetRows;
                this.dropdownData = data.dropdownData;
                this.currentStartDate = data.headerInfo.currentStartDate; 
                
                this.recalculateAllTotals();
                this.initialWeeklyTotal = this.footerTotals.weeklyTotal;
                this.persistedPayPeriodHours = data.payPeriodTotal;
                this.payPeriodTotal = data.payPeriodTotal;

                this.pristineTimesheetRows = JSON.parse(JSON.stringify(data.timesheetRows));
                
                this.isLoading = false;
                this.error = null;
            },

            handleFetchError(event) {
                const errorMessage = event.detail;
                this.handleError(errorMessage);
            },

            handleError(errorMessage) {
                this.error = errorMessage;
                this.isLoading = false;
                // Dispatch error modal instead of showing local error state
                this.$dispatch('error-modal', { message: this.error });
            }
        }
    }
</script>
@endpush

  