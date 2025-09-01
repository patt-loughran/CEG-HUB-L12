{{-- 
    This component expects a single prop: 'dateRanges'.
    The expected data structure is now an associative array:
    [
        '2024' => ['PP 27 (12/15 - 12/31)', 'PP26 (12/01 - 12/14)', ...],
        '2023' => [ ... ]
    ]
--}}
@props(['dateRanges'])

<div x-data='financeControlPanelLogic(@json($dateRanges))' class="bg-white p-6 border border-slate-300 rounded-lg shadow-sm w-full">
    <div class="space-y-6">

        <!-- Date Selection Section -->
        <div class="space-y-3">
           <!-- Header with Label -->
            <div class="flex justify-between items-center">
                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pay Period Selection</label>
            </div>
            <!-- Proportional Dropdowns Container -->
            <div class="grid grid-cols-4 gap-2">
                <!-- Year Dropdown (25% width) -->
                <select x-model="selectedYear" class="col-span-1 w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-500">
                    <template x-for="year in getAvailableYears()" :key="year">
                        <option x-bind:value="year" x-text="year"></option>
                    </template>
                </select>
                <!-- Date Range Dropdown (75% width) -->
                <select x-model="selectedDateRange" class="col-span-3 w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-500">
                    <template x-for="option in getDateRangeOptions()" :key="option">
                        <option x-bind:value="option" x-text="option"></option>
                    </template>
                </select>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="border-t border-slate-200 pt-4">
            <div class="flex justify-between items-center mb-3">
                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Quick Filters</label>
            </div>
            <div class="flex items-center gap-2">
                <template x-for="filter in ['active', 'hourly', 'salaried']" :key="filter">
                    <button
                        type="button"
                        @click="toggleFilter(filter)"
                        x-bind:class="activeFilters.includes(filter) 
                            ? 'bg-slate-700 text-white font-semibold' 
                            : 'bg-slate-200 text-slate-600 hover:bg-slate-300'"
                        class="px-3 py-1 text-sm rounded-full transition"
                        x-text="filter"
                    ></button>
                </template>
            </div>
        </div>
    </div>
</div>


{{-- The Alpine.js logic is pushed to the 'scripts' stack --}}
@push('scripts')
<script>
    function financeControlPanelLogic(dateRanges) {
        return {
            selectedYear:  null,
            selectedDateRange: null,
            activeFilters: null,
            isUpdating: null,

            init() {
                // Initialize Instance Variables
                this.selectedYear = new Date().getFullYear().toString();
                this.selectedDateRange = this.getDateRangeOptions()[0] || null;
                this.activeFilters = ["active"]
                this.isUpdating = false;

                // Set up watchers //

                // selectedYear Watcher
                this.$watch('selectedYear', () => {
                    this.isUpdating = true;
                    const newOptions = this.getDateRangeOptions();
                    this.selectedDateRange = newOptions[0] || null;
                    this.dispatchChangeEvent();
                    this.$nextTick(() => {
                        this.isUpdating = false;
                    });
                });

                // selectedDateRange Watcher   
                this.$watch('selectedDateRange', () => {
                    if (this.isUpdating) return; 

                    this.dispatchChangeEvent()
                });
                
                // activeFilters Watcher 
                this.$watch('activeFilters', () => this.dispatchChangeEvent());

                // Fire initial dispatch event with default values
                this.$nextTick(() => {
                    this.dispatchChangeEvent();
                });
            },

            getDateRangeOptions() {
                // MODIFIED: This now correctly returns the array directly for the selected year.
                return dateRanges[this.selectedYear] || [];
            },

            getAvailableYears() {
                return Object.keys(dateRanges).sort((a, b) => b - a); // Sort years descending
            },

            toggleFilter(filter) {
                const index = this.activeFilters.indexOf(filter);
                if (index === -1) {
                    this.activeFilters.push(filter);
                } else {
                    this.activeFilters.splice(index, 1);
                }
            },

            dispatchChangeEvent() {
                console.log("dispatched");
                console.log(this.selectedYear);
                console.log(this.selectedDateRange);
                console.log(this.activeFilters);
                this.$dispatch('control-panel-change', {
                    year: this.selectedYear,
                    dateRangeDropdown: this.selectedDateRange,
                    activeFilters: this.activeFilters
                });
            },
        }
    }
</script>
@endpush