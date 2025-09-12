{{--
    Expected data structure from the getData() endpoint:
    {
      "headerInfo": {
        "weekLabel": "Week 1",
        "payPeriodLabel": "of Pay Period (Aug 11 - Aug 24)"
      },
      "dateHeaders": [
        { "day": "Sun", "date": "08/11", "isWeekend": true },
        ... 6 more days
      ],
      "timesheetRows": [
        {
          "rowId": "unique_id_1",
          "project_code": "PROJ-456",
          "sub_project": "UPGRADE-SYS",
          "activity_code": "DEV-BACKEND",
          "is_pinned": true,
          "hours": [
            { "value": 0, "isWeekend": true },
            { "value": 7.5, "isWeekend": false },
            ... 5 more days
          ],
          "rowTotal": 37.0
        },
        ... more rows
      ],
      "footerTotals": {
        "dailyTotals": [0.0, 9.5, 8.0, 10.5, 11.0, 7.5, 4.0],
        "weeklyTotal": 50.5
      },
      "payPeriodTotal": 90.5
    }
--}}
<div x-data="timeDataBridgeLogic()"
     @timesheet-date-change.window="fetchData($event.detail)">
</div>

@push('scripts')
<script>
    function timeDataBridgeLogic() {
        return {
            isLoading: null,
            error: null,

            init() {
                this.isLoading = false;
                this.error = null;
            },

            async fetchData(payPeriodData) {
                // 1. Dispatch loading event immediately
                this.$dispatch('timesheet-data-loading');
                this.isLoading = true;
                this.error = null;

                try {
                    // 2. Send fetch request
                    const response = await fetch('/time/timesheet/data', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payPeriodData)
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.error || `Server responded with status: ${response.status}`);
                    }

                    const data = await response.json();

                    // 3. Dispatch success event
                    this.$dispatch('timesheet-data-updated', data);

                } catch (e) {
                    // 4. Dispatch error event
                    console.error("Timesheet fetch error:", e);
                    this.error = e.message;
                    this.$dispatch('timesheet-data-error', { message: this.error });
                } finally {
                    this.isLoading = false;
                }
            }
        }
    }
</script>
@endpush