{{--
    Aggregated Table — Dynamic Display Component
    Displays time data aggregated by category (PTO, Holiday, Billable, etc.)
    with periods as rows and categories as columns.

    Listens for:
      - employee-time-data-loading   → show skeleton
      - employee-time-data-updated   → extract aggregatedTable key, render data or error
      - employee-time-fetch-error    → show error state
--}}

<div
    x-data="timeEmployeeTimeAggregatedTableLogic()"
    @employee-time-data-loading.window="handleDataLoading()"
    @employee-time-data-updated.window="handleDataUpdate($event)"
    @employee-time-fetch-error.window="handleFetchError($event)"
    class="bg-slate-100 rounded-xl flex flex-col h-full overflow-hidden max-w-full w-max"
>

    {{-- ========================================================== --}}
    {{--  LOADING STATE                                              --}}
    {{-- ========================================================== --}}
    <div x-show="isLoading" x-cloak class="flex flex-col h-full">
        {{-- Skeleton toolbar --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <div class="h-4 w-28 bg-slate-200 rounded animate-pulse"></div>
            <div class="h-7 w-20 bg-slate-200 rounded animate-pulse"></div>
        </div>
        {{-- Skeleton table --}}
        <div class="p-4 space-y-2 flex-1">
            {{-- Header row skeleton --}}
            <div class="flex gap-3 mb-4">
                <div class="h-4 w-24 bg-slate-200 rounded animate-pulse"></div>
                <template x-for="i in 7" x-bind:key="'hdr-skel-' + i">
                    <div class="h-4 w-16 bg-slate-200 rounded animate-pulse"></div>
                </template>
            </div>
            {{-- Data row skeletons --}}
            <template x-for="i in 6" x-bind:key="'row-skel-' + i">
                <div class="flex gap-3 py-2">
                    <div class="h-3.5 w-24 bg-slate-100 rounded animate-pulse"></div>
                    <template x-for="j in 7" x-bind:key="'cell-skel-' + i + '-' + j">
                        <div class="h-3.5 w-16 bg-slate-100 rounded animate-pulse"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{--  ERROR STATE                                                --}}
    {{-- ========================================================== --}}
    <div x-show="!isLoading && error" x-cloak class="flex-1 flex items-center justify-center p-8">
        <div class="text-center max-w-sm">
            <div class="mx-auto w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <p class="text-sm font-semibold text-slate-700 mb-1">Failed to load Aggregated data</p>
            <p class="text-xs text-slate-500" x-text="error"></p>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{--  EMPTY STATE (successful fetch, zero rows)                  --}}
    {{-- ========================================================== --}}
    <div x-show="!isLoading && !error && rows.length === 0" x-cloak class="flex-1 flex items-center justify-center p-8">
        <div class="text-center max-w-sm">
            <div class="mx-auto w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <p class="text-sm font-semibold text-slate-700 mb-1">No aggregated data</p>
            <p class="text-xs text-slate-500">No time entries match the selected filters.</p>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{--  DATA STATE                                                 --}}
    {{-- ========================================================== --}}
    <div x-show="!isLoading && !error && rows.length > 0" x-cloak class="flex flex-col h-full overflow-hidden">

        {{-- Toolbar --}}
        <div class="shrink-0 flex items-center justify-between px-4 py-2.5 border-b border-slate-100 bg-slate-700">
            <div class="flex items-center gap-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Aggregated</h3>
                <span class="text-xs text-slate-200"
                    x-text="rows.length + ' row' + (rows.length !== 1 ? 's' : '')"></span>
            </div>

            <div class="flex items-center gap-2">
                {{-- Export button --}}
                <button
                    @click="exportCsv()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Export</span>
                </button>
            </div>
        </div>

        {{-- Table Scroll Container --}}
        <div class="flex-1 overflow-auto custom-scrollbar">
            <table class="w-auto border-separate border-spacing-0">

                {{-- Table Head --}}
                <thead>
                    <tr>
                        {{-- Period header — sticky top + left (corner cell) --}}
                        <th class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider bg-white border-b-2 border-slate-200 px-3 py-2.5 whitespace-nowrap"
                            style="position:sticky;top:0;left:0;z-index:30;"
                            x-text="granularity">
                        </th>

                        {{-- Data column headers — sticky top --}}
                        <template x-for="col in dataColumns()" x-bind:key="'th-' + col.key">
                            <th class="text-right text-xs font-bold text-slate-500 uppercase tracking-wider bg-white border-b-2 border-slate-200 px-3 py-2.5 whitespace-nowrap"
                                style="position:sticky;top:0;z-index:20;"
                                x-text="col.label">
                            </th>
                        </template>
                    </tr>
                </thead>

                {{-- Table Body --}}
                <tbody>
                    {{-- Data Rows --}}
                    <template x-for="(row, rowIdx) in rows" x-bind:key="'row-' + rowIdx">
                        <tr class="border-b border-slate-100 hover:bg-sky-50/50">
                            {{-- Period cell — sticky left --}}
                            <td class="text-sm text-slate-700 font-medium px-3 py-2 whitespace-nowrap border-r border-slate-100 bg-white"
                                style="position:sticky;left:0;z-index:10;"
                                x-text="row.period_label">
                            </td>

                            {{-- Data cells --}}
                            <template x-for="col in dataColumns()" x-bind:key="'cell-' + rowIdx + '-' + col.key">
                                <td class="text-sm text-right tabular-nums px-3 py-2 whitespace-nowrap bg-white"
                                    x-bind:class="getCellColorClass(row[col.key])"
                                    x-text="formatCellValue(col.key, row[col.key])">
                                </td>
                            </template>
                        </tr>
                    </template>

                    {{-- Totals Row — sticky bottom (only when more than 1 data row) --}}
                    <template x-if="rows.length > 1">
                        <tr class="border-t-2 border-slate-300 bg-slate-50 font-semibold">
                            {{-- "Totals" label — sticky bottom + left (corner cell) --}}
                            <td class="text-sm bg-slate-50 px-3 py-2.5 whitespace-nowrap border-r border-slate-100"
                                style="position:sticky;bottom:0;left:0;z-index:200;">
                                <span class="font-bold uppercase text-xs tracking-wider text-slate-600">Totals</span>
                            </td>

                            {{-- Totals for each data column — sticky bottom --}}
                            <template x-for="col in dataColumns()" x-bind:key="'total-' + col.key">
                                <td class="text-sm text-right tabular-nums text-slate-700 font-bold px-3 py-2.5 whitespace-nowrap bg-slate-50"
                                    style="position:sticky;bottom:0;z-index:100;"
                                    x-text="getTotalForColumn(col.key)">
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function timeEmployeeTimeAggregatedTableLogic() {
        return {
            isLoading: null,
            error: null,
            columns: null,
            rows: null,
            showOvertime: null,
            granularity: null,

            init() {
                // Initialize instance variables
                this.isLoading = true;
                this.error = null;
                this.columns = [];
                this.rows = [];
                this.showOvertime = false;
                this.granularity = null;
            },

            /**
             * Returns only the data columns (excludes the period_label column
             * which is rendered separately as the sticky-left first column).
             */
            dataColumns() {
                return this.columns.filter(c => c.key !== 'period_label');
            },

            /**
             * Format a numeric value as hours (2 decimal places).
             */
            formatHours(val) {
                const num = parseFloat(val) || 0;
                // Return em dash if 0, otherwise format as number
                return num === 0 ? '—' : num.toFixed(2);
            },

            /**
             * Format a numeric value as a percentage (1 decimal place + '%').
             */
            formatPercentage(val) {
                const num = parseFloat(val) || 0;
                return num.toFixed(1) + '%';
            },

            /**
             * Format a cell value based on the column it belongs to.
             */
            formatCellValue(colKey, value) {
                if (colKey === 'billable_percentage') return this.formatPercentage(value);
                return this.formatHours(value);
            },

            /**
             * Returns a Tailwind text-color class based on whether the value is zero.
             * Zero values are muted; non-zero values are full black.
             */
            getCellColorClass(value) {
                return parseFloat(value) > 0 ? 'text-black' : 'text-slate-300';
            },

            /**
             * Sum a single column across all data rows.
             */
            computeColumnTotal(colKey) {
                return this.rows.reduce((sum, row) => sum + (parseFloat(row[colKey]) || 0), 0);
            },

            /**
             * Compute the weighted billable percentage from column totals
             * (totalBillable / totalHours × 100) rather than averaging percentages.
             */
            computeBillablePercentageTotal() {
                const totalBillable = this.computeColumnTotal('billable');
                const totalHours = this.computeColumnTotal('total_hours');
                return totalHours > 0 ? (totalBillable / totalHours) * 100 : 0;
            },

            /**
             * Get the formatted total string for a given column key.
             */
            getTotalForColumn(colKey) {
                if (colKey === 'billable_percentage') {
                    return this.formatPercentage(this.computeBillablePercentageTotal());
                }
                return this.formatHours(this.computeColumnTotal(colKey));
            },

            /**
             * Export the current table data (including totals row) as a CSV download.
             */
            exportCsv() {
                const headers = this.columns.map(c => c.label);
                const csvRows = [headers.join(',')];

                // Data rows
                for (const row of this.rows) {
                    const values = this.columns.map(col => {
                        const val = row[col.key];
                        if (col.key === 'period_label') {
                            return '"' + String(val).replace(/"/g, '""') + '"';
                        }
                        if (col.key === 'billable_percentage') {
                            return (parseFloat(val) || 0).toFixed(1) + '%';
                        }
                        return (parseFloat(val) || 0).toFixed(2);
                    });
                    csvRows.push(values.join(','));
                }

                // Totals row (only when more than 1 data row)
                if (this.rows.length > 1) {
                    const totals = this.columns.map(col => {
                        if (col.key === 'period_label') return '"Totals"';
                        return this.getTotalForColumn(col.key);
                    });
                    csvRows.push(totals.join(','));
                }

                const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'aggregated-time-data.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            },

            // ==========================================================
            //  Event Handlers (loading → update → error, per convention)
            // ==========================================================

            /**
             * Data-bridge signals that a new fetch has started.
             */
            handleDataLoading() {
                this.isLoading = true;
                this.error = null;
            },

            /**
             * Data-bridge delivers the full response payload.
             * Extract the aggregatedTable key and check for component-level errors.
             */
            handleDataUpdate(event) {
                // 1. Extract the specific payload for THIS component
                const responseObj = event.detail.aggregatedTable;

                // Safety check: controller didn't return this key
                if (!responseObj) {
                    this.handleError('Invalid response format.');
                    return;
                }

                // 2. Check for component-specific error (ApiResponse::error)
                if (responseObj.errors) {
                    this.handleError(responseObj.errors);
                    return;
                }

                // 3. Populate component state with successful data
                const payload = responseObj.data;
                this.columns = payload.columns || [];
                this.rows = payload.rows || [];
                this.showOvertime = payload.showOvertime || false;
                this.isLoading = false;
                this.error = null;
                this.granularity = event.detail.granularity;
            },

            /**
             * Central error handler — sets error state and clears data.
             */
            handleError(errorMessage) {
                this.error = errorMessage;
                this.columns = [];
                this.rows = [];
                this.showOvertime = false;
                this.isLoading = false;
            },

            /**
             * Data-bridge signals a network-level fetch failure.
             */
            handleFetchError(event) {
                const errorMessage = event.detail.message || event.detail;
                this.handleError(errorMessage);
            },
        };
    }
</script>
@endpush