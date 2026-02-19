{{--
    Data-Bridge: Headless component for the Employee Time Summary page.
    
    Listens for 'employee-time-change' from the User Input Component.
    
    Expected payload from User Input:
    {
        granularity: 'day' | 'pay_period' | 'month' | 'quarter' | 'year',
        start_date: <varies by granularity>,
        end_date:   <varies by granularity>
    }

    Expected response from Controller (getData):
    {
        summaryTable: { data: {...}, errors: null } | { data: null, errors: "..." },
        granularity: 'day' | 'pay-period' | 'month' | 'quarter' | 'year'
    }

    Responsibilities:
    1. Listen for 'employee-time-change' from the User Input Component.
    2. Immediately dispatch 'employee-time-data-loading' so display components show skeletons.
    3. POST to the Summary controller's getData() endpoint.
    4. On HTTP 200: dispatch 'employee-time-data-updated' with the full response payload.
    5. On network/server failure: dispatch 'employee-time-fetch-error' with an error message.
--}}

<div 
    x-data="timeEmployeeTimeSummaryDataBridgeLogic()"
    @employee-time-change.window="fetchData($event)"
    class="hidden"
    aria-hidden="true"
></div>

@push('scripts')
<script>
    function timeEmployeeTimeSummaryDataBridgeLogic() {
        return {
            // In-flight AbortController so we can cancel stale requests
            _abortController: null,

            /**
             * Maps frontend granularity values to the controller's expected values.
             * (The select uses underscores; the controller uses hyphens for pay-period.)
             */
            GRANULARITY_MAP: {
                'day':        'day',
                'pay_period': 'pay-period',
                'month':      'month',
                'quarter':    'quarter',
                'year':       'year',
            },

            /**
             * @param {CustomEvent} event The event dispatched from the User Input Component.
             */
            async fetchData(event) {
                // --- 1. Dispatch loading event SYNCHRONOUSLY before the fetch ---
                this.$dispatch('employee-time-data-loading');

                // --- 2. Map frontend values to backend-expected values ---
                const payload = event.detail;
                const granularity = this.GRANULARITY_MAP[payload.granularity] || payload.granularity;

                const requestBody = {
                    granularity: granularity,
                    start: payload.start_date,
                    end: payload.end_date,
                };

                // --- 3. Cancel any in-flight request ---
                if (this._abortController) {
                    this._abortController.abort();
                }
                this._abortController = new AbortController();

                // --- 4. Fetch data from the controller ---
                try {
                    const response = await fetch("{{ route('time.employee-time-summary.getData') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify(requestBody),
                        signal: this._abortController.signal,
                    });

                    if (!response.ok) {
                        throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();

                    // --- 5a. Dispatch data-updated with the full associative payload ---
                    window.dispatchEvent(new CustomEvent('employee-time-data-updated', {
                        detail: data,
                    }));

                } catch (error) {
                    // Ignore aborted requests (user triggered a new one)
                    if (error.name === 'AbortError') return;

                    console.error('Employee Time Summary fetch error:', error);

                    // --- 5b. Dispatch fetch-error for network/server failures ---
                    window.dispatchEvent(new CustomEvent('employee-time-fetch-error', {
                        detail: { message: error.message || 'An unexpected network error occurred.' },
                    }));
                }
            },
        };
    }
</script>
@endpush