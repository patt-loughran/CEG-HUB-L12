<div x-data="PPQuickListLogic()" 
     @payroll-data-loading.window="handlePayrollFetchInitiated($event)"
     @payroll-data-updated.window="handlePayrollDataUpdate($event)"
     @payroll-data-error.window="handlePayrollError($event)"
     class="bg-white p-6 border border-slate-300 rounded-lg shadow-sm flex-1 flex flex-col min-h-[320px] overflow-hidden">
    <!-- Actual Content -->
    <template x-if="!isLoading">
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-slate-800 text-lg">Pay-Period Hours</h3>
                <span class="text-sm font-semibold text-slate-600" x-text="`${getCompletedCount()} / ${getTotalCount()}`"></span>
            </div>
            <p class="text-xs text-slate-500 mb-4" x-text="`Data for ${payPeriodIdentifier}`"></p>

            <!-- Proportional Progress Bar -->
            <div class="w-full bg-red-100 rounded-full h-2 overflow-hidden">
                <div class="bg-green-500 h-2 rounded-full" x-bind:style="{ width: getProgressPercentage() + '%' }" x-bind:title="`${getProgressPercentage().toFixed(0)}% Submitted`"></div>
            </div>

            <!-- List Header -->
            <div class="flex justify-between items-baseline border-t border-slate-200 mt-4 pt-3 mb-2">
                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Needs Hours</label>
                <span class="text-xs font-medium px-2 py-0.5 bg-red-100 text-red-800 rounded-full" x-text="`${getAwaitingCount()} People`"></span>
            </div>

            <!-- Scrollable List -->
            <div class="flex-1 overflow-y-auto -mx-2">
                <ul class="px-2">
                    <template x-for="employeeHoursData in getSortedEmployeeHoursData()" x-bind:key="employeeHoursData.employee_id">
                        <li class="flex items-center justify-between py-2.5 px-2 rounded-md hover:bg-slate-50">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600" x-text="help_generate_initials(employeeHoursData.employee_name)"></div>
                                <span class="text-slate-800 text-sm font-medium" x-text="employeeHoursData.employee_name"></span>
                            </div>
                            <span class="text-sm font-medium" x-text="employeeHoursData.total_hours.toFixed(2) + ' hrs'" x-bind:class="employeeHoursData.total_hours < 80 ? 'text-red-500' : 'text-slate-500'"></span>
                        </li>
                    </template>
                </ul>
            </div>

            <!-- Action Button -->
            <div class="mt-auto pt-4 border-t border-slate-200">
                <button class="w-full text-center px-4 py-2 text-sm font-semibold text-white bg-slate-700 rounded-md hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                    Send Reminders
                </button>
            </div>
        </div>
    </template>

    <!-- Error State -->
    <template x-if="!isLoading && error">
        <div class="flex flex-col items-center justify-center h-full text-center text-slate-500">
            <div class="w-12 h-12 flex items-center justify-center bg-red-100 rounded-full mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="font-bold text-slate-800 text-lg">Request Failed</h3>
            <p class="text-sm mt-1 mb-4">Could not retrieve data from the server.</p>
            <p class="text-xs font-mono text-red-700 bg-red-50 p-2 rounded-md" x-text="error"></p>
        </div>
    </template>

        <!-- Skeleton Loader -->
    <template x-if="isLoading">
        <div class="animate-pulse flex-1 flex flex-col">
            <!-- Header Skeleton -->
            <div class="flex justify-between items-center">
                <div class="h-6 w-1/3 bg-slate-200 rounded"></div>
                <div class="h-5 w-1/6 bg-slate-200 rounded"></div>
            </div>
            <div class="h-3 w-2/5 bg-slate-200 rounded mt-2 mb-4"></div>

            <!-- Progress Bar Skeleton -->
            <div class="w-full bg-slate-200 rounded-full h-2"></div>

            <!-- List Header Skeleton -->
            <div class="flex justify-between items-baseline border-t border-slate-200 mt-4 pt-3 mb-2">
                <div class="h-4 w-1/4 bg-slate-200 rounded"></div>
                <div class="h-4 w-1/5 bg-slate-200 rounded"></div>
            </div>

            <!-- List Skeleton -->
            <div class="flex-1 overflow-y-auto -mx-2">
                <ul class="px-2">
                    <li class="flex items-center justify-between py-2.5 px-2">
                        <div class="flex items-center gap-3 w-full">
                            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-slate-200"></div>
                            <div class="h-4 bg-slate-200 rounded w-1/2"></div>
                        </div>
                        <div class="h-4 bg-slate-200 rounded w-1/4"></div>
                    </li>
                    <li class="flex items-center justify-between py-2.5 px-2">
                        <div class="flex items-center gap-3 w-full">
                            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-slate-200"></div>
                            <div class="h-4 bg-slate-200 rounded w-2/3"></div>
                        </div>
                        <div class="h-4 bg-slate-200 rounded w-1/6"></div>
                    </li>
                    <li class="flex items-center justify-between py-2.5 px-2">
                        <div class="flex items-center gap-3 w-full">
                            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-slate-200"></div>
                            <div class="h-4 bg-slate-200 rounded w-1/2"></div>
                        </div>
                        <div class="h-4 bg-slate-200 rounded w-1/4"></div>
                    </li>
                     <li class="flex items-center justify-between py-2.5 px-2">
                        <div class="flex items-center gap-3 w-full">
                            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-slate-200"></div>
                            <div class="h-4 bg-slate-200 rounded w-1/2"></div>
                        </div>
                        <div class="h-4 bg-slate-200 rounded w-1/4"></div>
                    </li>
                </ul>
            </div>
             <!-- Button Skeleton -->
            <div class="mt-auto pt-4 border-t border-slate-200">
                <div class="h-9 w-full bg-slate-200 rounded-md"></div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
    function PPQuickListLogic() {
        return {
            // 1. Class/Instance Variables
            employeeHoursData: null,
            payPeriodIdentifier: null,
            isLoading: null,
            error: null,

            // 2. init()
            init() {
                this.isLoading = true;
            },

            // 3. otherFunctions()
            handlePayrollDataUpdate(event) {
                // Ensure the event has the data we need
                if (event.detail && event.detail.tableData) {
                    this.employeeHoursData = event.detail.tableData;
                    this.payPeriodIdentifier = event.detail.payPeriodIdentifier || 'N/A';
                    this.isLoading = false;
                }
            },

            getSortedEmployeeHoursData() {
                if (!this.employeeHoursData) return [];
                return [...this.employeeHoursData].sort((a, b) => a.total_hours - b.total_hours);
            },


            getCompletedCount() {
                if (!this.employeeHoursData) return 0;
                return this.employeeHoursData.filter(s => s.total_hours >= 80).length;
            },

            getTotalCount() {
                if (!this.employeeHoursData) return 0;
                return this.employeeHoursData.length;
            },

            getAwaitingCount() {
                if (!this.employeeHoursData) return 0;
                return this.getTotalCount() - this.getCompletedCount();
            },

            getProgressPercentage() {
                const total = this.getTotalCount();
                if (total === 0) return 0;
                const completed = this.getCompletedCount();
                return (completed / total) * 100;
            },

            handlePayrollFetchInitiated() {
                this.isLoading = true;
                this.error = null; // Clear previous errors
            },

            handlePayrollError(event) {
                this.isLoading = false;
                this.employeeHoursData = null; // Clear any stale data
                this.error = event.detail?.message || 'An unknown error occurred.';
            },

        }
    }
</script>
@endpush