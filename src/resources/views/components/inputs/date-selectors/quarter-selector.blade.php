@props(['startDateCutOff' => null, 'endDateCutOff' => null, 'identifier' => null])

<div 
    x-data="quarterSelector('{{ $startDateCutOff }}', '{{ $endDateCutOff }}', '{{ $identifier }}')"
    @click.outside="open = false"
    @report-current-state.window="if($event.detail === 'quarter') dispatchState()"
    class="relative"
>
    {{-- Trigger Button --}}
    <button 
        @click="open = !open"
        type="button"
        class="flex h-10 w-[240px] items-center justify-start rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm ring-offset-white focus:outline-none focus:ring-2 focus:ring-slate-950"
        :class="!selectedQuarter ? 'text-slate-500' : 'text-slate-900'"
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

        {{-- VIEW: Quarters --}}
        <div x-show="view === 'quarters'">
            <div class="grid grid-cols-1 gap-2">
                <template x-for="(quarter, index) in quarters" :key="index">
                    <button 
                        @click="selectPeriod(index)"
                        :disabled="isPeriodDisabled(index)"
                        type="button"
                        class="inline-flex h-10 w-full items-center justify-center whitespace-nowrap rounded-md text-sm transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:bg-white disabled:text-slate-400"
                        :class="isSelected(index) 
                            ? 'bg-slate-900 text-white' 
                            : isPeriodDisabled(index) ? '' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                        x-text="quarter.name"
                    ></button>
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
function quarterSelector(startDateCutOff, endDateCutOff, identifier) {

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
        
        if (identifier === "start") {
            // January 1st, 2 years ago
            const startDate = new Date(today.getFullYear() - 2, 0, 1);
            return startDate;
        }
        else if (identifier === "end") {
            // Last day of current month
            const endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            return endDate;
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
        view: 'quarters',
        identifier: identifier,

        // Cursor/Browsing State
        headerYear: new Date(defaultDate).getFullYear(),
        decadeStart: Math.floor(new Date(defaultDate).getFullYear() / 10) * 10,

        // Selection
        selectedQuarter: null, // Object: { start_date, end_date }

        // Constraints
        minDate: startDateCutOff ? new Date(startDateCutOff) : null,
        maxDate: endDateCutOff ? new Date(endDateCutOff) : null,

        // Data
        quarters: [
            { name: "Q1 (Jan - Mar)" },
            { name: "Q2 (Apr - Jun)" },
            { name: "Q3 (Jul - Sep)" },
            { name: "Q4 (Oct - Dec)" },
        ],

        // Grid Data
        decadeArray: [],

        // ─── Initialization ────────────────────────────────

        init() {
            // Try to select initial period (Current Quarter)
            const referenceDate = new Date(defaultDate);
            const currentYear = referenceDate.getFullYear();
            const currentQuarterIndex = Math.floor(referenceDate.getMonth() / 3); // 0-3

            // Set Header Year to referenceDate (or adjust to minDate)
            this.headerYear = currentYear;
            if (this.minDate && this.headerYear < this.minDate.getFullYear()) {
                this.headerYear = this.minDate.getFullYear();
            }

            // Create temporary period for referenceDate to check validity
            const startMonth = currentQuarterIndex * 3;
            const start = new Date(currentYear, startMonth, 1);
            const end = new Date(currentYear, startMonth + 3, 0);

            // Auto-select if valid
            if (!this.isDateRangeDisabled(start, end)) {
                this.selectedQuarter = {
                    start_date: toISODateString(start),
                    end_date: toISODateString(end)
                };
                this.headerYear = currentYear;
            } else {
                this.selectedQuarter = null;
            }

            this.decadeStart = Math.floor(this.headerYear / 10) * 10;
            this.calculateGrid();
        },

        // ─── Constraints ───────────────────────────────────

        /**
         * Check if a specific quarter index (0-3) in the current headerYear is disabled.
         */
        isPeriodDisabled(quarterIndex) {
            const startMonth = quarterIndex * 3;
            const start = new Date(this.headerYear, startMonth, 1);
            const end = new Date(this.headerYear, startMonth + 3, 0); // Last day of quarter
            
            return this.isDateRangeDisabled(start, end);
        },

        /**
         * Helper to check date range against min/max constraints.
         */
        isDateRangeDisabled(start, end) {
            const pStart = startOfDay(start);
            const pEnd = endOfDay(end);

            if (this.minDate && pEnd < startOfDay(this.minDate)) return true;
            if (this.maxDate && pStart > endOfDay(this.maxDate)) return true;

            return false;
        },

        /**
         * Check if a year should be disabled in the year grid.
         */
        isYearDisabled(year) {
            const yearStart = startOfDay(new Date(year, 0, 1));
            const yearEnd = endOfDay(new Date(year, 11, 31));

            if (this.minDate && yearEnd < startOfDay(this.minDate)) return true;
            if (this.maxDate && yearStart > endOfDay(this.maxDate)) return true;

            return false;
        },

        // ─── Navigation ────────────────────────────────────

        /**
         * Check if navigation in a direction (-1 or 1) is allowed.
         */
        canNavigate(direction) {
            if (this.view === 'quarters') {
                const targetYear = this.headerYear + direction;
                return !this.isYearDisabled(targetYear);
            } 
            else if (this.view === 'years') {
                const targetDecadeStart = this.decadeStart + (direction * 10);
                
                // Effective check for decade overlap
                const targetDecadeEnd = targetDecadeStart + 9;
                const minYear = this.minDate ? this.minDate.getFullYear() : -Infinity;
                const maxYear = this.maxDate ? this.maxDate.getFullYear() : Infinity;

                return targetDecadeEnd >= minYear && targetDecadeStart <= maxYear;
            }

            return false;
        },

        /**
         * Navigate forward or backward based on the current view.
         */
        navigate(direction) {
            if (!this.canNavigate(direction)) return;

            if (this.view === 'quarters') {
                this.headerYear += direction;
            } 
            else if (this.view === 'years') {
                this.decadeStart += direction * 10;
            }

            this.calculateGrid();
        },

        // ─── View Management ───────────────────────────────

        /**
         * Cycle through views: quarters → years → quarters
         */
        cycleView() {
            if (this.view === 'quarters') {
                this.view = 'years';
            } else {
                this.view = 'quarters';
            }
            this.decadeStart = Math.floor(this.headerYear / 10) * 10;
            this.calculateGrid();
        },

        /**
         * Drill down from year view to quarters view.
         */
        drillDownYear(year) {
            if (this.isYearDisabled(year)) return;

            this.headerYear = year;
            this.decadeStart = Math.floor(year / 10) * 10;
            this.view = 'quarters';
            this.calculateGrid();
        },

        // ─── Selection ─────────────────────────────────────

        /**
         * Select a specific quarter and close the picker.
         */
        selectPeriod(quarterIndex) {
            if (this.isPeriodDisabled(quarterIndex)) return;

            const startMonth = quarterIndex * 3;
            const start = new Date(this.headerYear, startMonth, 1);
            const end = new Date(this.headerYear, startMonth + 3, 0);

            this.selectedQuarter = {
                start_date: toISODateString(start),
                end_date: toISODateString(end)
            };

            this.open = false;
            this.dispatchState();
        },

        // ─── Grid Calculation ──────────────────────────────

        /**
         * Recalculate grid data based on current header position.
         */
        calculateGrid() {
            this.decadeArray = Array.from({ length: 12 }, (_, i) => this.decadeStart - 1 + i);
        },

        // ─── Display Helpers ───────────────────────────────

        /**
         * Get the label for the navigation header.
         */
        getHeaderLabel() {
            if (this.view === 'quarters') {
                return `${this.headerYear}`;
            }
            return `${this.decadeStart} - ${this.decadeStart + 9}`;
        },

        /**
         * Check if a year matches the current header year.
         */
        isHeaderYear(year) {
            return this.headerYear === year;
        },

        /**
         * Check if a quarter index is currently selected.
         */
        isSelected(quarterIndex) {
            if (!this.selectedQuarter) return false;

            const selectedStart = new Date(this.selectedQuarter.start_date + 'T00:00:00');
            const selectedQIndex = Math.floor(selectedStart.getMonth() / 3);

            return selectedQIndex === quarterIndex && 
                   selectedStart.getFullYear() === this.headerYear;
        },

        // ─── Formatting & Dispatch ─────────────────────────

        /**
         * Format the selected period for display in the trigger button.
         * Example: "Q1 2024 (Jan - Mar)"
         */
        formatDisplayText() {
            if (!this.selectedQuarter) return 'Select a quarter';

            // Use T00:00:00 to prevent timezone shift issues
            const start = new Date(this.selectedQuarter.start_date + 'T00:00:00');
            
            const qNum = Math.floor(start.getMonth() / 3) + 1;
            const year = start.getFullYear();
            
            // Reconstruct the (Month - Month) string for display
            // Note: We could store this in the period object, but calculating it ensures sync
            const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            const startM = monthNames[start.getMonth()];
            const endM = monthNames[start.getMonth() + 2];

            return `Q${qNum} ${year} (${startM} - ${endM})`;
        },

        /**
         * Dispatch the selected period as a custom event.
         */
        dispatchState() {
            if (!this.selectedQuarter) return;

            this.$dispatch('date-selector-change', {
                type: 'quarter',
                identifier: this.identifier,
                start: this.selectedQuarter.start_date,
                end: this.selectedQuarter.end_date
            });
        }
    };
}
</script>