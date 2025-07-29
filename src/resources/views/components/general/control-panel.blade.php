<div class="bg-white p-6 border border-slate-300 rounded-lg shadow-sm w-full">
    <div class="space-y-6">

        <!-- Date Selection Section -->
        <div class="space-y-3">
            <!-- Header with Label and Toggle -->
            <div class="flex justify-between items-center">
                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Date Range Selection</label>
                <div class="p-0.5 bg-slate-200 rounded-md flex items-center text-sm">
                    <button class="px-2 py-0.5 rounded-sm font-medium text-slate-700">Month</button>
                    <button class="px-2 py-0.5 rounded-sm font-semibold text-slate-800 bg-white shadow-sm">Pay Period</button>
                </div>
            </div>
            <!-- Proportional Dropdowns Container -->
            <div class="grid grid-cols-4 gap-2">
                <!-- Year Dropdown (25% width) -->
                <select class="col-span-1 w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-500">
                    <option>2024</option>
                    <option>2023</option>
                </select>
                <!-- Date Range Dropdown (75% width) -->
                <select class="col-span-3 w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-500">
                    <option>PP 24 (11/17 - 11/30)</option>
                    <option>PP 23 (11/03 - 11/16)</option>
                </select>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="border-t border-slate-200 pt-4">
            <div class="flex justify-between items-center mb-3">
                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Quick Filters</label>
                <div class="flex items-center gap-2">
                    <!-- More Filters Icon Button -->
                    <div class="relative">
                        <button class="group p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 rounded-full">
                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max bg-gray-800 text-white text-xs rounded-md py-1 px-2 invisible group-hover:visible">More filters</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" /></svg>
                        </button>
                    </div>
                    <!-- Save/Load Presets Icon Button -->
                    <div class="relative">
                        <button class="group p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 rounded-full">
                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max bg-gray-800 text-white text-xs rounded-md py-1 px-2 invisible group-hover:visible">Save/Load configs</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v12l-5-2.5L5 16V4z" /></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button class="px-3 py-1 text-sm font-semibold text-white bg-slate-700 rounded-full">Active</button>
                <button class="px-3 py-1 text-sm font-medium text-slate-600 bg-slate-200 hover:bg-slate-300 rounded-full">Hourly</button>
                <button class="px-3 py-1 text-sm font-medium text-slate-600 bg-slate-200 hover:bg-slate-300 rounded-full">Salaried</button>
            </div>
        </div>
    </div>
</div>