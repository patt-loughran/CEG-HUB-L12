@props(['startDateCutOff' => null, 'endDateCutOff' => null, 'identifier' => null])

<div 
    x-data="daySelector('{{ $startDateCutOff }}', '{{ $endDateCutOff }}', '{{ $identifier }}')" 
    @click.outside="open = false"
    @report-current-state.window="if($event.detail === 'day') dispatchState()"
    class="relative"
>
    {{-- Trigger Button --}}
    <button 
        @click="open = !open"
        type="button"
        class="flex h-10 w-[240px] items-center justify-start rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm ring-offset-white focus:outline-none focus:ring-2 focus:ring-slate-950"
        :class="!selectedDate ? 'text-slate-500' : 'text-slate-900'"
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
        class="absolute top-0 z-50 mt-12 w-[280px] p-3 rounded-md border border-slate-200 bg-white shadow-md text-slate-950"
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
                class="flex-1 text-sm font-semibold hover:bg-slate-100 py-1 rounded-md transition-colors"
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

        {{-- VIEW: Days --}}
        <div x-show="view === 'days'">
            <div class="grid grid-cols-7 mb-2 text-center">
                <template x-for="day in ['Su','Mo','Tu','We','Th','Fr','Sa']">
                    <div class="text-[0.8rem] text-slate-500 font-medium" x-text="day"></div>
                </template>
            </div>
            <div class="grid grid-cols-7 gap-1">
                <template x-for="i in firstDayOfMonth"><div></div></template>
                
                <template x-for="day in daysInMonth">
                    <button 
                        @click="selectDate(day)"
                        :disabled="isDayDisabled(day)"
                        type="button"
                        class="h-8 w-8 rounded-md text-sm flex items-center justify-center transition-colors disabled:text-slate-300 disabled:cursor-not-allowed disabled:hover:bg-white"
                        :class="isSelected(day) ? 'bg-slate-900 text-white hover:bg-slate-900' : 'text-slate-900 hover:bg-slate-100'"
                        x-text="day"
                    ></button>
                </template>
            </div>
        </div>

        {{-- VIEW: Months --}}
        <div x-show="view === 'months'">
            <div class="grid grid-cols-3 gap-2">
                <template x-for="(month, index) in monthNames">
                    <button 
                        @click="drillDownMonth(index)"
                        :disabled="isMonthDisabled(index)"
                        type="button"
                        class="h-9 w-full rounded-md text-sm transition-colors disabled:text-slate-300 disabled:cursor-not-allowed disabled:hover:bg-white"
                        :class="isCurrentMonth(index) ? 'bg-slate-900 text-white' : 'hover:bg-slate-100 text-slate-900'"
                        x-text="month.substring(0, 3)"
                    ></button>
                </template>
            </div>
        </div>

        {{-- VIEW: Years --}}
        <div x-show="view === 'years'">
            <div class="grid grid-cols-4 gap-2">
                <template x-for="year in decadeArray">
                    <button 
                        @click="drillDownYear(year)"
                        :disabled="isYearDisabled(year)"
                        type="button"
                        class="h-9 w-full rounded-md text-sm transition-colors disabled:text-slate-300 disabled:cursor-not-allowed disabled:hover:bg-white"
                        :class="isCurrentYear(year) ? 'bg-slate-900 text-white' : 'hover:bg-slate-100 text-slate-900'"
                        x-text="year"
                    ></button>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function daySelector(startDateCutOff, endDateCutOff, identifier) {

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
            if (currentDay <= 14) {
                // First day of previous month
                return new Date(today.getFullYear(), today.getMonth() - 1, 1);
            } else {
                // First day of current month
                return new Date(today.getFullYear(), today.getMonth(), 1);
            }
        }
        else if (identifier === "end") {
            if (currentDay <= 14) {
                // Last day of previous month
                return new Date(today.getFullYear(), today.getMonth(), 0);
            } else {
                // Last day of current month
                return new Date(today.getFullYear(), today.getMonth() + 1, 0);
            }
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
        view: 'days',
        identifier: identifier,

        defaultDate: defaultDate,

        // Cursor/Browsing State
        headerYear: new Date(defaultDate).getFullYear(),
        headerMonth: new Date(defaultDate).getMonth(),
        decadeStart: Math.floor(new Date(defaultDate).getFullYear() / 10) * 10,

        // Selection
        selectedDate: defaultDate ? new Date(defaultDate) : new Date(),

        // Constraints
        minDate: startDateCutOff ? new Date(startDateCutOff) : null,
        maxDate: endDateCutOff ? new Date(endDateCutOff) : null,

        // Grid Data
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 
                     'July', 'August', 'September', 'October', 'November', 'December'],
        daysInMonth: [],
        firstDayOfMonth: 0,
        decadeArray: [],

        // ─── Initialization ────────────────────────────────

        init() {
            const referenceDate = defaultDate ? new Date(defaultDate) : new Date();

            // If referenceDate is outside constraints, adjust browsing position
            if (this.minDate && referenceDate < this.minDate) {
                this.headerYear = this.minDate.getFullYear();
                this.headerMonth = this.minDate.getMonth();
                this.selectedDate = null;
            } else if (this.maxDate && referenceDate > this.maxDate) {
                this.headerYear = this.maxDate.getFullYear();
                this.headerMonth = this.maxDate.getMonth();
                this.selectedDate = null;
            }

            this.decadeStart = Math.floor(this.headerYear / 10) * 10;
            this.calculateGrid();
        },

        // ─── Constraints ───────────────────────────────────

        /**
         * Check if a specific day in the current month is outside the allowed range.
         */
        isDayDisabled(day) {
            const dateToCheck = startOfDay(new Date(this.headerYear, this.headerMonth, day));

            if (this.minDate && dateToCheck < startOfDay(this.minDate)) return true;
            if (this.maxDate && dateToCheck > startOfDay(this.maxDate)) return true;

            return false;
        },

        /**
         * Check if an entire month is outside the allowed range.
         */
        isMonthDisabled(monthIndex) {
            const monthStart = startOfDay(new Date(this.headerYear, monthIndex, 1));
            const monthEnd = endOfDay(new Date(this.headerYear, monthIndex + 1, 0));

            if (this.minDate && monthEnd < startOfDay(this.minDate)) return true;
            if (this.maxDate && monthStart > endOfDay(this.maxDate)) return true;

            return false;
        },

        /**
         * Check if an entire year is outside the allowed range.
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
            if (this.view === 'days') {
                // Calculate target month with rollover
                let targetYear = this.headerYear;
                let targetMonth = this.headerMonth + direction;

                if (targetMonth > 11) {
                    targetMonth = 0;
                    targetYear++;
                } else if (targetMonth < 0) {
                    targetMonth = 11;
                    targetYear--;
                }

                if (direction === 1 && this.maxDate) {
                    const firstOfTarget = startOfDay(new Date(targetYear, targetMonth, 1));
                    return firstOfTarget <= this.maxDate;
                }
                if (direction === -1 && this.minDate) {
                    const lastOfTarget = endOfDay(new Date(targetYear, targetMonth + 1, 0));
                    return lastOfTarget >= this.minDate;
                }
            } 
            else if (this.view === 'months') {
                const targetYear = this.headerYear + direction;

                if (direction === 1 && this.maxDate) {
                    return targetYear <= this.maxDate.getFullYear();
                }
                if (direction === -1 && this.minDate) {
                    return targetYear >= this.minDate.getFullYear();
                }
            } 
            else if (this.view === 'years') {
                const targetDecadeStart = this.decadeStart + (direction * 10);

                if (direction === 1 && this.maxDate) {
                    return targetDecadeStart <= this.maxDate.getFullYear();
                }
                if (direction === -1 && this.minDate) {
                    return (targetDecadeStart + 9) >= this.minDate.getFullYear();
                }
            }

            return true;
        },

        /**
         * Navigate forward or backward based on the current view.
         */
        navigate(direction) {
            if (!this.canNavigate(direction)) return;

            if (this.view === 'days') {
                this.headerMonth += direction;

                // Handle month rollover
                if (this.headerMonth > 11) {
                    this.headerMonth = 0;
                    this.headerYear++;
                } else if (this.headerMonth < 0) {
                    this.headerMonth = 11;
                    this.headerYear--;
                }
            } 
            else if (this.view === 'months') {
                this.headerYear += direction;
            } 
            else if (this.view === 'years') {
                this.decadeStart += direction * 10;
            }

            this.calculateGrid();
        },

        // ─── View Management ───────────────────────────────

        /**
         * Cycle through views: days → months → years → days
         */
        cycleView() {
            if (this.view === 'days') {
                this.view = 'months';
            } else if (this.view === 'months') {
                this.view = 'years';
            } else {
                this.view = 'days';
            }
            this.calculateGrid();
        },

        /**
         * Drill down from year view to month view.
         */
        drillDownYear(year) {
            if (this.isYearDisabled(year)) return;

            this.headerYear = year;
            this.decadeStart = Math.floor(year / 10) * 10;
            this.view = 'months';
            this.calculateGrid();
        },

        /**
         * Drill down from month view to day view.
         */
        drillDownMonth(monthIndex) {
            if (this.isMonthDisabled(monthIndex)) return;

            this.headerMonth = monthIndex;
            this.view = 'days';
            this.calculateGrid();
        },

        // ─── Selection ─────────────────────────────────────

        /**
         * Select a specific day and close the picker.
         */
        selectDate(day) {
            if (this.isDayDisabled(day)) return;

            this.selectedDate = new Date(this.headerYear, this.headerMonth, day);
            this.open = false;
            this.dispatchState();
        },

        // ─── Grid Calculation ──────────────────────────────

        /**
         * Recalculate grid data based on current header position.
         */
        calculateGrid() {
            // Days in current month
            const numDays = new Date(this.headerYear, this.headerMonth + 1, 0).getDate();
            this.daysInMonth = Array.from({ length: numDays }, (_, i) => i + 1);

            // First day of month (0 = Sunday, 6 = Saturday)
            this.firstDayOfMonth = new Date(this.headerYear, this.headerMonth, 1).getDay();

            // Decade array (12 years: 1 before, 10 during, 1 after)
            this.decadeArray = Array.from({ length: 12 }, (_, i) => this.decadeStart - 1 + i);
        },

        // ─── Display Helpers ───────────────────────────────

        /**
         * Get the label for the navigation header.
         */
        getHeaderLabel() {
            if (this.view === 'days') {
                return `${this.monthNames[this.headerMonth]} ${this.headerYear}`;
            }
            if (this.view === 'months') {
                return `${this.headerYear}`;
            }
            return `${this.decadeStart} - ${this.decadeStart + 9}`;
        },

        /**
         * Check if a day is currently selected.
         */
        isSelected(day) {
            if (!this.selectedDate) return false;
            return this.selectedDate.getDate() === day &&
                   this.selectedDate.getMonth() === this.headerMonth &&
                   this.selectedDate.getFullYear() === this.headerYear;
        },

        /**
         * Check if a month contains the selected date.
         */
        isCurrentMonth(monthIndex) {
            if (!this.selectedDate) return false;
            return this.selectedDate.getMonth() === monthIndex &&
                   this.selectedDate.getFullYear() === this.headerYear;
        },

        /**
         * Check if a year contains the selected date.
         */
        isCurrentYear(year) {
            if (!this.selectedDate) return false;
            return this.selectedDate.getFullYear() === year;
        },

        // ─── Formatting & Dispatch ─────────────────────────

        /**
         * Format the selected date for display in the trigger button.
         */
        formatDisplayText() {
            if (!this.selectedDate) return 'Select a date';
            return this.selectedDate.toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });
        },

        /**
         * Dispatch the selected date as a custom event.
         */
        dispatchState() {
            if (!this.selectedDate) return;

            const isoDate = toISODateString(this.selectedDate);

            this.$dispatch('date-selector-change', {
                type: 'day',
                identifier: this.identifier,
                start: isoDate,
                end: isoDate
            });
        }
    };
}
</script>