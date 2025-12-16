<div x-data='timeTimesheetEntryLogic()'
     @timesheet-data-loading.window="handleDataLoading()"
     @timesheet-data-updated.window="handleDataUpdate($event.detail)"
     @timesheet-data-error.window="handleDataError($event.detail)"
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
                            <div x-data="dropdownComponent('projectCode', rowIndex, 2)" class="relative">
                                <input 
                                    x-model="search"
                                    @focus="openDropdown()"
                                    @click.away="closeDropdown()"
                                    type="text"
                                    placeholder="Select..."
                                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >

                                <div
                                    x-show="isOpen" 
                                    x-transition 
                                    class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg"
                                >
                                    <template x-for="option in filteredOptions()" :key="option">
                                        <div 
                                            @click="selectOption(option)" 
                                            class="px-4 py-2 cursor-pointer hover:bg-gray-100" 
                                            x-text="option"
                                        ></div>
                                    </template>
                                </div>
                            </div>
                        </td>
                        <td class="p-0 align-middle">
                            <div x-data="dropdownComponent('subCode', rowIndex, 3)" class="relative">
                                <input 
                                    x-model="search"
                                    @focus="openDropdown()"
                                    @click.away="closeDropdown()"
                                    type="text"
                                    placeholder="Select..."
                                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >

                                <div
                                    x-show="isOpen" 
                                    x-transition 
                                    class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg"
                                >
                                    <template x-for="option in filteredOptions()" :key="option">
                                        <div 
                                            @click="selectOption(option)" 
                                            class="px-4 py-2 cursor-pointer hover:bg-gray-100" 
                                            x-text="option"
                                        ></div>
                                    </template>
                                </div>
                            </div>
                        </td>
                        <td class="p-0 align-middle">
                            <div x-data="dropdownComponent('projectCode', rowIndex, 2)" class="relative">
                                <input 
                                    x-model="search"
                                    @focus="openDropdown()"
                                    @click.away="closeDropdown()"
                                    type="text"
                                    placeholder="Select..."
                                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >

                                <div
                                    x-show="isOpen" 
                                    x-transition 
                                    class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg"
                                >
                                    <template x-for="option in filteredOptions()" :key="option">
                                        <div 
                                            @click="selectOption(option)" 
                                            class="px-4 py-2 cursor-pointer hover:bg-gray-100" 
                                            x-text="option"
                                        ></div>
                                    </template>
                                </div>
                            </div>
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
            <tfoot class="bg-slate-100 sticky bottom-0">
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
                <div x-show="showActionsMenu" @click.away="showActionsMenu = false" x-transition class="absolute bottom-full z-10 mb-2 w-max min-w-full rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5" style="display: none;">
                    <button @click="revertChanges(); showActionsMenu = false;" class="block w-full whitespace-nowrap px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">Revert Changes</button>
                    <button @click="loadFromLastWeek(); showActionsMenu = false;" class="block w-full whitespace-nowrap px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">Load from last week</button>
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
  
<script>
    function timeTimesheetEntryLogic() {
        return {
            // Data properties
            headerInfo: null,
            dateHeaders: null,
            timesheetRows: null,
            pristineTimesheetRows: null,
            footerTotals: null,
            payPeriodTotal: null,
            persistedPayPeriodHours: null, 
            initialWeeklyTotal: null,
            dropdownData: null,

            // State management
            isLoading: null,
            error: null,
            hasUnsavedChanges: null,
            isEditingCell: null,



            init() {
                this.headerInfo = { weekLabel: '', payPeriodLabel: '' };
                this.dateHeaders = Array(7).fill({ day: '', date: '', isWeekend: false });
                this.timesheetRows = [];
                this.pristineTimesheetRows = [];
                this.footerTotals = { dailyTotals: Array(7).fill(0), weeklyTotal: 0 };
                this.payPeriodTotal = 0;
                this.persistedPayPeriodHours = 0;
                this.initialWeeklyTotal = 0;
                this.dropdownData = {};
                this.isLoading = true;
                this.error = null;
                this.hasUnsavedChanges = false;
                this.isEditingCell = false;
                
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

                    // Standard unsaved changes check
                     if (!this.isLoading) {
                        this.hasUnsavedChanges = JSON.stringify(this.timesheetRows) !== JSON.stringify(this.pristineTimesheetRows);
                    }
                }, { deep: true });

                window.addEventListener('beforeunload', (event) => {
                    if (this.hasUnsavedChanges) {
                        event.preventDefault();
                        event.returnValue = '';
                    }
                });
            },

            // === DATA CALCULATION METHODS ===
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

                // Update pay period total in real-time based on the delta from its initial state
                this.payPeriodTotal = (this.persistedPayPeriodHours - this.initialWeeklyTotal) + newWeeklyTotal;
            },

            validateHourInput(rowIndex, hourIndex) {
                let input = this.timesheetRows[rowIndex].hours[hourIndex].value;
                let numericValue = parseFloat(input);

                if (isNaN(numericValue) || numericValue < 0) {
                    this.timesheetRows[rowIndex].hours[hourIndex].value = 0;
                } else {
                    // Store as a number, not a formatted string
                    this.timesheetRows[rowIndex].hours[hourIndex].value = (Math.round(numericValue * 4) / 4);
                }
                this.recalculateAllTotals();
            },

            // === ROW MANAGEMENT METHODS ===
            addNewRow() {
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

            togglePin(rowIndex) {
                // Flip the boolean value for the is_pinned property
                this.timesheetRows[rowIndex].is_pinned = !this.timesheetRows[rowIndex].is_pinned;
            },

            revertChanges() {
                if (confirm('Are you sure you want to discard all changes?')) {
                    this.timesheetRows = JSON.parse(JSON.stringify(this.pristineTimesheetRows));
                    this.recalculateAllTotals();
                    this.hasUnsavedChanges = false;
                }
            },

            onCellFocus(event) {
                if (!this.isEditingCell) {
                    event.target.select();
                }
            },
            onCellEdit(event) {
                // The input element that was double-clicked
                const input = event.target;

                // Get the X and Y coordinates of the mouse click
                const x = event.clientX;
                const y = event.clientY;

                // Use the browser's built-in function to find the text position from the coordinates
                let caretPosition;
                if (document.caretPositionFromPoint) {
                    caretPosition = document.caretPositionFromPoint(x, y);
                } else {
                    // Fallback for older browsers (less common now)
                    console.warn('document.caretPositionFromPoint is not supported in this browser.');
                    return;
                }

                // The caretPosition object contains the text node and the character offset
                if (caretPosition) {
                    const offset = caretPosition.offset;
                    
                    // Use setSelectionRange to place the cursor at the calculated offset.
                    // The two arguments are the start and end of the selection.
                    // By making them the same, we place the cursor without a selection.
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
                    default:
                        // Allow other keys to function as normal
                        return;
                }
                
                const nextCellId = `cell-${nextRowIndex}-${nextHourIndex}`;
                console.log(nextCellId);
                this.$nextTick(() => {
                    const nextCell = document.getElementById(nextCellId);
                    if (nextCell) {
                        nextCell.focus();
                        nextCell.select();
                    }
                });
            },

            // === EVENT HANDLERS ===
            handleDataLoading() {
                this.isLoading = true;
                this.error = null;
                this.headerInfo = { weekLabel: 'Loading...', payPeriodLabel: '' };
                this.timesheetRows = [];
                this.payPeriodTotal = 0;
                this.hasUnsavedChanges = false;
            },

            handleDataUpdate(response) {
                const timesheetPayload = response.timesheetData;
                if (!timesheetPayload || !timesheetPayload.success) {
                    this.handleInputError({ message: timesheetPayload.errors || 'Failed to parse timesheet data.' });
                    return;
                }

                const data = timesheetPayload.data;
                this.headerInfo = data.headerInfo;
                this.dateHeaders = data.dateHeaders;
                this.timesheetRows = data.timesheetRows;
                this.dropdownData = data.dropdownData;
                console.log('[Parent Component] Dropdown data received from server:', JSON.parse(JSON.stringify(this.dropdownData)));

                // Perform initial calculation for the newly loaded week
                this.recalculateAllTotals();
                
                // Store the initial weekly total to calculate the delta on user input
                this.initialWeeklyTotal = this.footerTotals.weeklyTotal;
                
               // Store the authoritative pay period total from the DB. This is our baseline.
                this.persistedPayPeriodHours = data.payPeriodTotal;

                // Display this authoritative total on initial load of the new week's data.
                this.payPeriodTotal = data.payPeriodTotal;

                this.pristineTimesheetRows = JSON.parse(JSON.stringify(data.timesheetRows));
                this.hasUnsavedChanges = false;
                this.isLoading = false;
            },

            handleDataError(detail) {
                this.error = detail.message;
                this.isLoading = false;
                alert(`Error fetching timesheet data: ${this.error}`);
            }
        }
    }
</script>

<script>
    function dropdownComponent(cellType, rowIndex, columnIndex) {
        console.log(`cellType: ${cellType} (type: ${typeof cellType})`);
        console.log(`rowIndex: ${rowIndex} (type: ${typeof rowIndex})`);
        console.log(`columnIndex: ${columnIndex} (type: ${typeof columnIndex})`);
        return {
            type: cellType,
            isOpen: false,
            search: '',
            options: [],

            init() {
            },

            openDropdown() {
                this.isOpen = true;
            },

            closeDropdown() {
                this.options = [];
                this.isOpen = false;
            },

            selectOption(option) {
                this.search = option;
                this.isOpen = false;
            },
            filteredOptions() {
                if (!this.search) {
                    return this.options;
                }
                return this.options.filter(
                    option => option.toLowerCase().includes(this.search.toLowerCase())
                );
            }
        }
    }
</script>