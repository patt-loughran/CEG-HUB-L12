@props([
    'payPeriodData' => [],
    'startDateCutOff' => null,
    'endDateCutOff' => null
])

<div 
    x-data="employeeTimeInput()" 
    class="w-80 bg-white border-r border-slate-200 flex flex-col h-full shrink-0 shadow-lg"
    {{-- Listen for events bubbling up from the child custom date components --}}
    @date-selector-change="handleDateUpdate($event.detail)"
>
    <!-- Header -->
    <div class="bg-slate-700 px-5 py-4 flex justify-between items-center shrink-0">
        <span class="text-white text-xs font-bold uppercase tracking-widest">Control Panel</span>
        <div class="flex gap-2">
            {{-- Refresh / Reset Action --}}
            <button 
                @click="triggerRefresh()"
                title="Refresh Data"
                class="p-1.5 bg-slate-600 rounded text-slate-200 hover:bg-slate-500 hover:text-white transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
        </div>
    </div>

    <!-- Scrollable Input Area -->
    <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar flex-1">
        
<!-- Granularity Selector -->
<div>
    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Time Granularity</label>
    <div class="relative w-60">
        <select 
            x-model="granularity" 
            @change="handleGranularityChange()"
            class="w-full bg-slate-50 border border-slate-200 rounded-md py-2 px-3 text-slate-700 text-sm focus:outline-none focus:ring-1 focus:ring-slate-500 appearance-none"
        >
            <option value="day">Day</option>
            <option value="pay_period">Pay-Period</option>
            <option value="month">Month</option>
            <option value="quarter">Quarter</option>
            <option value="year">Year</option>
        </select>
        {{-- Chevron --}}
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
            <x-general.icon name="downChevron" class="h-4 w-4" />
        </div>
    </div>
</div>
        
        <!-- Arrow Divider (Darkened) -->
        <div class="flex items-center justify-center">
            <div class="h-px bg-slate-300 w-full"></div>
            <span class="px-2 text-slate-500">
               <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-8l-7 7-7-7"></path></svg>
            </span>
            <div class="h-px bg-slate-300 w-full"></div>
        </div>

        <!-- Date Selectors -->
        <div class="space-y-4">
            {{-- START DATE LABEL --}}
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Start Date</label>
                
                {{-- 1. Day Selector --}}
                <div x-show="granularity === 'day'" style="display: none;">
                    <x-inputs.date-selectors.day-selector 
                        identifier="start"
                        :startDateCutOff="$startDateCutOff"
                        :endDateCutOff="$endDateCutOff"
                    />
                </div>

                {{-- 2. Pay Period Selector --}}
                <div x-show="granularity === 'pay_period'" style="display: none;">
                    <x-inputs.date-selectors.pay-period-selector 
                        identifier="start"
                        :payPeriods="$payPeriodData"
                        :startDateCutOff="$startDateCutOff"
                        :endDateCutOff="$endDateCutOff"
                    />
                </div>

                {{-- 3. Month Selector --}}
                <div x-show="granularity === 'month'" style="display: none;">
                    <x-inputs.date-selectors.month-selector 
                        identifier="start"
                        :startDateCutOff="$startDateCutOff"
                        :endDateCutOff="$endDateCutOff"
                    />
                </div>

                {{-- 4. Quarter Selector --}}
                <div x-show="granularity === 'quarter'" style="display: none;">
                    <x-inputs.date-selectors.quarter-selector 
                        identifier="start"
                        :startDateCutOff="$startDateCutOff"
                        :endDateCutOff="$endDateCutOff"
                    />
                </div>

                {{-- 5. Year Selector --}}
                <div x-show="granularity === 'year'" style="display: none;">
                    <x-inputs.date-selectors.year-selector 
                        identifier="start"
                        :startDateCutOff="$startDateCutOff"
                        :endDateCutOff="$endDateCutOff"
                    />
                </div>
            </div>

            {{-- END DATE LABEL --}}
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">End Date</label>

                {{-- 1. Day Selector --}}
                <div x-show="granularity === 'day'" style="display: none;">
                    <x-inputs.date-selectors.day-selector 
                        identifier="end"
                        :startDateCutOff="$startDateCutOff"
                        :endDateCutOff="$endDateCutOff"
                    />
                </div>

                {{-- 2. Pay Period Selector --}}
                <div x-show="granularity === 'pay_period'" style="display: none;">
                    <x-inputs.date-selectors.pay-period-selector 
                        identifier="end"
                        :payPeriods="$payPeriodData"
                        :startDateCutOff="$startDateCutOff"
                        :endDateCutOff="$endDateCutOff"
                    />
                </div>

                {{-- 3. Month Selector --}}
                <div x-show="granularity === 'month'" style="display: none;">
                    <x-inputs.date-selectors.month-selector 
                        identifier="end"
                        :startDateCutOff="$startDateCutOff"
                        :endDateCutOff="$endDateCutOff"
                    />
                </div>

                {{-- 4. Quarter Selector --}}
                <div x-show="granularity === 'quarter'" style="display: none;">
                    <x-inputs.date-selectors.quarter-selector 
                        identifier="end"
                        :startDateCutOff="$startDateCutOff"
                        :endDateCutOff="$endDateCutOff"
                    />
                </div>

                {{-- 5. Year Selector --}}
                <div x-show="granularity === 'year'" style="display: none;">
                    <x-inputs.date-selectors.year-selector 
                        identifier="end"
                        :startDateCutOff="$startDateCutOff"
                        :endDateCutOff="$endDateCutOff"
                    />
                </div>
            </div>
        </div>

        <!-- Scope Divider (Moved inside scroll area) -->
        <div class="h-px bg-slate-200 w-full"></div>

        <!-- Display Scope (Moved inside scroll area, Multi-Select) -->
        <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 block">Display Scope</label>
            <div class="flex flex-wrap gap-2">
                <template x-for="option in ['Project', 'Sub-Code', 'Activity']">
                    <button 
                        @click="toggleScope(option)"
                        class="px-4 py-1.5 rounded-full text-xs font-semibold transition-all duration-200"
                        :class="scope.includes(option)
                            ? 'bg-slate-700 text-white shadow-md' 
                            : 'bg-slate-50 text-slate-500 hover:bg-slate-100'"
                        x-text="option"
                    ></button>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
    function employeeTimeInput() {
        return {
            // State
            granularity: 'day', 
            scope: ['Project'],
            startDate: null, 
            endDate: null,

            init() {
                this.$nextTick(() => {
                    this.askChildrenForState();
                });
            },

            /**
             * Broadcasts an event to window.
             */
            askChildrenForState() {
                this.$dispatch('report-current-state', this.granularity);
            },

            /**
             * Triggered when the granularity select changes.
             */
            handleGranularityChange() {
                this.startDate = null;
                this.endDate = null;
                
                this.$nextTick(() => {
                    this.askChildrenForState();
                });
            },

            /**
             * Triggered when a child Date Component fires 'date-selector-change'.
             */
            handleDateUpdate(payload) {
                if (payload.type !== this.granularity) return;

                if (payload.identifier === 'start') {
                    this.startDate = payload.start;
                } else if (payload.identifier === 'end') {
                    this.endDate = payload.end;
                }

                this.checkAndDispatch();
            },

            /**
             * Toggles scope items (Multi-select).
             */
            toggleScope(option) {
                if (this.scope.includes(option)) {
                    // Remove it (Prevent removing the last one if you want to enforce at least one)
                    // if (this.scope.length > 1) { 
                        this.scope = this.scope.filter(item => item !== option);
                    // }
                } else {
                    // Add it
                    this.scope.push(option);
                }
                this.checkAndDispatch();
            },

            /**
             * Manual refresh button trigger
             */
            triggerRefresh() {
                this.askChildrenForState();
            },

            /**
             * Validates state and dispatches the event.
             */
            checkAndDispatch() {
                // Ensure dates exist AND at least one scope is selected
                if (this.startDate && this.endDate && this.scope.length > 0) {
                    
                    this.$dispatch('employee-time-change', {
                        granularity: this.granularity,
                        scope: this.scope, // Now sends an array
                        start_date: this.startDate,
                        end_date: this.endDate
                    });
                }
            }
        };
    }
</script>