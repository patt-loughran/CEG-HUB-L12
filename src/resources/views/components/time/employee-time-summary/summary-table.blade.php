{{--
    Summary Table — Dynamic Display Component
    Displays time data aggregated by category (PTO, Holiday, Billable, etc.)
    with periods as rows and categories as columns.

    Detail level is controlled by two client-side steppers:
      - Non-Billable: Σ (summary) → Level 1 → Level 2
      - Billable:     Σ (summary) → Top 3   → Top 5

    No round-trip is needed when toggling detail — the server always sends
    full-detail data and the client controls column visibility.

    Listens for:
      - employee-time-data-loading   → show skeleton
      - employee-time-data-updated   → extract summaryTable key, render data or error
      - employee-time-fetch-error    → show error state
      - process-export               → trigger CSV download via modal
--}}

<div
    x-data="timeEmployeeTimeSummaryTableLogic()"
    @employee-time-data-loading.window="handleDataLoading()"
    @employee-time-data-updated.window="handleDataUpdate($event)"
    @employee-time-fetch-error.window="handleFetchError($event)"
    @process-export.window="handleExport($event)"
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
        {{-- Skeleton stepper bar --}}
        <div class="flex items-center gap-6 px-4 py-2.5 border-b border-slate-200">
            <div class="h-4 w-40 bg-slate-200 rounded animate-pulse"></div>
            <div class="h-4 w-40 bg-slate-200 rounded animate-pulse"></div>
        </div>
        {{-- Skeleton table --}}
        <div class="p-4 space-y-2 flex-1">
            <div class="flex gap-3 mb-4">
                <div class="h-4 w-24 bg-slate-200 rounded animate-pulse"></div>
                <template x-for="i in 7" x-bind:key="'hdr-skel-' + i">
                    <div class="h-4 w-16 bg-slate-200 rounded animate-pulse"></div>
                </template>
            </div>
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
            <p class="text-sm font-semibold text-slate-700 mb-1">Failed to load Summary data</p>
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
            <p class="text-sm font-semibold text-slate-700 mb-1">No summary data</p>
            <p class="text-xs text-slate-500">No time entries match the selected filters.</p>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{--  DATA STATE                                                 --}}
    {{-- ========================================================== --}}
    <div x-show="!isLoading && !error && rows.length > 0" x-cloak class="flex flex-col h-full overflow-hidden">

        {{-- ====================================================== --}}
        {{--  TOOLBAR                                                --}}
        {{-- ====================================================== --}}
        <div class="shrink-0 flex items-center justify-between px-4 py-2.5 border-b border-slate-100 bg-slate-700">
            <div class="flex items-center gap-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Summary</h3>
                <span class="text-xs text-slate-200"
                    x-text="rows.length + ' row' + (rows.length !== 1 ? 's' : '')"></span>
            </div>

            <div class="flex items-center gap-2">
                <button
                    @click="openExportModal()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Export</span>
                </button>
            </div>
        </div>

        {{-- ====================================================== --}}
        {{--  DEPTH STEPPER BAR                                      --}}
        {{-- ====================================================== --}}
        <div class="shrink-0 flex items-center gap-6 px-4 py-2.5 bg-slate-50 border-b border-slate-200">

            {{-- Non-Billable Stepper --}}
            <div class="flex items-center gap-2.5">
                <div class="flex items-center gap-1.5">
                    <div class="w-1.5 h-1.5 rounded-full bg-amber-400"></div>
                    <span class="text-[11px] font-semibold text-slate-600">Non-Billable</span>
                </div>
                <div class="flex items-center">
                    {{-- Σ (summary) --}}
                    <button @click="setNbLevel(0)"
                            class="group flex flex-col items-center px-2 py-0.5 rounded hover:bg-amber-50 transition cursor-pointer">
                        <div class="w-3 h-3 rounded-full border-2 transition"
                             :class="nbIdx >= 0 ? 'border-amber-400 bg-amber-400' : 'border-amber-200 bg-white'"></div>
                        <span class="text-[9px] mt-0.5 transition"
                              :class="nbIdx >= 0 ? 'text-amber-600 font-semibold' : 'text-slate-400 font-medium'">Σ</span>
                    </button>
                    <div class="w-4 h-px bg-slate-300"></div>
                    {{-- Level 1 --}}
                    <button @click="setNbLevel(1)"
                            class="group flex flex-col items-center px-2 py-0.5 rounded hover:bg-amber-50 transition cursor-pointer">
                        <div class="w-3 h-3 rounded-full border-2 transition"
                             :class="nbIdx >= 1 ? 'border-amber-400 bg-amber-400' : 'border-amber-200 bg-white'"></div>
                        <span class="text-[9px] mt-0.5 transition"
                              :class="nbIdx >= 1 ? 'text-amber-600 font-semibold' : 'text-slate-400 font-medium'">Detail</span>
                    </button>
                    <div class="w-4 h-px bg-slate-300"></div>
                    {{-- Level 2 --}}
                    <button @click="setNbLevel(2)"
                            class="group flex flex-col items-center px-2 py-0.5 rounded hover:bg-amber-50 transition cursor-pointer">
                        <div class="w-3 h-3 rounded-full border-2 transition"
                             :class="nbIdx >= 2 ? 'border-amber-400 bg-amber-400' : 'border-amber-200 bg-white'"></div>
                        <span class="text-[9px] mt-0.5 transition"
                              :class="nbIdx >= 2 ? 'text-amber-600 font-semibold' : 'text-slate-400 font-medium'">Full</span>
                    </button>
                </div>
            </div>

            <div class="w-px h-6 bg-slate-200"></div>

            {{-- Billable Stepper --}}
            <div class="flex items-center gap-2.5">
                <div class="flex items-center gap-1.5">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>
                    <span class="text-[11px] font-semibold text-slate-600">Billable</span>
                </div>
                <div class="flex items-center">
                    {{-- Σ (summary) --}}
                    <button @click="setBLevel(0)"
                            class="group flex flex-col items-center px-2 py-0.5 rounded hover:bg-emerald-50 transition cursor-pointer">
                        <div class="w-3 h-3 rounded-full border-2 transition"
                             :class="bIdx >= 0 ? 'border-emerald-400 bg-emerald-400' : 'border-emerald-200 bg-white'"></div>
                        <span class="text-[9px] mt-0.5 transition"
                              :class="bIdx >= 0 ? 'text-emerald-600 font-semibold' : 'text-slate-400 font-medium'">Σ</span>
                    </button>
                    <div class="w-4 h-px bg-slate-300"></div>
                    {{-- Top 3 --}}
                    <button @click="setBLevel(1)"
                            class="group flex flex-col items-center px-2 py-0.5 rounded hover:bg-emerald-50 transition cursor-pointer">
                        <div class="w-3 h-3 rounded-full border-2 transition"
                             :class="bIdx >= 1 ? 'border-emerald-400 bg-emerald-400' : 'border-emerald-200 bg-white'"></div>
                        <span class="text-[9px] mt-0.5 transition"
                              :class="bIdx >= 1 ? 'text-emerald-600 font-semibold' : 'text-slate-400 font-medium'">Top 3</span>
                    </button>
                    <div class="w-4 h-px bg-slate-300"></div>
                    {{-- Top 5 --}}
                    <button @click="setBLevel(2)"
                            class="group flex flex-col items-center px-2 py-0.5 rounded hover:bg-emerald-50 transition cursor-pointer">
                        <div class="w-3 h-3 rounded-full border-2 transition"
                             :class="bIdx >= 2 ? 'border-emerald-400 bg-emerald-400' : 'border-emerald-200 bg-white'"></div>
                        <span class="text-[9px] mt-0.5 transition"
                              :class="bIdx >= 2 ? 'text-emerald-600 font-semibold' : 'text-slate-400 font-medium'">Top 5</span>
                    </button>
                </div>
            </div>

        </div>

        {{-- ====================================================== --}}
        {{--  TABLE                                                  --}}
        {{-- ====================================================== --}}
        <div class="flex-1 overflow-auto custom-scrollbar">
            <table class="w-auto border-separate border-spacing-0">

                {{-- Table Head --}}
                <thead>
                    <tr>
                        {{-- Period header — sticky corner --}}
                        <th class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider bg-white border-b-2 border-slate-200 px-3 py-2.5 whitespace-nowrap"
                            style="position:sticky;top:0;left:0;z-index:30;"
                            x-text="granularity">
                        </th>

                        {{-- Data column headers — sticky top --}}
                        <template x-for="col in visibleColumns()" x-bind:key="'th-' + col.key">
                            <th class="text-right text-xs font-bold uppercase tracking-wider px-3 py-2.5 whitespace-nowrap min-w-[128px]"
                                style="position:sticky;top:0;z-index:20;"
                                :class="getHeaderClasses(col)"
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
                            <template x-for="col in visibleColumns()" x-bind:key="'cell-' + rowIdx + '-' + col.key">
                                <td class="text-sm text-right tabular-nums px-3 py-2 whitespace-nowrap bg-white min-w-[128px]"
                                    :class="getCellColorClass(getCellValue(row, col.key))"
                                    x-text="formatCellValue(col.key, getCellValue(row, col.key))">
                                </td>
                            </template>
                        </tr>
                    </template>

                    {{-- Totals Row — sticky bottom (only when more than 1 data row) --}}
                    <template x-if="rows.length > 1">
                        <tr class="border-t-2 border-slate-300 bg-slate-50 font-semibold">
                            <td class="text-sm bg-slate-50 px-3 py-2.5 whitespace-nowrap border-r border-slate-100"
                                style="position:sticky;bottom:0;left:0;z-index:200;">
                                <span class="font-bold uppercase text-xs tracking-wider text-slate-600">Totals</span>
                            </td>

                            <template x-for="col in visibleColumns()" x-bind:key="'total-' + col.key">
                                <td class="text-sm text-right tabular-nums text-slate-700 font-bold px-3 py-2.5 whitespace-nowrap bg-slate-50 min-w-[128px]"
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
    function timeEmployeeTimeSummaryTableLogic() {
        return {
            // ── Core State ──────────────────────────────────────────
            isLoading: null,
            error: null,
            rows: null,
            showOvertime: null,
            granularity: null,
            billableTopProjects: null,

            // ── Stepper State ───────────────────────────────────────
            // Indices: 0 = summary, 1 = detail/top3, 2 = full/top5
            nbIdx: 0,
            bIdx: 0,

            init() {
                this.isLoading = true;
                this.error = null;
                this.rows = [];
                this.showOvertime = false;
                this.granularity = null;
                this.billableTopProjects = [];
                this.nbIdx = 0;
                this.bIdx = 0;
            },

            // ── Stepper Interaction ─────────────────────────────────

            setNbLevel(idx) {
                this.nbIdx = idx;
            },

            setBLevel(idx) {
                this.bIdx = idx;
            },

            // ============================================================
            //  COLUMN DEFINITIONS (computed from stepper state)
            // ============================================================

            /**
             * NB columns based on the current nbIdx.
             *
             * 0 (Σ):      Single "Non-Billable" total column.
             * 1 (Detail):  PTO, Holiday, Meetings, Other NB, Total NB.
             * 2 (Full):    PTO, Holiday, Other T.O., Meetings, Training,
             *              Marketing, Education, Other NB, Total NB.
             */
            nbColumns() {
                if (this.nbIdx === 0) {
                    return [{ key: 'total_nb', label: 'Non-Billable', group: 'nb' }];
                }
                if (this.nbIdx === 1) {
                    return [
                        { key: 'pto',         label: 'PTO',       group: 'nb' },
                        { key: 'holiday',     label: 'Holiday',   group: 'nb' },
                        { key: 'meetings',    label: 'Meetings',  group: 'nb' },
                        { key: 'other_nb_l1', label: 'Other NB',  group: 'nb' },
                        { key: 'total_nb',    label: 'Total NB',  group: 'nb' },
                    ];
                }
                // nbIdx === 2
                return [
                    { key: 'pto',         label: 'PTO',        group: 'nb' },
                    { key: 'holiday',     label: 'Holiday',    group: 'nb' },
                    { key: 'other_to',    label: 'Other T.O.', group: 'nb' },
                    { key: 'meetings',    label: 'Meetings',   group: 'nb' },
                    { key: 'training',    label: 'Training',   group: 'nb' },
                    { key: 'marketing',   label: 'Marketing',  group: 'nb' },
                    { key: 'education',   label: 'Education',  group: 'nb' },
                    { key: 'other_nb_l2', label: 'Other NB',   group: 'nb' },
                    { key: 'total_nb',    label: 'Total NB',   group: 'nb' },
                ];
            },

            /**
             * Billable columns based on the current bIdx.
             *
             * 0 (Σ):      Single "Billable" total column.
             * 1 (Top 3):  Top 3 projects + Other Bill. + Total Bill.
             * 2 (Top 5):  Top 5 projects + Other Bill. + Total Bill.
             *
             * Project labels are pulled from billableTopProjects metadata.
             */
            bColumns() {
                if (this.bIdx === 0) {
                    return [{ key: 'billable', label: 'Billable', group: 'b' }];
                }

                const n = this.bIdx === 1 ? 3 : 5;
                const cols = [];

                // Add columns for each top project (up to n or however many exist)
                const available = Math.min(n, this.billableTopProjects.length);
                for (let i = 0; i < available; i++) {
                    cols.push({
                        key:   'top_' + (i + 1),
                        label: this.billableTopProjects[i].label,
                        group: 'b',
                    });
                }

                // "Other Billable" — uses a virtual key for top-3 level
                const otherKey = this.bIdx === 1 ? 'other_billable_top3' : 'other_billable';
                cols.push({ key: otherKey, label: 'Other Bill.', group: 'b' });

                // Total Billable
                cols.push({ key: 'billable', label: 'Total Bill.', group: 'b' });

                return cols;
            },

            /**
             * Fixed columns that always appear at the right edge.
             */
            fixedColumns() {
                const cols = [
                    { key: 'total_hours',         label: 'Total',   group: 'fixed' },
                    { key: 'billable_percentage',  label: 'Bill %',  group: 'fixed' },
                ];
                if (this.showOvertime) {
                    cols.push({ key: 'overtime', label: 'Overtime', group: 'fixed' });
                }
                return cols;
            },

            /**
             * The full ordered set of visible columns.
             */
            visibleColumns() {
                return [...this.nbColumns(), ...this.bColumns(), ...this.fixedColumns()];
            },

            // ============================================================
            //  CELL VALUE RESOLUTION
            // ============================================================

            /**
             * Resolve the display value for a given row and column key.
             *
             * Most keys map directly to row properties. The virtual key
             * "other_billable_top3" is derived client-side as:
             *   top_4 + top_5 + other_billable
             */
            getCellValue(row, colKey) {
                if (colKey === 'other_billable_top3') {
                    return (parseFloat(row.top_4) || 0)
                         + (parseFloat(row.top_5) || 0)
                         + (parseFloat(row.other_billable) || 0);
                }
                return row[colKey];
            },

            // ============================================================
            //  FORMATTING HELPERS
            // ============================================================

            formatHours(val) {
                const num = parseFloat(val) || 0;
                return num === 0 ? '—' : num.toFixed(2);
            },

            formatPercentage(val) {
                const num = parseFloat(val) || 0;
                return num.toFixed(1) + '%';
            },

            formatCellValue(colKey, value) {
                if (colKey === 'billable_percentage') return this.formatPercentage(value);
                return this.formatHours(value);
            },

            getCellColorClass(value) {
                return parseFloat(value) > 0 ? 'text-black' : 'text-slate-300';
            },

            // ============================================================
            //  HEADER STYLING
            // ============================================================

            /**
             * Returns Tailwind classes for a column header based on its group
             * and whether that group is currently expanded.
             */
            getHeaderClasses(col) {
                if (col.group === 'nb') {
                    const expanded = this.nbIdx > 0;
                    return expanded
                        ? 'text-amber-700 bg-amber-100/60 border-b-2 border-amber-400'
                        : 'text-amber-700 bg-amber-50/70 border-b-2 border-amber-300';
                }
                if (col.group === 'b') {
                    const expanded = this.bIdx > 0;
                    return expanded
                        ? 'text-emerald-700 bg-emerald-100/60 border-b-2 border-emerald-400'
                        : 'text-emerald-700 bg-emerald-50/70 border-b-2 border-emerald-300';
                }
                // Fixed columns
                return 'text-slate-500 bg-white border-b-2 border-slate-200';
            },

            // ============================================================
            //  TOTALS
            // ============================================================

            /**
             * Sum a column across all rows, using getCellValue for virtual keys.
             */
            computeColumnTotal(colKey) {
                return this.rows.reduce((sum, row) => {
                    return sum + (parseFloat(this.getCellValue(row, colKey)) || 0);
                }, 0);
            },

            /**
             * Weighted billable percentage total: totalBillable / totalHours × 100.
             */
            computeBillablePercentageTotal() {
                const totalBillable = this.computeColumnTotal('billable');
                const totalHours = this.computeColumnTotal('total_hours');
                return totalHours > 0 ? (totalBillable / totalHours) * 100 : 0;
            },

            getTotalForColumn(colKey) {
                if (colKey === 'billable_percentage') {
                    return this.formatPercentage(this.computeBillablePercentageTotal());
                }
                return this.formatHours(this.computeColumnTotal(colKey));
            },

            // ============================================================
            //  EXPORT
            // ============================================================

            openExportModal() {
                const date = new Date().toISOString().slice(0, 10);
                const defaultName = `summary-export-${date}`;
                this.$dispatch('open-export-modal', { defaultName: defaultName });
            },

            handleExport(event) {
                const fileName = event.detail.name || 'summary-export';
                this.exportCsv(fileName);
            },

            /**
             * Export the currently visible columns as CSV.
             */
            exportCsv(fileName) {
                if (!this.rows || this.rows.length === 0) return;

                const cols = this.visibleColumns();
                const allCols = [{ key: 'period_label', label: 'Period' }, ...cols];

                const headers = allCols.map(c => c.label);
                const csvRows = [headers.join(',')];

                // Data rows
                for (const row of this.rows) {
                    const values = allCols.map(col => {
                        if (col.key === 'period_label') {
                            return '"' + String(row.period_label).replace(/"/g, '""') + '"';
                        }
                        const val = this.getCellValue(row, col.key);
                        if (col.key === 'billable_percentage') {
                            return (parseFloat(val) || 0).toFixed(1) + '%';
                        }
                        return (parseFloat(val) || 0).toFixed(2);
                    });
                    csvRows.push(values.join(','));
                }

                // Totals row
                if (this.rows.length > 1) {
                    const totals = allCols.map(col => {
                        if (col.key === 'period_label') return '"Totals"';
                        return this.getTotalForColumn(col.key);
                    });
                    csvRows.push(totals.join(','));
                }

                const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${fileName}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            },

            // ============================================================
            //  EVENT HANDLERS
            // ============================================================

            handleDataLoading() {
                this.isLoading = true;
                this.error = null;
            },

            handleDataUpdate(event) {
                const responseObj = event.detail.summaryTable;

                if (!responseObj) {
                    this.handleError('Invalid response format.');
                    return;
                }

                if (responseObj.errors) {
                    this.handleError(responseObj.errors);
                    return;
                }

                const payload = responseObj.data;
                this.rows = payload.rows || [];
                this.showOvertime = payload.showOvertime || false;
                this.billableTopProjects = payload.billableTopProjects || [];
                this.isLoading = false;
                this.error = null;
                this.granularity = event.detail.granularity;
            },

            handleError(errorMessage) {
                this.error = errorMessage;
                this.rows = [];
                this.showOvertime = false;
                this.billableTopProjects = [];
                this.isLoading = false;
            },

            handleFetchError(event) {
                const errorMessage = event.detail.message || event.detail;
                this.handleError(errorMessage);
            },
        };
    }
</script>
@endpush