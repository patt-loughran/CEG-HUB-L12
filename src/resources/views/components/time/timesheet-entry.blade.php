<div x-data='timeTimesheetEntryLogic()'
     @timesheet-data-loading.window="handleDataLoading()"
     @timesheet-data-updated.window="handleDataUpdate($event)"
     @timesheet-fetch-error.window="handleFetchError($event)"
     @timesheet-recent-loaded.window="handleRecentLoaded($event)"
     @timesheet-data-saved.window="handleSaveSuccess()"
     @timesheet-save-error.window="handleSaveError($event)"
     class="flex flex-col h-full w-full rounded-lg border border-slate-300 bg-white p-6 font-sans shadow-sm">
    
    <!-- ============================================
     NOTIFICATION BANNER
     ============================================ -->
    <div x-show="bannerNotification.show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-full"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-full"
        class="fixed top-0 left-0 right-0 z-50 bg-green-600 text-white px-4 py-3 shadow-lg text-center"
        style="display: none;">
        <span x-text="bannerNotification.message" class="font-medium"></span>
    </div>
    <!-- ============================================
         HEADER SECTION
         ============================================ -->
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

    <!-- ============================================
         TABLE SECTION
         ============================================ -->
    <div class="overflow-x-auto rounded-lg border border-slate-200 flex-grow">
        <table class="min-w-full border-collapse text-sm">
            <!-- ----------------------------------------
                 Table Header
                 ---------------------------------------- -->
            <thead class="bg-slate-700 text-white sticky top-0 z-10">
                <tr class="divide-x divide-slate-600">
                    <th class="w-10 p-2 text-center">
                        <button @click="pinAll()" x-bind:disabled="isSaving" class="p-2 rounded-lg hover:bg-slate-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <x-general.icon name="thumbPin" class="w-4 h-4 text-white" />
                        </button>
                    </th>
                    <th class="w-10 p-2 text-center">
                        <button @click="deleteAll()" x-bind:disabled="isSaving" class="p-2 rounded-lg hover:bg-slate-500 disabled:opacity-50 disabled:cursor-not-allowed">
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

            <!-- ----------------------------------------
                 Table Body
                 ---------------------------------------- -->
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
                            <!-- Pin Button -->
                            <td class="p-0 text-center align-middle">
                                <button @click="togglePin(rowIndex)"
                                        x-bind:disabled="isSaving"
                                        x-bind:title="row.is_pinned ? 'Un-Pin Row' : 'Pin Row'"
                                        class="p-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        x-bind:class="row.is_pinned ? 'text-slate-700 hover:text-red-600' : 'text-slate-300 hover:text-slate-700'">
                                    <x-general.icon name="thumbPin" class="w-4 h-4" />
                                </button>
                            </td>

                            <!-- Delete Button -->
                            <td class="p-0 text-center align-middle">
                                <button @click="removeRow(rowIndex)" x-bind:disabled="isSaving" title="Remove Row" class="p-2 text-slate-400 hover:text-red-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <x-general.icon name="trash" class="w-4 h-4" />
                                </button>
                            </td>

                            <!-- Project Code Dropdown Cell -->
                            <td class="p-0 align-middle">
                                <input
                                    type="text"
                                    x-model="row.project_code"
                                    x-bind:disabled="isSaving"
                                    x-bind:id="`dropdown-input-${rowIndex}-project_code`"
                                    @focus="openDropdown(rowIndex, 'project_code', $event)"
                                    @keydown.escape.prevent="closeDropdown()"
                                    @keydown.arrow-down.prevent="navigateDropdownOptions(1)"
                                    @keydown.arrow-up.prevent="navigateDropdownOptions(-1)"
                                    @keydown.enter.prevent="selectHighlightedOption()"
                                    placeholder="Project Code"
                                    autocomplete="off"
                                    class="w-full px-4 py-2 text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:cursor-not-allowed disabled:text-slate-400"
                                >
                            </td>

                            <!-- Sub-Code Dropdown Cell -->
                            <td class="p-0 align-middle">
                                <input
                                    type="text"
                                    x-model="row.sub_project"
                                    x-bind:disabled="isSaving"
                                    x-bind:id="`dropdown-input-${rowIndex}-sub_project`"
                                    @focus="openDropdown(rowIndex, 'sub_project', $event)"
                                    @keydown.escape.prevent="closeDropdown()"
                                    @keydown.arrow-down.prevent="navigateDropdownOptions(1)"
                                    @keydown.arrow-up.prevent="navigateDropdownOptions(-1)"
                                    @keydown.enter.prevent="selectHighlightedOption()"
                                    placeholder="Sub-Code"
                                    autocomplete="off"
                                    class="w-full px-4 py-2 text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:cursor-not-allowed disabled:text-slate-400"
                                >
                            </td>

                            <!-- Activity Code Dropdown Cell -->
                            <td class="p-0 align-middle">
                                <input
                                    type="text"
                                    x-model="row.activity_code"
                                    x-bind:disabled="isSaving"
                                    x-bind:id="`dropdown-input-${rowIndex}-activity_code`"
                                    @focus="openDropdown(rowIndex, 'activity_code', $event)"
                                    @keydown.escape.prevent="closeDropdown()"
                                    @keydown.arrow-down.prevent="navigateDropdownOptions(1)"
                                    @keydown.arrow-up.prevent="navigateDropdownOptions(-1)"
                                    @keydown.enter.prevent="selectHighlightedOption()"
                                    placeholder="Activity Code"
                                    autocomplete="off"
                                    class="w-full px-4 py-2 text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:cursor-not-allowed disabled:text-slate-400"
                                >
                            </td>

                            <!-- Hour Cells -->
                            <template x-for="(hour, hourIndex) in row.hours" x-bind:key="hourIndex">
                                <td class="p-0 align-middle hover:cursor-cell" x-bind:class="{ 'bg-slate-50/75': hour.isWeekend }">
                                    <input type="text"
                                        x-bind:value="hour.value"
                                        x-bind:disabled="isSaving"
                                        x-bind:id="`cell-${rowIndex}-${hourIndex}`"
                                        inputmode="decimal"
                                        @keydown="handleHourKeydown($event, rowIndex, hourIndex)"
                                        @change="hour.value = $el.value; validateHourInput(rowIndex, hourIndex)"
                                        @focus="onCellFocus($event)"
                                        @click="onCellClick($event)"
                                        @dblclick.prevent="onCellEdit($event)"
                                        @blur="onCellBlur($event)"
                                        class="h-full w-full border-0 bg-transparent px-1 py-2 text-center hover:cursor-cell disabled:cursor-not-allowed disabled:text-slate-400"
                                        x-bind:class="hour.value != 0 ? 'text-slate-950' : 'text-slate-400'"/>
                                </td>
                            </template>

                            <!-- Row Total -->
                            <td class="p-0 align-middle">
                                <div class="bg-slate-100 px-3 py-2 text-right font-semibold text-slate-800" x-text="(row.rowTotal || 0).toFixed(2)"></div>
                            </td>
                        </tr>
                    </template>
                </template>
            </tbody>

            <!-- ----------------------------------------
                 Table Footer
                 ---------------------------------------- -->
            <tfoot class="bg-slate-100 sticky bottom-0 z-10">
                <tr class="divide-x divide-slate-200">
                    <th colspan="5" scope="row" class="px-3 py-2 text-right text-sm font-bold text-slate-600">Daily Totals</th>
                    <template x-for="total in footerTotals.dailyTotals">
                        <td class="p-2 text-center font-semibold text-slate-800" x-text="total.toFixed(2)"></td>
                    </template>
                    <th scope="row" class="px-3 py-2 text-right font-bold text-slate-800" x-text="footerTotals.weeklyTotal.toFixed(2)"></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- ============================================
         SHARED DROPDOWN PANEL (Teleported to body)
         ============================================ -->
    <template x-teleport="body">
        <div
            x-show="dropdownState.isOpen"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-ref="dropdownPanel"
            @mousedown.away="closeDropdown()"
            class="fixed bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto z-[100]"
            style="display: none;"
        >
            <!-- Double-line display for Project Code -->
            <template x-if="dropdownState.cellType === 'project_code'">
                <template x-for="(option, optionIndex) in getActiveDropdownOptions()" x-bind:key="optionIndex">
                    <div
                        @click="selectDropdownOption(option)"
                        data-dropdown-option
                        @mouseenter="dropdownState.highlightedIndex = optionIndex"
                        class="px-4 py-2 cursor-pointer flex flex-col"
                        x-bind:class="dropdownState.highlightedIndex === optionIndex ? 'bg-blue-100' : 'hover:bg-gray-100'"
                    >
                        <strong class="text-sm font-bold" x-text="option.code"></strong>
                        <span class="text-sm text-gray-500" x-text="option.name"></span>
                    </div>
                </template>
            </template>

            <!-- Single-line display for Sub-Code and Activity Code -->
            <template x-if="dropdownState.cellType !== 'project_code'">
                <template x-for="(option, optionIndex) in getActiveDropdownOptions()" x-bind:key="optionIndex">
                    <div
                        @click="selectDropdownOption(option)"
                        data-dropdown-option
                        @mouseenter="dropdownState.highlightedIndex = optionIndex"
                        class="px-4 py-2 cursor-pointer"
                        x-bind:class="dropdownState.highlightedIndex === optionIndex ? 'bg-blue-100' : 'hover:bg-gray-100'"
                        x-text="option"
                    ></div>
                </template>
            </template>
        </div>
    </template>

    <!-- ============================================
         FOOTER ACTIONS SECTION
         ============================================ -->
    <div class="mt-6 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button @click="addNewRow()" x-bind:disabled="isSaving" class="flex items-center gap-2 whitespace-nowrap rounded-md bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300 disabled:opacity-50 disabled:cursor-not-allowed">
                <span>Add New Row</span>
                <x-general.icon name="add" class="w-5 h-5" />
            </button>
            <div x-data="{ showActionsMenu: false }" class="relative">
                <button @click="showActionsMenu = !showActionsMenu" x-bind:disabled="isSaving" class="flex items-center gap-2 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
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
                <span class="block text-lg font-semibold text-slate-800" x-text="payPeriodTotal.toFixed(2)"></span>
            </div>
            <button @click="saveTimesheet()" x-bind:disabled="isSaving" class="rounded-md bg-slate-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-75 disabled:cursor-not-allowed flex items-center gap-2">
                <!-- Spinner Icon (Only shows when saving) -->
                <svg x-show="isSaving" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
    
                <span x-text="isSaving ? 'Saving...' : 'Save Timesheet'"></span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function timeTimesheetEntryLogic() {
    return {
        // ==========================================
        // 1. STATE - Class/Instance Variables
        // ==========================================
        
        // Header & Structure
        headerInfo: null,
        dateHeaders: null,
        currentStartDate: null,

        // Timesheet Data
        timesheetRows: null,
        pristineTimesheetRows: null,
        dropdownData: null,

        // Totals
        footerTotals: null,
        payPeriodTotal: null,
        persistedPayPeriodHours: null,
        initialWeeklyTotal: null,

        // UI State
        isLoading: null,
        isLoadingRecent: null,
        isSaving: null, 
        error: null,
        isEditingCell: null,
        bannerNotification: null,

        // Dropdown State (single shared dropdown)
        dropdownState: null,
        boundRepositionDropdown: null,

        // Load Recent State
        recentRowsRequestDate: null, // This remembers which week we were on when we made the request

        // ==========================================
        // 2. LIFECYCLE - init()
        // ==========================================
        init() {
            // 2a. Initialize class/instance variables
            this.headerInfo = { weekNum: '', payPeriodLabel: '' };
            this.dateHeaders = Array(7).fill({ day: '', date: '', isWeekend: false });
            this.currentStartDate = null;

            this.timesheetRows = [];
            this.pristineTimesheetRows = [];
            this.dropdownData = {};
            this.recentRowsRequestDate = null;

            this.footerTotals = { dailyTotals: Array(7).fill(0), weeklyTotal: 0 };
            this.payPeriodTotal = 0;
            this.persistedPayPeriodHours = 0;
            this.initialWeeklyTotal = 0;

            // Display components SHOULD set isLoading to true to handle initial page load
            this.isLoading = true;
            this.isLoadingRecent = false;
            this.isSaving = false;
            this.error = null;
            this.isEditingCell = false;
            this.bannerNotification = { show: false, message: '', timeoutId: null };

            // Initialize dropdown state
            this.dropdownState = {
                isOpen: false,
                rowIndex: null,
                cellType: null,      // 'project_code' | 'sub_project' | 'activity_code'
                inputEl: null,
                highlightedIndex: 0,
                positionMode: null
            };

            // Create bound reference for event listeners
            this.boundRepositionDropdown = this.repositionDropdown.bind(this);

            // 2b. Define Watchers

            // Watch for dropdown open/close to manage scroll/resize listeners
            this.$watch('dropdownState.isOpen', (isOpen) => {
                if (isOpen) {
                    this.$nextTick(() => {
                        requestAnimationFrame(() => this.repositionDropdown());
                    });
                    window.addEventListener('scroll', this.boundRepositionDropdown, true);
                    window.addEventListener('resize', this.boundRepositionDropdown);
                } else {
                    window.removeEventListener('scroll', this.boundRepositionDropdown, true);
                    window.removeEventListener('resize', this.boundRepositionDropdown);
                }
            });

            // Watch timesheet rows for cascading dropdown resets
            this.$watch('timesheetRows', (newRows, oldRows) => {
                if (this.dropdownState.isOpen) {
                    this.dropdownState.highlightedIndex = 0;
                }
                this.$nextTick(() => {
                    requestAnimationFrame(() => this.repositionDropdown());
                });
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

        destroy() {
            // Clean up global event listeners
            if (this.boundRepositionDropdown) {
                window.removeEventListener('scroll', this.boundRepositionDropdown, true);
                window.removeEventListener('resize', this.boundRepositionDropdown);
            }
            
            // Unregister from the store if needed
            if (this.$store.timesheetPageRegistry?.unregisterDirtyCheck) {
                this.$store.timesheetPageRegistry.unregisterDirtyCheck();
            }

            if (this.bannerNotification?.timeoutId) {
                clearTimeout(this.bannerNotification.timeoutId);
            }
        },

        // ==========================================
        // 3. ROW OPERATIONS
        // ==========================================

        addNewRow() {
            // Guard against adding rows during loading or invalid state
            if (this.isLoading || this.isSaving) return;

            if (!this.dateHeaders || this.dateHeaders.length === 0) {
                this.handleError("Error adding a new row, dateHeaders null or empty, alert software team");
                return;
            }
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
            if (this.isLoading || this.isSaving) return;
            if (rowIndex < 0 || rowIndex >= this.timesheetRows.length) {
                this.handleError('removeRow: Invalid row index, please alert software team');
                return;
            }

            if (this.timesheetRows[rowIndex].is_pinned) {
                alert("You cannot delete a pinned row.");
                return;
            }
            // Close dropdown
            if (this.dropdownState.isOpen) {
                this.closeDropdown();
            }
            this.timesheetRows.splice(rowIndex, 1);
            this.recalculateAllTotals();
        },

        deleteAll() {
            if (this.isLoading || this.isSaving || this.timesheetRows.length === 0) return;

            // Check if there are any unpinned rows to delete
            const unpinnedCount = this.timesheetRows.filter(row => !row.is_pinned).length;
            if (unpinnedCount === 0) {
                alert("There are no unpinned rows to delete");
                return;
            }

            if (!confirm("Are you sure you want to delete all un-pinned rows?")) return;

            // Always close dropdown - index mapping is complex and error-prone
            if (this.dropdownState.isOpen) {
                this.closeDropdown();
            }

            this.timesheetRows = this.timesheetRows.filter(row => row.is_pinned);
            this.recalculateAllTotals();
        },

        togglePin(rowIndex) {
            if (this.isLoading || this.isSaving) return;
            if (rowIndex < 0 || rowIndex >= this.timesheetRows.length) {
                this.handleError("Cannot toggle pin status, error in rowIndex, please alert software team");
                return;
            }

            this.timesheetRows[rowIndex].is_pinned = !this.timesheetRows[rowIndex].is_pinned;
        },

        pinAll() {
            if (this.isLoading || this.isSaving || this.timesheetRows.length === 0) return;
            
            const allPinned = this.timesheetRows.every(row => row.is_pinned);
            this.timesheetRows.forEach(row => {
                row.is_pinned = !allPinned;
            });
        },

        revertChanges() {
            if (this.isLoading || this.isSaving) return;
            if (!this.pristineTimesheetRows || !Array.isArray(this.pristineTimesheetRows)) {
                this.handleError("Cannot revert: No saved state available. Please alert software team");
                return;
            }
            if (confirm('Are you sure you want to discard all changes?')) {
                this.closeDropdown();
                this.timesheetRows = JSON.parse(JSON.stringify(this.pristineTimesheetRows));
                this.recalculateAllTotals();
            }
        },

        loadRecentRows(weeksBack) {
            if (this.isLoading || this.isSaving || this.isLoadingRecent) return;
            if (!this.currentStartDate) {
                this.handleError("Reference date missing. Please reload. If problem persists, please alert software team");
                return;
            }

            this.isLoadingRecent = true;
            this.recentRowsRequestDate = this.currentStartDate;
            
            this.$dispatch('timesheet-load-recent', {
                referenceDate: this.currentStartDate,
                weeksBack: weeksBack
            });
        },

        generateRowId(prefix) {
            return `${prefix}_${Date.now()}_${Math.random().toString(36).slice(2, 11)}`;
        },

        // ==========================================
        // 4. DROPDOWN BEHAVIOR
        // ==========================================

        openDropdown(rowIndex, cellType, event) {
            if (this.isLoading || this.isSaving) return;
            if (rowIndex < 0 || rowIndex >= this.timesheetRows.length) {
                this.handleError('openDropdown: Invalid rowIndex. Please Contact Software Team');
                return;
            }
            // Close any existing dropdown first
            this.closeDropdown();

            this.dropdownState = {
                isOpen: true,
                rowIndex: rowIndex,
                cellType: cellType,
                inputEl: event.target,
                highlightedIndex: 0,
                positionMode: null
            };
        },

        closeDropdown() {
            if (this.isLoading) return;

            if (this.dropdownState.isOpen) {
                this.dropdownState.isOpen = false;
                this.dropdownState.rowIndex = null;
                this.dropdownState.cellType = null;
                this.dropdownState.inputEl = null;
                this.dropdownState.highlightedIndex = 0;
                this.dropdownState.positionMode = null;
            }
        },

        repositionDropdown() {
            if (!this.dropdownState.isOpen) return;

            const input = this.dropdownState.inputEl;
            const panel = this.$refs.dropdownPanel;
            
            if (!input || !panel || !input.isConnected) {
                this.closeDropdown();
                return;
            }

            // Get position and dimensions of the input field relative to the viewport
            const inputRect = input.getBoundingClientRect();

            // Set the panel's width to match the input's width
            panel.style.width = `${inputRect.width}px`;
            
            // --- Flipping Logic ---
            const panelHeight = panel.offsetHeight;
            const spaceBelow = window.innerHeight - inputRect.bottom;
            const spaceAbove = inputRect.top;

            let top;

            console.log(this.dropdownState.positionMode === null);
            // If not enough space below AND there's more space above, place it on top.
            if (this.dropdownState.positionMode === 'above' || (this.dropdownState.positionMode === null && spaceBelow < panelHeight && spaceAbove > spaceBelow)) {
                // Position Above
                top = inputRect.top - panelHeight - 4; // 4px gap
                if (this.dropdownState.positionMode === null) this.dropdownState.positionMode = 'above';
            } else {
                // Position Below (Default)
                top = inputRect.bottom + 4; // 4px gap
                if (this.dropdownState.positionMode === null) this.dropdownState.positionMode = 'below';
            }
            
            const left = inputRect.left;

            // Apply the calculated styles to the panel (using fixed positioning)
            panel.style.top = `${top}px`;
            panel.style.left = `${left}px`;
        },

        getActiveDropdownOptions() {
            const { rowIndex, cellType } = this.dropdownState;
            if (rowIndex === null || !this.timesheetRows[rowIndex]) return [];

            const row = this.timesheetRows[rowIndex];

            // Project Code: Returns array of {code, name} objects
            if (cellType === 'project_code') {
                const allProjects = Object.keys(this.dropdownData || {}).map(code => ({
                    code: code,
                    name: this.dropdownData[code].project_name
                }));

                const searchTerm = row.project_code;

                if (!searchTerm) {
                    return allProjects;
                }
                
                // Filter by code OR name
                return allProjects.filter(
                    project => project.code.toLowerCase().includes(searchTerm.toLowerCase()) ||
                               project.name.toLowerCase().includes(searchTerm.toLowerCase())
                );
            }

            // Sub-Code: Returns array of strings
            if (cellType === 'sub_project') {
                const projectCode = row.project_code;
                if (!projectCode || !this.dropdownData[projectCode]) return [];

                const subCodes = Object.keys(this.dropdownData[projectCode]["sub_projects"] || {});
                
                if (!row.sub_project) {
                    return subCodes;
                }
                
                return subCodes.filter(
                    code => code.toLowerCase().includes(row.sub_project.toLowerCase())
                );
            }

            // Activity Code: Returns array of strings
            if (cellType === 'activity_code') {
                const projectCode = row.project_code;
                const subProject = row.sub_project;
                
                if (!projectCode || !subProject) return [];
                if (!this.dropdownData[projectCode] || !this.dropdownData[projectCode].sub_projects ||!this.dropdownData[projectCode].sub_projects[subProject]) return [];
                const activityCodes = Object.values(this.dropdownData[projectCode]["sub_projects"][subProject] || {});
                
                if (!row.activity_code) {
                    return activityCodes;
                }
                
                return activityCodes.filter(
                    code => code.toLowerCase().includes(row.activity_code.toLowerCase())
                );
            }

            return [];
        },

        selectDropdownOption(option) {
            const { rowIndex, cellType } = this.dropdownState;
            if (rowIndex === null || !this.timesheetRows[rowIndex]) return;

            const row = this.timesheetRows[rowIndex];
            const rowId = row.rowId; // Capture for async validation

            const currentCellType = cellType;

            if (currentCellType === 'project_code') {
                row.project_code = typeof option === 'object' ? option.code : option;
            } else if (currentCellType === 'sub_project') {
                row.sub_project = option;
            } else if (currentCellType === 'activity_code') {
                row.activity_code = option;
            }

            this.closeDropdown();
            
            // Handle auto-advance after dropdown is closed and watcher has run
            this.handleAutoAdvance(rowIndex, rowId, currentCellType);
        },

        navigateDropdownOptions(direction) {
            if (!this.dropdownState.isOpen) return;

            const options = this.getActiveDropdownOptions();
            if (options.length === 0) return;

            let newIndex = this.dropdownState.highlightedIndex + direction;
            
            // Wrap around
            if (newIndex < 0) {
                newIndex = options.length - 1;
            } else if (newIndex >= options.length) {
                newIndex = 0;
            }

            this.dropdownState.highlightedIndex = newIndex;

            // Scroll highlighted option into view
            this.$nextTick(() => {
                const panel = this.$refs.dropdownPanel;
                if (panel) {
                    // More robust: find by data attribute or class
                    const options = panel.querySelectorAll('[data-dropdown-option]');
                    const highlightedEl = options[newIndex];
                    if (highlightedEl) {
                        highlightedEl.scrollIntoView({ block: 'nearest' });
                    }
                }
            });
        },

        selectHighlightedOption() {
            if (!this.dropdownState.isOpen) return;

            const options = this.getActiveDropdownOptions();
            if (options.length === 0) return;

            const safeIndex = Math.min(this.dropdownState.highlightedIndex, options.length - 1);
            const selectedOption = options[safeIndex];

            if (selectedOption) {
                this.selectDropdownOption(selectedOption);
            }
        },

        // ==========================================
        // 5. HOUR CELL BEHAVIOR
        // ==========================================

        validateHourInput(rowIndex, hourIndex) {
            // Guard clauses
            if (this.isLoading) return;
            if (rowIndex < 0 || rowIndex >= this.timesheetRows.length) return;
            
            const row = this.timesheetRows[rowIndex];
            if (!row || hourIndex < 0 || hourIndex >= row.hours.length) return;

            let input = row.hours[hourIndex].value;
            let numericValue = parseFloat(input);

            if (isNaN(numericValue) || numericValue < 0) {
                row.hours[hourIndex].value = 0;
            } else {
                numericValue = Math.min(numericValue, 24);
                row.hours[hourIndex].value = Math.round(numericValue * 4) / 4;
            }
            this.recalculateAllTotals();
        },

        onCellFocus(event) {
            this.closeDropdown(); // if transitioning from activity cell to hour cell via tab key

            requestAnimationFrame(() => {
                if (!this.isEditingCell && document.activeElement === event.target) {
                    event.target.select();
                }
            });
        },

        onCellClick(event) {
            // If we're not in editing mode, re-select all text
            // This prevents a second single-click from positioning the cursor
            if (!this.isEditingCell) {
                event.target.select();
            }
        },

        onCellEdit(event) {
            const input = event.target;
            const x = event.clientX;
            const y = event.clientY;

            let offset = null;

            // Firefox
            if (document.caretPositionFromPoint) {
                const caretPosition = document.caretPositionFromPoint(x, y);
                if (caretPosition) {
                    offset = caretPosition.offset;
                }
            } 
            // Chrome, Safari, Edge
            else if (document.caretRangeFromPoint) {
                const range = document.caretRangeFromPoint(x, y);
                if (range) {
                    offset = range.startOffset;
                }
            }

            if (offset !== null) {
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
            if (this.isLoading || this.timesheetRows.length === 0) return;
            if (rowIndex < 0 || rowIndex >= this.timesheetRows.length) return;
            const row = this.timesheetRows[rowIndex];
            if (!row || hourIndex < 0 || hourIndex >= row.hours.length) return;

            // === EDIT MODE ===
            if (this.isEditingCell) {
                // Enter key: "lock in" the value and exit edit mode
                if (event.key === 'Enter') {
                    event.preventDefault();
                    this.timesheetRows[rowIndex].hours[hourIndex].value = event.target.value;
                    this.validateHourInput(rowIndex, hourIndex);
                    this.isEditingCell = false;

                }
                // All other keys (arrows, characters, etc.) work naturally
                else {
                    return;
                }
            }

            // === NAVIGATION MODE (not editing) ===

            // Check if user is starting to type (transition to edit mode)
            if (this.isCharacterInput(event)) {
                this.isEditingCell = true;
                // Don't prevent default - let the character be typed
                return;
            }

            // Handle navigation keys
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
                    return; // Don't navigate for other keys
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

        // Helper: returns true if this keypress would type a character or modify content
        isCharacterInput(event) {
            // Ignore if modifier keys are held (Ctrl+C, Cmd+V, etc.)
            if (event.ctrlKey || event.metaKey || event.altKey) {
                return false;
            }
            
            // Single printable characters
            if (event.key.length === 1) {
                return true;
            }
            
            // Backspace/Delete also count as "starting to edit"
            if (event.key === 'Backspace' || event.key === 'Delete') {
                return true;
            }
            
            return false;
        },

        // ==========================================
        // 6. CALCULATIONS
        // ==========================================
        recalculateAllTotals() {
            const newDailyTotals = Array(7).fill(0);
            if (!this.timesheetRows) return;

            this.timesheetRows.forEach(row => {
                let rowTotal = 0;
                row.hours.forEach((hour, index) => {
                    const parsed = parseFloat(hour.value);
                    const value = Number.isNaN(parsed) ? 0 : parsed;
                    newDailyTotals[index] += value;
                    rowTotal += value;
                });
                row.rowTotal = Math.round(rowTotal * 100) / 100; // help prevent floating point imprecesion
            });

            this.footerTotals.dailyTotals = newDailyTotals.map(t => Math.round(t * 100) / 100);
            const newWeeklyTotal = Math.round(
                newDailyTotals.reduce((sum, total) => sum + total, 0) * 100
            ) / 100;
            this.footerTotals.weeklyTotal = newWeeklyTotal;
            this.payPeriodTotal = Math.round(
                ((this.persistedPayPeriodHours || 0) - (this.initialWeeklyTotal || 0) + newWeeklyTotal) * 100
            ) / 100;
        },

        hasUnsavedChanges() {
            if (this.isLoading) return false;
            if (!this.pristineTimesheetRows || !this.timesheetRows) return false;
            
            return JSON.stringify(this.timesheetRows) !== JSON.stringify(this.pristineTimesheetRows);
        },

        // ==========================================
        // 7. AUTO-ADVANCE LOGIC
        // ==========================================

        handleAutoAdvance(rowIndex, rowId, completedCellType) {
            // Wait for the cascading reset watcher to process
            this.$nextTick(() => {
                // Validate row still exists and hasn't been replaced
                if (!this.isValidRowContext(rowIndex, rowId)) return;
                
                const row = this.timesheetRows[rowIndex];
                
                if (completedCellType === 'project_code') {
                    this.advanceFromProjectCode(rowIndex, rowId, row);
                } else if (completedCellType === 'sub_project') {
                    this.advanceFromSubProject(rowIndex, rowId);
                }
                // activity_code: optionally advance to first hour cell
            });
        },

        advanceFromProjectCode(rowIndex, rowId, row) {
            const projectCode = row.project_code;
            
            // Validate project code exists in dropdown data
            if (!projectCode || !this.dropdownData[projectCode]) {
                // Invalid/unknown project code - focus sub_project to let user see the issue
                this.focusDropdownInput(rowIndex, 'sub_project');
                return;
            }
            
            const subProjectsObj = this.dropdownData[projectCode].sub_projects || {};
            const subProjectKeys = Object.keys(subProjectsObj);
            
            if (subProjectKeys.length === 1) {
                // 1. Update State Explicitly
                // We know that changing sub_project invalidates activity_code.
                // Do it here immediately. Don't wait for the watcher.
                row.sub_project = subProjectKeys[0];
                row.activity_code = ''; 
                
                // 2. Single Tick for DOM Update
                // Since data is settled, we just need the DOM to render the new state
                // before we attempt to focus.
                this.$nextTick(() => {
                    if (!this.isValidRowContext(rowIndex, rowId)) return;
                    this.advanceFromSubProject(rowIndex, rowId);
                });

            } else {
                // Multiple sub-projects - let user choose
                this.focusDropdownInput(rowIndex, 'sub_project');
            }
        },

        advanceFromSubProject(rowIndex, rowId) {
            // activity_code is guaranteed to have more than one option,
            // so we always focus it without auto-filling
            if (!this.isValidRowContext(rowIndex, rowId)) return;
            this.focusDropdownInput(rowIndex, 'activity_code');
        },

        focusDropdownInput(rowIndex, cellType) {
            // Don't steal focus if user has already moved to another dropdown
            if (this.dropdownState.isOpen) return;
            
            const inputId = `dropdown-input-${rowIndex}-${cellType}`;
            this.$nextTick(() => {
                // Check again after nextTick in case state changed
                if (this.dropdownState.isOpen) return;

                // If the user has focused on ANY interactive element (button, link, other input)
                // that is not the body, we should probably abort auto-focusing.

                
                const currentFocus = document.activeElement;

                const isFocusingSameRow = currentFocus 
                                          && currentFocus.id 
                                          && currentFocus.id.startsWith(`dropdown-input-${rowIndex}-`);

                const isFocusingSomethingElse = currentFocus 
                                                && currentFocus !== document.body
                                                && currentFocus.id !== inputId
                                                 && !isFocusingSameRow;

                if (isFocusingSomethingElse) return;
                
                const input = document.getElementById(inputId);
                if (input) {
                    input.focus();
                }
            });
        },

        isValidRowContext(rowIndex, expectedRowId) {
            // Guard against row deletion, reordering, or replacement during async operations
            if (rowIndex < 0 || rowIndex >= this.timesheetRows.length) return false;
            if (this.timesheetRows[rowIndex].rowId !== expectedRowId) return false;
            return true;
        },

        // ==========================================
        // 8. VALIDATION
        // ==========================================

        validateTimesheetRows() {
            const errors = [];

            //  Check for daily totals exceeding 24 hours ===
            const dailyTotals = Array(7).fill(0);
            this.timesheetRows.forEach(row => {
                row.hours.forEach((hour, dayIndex) => {
                    const value = parseFloat(hour.value) || 0;
                    dailyTotals[dayIndex] += value;
                });
            });
            
            dailyTotals.forEach((total, dayIndex) => {
                if (Math.round(total * 100) / 100 > 24) {
                    const dayHeader = this.dateHeaders[dayIndex];
                    errors.push(`${dayHeader.day} ${dayHeader.date}: Total hours (${total.toFixed(2)}) exceeds 24 hours.`);
                }
            });

             // Check for duplicate rows ===
            const seenCombinations = new Map(); // Maps combination key to row number
            this.timesheetRows.forEach((row, index) => {
                const { project_code, sub_project, activity_code } = row;
                
                // Only check non-empty rows
                if (project_code && sub_project && activity_code) {
                    const key = `${project_code}|${sub_project}|${activity_code}`;
                    
                    if (seenCombinations.has(key)) {
                        const firstRowNum = seenCombinations.get(key);
                        errors.push(`Row ${index + 1}: Duplicate of Row ${firstRowNum} (${project_code} / ${sub_project} / ${activity_code}).`);
                    } else {
                        seenCombinations.set(key, index + 1);
                    }
                }
            });
            
            this.timesheetRows.forEach((row, index) => {
                const rowNum = index + 1;
                const { project_code, sub_project, activity_code } = row;
                
                // Skip completely empty rows (optional - remove if you want to require all rows be filled)
                if (!project_code && !sub_project && !activity_code) {
                    // Check if this row has any hours entered
                    const hasHours = row.hours.some(h => parseFloat(h.value) > 0);
                    if (hasHours) {
                        errors.push(`Row ${rowNum}: Has hours entered but no project codes selected.`);
                    }
                    return; // Skip further validation for this row
                }
                
                // Validate project_code exists
                if (!project_code) {
                    errors.push(`Row ${rowNum}: Project Code is required.`);
                    return;
                }
                if (!this.dropdownData[project_code]) {
                    errors.push(`Row ${rowNum}: Invalid Project Code "${project_code}".`);
                    return; // Can't validate sub_project/activity without valid project
                }
                
                // Validate sub_project exists under this project
                if (!sub_project) {
                    errors.push(`Row ${rowNum}: Sub-Code is required.`);
                    return;
                }
                const validSubProjects = this.dropdownData[project_code]?.sub_projects || {};
                if (!validSubProjects[sub_project]) {
                    errors.push(`Row ${rowNum}: Invalid Sub-Code "${sub_project}" for project "${project_code}".`);
                    return; // Can't validate activity without valid sub_project
                }
                
                // Validate activity_code exists under this sub_project
                if (!activity_code) {
                    errors.push(`Row ${rowNum}: Activity Code is required.`);
                    return;
                }
                const validActivityCodes = Object.values(validSubProjects[sub_project] || {});
                if (!validActivityCodes.includes(activity_code)) {
                    errors.push(`Row ${rowNum}: Invalid Activity Code "${activity_code}" for sub-code "${sub_project}".`);
                }
            });
            
            return errors;
        },

        

        // ==========================================
        // 9. DATA HANDLING / EVENTS
        // ==========================================

         saveTimesheet() {
            if (this.isLoading || this.isSaving) return;

            this.isSaving = true; 
            const validationErrors = this.validateTimesheetRows();
            if (validationErrors.length > 0) {
                this.handleError("Please fix the following errors:\n\n" + validationErrors.join("\n"));
                this.isSaving = false;
                return;
            }

            this.closeDropdown();

            // Dispatch to Data Bridge
            this.$dispatch('save-timesheet', {
                timesheetRows: this.timesheetRows,
                headerInfo: { 
                    currentStartDate: this.currentStartDate 
                }
            });
        },

        handleSaveSuccess() {
            // 1. Update the "Pristine" state to match current state
            // This ensures hasUnsavedChanges() returns false
            this.pristineTimesheetRows = JSON.parse(JSON.stringify(this.timesheetRows));
            
            this.showNotification("Timesheet saved successfully.");

            this.isSaving = false; 
            
            // 3. Optional: Re-fetch data if you need server-calculated totals to update strictly
            // Since ApiResult doesn't return data, we rely on local calc or trigger a refresh:
            // this.$dispatch('timesheet-date-change', ...params);
        },

        handleSaveError(event) {
            this.isSaving = false;
            const errorMessage = event.detail;
            this.handleError(errorMessage); // Re-use existing error handler
        },

        handleDataLoading() {
             // Close any open dropdown before loading new data
            this.closeDropdown();

            this.isLoading = true;
            this.error = null;
            // Reset visual state for skeleton
            this.headerInfo = { weekNum: 'Loading...', payPeriodLabel: '' };
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

            this.persistedPayPeriodHours = data.payPeriodTotal;
            this.recalculateAllTotals();
            this.initialWeeklyTotal = this.footerTotals.weeklyTotal;
            this.payPeriodTotal = data.payPeriodTotal;

            this.pristineTimesheetRows = JSON.parse(JSON.stringify(data.timesheetRows));
            
            this.isLoading = false;
            this.error = null;
        },

        handleRecentLoaded(event) {
            const responseObj = event.detail.recentRows;

            if (this.recentRowsRequestDate !== this.currentStartDate) {
                console.log('Ignoring stale recent rows response:', {
                    requestedFor: this.recentRowsRequestDate,
                    currentWeek: this.currentStartDate
                });
        
                // Clean up the loading state and exit
                this.isLoadingRecent = false;
                this.recentRowsRequestDate = null;
                return;
            }

            if (!responseObj) {
                this.isLoadingRecent = false;
                this.recentRowsRequestDate = null;
                return;
            }
            if (responseObj.errors) {
                this.isLoadingRecent = false;
                this.recentRowsRequestDate = null;
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
                        is_pinned: false,
                        hours: JSON.parse(JSON.stringify(emptyHours)),
                        rowTotal: 0
                    });
                    addedCount++;
                }
            });

            // Force UI update and recalculate
            this.isLoadingRecent = false;
            this.recentRowsRequestDate = null;
            
            if (addedCount > 0) {
                this.recalculateAllTotals();
            }
        },

        handleFetchError(event) {
            const errorMessage = event.detail;
            this.handleError(errorMessage);
        },

        handleError(errorMessage) {
            this.error = errorMessage;
            this.isLoading = false;
            this.isLoadingRecent = false;
            // Close any open dropdown on error
            this.closeDropdown();
            // Dispatch error modal instead of showing local error state
            this.$dispatch('error-modal', { message: this.error });
        },
        showNotification(message) {
            // Clear any existing timeout
            if (this.bannerNotification.timeoutId) {
                clearTimeout(this.bannerNotification.timeoutId);
            }
            
            this.bannerNotification.message = message;
            this.bannerNotification.show = true;
            
            // Auto-close after 5 seconds
            this.bannerNotification.timeoutId = setTimeout(() => {
                this.closeNotification();
            }, 5000);
        },

        closeNotification() {
            this.bannerNotification.show = false;
            if (this.bannerNotification.timeoutId) {
                clearTimeout(this.bannerNotification.timeoutId);
                this.bannerNotification.timeoutId = null;
            }
        },
    }
}
</script>
@endpush