@props(['dateNavigatorData' => []])

{{-- 
    This is a dedicated User Input component.
    Its SOLE responsibility is to manage the date selection UI and
    dispatch a 'timesheet-date-change' event when the user selects a new week.
    It does not know about or display any other timesheet data.
--}}
<div x-data='timeTimesheetDateNavigatorLogic(@json($dateNavigatorData))'>
    <div class="flex items-center rounded-md border border-slate-300 shadow-sm">
        <button @click="navigateWeek(-1)" title="Previous Week" class="rounded-l-md border-r border-slate-300 p-2 text-slate-500 hover:bg-slate-100">
            <x-general.icon name="leftChevron" class="w-5 h-5 text-slate-500" />
        </button>
        
        <div class="relative">
            <button @click="showWeekSelector = !showWeekSelector" class="flex w-40 items-center justify-between bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <span x-text="selectedWeek() ? selectedWeek().weekLabel : 'Select Week'"></span>
                <x-general.icon name="downChevron" class="w-5 h-5 text-slate-500" />
            </button>
            
            <div x-show="showWeekSelector" 
                 @click.away="showWeekSelector = false" 
                 class="absolute z-10 mt-1 w-full rounded-md bg-white shadow-lg border border-slate-300 h-96 overflow-y-auto">
                
                <!-- Loop through each Pay Period -->
                <template x-for="(period, periodIndex) in payPeriodNavData" :key="periodIndex">
                    <!-- This div wraps the two weeks of a pay period -->
                    <div class="py-1" 
                        :class="{ 'border-b border-slate-200': periodIndex < payPeriodNavData.length - 1 }">
                        <!-- Loop through the weeks within the period -->
                        <template x-for="(week, weekIndex) in period.weeks" :key="weekIndex">
                            <a href="#" @click.prevent="selectWeek(periodIndex, weekIndex)" x-bind:class="{ 'bg-blue-100': isWeekSelected(week) }" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" x-text="week.weekLabel"></a>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <button @click="navigateWeek(1)" title="Next Week" class="rounded-r-md border-l border-slate-300 p-2 text-slate-500 hover:bg-slate-100">
            <x-general.icon name="rightChevron" class="w-5 h-5 text-slate-500" />
        </button>
    </div>
</div>

@push('scripts')
<script>
    /**
     * dateNavigatorData structure:
     * [
     *   {
     *     "payPeriodLabel": "Jun 8 - Jun 21",
     *     "weeks": [
     *       {
     *         "weekLabel": "Jun 8 - Jun 14",
     *         "startDate": "2025-06-08",
     *         "endDate": "2025-06-14"
     *       },
     *       {
     *         "weekLabel": "Jun 15 - Jun 21", 
     *         "startDate": "2025-06-15",
     *         "endDate": "2025-06-21"
     *       }
     *     ]
     *   },
     *   {
     *     "payPeriodLabel": "Jun 22 - Jul 5",
     *     "weeks": [
     *       // ... 2 weeks per pay period
     *     ]
     *   }
     *   // ... continues for remaining pay periods
     * ]
     */
    function timeTimesheetDateNavigatorLogic(dateNavigatorData) {
        return {
            // 1) Class/Instance Variables
            payPeriodNavData: null,
            selectedPayPeriodIndex: null,
            selectedWeekIndex: null,
            showWeekSelector: null,

            // 2) init()
            init() {
                // 2a) Initialize class/instance variables
                this.payPeriodNavData = dateNavigatorData;
                console.log(dateNavigatorData);
                this.showWeekSelector = false;
                
                this.setDefaultWeek();
                
                // 2b) Fire initial dispatch event with default values
                this.$nextTick(() => {
                    this.dispatchChangeEvent();
                });
            },

            // 3) Other Functions
            setDefaultWeek() {
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                for (let i = 0; i < this.payPeriodNavData.length; i++) {
                    for (let j = 0; j < this.payPeriodNavData[i].weeks.length; j++) {
                        const week = this.payPeriodNavData[i].weeks[j];
                        const start = new Date(week.startDate + 'T00:00:00');
                        const end = new Date(week.endDate + 'T00:00:00');
                        if (today >= start && today <= end) {
                            this.selectedPayPeriodIndex = i;
                            this.selectedWeekIndex = j;
                            return;
                        }
                    }
                }
                // Fallback to the middle-most pay period if today is out of range
                this.selectedPayPeriodIndex = Math.floor(this.payPeriodNavData.length / 2);
                this.selectedWeekIndex = 0;
            },

            selectedWeek() {
                if (this.selectedPayPeriodIndex !== null && this.selectedWeekIndex !== null) {
                    if (this.payPeriodNavData[this.selectedPayPeriodIndex]) {
                        return this.payPeriodNavData[this.selectedPayPeriodIndex].weeks[this.selectedWeekIndex];
                    }
                }
                return null;
            },

            navigateWeek(direction) {
                let currentWeek = this.selectedWeekIndex;
                let currentPeriod = this.selectedPayPeriodIndex;

                currentWeek += direction;

                if (currentWeek < 0) {
                    currentPeriod--;
                    currentWeek = 1; // Go to the second week of the previous period
                } else if (currentWeek > 1) {
                    currentPeriod++;
                    currentWeek = 0; // Go to the first week of the next period
                }

                if (this.payPeriodNavData[currentPeriod] && this.payPeriodNavData[currentPeriod].weeks[currentWeek]) {
                    this.selectedPayPeriodIndex = currentPeriod;
                    this.selectedWeekIndex = currentWeek;
                    this.dispatchChangeEvent();
                }
            },

            selectWeek(periodIndex, weekIndex) {
                this.selectedPayPeriodIndex = periodIndex;
                this.selectedWeekIndex = weekIndex;
                this.showWeekSelector = false;
                this.dispatchChangeEvent();
            },

            isWeekSelected(week) {
                const currentSelection = this.selectedWeek();
                return currentSelection && currentSelection.startDate === week.startDate;
            },
            
            // 4) dispatchChangeEvent()
            dispatchChangeEvent() {
                const week = this.selectedWeek();
                if (!week) return;

                const payPeriod = this.payPeriodNavData[this.selectedPayPeriodIndex];
                // Determine the week number based on the index (0 or 1)
                const weekNum = this.selectedWeekIndex === 0 ? 'Week 1' : 'Week 2';
                
                this.$dispatch('timesheet-date-change', {
                    // Week-specific data
                    startDate: week.startDate,
                    endDate: week.endDate,
                    weekNum: weekNum,
                    payPeriodLabel: payPeriod.payPeriodLabel,
                });
            },
        }
    }
</script>
@endpush