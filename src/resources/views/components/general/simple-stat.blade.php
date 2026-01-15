{{--
    This is a "Display Component" for a single statistic.
    It is responsible for displaying a single value like Total Hours.

    It listens for events from a data-provider component to update its state.
    The events it listens to are defined by the `eventPrefix` prop.
    - `{{eventPrefix}}-data-loading`: Shows a loading skeleton.
    - `{{eventPrefix}}-data-updated`: Receives data and displays the value.
    - `{{eventPrefix}}-fetch-error`: Shows an error state.

      Props:
    - title: The text label for the statistic.
    - dataKey: The key to look for in the data payload.
    - format: How to format the value ('hours', 'percentage', 'number', 'string').
    - iconClasses: Tailwind CSS classes for the icon (used when condition passes).
    - iconName: The name of the icon to use.
    - eventPrefix: The prefix for the window events to listen for.
    - successCondition: JSON object defining when to show success state (optional).
    - failClasses: Classes to use when condition fails (optional).
--}}
@props([
    'title',
    'dataKey',
    'eventPrefix', 
    'format' => 'number',
    'iconClasses' => 'bg-slate-100 text-slate-700',
    'iconName',
    'successCondition' => null,
    'failClasses' => 'bg-red-100 text-red-700'
])

{{-- HTML Structure --}}
<div x-data="simpleStatLogic('{{ $dataKey }}', '{{ $format }}', {{ $successCondition ? $successCondition : 'null' }})"
    x-on:{{ $eventPrefix }}-data-loading.window="handleFetchInitiated()"
    x-on:{{ $eventPrefix }}-data-updated.window="handleDataUpdate($event)"
    x-on:{{ $eventPrefix }}-fetch-error.window="handleFetchError()"
    class="relative bg-white p-4 border border-slate-200 rounded-lg shadow-sm flex items-center gap-4 flex-1 min-w-[280px]">

    {{-- Skeleton State --}}
    <template x-if="isLoading">
        <div class="flex items-center gap-4 w-full animate-pulse">
            <div class="flex-shrink-0 w-16 h-16 bg-slate-200 rounded-lg"></div>
            <div class="flex-1 space-y-2">
                <div class="h-3 bg-slate-200 rounded w-3/4"></div>
                <div class="h-8 bg-slate-200 rounded w-1/2"></div>
            </div>
        </div>
    </template>

    {{-- Data State --}}
    <template x-if="!isLoading">
        <div class="flex items-center gap-4 w-full">
            <div x-bind:class="getIconClasses('{{ $iconClasses }}', '{{ $failClasses }}')"
                 class="flex-shrink-0 w-16 h-16 rounded-lg flex items-center justify-center transition-colors">
                <template x-if="error">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-8 w-8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                </template>
                <template x-if="!error">
                    <x-general.icon :name="$iconName" class="h-10 w-10" />
                </template>
            </div>
            
            <div class="flex-1">
                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $title }}</label>
                <div class="flex items-baseline gap-2 mt-1">
                    <p class="text-3xl font-bold text-slate-800" x-text="formattedValue()"></p>
                </div>
            </div>
        </div>
    </template>
    
    <a href="#" class="absolute top-3 right-2 p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 rounded-full">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
    </a>
</div>

{{-- Script Logic --}}
@once
    @push('scripts')
    <script>
        function simpleStatLogic(dataKey, format, successCondition) {
            return {
                value: null,
                isLoading: null,
                error: null,
                condition: successCondition,

                init() {
                    this.value = format === 'string' ? '' : 0;
                    this.isLoading = true;
                    this.error = false;
                },

                formattedValue() {
                    if (this.error) return '--';
                    if (this.value === null || this.value === undefined) {
                        return format === 'string' ? '' : '0';
                    }
                    
                    switch (format) {
                        case 'hours': return Number(this.value).toFixed(2);
                        case 'percentage': return `${Number(this.value).toFixed(1)}%`;
                        case 'string': return String(this.value);
                        case 'number': default: return Number(this.value).toLocaleString('en-US');
                    }
                },

                /**
                 * Evaluates whether the current value passes the success condition.
                 * 
                 * Supported condition types:
                 * - { type: 'equals', value: 'submitted' }        → string/number equals
                 * - { type: 'not_equals', value: 'pending' }      → string/number not equals
                 * - { type: 'in', values: ['a', 'b', 'c'] }       → value is in array
                 * - { type: 'not_in', values: ['x', 'y'] }        → value is not in array
                 * - { type: 'min', value: 80 }                    → number >= value
                 * - { type: 'max', value: 100 }                   → number <= value
                 * - { type: 'range', min: 0, max: 40 }            → number between min and max (inclusive)
                 * - { type: 'gt', value: 0 }                      → number > value
                 * - { type: 'lt', value: 100 }                    → number < value
                 */
                evaluateCondition() {
                    if (!this.condition) return true; // No condition = always success
                    
                    const val = this.value;
                    const c = this.condition;
                    
                    switch (c.type) {
                        // String/exact comparisons
                        case 'equals':
                            return String(val).toLowerCase() === String(c.value).toLowerCase();
                        case 'not_equals':
                            return String(val).toLowerCase() !== String(c.value).toLowerCase();
                        case 'in':
                            return c.values.map(v => String(v).toLowerCase()).includes(String(val).toLowerCase());
                        case 'not_in':
                            return !c.values.map(v => String(v).toLowerCase()).includes(String(val).toLowerCase());
                        
                        // Numeric comparisons
                        case 'min':
                            return Number(val) >= Number(c.value);
                        case 'max':
                            return Number(val) <= Number(c.value);
                        case 'range':
                            return Number(val) >= Number(c.min) && Number(val) <= Number(c.max);
                        case 'gt':
                            return Number(val) > Number(c.value);
                        case 'lt':
                            return Number(val) < Number(c.value);
                        
                        default:
                            console.warn(`Unknown condition type: ${c.type}`);
                            return true;
                    }
                },

                getIconClasses(successClasses, failClasses) {
                    if (this.error) return 'bg-red-100 text-red-700';
                    return this.evaluateCondition() ? successClasses : failClasses;
                },

                handleFetchInitiated() {
                    this.isLoading = true;
                    this.error = false;
                },

                handleDataUpdate(event) {
                    const responseObj = event.detail[dataKey];

                    if (!responseObj) {
                        console.warn(`Stat Component: Key '${dataKey}' missing in payload`);
                        this.handleFetchError(); 
                        return;
                    }

                    if (responseObj.errors) {
                        this.isLoading = false;
                        this.error = true;
                        return;
                    }

                    this.value = responseObj.data;
                    this.isLoading = false;
                    this.error = false;
                },

                handleFetchError() {
                    this.isLoading = false;
                    this.error = true;
                }
            }
        }
    </script>
    @endpush
@endonce