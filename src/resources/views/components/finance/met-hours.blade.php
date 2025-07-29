<div x-data="submissionsWidgetLogic()" @table-data-updated.window="handleDataUpdate($event)" class="bg-white p-6 border border-slate-300 rounded-lg shadow-sm flex-1 flex flex-col min-h-[320px] overflow-hidden">

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

    <!-- Actual Content -->
    <template x-if="!isLoading">
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-slate-800 text-lg">Submissions</h3>
                <span class="text-sm font-semibold text-slate-600" x-text="`${getSubmittedCount()} / ${getTotalCount()}`"></span>
            </div>
            <p class="text-xs text-slate-500 mb-4" x-text="`Awaiting submission for ${payPeriodIdentifier}`"></p>

            <!-- Proportional Progress Bar -->
            <div class="w-full bg-red-100 rounded-full h-2 overflow-hidden">
                <div class="bg-green-500 h-2 rounded-full" x-bind:style="{ width: getProgressPercentage() + '%' }" x-bind:title="`${getProgressPercentage().toFixed(0)}% Submitted`"></div>
            </div>

            <!-- List Header -->
            <div class="flex justify-between items-baseline border-t border-slate-200 mt-4 pt-3 mb-2">
                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Awaiting Submission</label>
                <span class="text-xs font-medium px-2 py-0.5 bg-red-100 text-red-800 rounded-full" x-text="`${getAwaitingCount()} People`"></span>
            </div>

            <!-- Scrollable List -->
            <div class="flex-1 overflow-y-auto -mx-2">
                <ul class="px-2">
                    <template x-for="submission in getSortedSubmissions()" x-bind:key="submission.employee_id">
                        <li class="flex items-center justify-between py-2.5 px-2 rounded-md hover:bg-slate-50">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600" x-text="help_generate_initials(submission.employee_name)"></div>
                                <span class="text-slate-800 text-sm font-medium" x-text="submission.employee_name"></span>
                            </div>
                            <span class="text-sm font-medium" x-text="submission.total_hours.toFixed(2) + ' hrs'" x-bind:class="submission.total_hours < 80 ? 'text-red-500' : 'text-slate-500'"></span>
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
</div>

@push('scripts')
<script>
    function submissionsWidgetLogic() {
        return {
            // 1. Class/Instance Variables
            submissionsData: null,
            payPeriodIdentifier: null,
            isLoading: null,

            // 2. init()
            init() {
                this.isLoading = true;
            },

            // 3. otherFunctions()
            handleDataUpdate(event) {
                // Ensure the event has the data we need
                if (event.detail && event.detail.tableData) {
                    this.submissionsData = event.detail.tableData;
                    this.payPeriodIdentifier = event.detail.payPeriodIdentifier || 'N/A';
                    this.isLoading = false;
                }
            },

            getSortedSubmissions() {
                if (!this.submissionsData) return [];
                return [...this.submissionsData].sort((a, b) => a.total_hours - b.total_hours);
            },


            getSubmittedCount() {
                if (!this.submissionsData) return 0;
                return this.submissionsData.filter(s => s.total_hours >= 80).length;
            },

            getTotalCount() {
                if (!this.submissionsData) return 0;
                return this.submissionsData.length;
            },

            getAwaitingCount() {
                if (!this.submissionsData) return 0;
                return this.getTotalCount() - this.getSubmittedCount();
            },

            getProgressPercentage() {
                const total = this.getTotalCount();
                if (total === 0) return 0;
                const submitted = this.getSubmittedCount();
                return (submitted / total) * 100;
            }
        }
    }
</script>
@endpush