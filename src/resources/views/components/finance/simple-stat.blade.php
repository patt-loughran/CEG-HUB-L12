{{--
    This is a "Display Component" for a single statistic.
    It is responsible for displaying a single value like Total Hours.

    It listens for events from the data-bridge component to update its state.
    - @payroll-data-loading.window: Shows a loading skeleton.
    - @payroll-data-updated.window: Receives data and displays the value.
    - @payroll-data-error.window: Shows an error state.

    Props:
    - title: The text label for the statistic (e.g., "Total Hours").
    - dataKey: The key to look for in the data payload (e.g., "totalHours").
    - format: How to format the value ('hours', 'percentage', 'number').
    - iconClasses: Tailwind CSS classes for the icon background and color.
    - iconPath: The SVG path data for the icon.
--}}
@props([
    'title',
    'dataKey',
    'format' => 'number',
    'iconClasses' => 'bg-slate-100 text-slate-700',
    'iconName'
])

<div x-data="simpleStatLogic('{{ $dataKey }}', '{{ $format }}')"
    @payroll-data-loading.window="handleFetchInitiated()"
    @payroll-data-updated.window="handleDataUpdate($event)"
    @payroll-data-error.window="handleError()"
    class="relative bg-white p-4 border border-slate-200 rounded-lg shadow-sm flex items-center gap-4 flex-1 min-w-[280px]">

    {{-- Main Content Area: Switches between Skeleton and Data view --}}
    <template x-if="isLoading">
        {{-- Loading Skeleton State --}}
        <div class="flex items-center gap-4 w-full animate-pulse">
            <div class="flex-shrink-0 w-16 h-16 bg-slate-200 rounded-lg"></div>
            <div class="flex-1 space-y-2">
                <div class="h-3 bg-slate-200 rounded w-3/4"></div>
                <div class="h-8 bg-slate-200 rounded w-1/2"></div>
            </div>
        </div>
    </template>

    <template x-if="!isLoading">
        {{-- Data/Error State --}}
        <div class="flex items-center gap-4 w-full">
            <!-- Left Icon -->
            <div x-bind:class="error ? 'bg-red-100 text-red-700' : '{{ $iconClasses }}'"
                 class="flex-shrink-0 w-16 h-16 rounded-lg flex items-center justify-center transition-colors">
                <x-general.icon :name="$iconName" class="h-10 w-10" />
            </div>
            <!-- Right Content -->
            <div class="flex-1">
                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $title }}</label>
                <div class="flex items-baseline gap-2 mt-1">
                    <p class="text-3xl font-bold text-slate-800" x-text="formattedValue()"></p>
                </div>
            </div>
        </div>
    </template>
    
    <!-- Top Right Link (Static for now) -->
    <a href="#" class="absolute top-3 right-2 p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 rounded-full" title="View historical chart">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
    </a>
</div>

@push('scripts')
<script>
    function simpleStatLogic(dataKey, format) {
        return {
            value: null,
            isLoading: null,
            error: null,

            init() {
                this.value = 0;
                this.isLoading = true; // Show skeleton on initial page load
                this.error = false;
            },
            
            // --- Helper Functions ---
            formattedValue() {
                if (this.error) {
                    return '--';
                }
                if (this.value === null) {
                    return '0';
                }

                switch (format) {
                    case 'hours':
                        return this.value.toFixed(1);
                    case 'percentage':
                        return `${this.value.toFixed(1)}%`;
                    case 'number':
                    default:
                        return this.value.toLocaleString('en-US');
                }
            },

            // --- Event Handlers from Data-Bridge ---
            handleFetchInitiated() {
                this.isLoading = true;
                this.error = false;
            },

            handleDataUpdate(event) {
                this.value = event.detail[dataKey] || 0;
                this.isLoading = false;
                this.error = false;
            },

            handleError() {
                this.isLoading = false;
                this.error = true;
            }
        }
    }
</script>
@endpush