@props(['payPeriods' => [], 'startDateCutOff' => null, 'endDateCutOff' => null, 'identifier' => null])

<div 
    x-data="payPeriodSelector(@js($payPeriods), '{{ $startDateCutOff }}', '{{ $endDateCutOff }}', '{{ $identifier }}')"
    @click.outside="open = false"
    @report-current-state.window="if($event.detail === 'pay_period') dispatchState()"
    class="relative"
>
    {{-- Trigger Button --}}
    <button 
        @click="open = !open"
        type="button"
        class="flex h-10 w-[240px] items-center justify-start rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm ring-offset-white focus:outline-none focus:ring-2 focus:ring-slate-950"
        :class="!selectedPeriod ? 'text-slate-500' : 'text-slate-900'"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
        <span x-text="formatDisplayText()"></span>
    </button>

    {{-- Popover Content --}}
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute top-0 z-50 mt-12 w-[280px] rounded-md border border-slate-200 bg-white p-3 text-slate-950 shadow-md outline-none"
        style="display: none;"
    >
        {{-- Navigation Header --}}
        <div class="flex items-center justify-between mb-4 space-x-1">
            <button 
                @click="navigate(-1)" 
                :disabled="!canNavigate(-1)"
                type="button"
                class="h-7 w-7 flex items-center justify-center rounded-md border border-slate-200 text-slate-500 transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:bg-slate-50"
                :class="canNavigate(-1) ? 'hover:bg-slate-100 hover:text-slate-900' : ''"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            
            <button 
                @click="cycleView()"
                type="button"
                class="flex-1 text-sm font-semibold hover:bg-slate-100 py-1 rounded-md transition-colors text-slate-900"
                x-text="getHeaderLabel()"
            ></button>

            <button 
                @click="navigate(1)" 
                :disabled="!canNavigate(1)"
                type="button"
                class="h-7 w-7 flex items-center justify-center rounded-md border border-slate-200 text-slate-500 transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:bg-slate-50"
                :class="canNavigate(1) ? 'hover:bg-slate-100 hover:text-slate-900' : ''"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>

        {{-- VIEW: Periods --}}
        <div x-show="view === 'periods'">
            <div class="max-h-[260px] overflow-y-auto space-y-1 custom-scrollbar pr-1">
                <template x-for="(period, index) in (data[headerYear] || [])" :key="index">
                    <button 
                        @click="selectPeriod(period)"
                        :disabled="isPeriodDisabled(period)"
                        type="button"
                        class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors flex items-center justify-between group disabled:opacity-30 disabled:cursor-not-allowed disabled:bg-white disabled:text-slate-400"
                        :class="isSelected(period) 
                            ? 'bg-slate-900 text-white' 
                            : isPeriodDisabled(period) ? '' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    >
                        <span x-text="formatListLabel(period)"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- VIEW: Years --}}
        <div x-show="view === 'years'">
            <div class="grid grid-cols-4 gap-2">
                <template x-for="year in decadeArray" :key="year">
                    <button 
                        @click="drillDownYear(year)"
                        :disabled="isYearDisabled(year)"
                        type="button"
                        class="h-9 w-full rounded-md text-sm transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:bg-white disabled:text-slate-400"
                        :class="isHeaderYear(year) 
                            ? 'bg-slate-900 text-white' 
                            : 'hover:bg-slate-100 text-slate-900'"
                        x-text="year"
                    ></button>
                </template>
            </div>
        </div>

    </div>
</div>

<script>
function payPeriodSelector(payPeriodData, startDateCutOff, endDateCutOff, identifier) {

    // ─── Helpers ───────────────────────────────────────────

    function startOfDay(date) {
        const d = new Date(date);
        d.setHours(0, 0, 0, 0);
        return d;
    }

    function endOfDay(date) {
        const d = new Date(date);
        d.setHours(23, 59, 59, 999);
        return d;
    }

    function toISODateString(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function findDefaultDate() {
        const today = new Date();
        const currentDay = today.getDate();

        if (identifier === "start") {
            return new Date(today.getFullYear(), today.getMonth() - 6, 1);
        }
        else if (identifier === "end") {
            return new Date(today.getFullYear(), today.getMonth() + 1, 0);
        }
        else {
            return today;
        }
    }

    const defaultDate = findDefaultDate();

    // ───────────────────────────────────────────────────────

    return {
        // ─── State ─────────────────────────────────────────

        open: false,
        view: 'periods',
        identifier: identifier,

        // Cursor/Browsing State
        headerYear: new Date(defaultDate).getFullYear(),
        decadeStart: Math.floor(new Date(defaultDate).getFullYear() / 10) * 10,

        // Selection
        selectedPeriod: null,

        // Constraints
        minDate: startDateCutOff ? new Date(startDateCutOff) : null,
        maxDate: endDateCutOff ? new Date(endDateCutOff) : null,

        // Data
        data: payPeriodData || {},
        sortedYears: [],

        // Grid Data
        decadeArray: [],

        // ─── Initialization ────────────────────────────────

        init() {
            // Process valid years from data
            this.sortedYears = Object.keys(this.data)
                .map(Number)
                .sort((a, b) => a - b);

            // Try to select initial period
            const referenceDate = new Date(defaultDate);
            let found = false;

            // First: Try to find period containing referenceDate (within constraints)
            for (const year of this.sortedYears) {
                const periods = this.data[year];
                for (const period of periods) {
                    const periodStart = new Date(period.start_date);
                    const periodEnd = new Date(period.end_date);
                    if (referenceDate >= periodStart && referenceDate <= periodEnd && !this.isPeriodDisabled(period)) {
                        this.selectedPeriod = period;
                        this.headerYear = year;
                        found = true;
                        break;
                    }
                }
                if (found) break;
            }

            // Fallback: Latest available period within constraints
            if (!found && this.sortedYears.length > 0) {
                for (let i = this.sortedYears.length - 1; i >= 0; i--) {
                    const year = this.sortedYears[i];
                    const periods = this.data[year];
                    for (let j = periods.length - 1; j >= 0; j--) {
                        const period = periods[j];
                        if (!this.isPeriodDisabled(period)) {
                            this.selectedPeriod = period;
                            this.headerYear = year;
                            found = true;
                            break;
                        }
                    }
                    if (found) break;
                }
            }

            // Last resort: Set browsing year to first valid year
            if (!found && this.sortedYears.length > 0) {
                for (const year of this.sortedYears) {
                    if (!this.isYearFullyDisabled(year)) {
                        this.headerYear = year;
                        break;
                    }
                }
            }

            this.decadeStart = Math.floor(this.headerYear / 10) * 10;
            this.calculateGrid();
        },

        // ─── Constraints ───────────────────────────────────

        /**
         * Check if a specific pay period is outside the allowed range.
         * A period is disabled if it ends before minDate OR starts after maxDate.
         */
        isPeriodDisabled(period) {
            const periodStart = startOfDay(new Date(period.start_date));
            const periodEnd = endOfDay(new Date(period.end_date));

            if (this.minDate && periodEnd < startOfDay(this.minDate)) return true;
            if (this.maxDate && periodStart > endOfDay(this.maxDate)) return true;

            return false;
        },

        /**
         * Check if a year should be disabled in the year grid.
         * A year is disabled if no data exists OR the entire year is outside the range.
         */
        isYearDisabled(year) {
            if (!this.data[year]) return true;

            const yearStart = startOfDay(new Date(year, 0, 1));
            const yearEnd = endOfDay(new Date(year, 11, 31));

            if (this.minDate && yearEnd < startOfDay(this.minDate)) return true;
            if (this.maxDate && yearStart > endOfDay(this.maxDate)) return true;

            return false;
        },

        /**
         * Check if ALL periods in a year are disabled (used for smarter navigation).
         */
        isYearFullyDisabled(year) {
            if (!this.data[year]) return true;

            const periods = this.data[year];
            return periods.every(period => this.isPeriodDisabled(period));
        },

        // ─── Navigation ────────────────────────────────────

        /**
         * Check if navigation in a direction (-1 or 1) is allowed.
         */
        canNavigate(direction) {
            if (this.sortedYears.length === 0) return false;

            // Calculate effective min/max years considering both data and date constraints
            let effectiveMinYear = this.sortedYears[0];
            let effectiveMaxYear = this.sortedYears[this.sortedYears.length - 1];

            if (this.minDate) {
                effectiveMinYear = Math.max(effectiveMinYear, this.minDate.getFullYear());
            }
            if (this.maxDate) {
                effectiveMaxYear = Math.min(effectiveMaxYear, this.maxDate.getFullYear());
            }

            if (this.view === 'periods') {
                if (direction === 1) {
                    return this.sortedYears.some(y => y > this.headerYear && !this.isYearFullyDisabled(y));
                }
                if (direction === -1) {
                    return this.sortedYears.some(y => y < this.headerYear && !this.isYearFullyDisabled(y));
                }
            } 
            else if (this.view === 'years') {
                const targetDecadeStart = this.decadeStart + (direction * 10);

                if (direction === 1) {
                    return effectiveMaxYear >= (targetDecadeStart - 1);
                }
                if (direction === -1) {
                    return effectiveMinYear <= (targetDecadeStart + 10);
                }
            }

            return false;
        },

        /**
         * Navigate forward or backward based on the current view.
         */
        navigate(direction) {
            if (!this.canNavigate(direction)) return;

            if (this.view === 'periods') {
                // Smart navigation: Skip to next/prev year that has valid periods
                if (direction === 1) {
                    const next = this.sortedYears.find(y => y > this.headerYear && !this.isYearFullyDisabled(y));
                    if (next) this.headerYear = next;
                } else {
                    const prev = [...this.sortedYears].reverse().find(y => y < this.headerYear && !this.isYearFullyDisabled(y));
                    if (prev) this.headerYear = prev;
                }
            } 
            else if (this.view === 'years') {
                this.decadeStart += direction * 10;
            }

            this.calculateGrid();
        },

        // ─── View Management ───────────────────────────────

        /**
         * Cycle through views: periods → years → periods
         */
        cycleView() {
            if (this.view === 'periods') {
                this.view = 'years';
            } else {
                this.view = 'periods';
            }
            this.decadeStart = Math.floor(this.headerYear / 10) * 10;
            this.calculateGrid();
        },

        /**
         * Drill down from year view to periods view.
         */
        drillDownYear(year) {
            if (this.isYearDisabled(year)) return;

            this.headerYear = year;
            this.decadeStart = Math.floor(year / 10) * 10;
            this.view = 'periods';
            this.calculateGrid();
        },

        // ─── Selection ─────────────────────────────────────

        /**
         * Select a specific pay period and close the picker.
         */
        selectPeriod(period) {
            if (this.isPeriodDisabled(period)) return;

            this.selectedPeriod = period;
            this.headerYear = new Date(period.start_date).getFullYear();
            this.open = false;
            this.dispatchState();
        },

        // ─── Grid Calculation ──────────────────────────────

        /**
         * Recalculate grid data based on current header position.
         */
        calculateGrid() {
            // Decade array (12 years: 1 before, 10 during, 1 after)
            this.decadeArray = Array.from({ length: 12 }, (_, i) => this.decadeStart - 1 + i);
        },

        // ─── Display Helpers ───────────────────────────────

        /**
         * Get the label for the navigation header.
         */
        getHeaderLabel() {
            if (this.view === 'periods') {
                return `${this.headerYear}`;
            }
            return `${this.decadeStart} - ${this.decadeStart + 9}`;
        },

        /**
         * Check if a year matches the current header year (for highlighting).
         */
        isHeaderYear(year) {
            return this.headerYear === year;
        },

        /**
         * Check if a period is currently selected.
         */
        isSelected(period) {
            if (!this.selectedPeriod) return false;
            return period.start_date === this.selectedPeriod.start_date &&
                   period.end_date === this.selectedPeriod.end_date;
        },

        // ─── Formatting & Dispatch ─────────────────────────

        /**
         * Format the selected period for display in the trigger button.
         */
        formatDisplayText() {
            if (!this.selectedPeriod) return 'Select a pay period';
            const start = new Date(this.selectedPeriod.start_date);
            const end = new Date(this.selectedPeriod.end_date);
            const options = { month: 'short', day: 'numeric', year: 'numeric' };
            return `${start.toLocaleDateString('en-US', options)} - ${end.toLocaleDateString('en-US', options)}`;
        },

        /**
         * Format a period for display in the list view.
         */
        formatListLabel(period) {
            const start = new Date(period.start_date);
            const end = new Date(period.end_date);
            const format = (d) => {
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                const year = String(d.getFullYear()).slice(-2);
                return `${month}/${day}/${year}`;
            };
            return `${format(start)} - ${format(end)}`;
        },

        /**
         * Dispatch the selected period as a custom event.
         */
        dispatchState() {
            if (!this.selectedPeriod) return;

            this.$dispatch('date-selector-change', {
                type: 'pay_period',
                identifier: this.identifier,
                start: toISODateString(this.selectedPeriod.start_date),
                end: toISODateString(this.selectedPeriod.end_date)
            });
        }
    };
}
</script>