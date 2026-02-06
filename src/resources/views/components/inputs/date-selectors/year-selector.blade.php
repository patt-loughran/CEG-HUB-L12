@props(['startDateCutOff' => null, 'endDateCutOff' => null, 'identifier' => null])

<div 
    x-data="yearSelector('{{ $startDateCutOff }}', '{{ $endDateCutOff }}', '{{ $identifier }}')"
    @click.outside="open = false"
    @report-current-state.window="if($event.detail === 'year') dispatchState()"
    class="relative"
>
    {{-- Trigger Button --}}
    <button 
        @click="open = !open" 
        type="button"
        class="flex h-10 w-[240px] items-center justify-start rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm ring-offset-white focus:outline-none focus:ring-2 focus:ring-slate-950"
        :class="!selectedYear ? 'text-slate-500' : 'text-slate-900'"
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
        class="absolute top-0 z-50 mt-12 w-[280px] rounded-md border border-slate-200 bg-white p-3 shadow-md text-slate-950 outline-none"
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
            
            {{-- Static Title Button (No drill-up needed, but keeps style consistency) --}}
            <button 
                type="button"
                class="flex-1 text-sm font-semibold cursor-default py-1 rounded-md text-slate-900"
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

        {{-- VIEW: Years --}}
        <div x-show="view === 'years'">
            <div class="grid grid-cols-4 gap-2">
                <template x-for="year in decadeArray" :key="year">
                    <button 
                        @click="selectPeriod(year)"
                        :disabled="isYearDisabled(year)"
                        type="button"
                        class="h-9 w-full rounded-md text-sm transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:bg-white disabled:text-slate-400"
                        :class="isSelected(year) 
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
function yearSelector(startDateCutOff, endDateCutOff, identifier) {

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
            return new Date("2021-06-01");
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
        view: 'years', // Only one view, but kept for consistency
        identifier: identifier,

        // Cursor/Browsing State
        headerYear: new Date(defaultDate).getFullYear(),
        decadeStart: Math.floor(new Date(defaultDate).getFullYear() / 10) * 10,

        // Selection
        selectedYear: null, // Object: { start_date, end_date }

        // Constraints
        minDate: startDateCutOff ? new Date(startDateCutOff) : null,
        maxDate: endDateCutOff ? new Date(endDateCutOff) : null,

        // Grid Data
        decadeArray: [],

        // ─── Initialization ────────────────────────────────

        init() {
            const referenceDate = new Date(defaultDate);
            const currentYear = referenceDate.getFullYear();

            // Set Header Year to referenceDate (or adjust to minDate)
            this.headerYear = currentYear;
            if (this.minDate && this.headerYear < this.minDate.getFullYear()) {
                this.headerYear = this.minDate.getFullYear();
            }

            // Create temporary dates for the current year to check validity
            const start = new Date(currentYear, 0, 1);
            const end = new Date(currentYear, 11, 31);

            // Auto-select if valid
            if (!this.isDateRangeDisabled(start, end)) {
                this.selectedYear = {
                    start_date: toISODateString(start),
                    end_date: toISODateString(end)
                };
                this.headerYear = currentYear;
            } else {
                this.selectedYear = null;
            }

            this.decadeStart = Math.floor(this.headerYear / 10) * 10;
            this.calculateGrid();
        },

        // ─── Constraints ───────────────────────────────────

        /**
         * Check if a specific year is outside allowed range.
         */
        isYearDisabled(year) {
            const start = new Date(year, 0, 1);
            const end = new Date(year, 11, 31);
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

        // ─── Navigation ────────────────────────────────────

        /**
         * Check if navigation in a direction (-1 or 1) is allowed.
         * For Year Selector, direction represents a decade jump.
         */
        canNavigate(direction) {
            const targetDecadeStart = this.decadeStart + (direction * 10);
            
            // Effective check for decade overlap
            // We check if the target decade grid (12 years) contains ANY valid years
            const targetDecadeEnd = targetDecadeStart + 9; // Visual decade end
            
            const minYear = this.minDate ? this.minDate.getFullYear() : -Infinity;
            const maxYear = this.maxDate ? this.maxDate.getFullYear() : Infinity;

            // Simple overlap check: 
            // Decade End >= MinYear AND Decade Start <= MaxYear
            return targetDecadeEnd >= minYear && targetDecadeStart <= maxYear;
        },

        /**
         * Navigate forward or backward.
         */
        navigate(direction) {
            if (!this.canNavigate(direction)) return;

            this.decadeStart += direction * 10;
            this.calculateGrid();
        },

        // ─── View Management ───────────────────────────────

        // No cycleView needed for Year selector, but function kept for structure
        cycleView() {},

        // ─── Selection ─────────────────────────────────────

        /**
         * Select a specific year and close the picker.
         */
        selectPeriod(year) {
            if (this.isYearDisabled(year)) return;

            const start = new Date(year, 0, 1);
            const end = new Date(year, 11, 31);

            this.selectedYear = {
                start_date: toISODateString(start),
                end_date: toISODateString(end)
            };

            // Keep the header year synced with selection
            this.headerYear = year; 

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
            return `${this.decadeStart} - ${this.decadeStart + 9}`;
        },

        /**
         * Check if a year is currently selected.
         */
        isSelected(year) {
            if (!this.selectedYear) return false;
            
            // Parse selected period year
            const selectedStart = new Date(this.selectedYear.start_date + 'T00:00:00');
            return selectedStart.getFullYear() === year;
        },

        // ─── Formatting & Dispatch ─────────────────────────

        /**
         * Format the selected period for display in the trigger button.
         */
        formatDisplayText() {
            if (!this.selectedYear) return 'Select a year';

            // Add time to prevent timezone shifts when parsing ISO string
            const start = new Date(this.selectedYear.start_date + 'T00:00:00');
            return start.getFullYear().toString();
        },

        /**
         * Dispatch the selected period as a custom event.
         */
        dispatchState() {
            if (!this.selectedYear) return;

            this.$dispatch('date-selector-change', {
                type: 'year',
                identifier: this.identifier,
                start: this.selectedYear.start_date,
                end: this.selectedYear.end_date
            });
        }
    };
}
</script>