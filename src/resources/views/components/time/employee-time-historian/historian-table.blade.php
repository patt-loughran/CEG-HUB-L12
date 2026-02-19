{{--
    Display Component: Historian Table
    File: resources/views/components/time/employee-time/historian-table.blade.php

    Dynamic display component that shows the user's hours broken down by
    selected scopes (rows) and time-period columns. Supports a "compact"
    header mode that rotates period labels at -45° for dense date ranges.

    PERFORMANCE NOTE:
    The <thead> and <tbody> are rendered via innerHTML (string concatenation)
    rather than Alpine x-for loops. At 97 rows × 731 columns (~71k cells, this is a typical amount for the day view at 2 years),
    Alpine's reactive proxy wrapping + per-cell DOM generation was causing
    ~14 s main-thread freezes. innerHTML drops this to < 500 ms.

    Alpine is still used for: toolbar, state transitions (loading/error/empty),
    crosshair highlighting (event-delegated DOM manipulation), collapse toggle,
    export, and the table element's own style/class bindings.

    Events listened:
        - employee-time-data-loading  → show skeleton
        - employee-time-data-updated  → render table (or component-level error)
        - employee-time-fetch-error   → show network error state
        - process-export              → trigger CSV download
--}}

{{-- Component-scoped highlight styles (avoids per-cell Alpine reactivity) --}}
<style>
    /* Row highlight on hover — pure CSS, zero JS cost */
    .historian-table tbody tr:hover > td {
        background-color: rgb(240 249 255) !important; /* bg-sky-50 */
    }

    /* Column highlight for crosshair mode — applied via direct DOM manipulation */
    .historian-table td.crosshair-col-highlight {
        background-color: rgb(240 249 255) !important; /* bg-sky-50 */
    }

    /* Column collapse — hides period columns via CSS class toggle (no innerHTML rebuild) */
    .historian-table.cols-collapsed .period-cell {
        display: none !important;
    }
</style>

<div
    x-data="timeEmployeeTimeHistorianTableLogic()"
    x-ref="rootWrapper"
    @employee-time-data-loading.window="handleDataLoading()"
    @employee-time-data-updated.window="handleDataUpdate($event)"
    @employee-time-fetch-error.window="handleFetchError($event)"
    @process-export.window="handleExport($event)"
    class="bg-slate-100 rounded-xl flex flex-col h-full overflow-hidden max-w-full"
>

    {{-- ========================================================== --}}
    {{--  LOADING STATE                                              --}}
    {{-- ========================================================== --}}
    <div x-show="isLoading" x-cloak class="flex flex-col h-full">
        {{-- Skeleton toolbar --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <div class="h-4 w-24 bg-slate-200 rounded animate-pulse"></div>
            <div class="h-7 w-20 bg-slate-200 rounded animate-pulse"></div>
        </div>
        {{-- Skeleton table --}}
        <div class="p-4 space-y-2 flex-1">
            {{-- Header row skeleton --}}
            <div class="flex gap-3 mb-4">
                <div class="h-4 w-28 bg-slate-200 rounded animate-pulse"></div>
                <div class="h-4 w-28 bg-slate-200 rounded animate-pulse"></div>
                <div class="h-4 w-16 bg-slate-200 rounded animate-pulse"></div>
                <div class="h-4 w-16 bg-slate-200 rounded animate-pulse"></div>
                <div class="h-4 w-16 bg-slate-200 rounded animate-pulse"></div>
                <div class="h-4 w-16 bg-slate-200 rounded animate-pulse"></div>
                <div class="h-4 w-16 bg-slate-200 rounded animate-pulse"></div>
            </div>
            {{-- Data row skeletons --}}
            <template x-for="i in 8" x-bind:key="'skel-'+i">
                <div class="flex gap-3 py-2">
                    <div class="h-3.5 w-28 bg-slate-100 rounded animate-pulse"></div>
                    <div class="h-3.5 w-28 bg-slate-100 rounded animate-pulse"></div>
                    <div class="h-3.5 w-16 bg-slate-100 rounded animate-pulse"></div>
                    <div class="h-3.5 w-16 bg-slate-100 rounded animate-pulse"></div>
                    <div class="h-3.5 w-16 bg-slate-100 rounded animate-pulse"></div>
                    <div class="h-3.5 w-16 bg-slate-100 rounded animate-pulse"></div>
                    <div class="h-3.5 w-16 bg-slate-100 rounded animate-pulse"></div>
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
            <p class="text-sm font-semibold text-slate-700 mb-1">Failed to load Historian data</p>
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
            <p class="text-sm font-semibold text-slate-700 mb-1">No hours found</p>
            <p class="text-xs text-slate-500">No time entries match the selected filters.</p>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{--  DATA STATE                                                 --}}
    {{-- ========================================================== --}}
    <div x-show="!isLoading && !error && rows.length > 0" x-cloak class="flex flex-col h-full overflow-hidden">

        {{-- Toolbar --}}
        <div class="shrink-0 flex items-center justify-between px-4 py-2.5 border-b border-slate-100 shrink-0 bg-slate-700">
            <div class="flex items-center gap-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Historian</h3>
                <span class="text-xs text-slate-200"
                    x-text="rows.length + ' row' + (rows.length !== 1 ? 's' : '')"></span>
            </div>
            
            <div class="flex items-center gap-2">

                {{-- Sort by Total toggle button --}}
                <button
                    x-show="!columnsCollapsed"
                    @click="toggleSortByTotal()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium transition-all"
                    x-bind:class="sortByTotal
                        ? 'bg-sky-500 text-white shadow-sm hover:bg-sky-400'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    title="Sort rows by total hours descending"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m6-6v12m0 0-3.75-3.75M14.25 19.5l3.75-3.75" />
                    </svg>
                    <span x-text="sortByTotal ? 'Sorted Hours' : 'Sorted A-Z'"></span>
                </button>

                {{-- Crosshair toggle button (hidden when columns are collapsed) --}}
                <button
                    x-show="!columnsCollapsed"
                    @click="toggleCrosshair()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium"
                    x-bind:class="crosshairEnabled
                        ? 'bg-sky-500 text-white shadow-sm hover:bg-sky-400'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    title="Toggle crosshair highlight"
                >
                    <x-general.icon 
                        name="crosshair" 
                        class="w-3.5 h-3.5" 
                    />
                    <span>Crosshair</span>
                </button>

                {{-- Collapse columns toggle --}}
                <button
                    @click="toggleCollapse()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium transition-all"
                    x-bind:class="columnsCollapsed
                        ? 'bg-sky-500 text-white shadow-sm hover:bg-sky-400'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    title="Toggle period columns visibility"
                >
                    {{-- State: Collapsed. Action: Expand. --}}
                    <svg x-show="columnsCollapsed" style="display: none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                    </svg>

                    {{-- State: Expanded. Action: Collapse. --}}
                    <svg x-show="!columnsCollapsed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25" />
                    </svg>

                    <span x-text="columnsCollapsed ? 'Expand' : 'Collapse'"></span>
                </button>

                {{-- Separator before Export --}}
                <div x-show="!columnsCollapsed" class="w-px self-stretch my-0.5 bg-white"></div>


                {{-- Export button (hidden when columns are collapsed) --}}
                <button
                    x-show="!columnsCollapsed"
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

        {{-- Table Scroll Container --}}
        <div class="flex-1 overflow-auto custom-scrollbar" x-ref="tableContainer">
            {{--
                The <table> element retains Alpine bindings for style/class
                and event delegation. Its inner content (thead + tbody) is
                rendered via innerHTML by renderTable().
            --}}
            <table
                x-ref="historianTable"
                class="historian-table table-fixed border-separate border-spacing-0 shadow-lg"
                x-bind:class="{ 'cols-collapsed': columnsCollapsed }"
                x-bind:style="'width: ' + (tableWidth || MIN_TABLE_WIDTH) + 'px; min-width: ' + MIN_TABLE_WIDTH + 'px;'"
                @mouseover.throttle.32ms="handleTableHover($event)"
                @mouseleave="clearHighlights()"
            >
                {{-- Content injected by renderTable() via innerHTML --}}
            </table>
        </div>
    </div>
</div>


@push('scripts')
<script>
    function timeEmployeeTimeHistorianTableLogic() {
        return {
            // --- State ---
            isLoading: null,
            error: null,
            scopeColumns: null,
            periodColumns: null,
            rows: null,
            headerMode: null,
            crosshairEnabled: null,
            columnsCollapsed: null,
            sortByTotal: null,
            scopeColWidths: null,
            canvasContext: null,
            tableWidth: null,

            // --- Internal (non-reactive) tracking for highlight performance ---
            _currentHighlightCol: null,
            _resizeObserver: null,
            _originalRows: null,

            // --- Constants ---
            MIN_TABLE_WIDTH: 256,
            SCOPE_COL_WIDTH: 128,
            SUBCODE_COL_WIDTH: 256,

            // ================================================================
            //  INIT
            // ================================================================

            init() {
                this.isLoading = true;
                this.error = null;
                this.scopeColumns = [];
                this.periodColumns = [];
                this.rows = [];
                this.headerMode = null;
                this.crosshairEnabled = false;
                this.columnsCollapsed = false;
                this.sortByTotal = false;
                this.scopeColWidths = {};
                this.canvasContext = null;
                this._currentHighlightCol = null;
                this._resizeObserver = null;
                this._originalRows = null;
                this.tableWidth = null;
            },

            // ================================================================
            //  HTML HELPERS
            // ================================================================

            /**
             * Escapes a string for safe insertion into innerHTML.
             */
            escapeHtml(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            },

            // ================================================================
            //  innerHTML TABLE RENDERING
            //
            //  Replaces Alpine x-for loops with raw string concatenation.
            //  At 97 rows × 731 columns (~71k cells) this drops render time
            //  from ~14 s (Alpine DOM generation) to < 500 ms.
            // ================================================================

            /**
             * Main render entry point. Builds the full table innerHTML
             * from current state and injects it into the table element.
             */
            renderTable() {
                const table = this.$refs.historianTable;
                if (!table) return;

                table.innerHTML = this.buildTheadHTML() + this.buildTbodyHTML();
            },

            /**
             * Builds the <thead> HTML string.
             * Handles compact header mode (rotated labels, z-index stacking)
             * and column collapse state.
             */
            buildTheadHTML() {
                const mode = this.headerMode;
                const isCompact = mode !== 'standard';
                const parts = [];

                // 1. Determine Row Height based on mode
                let trClass = '';
                if (mode === 'compact-tall') trClass = ' class="h-24"';
                else if (mode === 'compact-short') trClass = ' class="h-16"';

                parts.push('<thead><tr', trClass, '>');

                // --- Scope Column Headers (Sticky Top + Left — corner cells) ---
                for (let colIdx = 0; colIdx < this.scopeColumns.length; colIdx++) {
                    const col = this.scopeColumns[colIdx];
                    const left = this.getScopeStickyLeft(colIdx);
                    const width = this.scopeColWidths[col.key] || this.SCOPE_COL_WIDTH;

                    parts.push(
                        '<th class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider bg-white border-b-2 border-slate-200 px-3 py-2.5 whitespace-nowrap align-bottom overflow-hidden text-ellipsis"',
                        ' style="position:sticky;top:0;left:', left,
                        'px;z-index:30000;width:', width,
                        'px;max-width:', width, 'px;">',
                        this.escapeHtml(col.label),
                        '</th>'
                    );
                }

                // --- Period Column Headers (Sticky Top) ---
                // Always rendered; visibility controlled by CSS .cols-collapsed class
                for (let idx = 0; idx < this.periodColumns.length; idx++) {
                    const col = this.periodColumns[idx];

                    if (isCompact) {
                        // Determine settings based on specific compact mode
                        let heightClass, translateClass;

                        if (mode === 'compact-tall') {
                            // Original settings for Pay Period (Long dates)
                            heightClass = 'h-26'; 
                            translateClass = 'translate-x-6 -translate-y-5';
                        } else {
                            // New settings for Day View (Short dates)
                            // Less height, less vertical translation needed
                            heightClass = 'h-16'; 
                            translateClass = 'translate-x-5 -translate-y-5';
                        }

                        parts.push(
                            '<th class="period-cell bg-white border-b-2 border-slate-200 sticky top-0 p-0 align-bottom relative whitespace-nowrap overflow-visible w-10 ', heightClass, '"',
                            ' style="z-index:', (15000 - idx), ';">',
                            '<div class="absolute bottom-0 left-0 w-full h-0">',
                            '<span class="block text-xs font-bold text-slate-500 uppercase tracking-wider transform -rotate-45 origin-bottom-left ', translateClass, '">',
                            this.escapeHtml(col.label),
                            '</span></div></th>'
                        );
                    } else {
                        // Standard Mode
                        parts.push(
                            '<th class="period-cell sticky top-0 text-right text-xs font-bold text-slate-500 uppercase tracking-wider px-3 py-2.5 whitespace-nowrap w-16 bg-white border-b-2 border-slate-200"',
                            ' style="z-index:20;">',
                            this.escapeHtml(col.label),
                            '</th>'
                        );
                    }
                }

                // --- Total Column Header (Sticky Top + Right — corner cell) ---
                const totalVAlign = isCompact ? ' vertical-align:bottom;' : '';
                parts.push(
                    '<th class="text-right text-xs font-bold text-slate-700 uppercase tracking-wider bg-white border-b-2 border-slate-300 px-3 py-2.5 whitespace-nowrap w-24"',
                    ' style="position:sticky;top:0;right:0;z-index:30000;', totalVAlign, '">',
                    'Total</th>'
                );

                parts.push('</tr></thead>');
                return parts.join('');
            },

            /**
             * Builds the <tbody> HTML string.
             * Includes all data rows and the totals row (when rows > 1).
             * Preserves sticky left/right/bottom positioning and data-col-key
             * attributes for crosshair highlighting.
             */
            buildTbodyHTML() {
                const parts = [];
                const rowCount = this.rows.length;
                const scopeCols = this.scopeColumns;
                const periodCols = this.periodColumns;

                // Pre-compute scope column layout (avoids repeated lookups in the hot loop)
                const scopeLayout = scopeCols.map((col, colIdx) => ({
                    key: col.key,
                    left: this.getScopeStickyLeft(colIdx),
                    width: this.scopeColWidths[col.key] || this.SCOPE_COL_WIDTH
                }));

                parts.push('<tbody>');

                // --- Data Rows ---
                for (let rowIdx = 0; rowIdx < rowCount; rowIdx++) {
                    const row = this.rows[rowIdx];
                    parts.push('<tr class="border-b border-slate-100">');

                    // Scope cells (Sticky Left)
                    for (let s = 0; s < scopeLayout.length; s++) {
                        const sc = scopeLayout[s];
                        const val = row[sc.key] || '—';
                        const escaped = this.escapeHtml(val);

                        parts.push(
                            '<td class="text-sm text-slate-700 font-medium px-3 py-2 whitespace-nowrap border-r border-slate-100 overflow-hidden text-ellipsis bg-white"',
                            ' data-col-key="', this.escapeHtml(sc.key), '"',
                            ' style="position:sticky;left:', sc.left,
                            'px;z-index:10;width:', sc.width,
                            'px;max-width:', sc.width, 'px;"',
                            ' title="', escaped, '">',
                            escaped,
                            '</td>'
                        );
                    }

                    // Period cells (visibility controlled by CSS .cols-collapsed class)
                    for (let p = 0; p < periodCols.length; p++) {
                        const pKey = periodCols[p].key;
                        const raw = row[pKey];
                        const num = parseFloat(raw) || 0;
                        const colorClass = num > 0 ? 'text-black' : 'text-slate-300';

                        parts.push(
                            '<td class="period-cell text-sm text-right tabular-nums px-3 py-2 whitespace-nowrap bg-white ',
                            colorClass, '"',
                            ' data-col-key="', this.escapeHtml(pKey), '">',
                            this.formatHours(raw),
                            '</td>'
                        );
                    }

                    // Total cell (Sticky Right)
                    parts.push(
                        '<td class="text-sm text-right font-semibold tabular-nums text-slate-800 px-3 py-2 whitespace-nowrap border-l border-slate-200 bg-slate-50"',
                        ' data-col-key="__total__"',
                        ' style="position:sticky;right:0;z-index:10;">',
                        this.formatHours(row.total),
                        '</td>'
                    );

                    parts.push('</tr>');
                }

                // --- Totals Row (Sticky Bottom) — only when more than 1 data row ---
                if (rowCount > 1) {
                    parts.push('<tr class="border-t-2 border-slate-300 bg-slate-50 font-semibold">');

                    // Scope cells — first shows "Totals", rest blank (Sticky Bottom + Left)
                    for (let s = 0; s < scopeLayout.length; s++) {
                        const sc = scopeLayout[s];
                        const content = s === 0
                            ? '<span class="font-bold uppercase text-xs tracking-wider text-slate-600">Totals</span>'
                            : '';

                        parts.push(
                            '<td class="text-sm text-slate-700 bg-slate-50 px-3 py-2.5 whitespace-nowrap border-r border-slate-100"',
                            ' style="position:sticky;bottom:0;left:', sc.left,
                            'px;z-index:200;width:', sc.width,
                            'px;max-width:', sc.width, 'px;">',
                            content,
                            '</td>'
                        );
                    }

                    // Period total cells (Sticky Bottom)
                    for (let p = 0; p < periodCols.length; p++) {
                        const total = this.computeColumnTotal(periodCols[p].key);
                        parts.push(
                            '<td class="period-cell text-sm text-right tabular-nums text-slate-700 font-bold px-3 py-2.5 whitespace-nowrap bg-slate-50"',
                            ' style="position:sticky;bottom:0;z-index:100;">',
                            this.formatHours(total),
                            '</td>'
                        );
                    }

                    // Grand total cell (Sticky Bottom + Right — corner)
                    parts.push(
                        '<td class="text-sm text-right tabular-nums text-slate-900 font-bold px-3 py-2.5 whitespace-nowrap bg-slate-200 border-l border-slate-300"',
                        ' style="position:sticky;bottom:0;right:0;z-index:200;">',
                        this.formatHours(this.computeGrandTotal()),
                        '</td>'
                    );

                    parts.push('</tr>');
                }

                parts.push('</tbody>');
                return parts.join('');
            },

            // ================================================================
            //  DISPLAY HELPERS
            // ================================================================

            /**
             * Returns whether a scope column is a "sub-code" column
             * (gets wider treatment).
             */
            isSubCodeColumn(col) {
                if (!col || !col.key) return false;
                const key = col.key.toLowerCase();
                return key.includes('sub');
            },

            /**
             * Helper to create/reuse canvas context for text measurement
             */
            getCanvasContext() {
                if (!this.canvasContext) {
                    const canvas = document.createElement('canvas');
                    this.canvasContext = canvas.getContext('2d');
                }
                return this.canvasContext;
            },

            /**
             * Measures text width in pixels matching the Tailwind classes.
             * Measures against both header (bold 12px) and body (medium 14px) styles
             * and returns the larger value to ensure fitment.
             */
            measureTextWidth(text) {
                const ctx = this.getCanvasContext();
                if (!text) return 0;
                
                ctx.font = "bold 12px sans-serif"; 
                const w1 = ctx.measureText(String(text).toUpperCase()).width;

                ctx.font = "500 14px sans-serif";
                const w2 = ctx.measureText(String(text)).width;

                return Math.max(w1, w2);
            },

            /**
             * Calculates the width for all scope columns based on content + padding,
             * respects max-width limits, and enforces the MIN_TABLE_WIDTH minimum.
             */
            calculateColumnWidths() {
                const newWidths = {};
                let totalScopeWidth = 0;

                this.scopeColumns.forEach(col => {
                    const isSub = this.isSubCodeColumn(col);
                    const maxAllowed = isSub ? this.SUBCODE_COL_WIDTH : this.SCOPE_COL_WIDTH;

                    let maxW = this.measureTextWidth(col.label);

                    this.rows.forEach(row => {
                        const val = row[col.key];
                        if (val) {
                            const w = this.measureTextWidth(val);
                            if (w > maxW) maxW = w;
                        }
                    });

                    let finalW = Math.ceil(maxW + 24);
                    if (finalW > maxAllowed) finalW = maxAllowed;

                    newWidths[col.key] = finalW;
                    totalScopeWidth += finalW;
                });

                const periodColWidth = 64; 
                const totalColWidth = 96;
                
                const currentTableWidth = totalScopeWidth 
                                        + (this.periodColumns.length * periodColWidth) 
                                        + totalColWidth;

                if (currentTableWidth < this.MIN_TABLE_WIDTH) {
                    const deficit = this.MIN_TABLE_WIDTH - currentTableWidth;
                    
                    if (this.scopeColumns.length > 0) {
                        const lastKey = this.scopeColumns[this.scopeColumns.length - 1].key;
                        newWidths[lastKey] += deficit;
                    }
                }

                this.scopeColWidths = newWidths;
                this.recalculateTableWidth();
            },

            /**
             * Returns the pixel width for a given scope column.
             */
            getColWidth(col) {
                if (!this.scopeColWidths || !this.scopeColWidths[col.key]) {
                     return this.isSubCodeColumn(col) ? this.SUBCODE_COL_WIDTH : this.SCOPE_COL_WIDTH;
                }
                return this.scopeColWidths[col.key];
            },

            /**
             * Returns the sticky left offset (px) for a scope column at the given index.
             */
            getScopeStickyLeft(index) {
                let left = 0;
                for (let i = 0; i < index; i++) {
                    const key = this.scopeColumns[i].key;
                    if (this.scopeColWidths && this.scopeColWidths[key]) {
                        left += this.scopeColWidths[key];
                    } else {
                        left += this.isSubCodeColumn(this.scopeColumns[i]) ? this.SUBCODE_COL_WIDTH : this.SCOPE_COL_WIDTH;
                    }
                }
                return left;
            },

            /**
             * Formats an hours value for display.
             * Shows two decimal places for non-zero values, a dash for zero.
             */
            formatHours(value) {
                const num = parseFloat(value) || 0;
                if (num === 0) return '—';
                return num % 1 === 0 ? num.toFixed(1) : num.toFixed(2);
            },

            /**
             * Computes the column total for a given period key across all rows.
             */
            computeColumnTotal(periodKey) {
                return this.rows.reduce((sum, row) => {
                    return sum + (parseFloat(row[periodKey]) || 0);
                }, 0);
            },

            /**
             * Computes the grand total across all rows.
             */
            computeGrandTotal() {
                return this.rows.reduce((sum, row) => {
                    return sum + (parseFloat(row.total) || 0);
                }, 0);
            },

            // ================================================================
            //  SORT
            // ================================================================

            /**
             * Toggles between the server's original sort order and
             * client-side sort by total (descending). The original row
             * order is stashed on first data load and restored on toggle-off.
             * Re-renders the table innerHTML after swapping.
             */
            toggleSortByTotal() {
                this.sortByTotal = !this.sortByTotal;

                if (this.sortByTotal) {
                    // Sort descending by total
                    this.rows = this.rows.slice().sort((a, b) => {
                        return (parseFloat(b.total) || 0) - (parseFloat(a.total) || 0);
                    });
                } else {
                    // Restore original server sort order
                    this.rows = [...this._originalRows];
                }

                this.renderTable();

                // Clear crosshair state since DOM was rebuilt
                this.clearHighlights();
            },

            // ================================================================
            //  HIGHLIGHT SYSTEM (event-delegated, direct DOM manipulation)
            //
            //  Row highlighting uses pure CSS (tr:hover) — zero JS cost.
            //  Column highlighting (crosshair mode) uses direct DOM class
            //  manipulation via a single delegated mouseover handler.
            //
            //  This works identically with innerHTML-rendered cells because
            //  it relies on data-col-key attributes and querySelectorAll.
            // ================================================================

            handleTableHover(event) {
                if (!this.crosshairEnabled) return;

                const td = event.target.closest('td');
                if (!td) return;

                const colKey = td.dataset.colKey;
                if (colKey === this._currentHighlightCol) return;

                this.clearColumnHighlights();
                this._currentHighlightCol = colKey;

                if (colKey) {
                    const table = this.$refs.historianTable;
                    if (table) {
                        const cells = table.querySelectorAll('td[data-col-key="' + colKey + '"]');
                        for (let i = 0; i < cells.length; i++) {
                            cells[i].classList.add('crosshair-col-highlight');
                        }
                    }
                }
            },

            clearHighlights() {
                this.clearColumnHighlights();
                this._currentHighlightCol = null;
            },

            clearColumnHighlights() {
                const table = this.$refs.historianTable;
                if (table) {
                    const highlighted = table.querySelectorAll('.crosshair-col-highlight');
                    for (let i = 0; i < highlighted.length; i++) {
                        highlighted[i].classList.remove('crosshair-col-highlight');
                    }
                }
            },

            toggleCrosshair() {
                this.crosshairEnabled = !this.crosshairEnabled;
                if (!this.crosshairEnabled) {
                    this.clearColumnHighlights();
                    this._currentHighlightCol = null;
                }
            },

            /**
             * Toggles column collapse. Visibility is handled by the CSS
             * .cols-collapsed class on the table — no innerHTML rebuild needed.
             * Only recalculates the table width to account for hidden/shown columns.
             */
            toggleCollapse() {
                this.columnsCollapsed = !this.columnsCollapsed;
                this.recalculateTableWidth();

                // Clear crosshair state when collapsing (period columns are hidden)
                if (this.columnsCollapsed) {
                    this.clearColumnHighlights();
                    this._currentHighlightCol = null;
                }
            },

            // ================================================================
            //  CONTAINER WIDTH SYNC
            // ================================================================

            syncContainerWidth() {
                this.$nextTick(() => {
                    const table = this.$refs.historianTable;
                    const wrapper = this.$refs.rootWrapper;
                    if (!table || !wrapper) return;

                    if (this._resizeObserver) {
                        this._resizeObserver.disconnect();
                    }

                    const updateWidth = () => {
                        const tableWidth = table.offsetWidth;
                        if (tableWidth > 0) {
                            wrapper.style.width = tableWidth + 'px';
                        }
                    };

                    this._resizeObserver = new ResizeObserver(updateWidth);
                    this._resizeObserver.observe(table);

                    updateWidth();
                });
            },

            resetContainerWidth() {
                const wrapper = this.$refs.rootWrapper;
                if (wrapper) {
                    wrapper.style.width = '';
                }
                if (this._resizeObserver) {
                    this._resizeObserver.disconnect();
                    this._resizeObserver = null;
                }
            },

            recalculateTableWidth() {
                const scopeWidth = Object.values(this.scopeColWidths).reduce((sum, w) => sum + w, 0);
                
                // If mode is NOT standard, we use the narrow column width (40), otherwise standard (64)
                const isCompact = this.headerMode !== 'standard';
                const periodColWidth = 72
                
                const totalColWidth = 96;

                const computedTableWidth = scopeWidth
                                        + (this.columnsCollapsed ? 0 : this.periodColumns.length * periodColWidth)
                                        + totalColWidth;

                this.tableWidth = Math.max(computedTableWidth, this.MIN_TABLE_WIDTH);
                this.syncContainerWidth();
            },


            // ================================================================
            //  EVENT HANDLERS
            // ================================================================

            handleDataLoading() {
                this.isLoading = true;
                this.error = null;
                this.resetContainerWidth();
            },

            handleDataUpdate(event) {
                const responseObj = event.detail.historianTable;

                if (!responseObj) {
                    this.handleError('Invalid response format: missing historianTable key.');
                    return;
                }
                if (responseObj.errors) {
                    this.handleError(responseObj.errors);
                    return;
                }

                const data = responseObj.data;
                
                // Assign data to reactive properties (used by toolbar, export, state checks)
                this.scopeColumns = data.scopeColumns || [];
                this.periodColumns = data.periodColumns || [];
                this.rows = data.rows || [];
                this.headerMode = data.headerMode || "standard";

                // Stash original server sort order and reset sort state
                this._originalRows = [...this.rows];
                this.sortByTotal = false;

                // Calculate column widths (canvas measurements)
                this.calculateColumnWidths();

                // Build and inject table HTML while still in loading state.
                // The table element exists in DOM (x-show is just display:none),
                // so innerHTML works with zero layout cost. This prevents the
                // flash of an empty/collapsed table between loading → rendered.
                this.renderTable();

                // NOW reveal — table already has content when Alpine shows the wrapper
                this.isLoading = false;
                this.error = null;

                this.$nextTick(() => {
                    this.syncContainerWidth();
                });
            },

            handleError(errorMessage) {
                this.error = errorMessage;
                this.scopeColumns = [];
                this.periodColumns = [];
                this.rows = [];
                this.isLoading = false;
                this.resetContainerWidth();
            },

            handleFetchError(event) {
                const errorMessage = event.detail.message || event.detail;
                this.handleError(errorMessage);
            },

            // ================================================================
            //  EXPORT FUNCTIONALITY
            // ================================================================

            openExportModal() {
                const date = new Date().toISOString().slice(0, 10);
                const defaultName = `historian-export-${date}`;
                this.$dispatch('open-export-modal', { defaultName: defaultName });
            },

            handleExport(event) {
                const fileName = event.detail.name || 'historian-export';
                this.exportToCSV(fileName);
            },

            exportToCSV(fileName) {
                if (!this.rows || this.rows.length === 0) return;

                const columns = [
                    ...this.scopeColumns.map(col => ({ header: col.label, key: col.key })),
                    ...this.periodColumns.map(col => ({ header: col.label, key: col.key })),
                    { header: 'Total', key: 'total' }
                ];

                const escapeCSV = (value) => {
                    if (value === null || value === undefined) return '';
                    const str = String(value);
                    if (str.includes(',') || str.includes('"') || str.includes('\n')) {
                        return `"${str.replace(/"/g, '""')}"`;
                    }
                    return str;
                };

                const formatRow = (rowObj) => {
                    return columns.map(col => {
                        let value = rowObj[col.key];
                        if (value === undefined || value === null) return '';
                        if (typeof value === 'number') {
                            value = value.toFixed(2);
                        }
                        return escapeCSV(value);
                    });
                };

                let csvRows = this.rows.map(row => formatRow(row));

                if (this.rows.length > 1) {
                    const totalsRow = {};

                    this.scopeColumns.forEach((col, idx) => {
                        totalsRow[col.key] = idx === 0 ? 'Totals' : '';
                    });

                    this.periodColumns.forEach(col => {
                        totalsRow[col.key] = this.computeColumnTotal(col.key);
                    });

                    totalsRow.total = this.computeGrandTotal();
                    csvRows.push(formatRow(totalsRow));
                }

                const headers = columns.map(col => escapeCSV(col.header));
                const csvContent = [
                    headers.join(','),
                    ...csvRows.map(row => row.join(','))
                ].join('\n');

                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);

                link.setAttribute('href', url);
                link.setAttribute('download', `${fileName}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            },
        };
    }
</script>
@endpush