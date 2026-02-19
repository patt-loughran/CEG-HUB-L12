@props([
    'payPeriodData' => [],
    'startDateCutOff' => null,
    'endDateCutOff' => null
])

<div 
    x-data="timeEmployeeTimeHistorianUserInputLogic()" 
    class="bg-white border-r border-slate-200 flex flex-col h-full shrink-0 shadow-lg relative transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)]"
    x-bind:class="sidebarOpen ? 'w-80' : 'w-14'"
    @date-selector-change="handleDateUpdate($event.detail)"
>
    <!-- Header -->
    <div 
        class="bg-slate-700 h-14 flex items-center shrink-0 transition-all duration-300 overflow-hidden"
        x-bind:class="sidebarOpen ? 'justify-between px-5' : 'justify-center px-0'"
    >
        <!-- Title (Hidden when closed) -->
        <div class="flex items-center overflow-hidden whitespace-nowrap transition-all duration-300"
             x-bind:class="sidebarOpen ? 'w-auto opacity-100' : 'w-0 opacity-0'">
            <span class="text-white text-xs font-bold uppercase tracking-widest">Control Panel</span>
        </div>

        <!-- Right Side Actions -->
        <div class="flex items-center gap-2">
            
            {{-- Refresh Button (Only visible when open) --}}
            <button 
                x-show="sidebarOpen"
                @click="triggerRefresh()"
                title="Refresh Data"
                class="p-1.5 bg-slate-600 rounded text-slate-200 hover:bg-slate-500 hover:text-white transition"
                style="display: none;" 
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>

            {{-- Toggle/Minimize Button --}}
            <button 
                @click="sidebarOpen = !sidebarOpen"
                class="bg-slate-600 text-slate-300 hover:text-white p-1 rounded hover:bg-slate-500 transition-colors focus:outline-none"
                x-bind:title="sidebarOpen ? 'Minimize' : 'Expand'"
            >
                <svg class="w-5 h-5 transition-transform duration-300" 
                     x-bind:class="!sidebarOpen ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Scrollable Input Area -->
    <div 
        class="flex-1 overflow-y-auto custom-scrollbar transition-all duration-300"
        x-bind:class="sidebarOpen ? 'opacity-100 p-6 space-y-6' : 'opacity-0 px-0 overflow-hidden'"
    >
        <div class="w-72"> {{-- Fixed width wrapper prevents content reflow during shrink --}}
            
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
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                        <x-general.icon name="downChevron" class="h-4 w-4" />
                    </div>
                </div>
            </div>
            
            <!-- Divider -->
            <div class="flex items-center justify-center my-6">
                <div class="h-px bg-slate-300 w-full"></div>
                <span class="px-2 text-slate-500">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-8l-7 7-7-7"></path></svg>
                </span>
                <div class="h-px bg-slate-300 w-full"></div>
            </div>

            <!-- Date Selectors -->
            <div class="space-y-4">
                {{-- START DATE --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Start Date</label>
                    
                    <div x-show="granularity === 'day'" style="display: none;">
                        <x-inputs.date-selectors.day-selector identifier="start" :startDateCutOff="$startDateCutOff" :endDateCutOff="$endDateCutOff" />
                    </div>
                    <div x-show="granularity === 'pay_period'" style="display: none;">
                        <x-inputs.date-selectors.pay-period-selector identifier="start" :payPeriods="$payPeriodData" :startDateCutOff="$startDateCutOff" :endDateCutOff="$endDateCutOff" />
                    </div>
                    <div x-show="granularity === 'month'" style="display: none;">
                        <x-inputs.date-selectors.month-selector identifier="start" :startDateCutOff="$startDateCutOff" :endDateCutOff="$endDateCutOff" />
                    </div>
                    <div x-show="granularity === 'quarter'" style="display: none;">
                        <x-inputs.date-selectors.quarter-selector identifier="start" :startDateCutOff="$startDateCutOff" :endDateCutOff="$endDateCutOff" />
                    </div>
                    <div x-show="granularity === 'year'" style="display: none;">
                        <x-inputs.date-selectors.year-selector identifier="start" :startDateCutOff="$startDateCutOff" :endDateCutOff="$endDateCutOff" />
                    </div>
                </div>

                {{-- END DATE --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">End Date</label>

                    <div x-show="granularity === 'day'" style="display: none;">
                        <x-inputs.date-selectors.day-selector identifier="end" :startDateCutOff="$startDateCutOff" :endDateCutOff="$endDateCutOff" />
                    </div>
                    <div x-show="granularity === 'pay_period'" style="display: none;">
                        <x-inputs.date-selectors.pay-period-selector identifier="end" :payPeriods="$payPeriodData" :startDateCutOff="$startDateCutOff" :endDateCutOff="$endDateCutOff" />
                    </div>
                    <div x-show="granularity === 'month'" style="display: none;">
                        <x-inputs.date-selectors.month-selector identifier="end" :startDateCutOff="$startDateCutOff" :endDateCutOff="$endDateCutOff" />
                    </div>
                    <div x-show="granularity === 'quarter'" style="display: none;">
                        <x-inputs.date-selectors.quarter-selector identifier="end" :startDateCutOff="$startDateCutOff" :endDateCutOff="$endDateCutOff" />
                    </div>
                    <div x-show="granularity === 'year'" style="display: none;">
                        <x-inputs.date-selectors.year-selector identifier="end" :startDateCutOff="$startDateCutOff" :endDateCutOff="$endDateCutOff" />
                    </div>
                </div>
            </div>

            <!-- Scope Divider -->
            <div class="h-px bg-slate-200 w-full my-6"></div>

            <!-- Display Scope -->
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 block">Display Scope</label>
                <div class="flex flex-wrap gap-2">
                    <template x-for="option in ['Project', 'Sub-Code', 'Activity']">
                        <button 
                            @click="toggleScope(option)"
                            class="px-4 py-1.5 rounded-full text-xs font-semibold transition-all duration-200"
                            x-bind:class="scope.includes(option)
                                ? 'bg-slate-700 text-white shadow-md' 
                                : 'bg-slate-50 text-slate-500 hover:bg-slate-100'"
                            x-text="option"
                        ></button>
                    </template>
                </div>
            </div>

        </div> {{-- End Fixed Width Wrapper --}}
    </div>

    {{-- Vertical Text Label (Only Visible when Sidebar is CLOSED) --}}
    <div 
        class="absolute inset-0 top-14 flex items-center justify-center pointer-events-none transition-opacity duration-300"
        x-bind:class="!sidebarOpen ? 'opacity-100 delay-200' : 'opacity-0'"
    >
        <div class="transform rotate-180" style="writing-mode: vertical-rl;">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] whitespace-nowrap">
                Control Panel
            </span>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function timeEmployeeTimeHistorianUserInputLogic() {
        return {
            // --- State ---
            granularity: null,
            scope: null,
            startDate: null,
            endDate: null,

            init() {
                // Initialize Instance Variables
                this.granularity = 'day';
                this.scope = ['Project'];
                this.startDate = null;
                this.endDate = null;

                // Fire initial dispatch to date selector children
                this.$nextTick(() => {
                    this.askChildrenForState();
                });
            },

            /**
             * Broadcasts an event to child date selectors requesting their current state.
             */
            askChildrenForState() {
                this.$dispatch('report-current-state', this.granularity);
            },

            /**
             * Triggered when the granularity select changes.
             * Resets dates and asks new date selectors for their defaults.
             */
            handleGranularityChange() {
                this.startDate = null;
                this.endDate = null;
                
                this.$nextTick(() => {
                    this.askChildrenForState();
                });
            },

            /**
             * Triggered when a child Date Selector fires 'date-selector-change'.
             */
            handleDateUpdate(payload) {
                if (payload.type !== this.granularity) return;

                if (this.granularity === 'day') {
                    if (payload.identifier === 'start') {
                        this.startDate = payload.start;
                    } else if (payload.identifier === 'end') {
                        this.endDate = payload.end;
                    }
                } else {
                    // Non-day granularities: controller expects { startDate, endDate }
                    if (payload.identifier === 'start') {
                        this.startDate = { startDate: payload.start, endDate: payload.end };
                    } else if (payload.identifier === 'end') {
                        this.endDate = { startDate: payload.start, endDate: payload.end };
                    }
                }

                this.dispatchChangeEvent();
            },

            /**
             * Toggles scope items (Multi-select).
             * Enforces consistent column order regardless of click order.
             */
            toggleScope(option) {
                if (this.scope.includes(option)) {
                    this.scope = this.scope.filter(item => item !== option);
                } else {
                    this.scope.push(option);
                }
                
                const desiredOrder = ['Project', 'Sub-Code', 'Activity'];
                this.scope.sort((a, b) => desiredOrder.indexOf(a) - desiredOrder.indexOf(b));

                this.dispatchChangeEvent();
            },

            /**
             * Manual refresh button trigger.
             */
            triggerRefresh() {
                this.askChildrenForState();
            },

            /**
             * Centralized dispatch function.
             * Validates state and dispatches the event to the data-bridge.
             */
            dispatchChangeEvent() {
                if (this.startDate && this.endDate && this.scope.length > 0) {
                    this.$dispatch('employee-time-change', {
                        granularity: this.granularity,
                        scope: this.scope,
                        start_date: this.startDate,
                        end_date: this.endDate,
                    });
                }
            },
        };
    }
</script>
@endpush