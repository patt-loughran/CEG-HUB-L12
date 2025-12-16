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
     "dropdownData": {
        "PROJ001": {
          "project_name": "Project Alpha",
          "sub_projects": {
            "Phase 1": ["ACT001", "ACT002", "ACT003"],
            "Phase 2": ["ACT004", "ACT005"]
          }
        },
        "PROJ002": {
          "project_name": "Project Beta",
          "sub_projects": {
            "Design": ["ACT010"],
            "Development": ["ACT011", "ACT012"]
          }
        }
      },
    }
--}}
<div x-data="timeDataBridgeLogic()"
     @timesheet-date-change.window="fetchData($event.detail)"
     @save-timesheet.window="saveTimesheet($event.detail)">
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
                // 1. Dispatch loading event for immediate UI feedback
                this.$dispatch('timesheet-data-loading');
                this.isLoading = true;
                this.error = null;

                try {
                    // 2. Send fetch request to the backend
                    const response = await fetch('/time/timesheet/data', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payPeriodData)
                    });

                    if (!response.ok) {
                        // Handles network or server errors (e.g., 500, 404)
                        throw new Error(`Server error: ${response.status} ${response.statusText}`);
                    }

                    const data = await response.json();

                    // 3. Check for application-level errors returned by the controller
                    if (data.timesheetData && data.timesheetData.success === false) {
                        throw new Error(data.timesheetData.errors || 'An unknown error occurred while fetching data.');
                    }

                    // 4. Dispatch the entire successful payload.
                    // Listening components can now access `event.detail.timesheetData` or `event.detail.statsData`.
                    this.$dispatch('timesheet-data-updated', data.timesheetData);
                    this.$dispatch('stats-data-updated', data.statsData)

                } catch (e) {
                    // 5. Dispatch a single, consistent error event for all components
                    console.error("Timesheet fetch error:", e);
                    this.error = e.message;
                    this.$dispatch('timesheet-data-error', { message: this.error });
                } finally {
                    this.isLoading = false;
                }
            },

            async saveTimesheet(Data) {
              try {
                // 2. Send fetch request to the backend
                const response = await fetch('/time/timesheet/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(Data)
                });

                if (!response.ok) {
                    // Handles network or server errors (e.g., 500, 404)
                    throw new Error(`Server error: ${response.status} ${response.statusText}`);
                }

                const responseFromController = await response.json();

                // 3. Check for application-level errors returned by the controller
                if (ResponseFromController.success === false) {
                    throw new Error(responseFromController.errors || 'An unknown error occurred while fetching data.');
                }

                // 4. Dispatch the entire successful payload.
                // Listening components can now access `event.detail.timesheetData` or `event.detail.statsData`.
                this.$dispatch('timesheet-data-saved');

            } catch (e) {
                // 5. Dispatch a single, consistent error event for all components
                console.error("Timesheet save error:", e);
                this.error = e.message;
                this.$dispatch('timesheet-save-error', { message: this.error });
            } finally {
            }
          }
        }
    }
</script>
@endpush