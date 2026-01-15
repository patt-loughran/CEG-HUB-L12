{{--
    This is a headless Blade component. It has no UI.

    - Listens for the 'finance-panel-change' event from the control panel.
    - When the event is caught, it performs a fetch() request to a Laravel route.
    - After fetching, it dispatches a new 'finance-data-updated' event
      with the payload received from the server.

      structure of data received:
      [
        'tableData' => [
            [
            'employee_name' => string
            'employee_id' => int,            
            'expected_billable' => bool,       
            'pto' => float,           
            'holiday' => float, 
            'other_200' => float,          
            'other_nb' => float,           
            'total_nb' => float,           
            'billable' => float,        
            'total_hours' => float,
            'billable_percentage' => float,    
            'overtime' => float,        
            ],
            // ... additional employee records
        ],
        'totalHours' => float,
        'totalOvertime' => float,                
        'averageBillablePercentage' => float,    // Average billable percentage across all employees weighted by hours
    ]
--}}
<div
    x-data="dataBridge()"
    x-on:control-panel-change.window="fetchData($event)"
    class="hidden"
></div>

@push('scripts')
<script>
function dataBridge() {
    return {
        /**
         * @param {CustomEvent} event The event dispatched from the control panel.
         */
        async fetchData(event) {
            this.$dispatch('payroll-data-loading');
            const userInputVariables = event.detail;

            try {
                // Perform a fetch request to our API endpoint
                const response = await fetch('/finance/payroll/getdata', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        // Laravel requires a CSRF token for POST requests
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(userInputVariables)
                });

                const payload = await response.json();
                // Dispatch a new event with the fetched data for other components
                this.$dispatch('payroll-data-updated', payload);

            } catch (error) {
                this.$dispatch('payroll-fetch-error', error.message);
            }
        }
    }
}
</script>
@endpush