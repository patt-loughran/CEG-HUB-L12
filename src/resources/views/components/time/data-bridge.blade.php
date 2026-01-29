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
     @timesheet-date-change.window="fetchData($event)"
     @save-timesheet.window="saveTimesheet($event)"
     @timesheet-load-recent.window="fetchRecentRows($event)">
</div>

@push('scripts')
<script>
    function timeDataBridgeLogic() {
        return {
            async fetchData(event) {
                // 1. Dispatch loading event for immediate UI feedback
                this.$dispatch('timesheet-data-loading');
                const userInputVariables = event.detail;

                try {
                    // 2. Send fetch request to the backend
                    const response = await fetch('/time/timesheet/data', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(userInputVariables)
                    });

                    const payload = await response.json();
                    const recievedSeqNum = payload.sequenceNum;
                    if (recievedSeqNum === this.$store.timesheetPageRegistry.sequenceNum) {
                        this.$dispatch('timesheet-data-updated', payload);
                    }

                } catch (error) {
                    // 5. Dispatch a single, consistent error event for all components
                    console.error("Timesheet fetch error:", error.message);
                    this.$dispatch('timesheet-fetch-error', error.message);
                }
            },

            async saveTimesheet(event) {
              try {
                // 1. Dispatch global loading if you want the UI to freeze/show spinner (optional but recommended)
                // this.$dispatch('timesheet-data-loading'); 

                const payload = event.detail;
                
                // 2. Send fetch request to the backend
                const response = await fetch('/time/timesheet/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) {
                    throw new Error(`Server error: ${response.status} ${response.statusText}`);
                }

                const result = await response.json();

                // 3. Check ApiResult success status
                if (!result.success) {
                    // result.errors contains the error message
                    throw new Error(result.errors || 'An unknown error occurred while saving.');
                }

                // 4. Dispatch success event
                // No data payload needed as ApiResult only indicates success
                this.$dispatch('timesheet-data-saved');

              } catch (e) {
                  console.error("Timesheet save error:", e);
                  // 5. Dispatch error event
                  this.$dispatch('timesheet-save-error', e.message);
                }
            },

          async fetchRecentRows(event) {
                const payload = event.detail; // { referenceDate: 'Y-m-d', weeksBack: int }

                try {
                    const response = await fetch('/time/timesheet/recent', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();
                    
                    // Dispatch specific event for the display component to merge data
                    this.$dispatch('timesheet-recent-loaded', data);

                } catch (error) {
                    console.error("Recent rows fetch error:", error.message);
                    this.$dispatch('timesheet-fetch-error', error.message);
                }
            }
        }
    }
</script>
@endpush