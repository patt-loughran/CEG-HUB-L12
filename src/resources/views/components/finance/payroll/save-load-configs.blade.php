 <div class="bg-white p-6 border border-slate-300 rounded-lg shadow-sm w-full flex flex-col" style="height: 550px;">
    <div class="flex-shrink-0 flex justify-between items-center"><h3 class="font-bold text-slate-800 text-lg">Save or Load Preset</h3><button class="p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 rounded-full"><svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button></div>
    <div class="flex-shrink-0 space-y-3 mt-5">
        <label for="preset-name-v2" class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Save Current Configuration</label>
        <div class="flex gap-2 mt-2">
            <input type="text" id="preset-name-v2" placeholder="Enter new preset name..." class="flex-grow w-full bg-slate-50 border border-slate-300 rounded-md py-2 px-3 text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-500 text-sm">
            <button class="px-4 py-2 text-sm font-semibold text-white bg-slate-700 rounded-md hover:bg-slate-800 flex-shrink-0">Save</button>
        </div>
    </div>
    <div class="flex-shrink-0 border-t border-slate-200 my-5"></div>
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="flex-shrink-0"><label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Load Existing Preset</label></div>
        <div class="flex-1 overflow-y-auto -mx-1 px-1 mt-2">
            <div class="space-y-2">
                <a href="#" class="block w-full text-left p-2.5 text-sm font-medium text-slate-700 border border-slate-200 rounded-md hover:bg-slate-100 hover:border-slate-300">Q4 Marketing Analysis</a>
                <!-- Selected Item: Note the border-2 and different bg/text colors -->
                <a href="#" class="block w-full text-left p-2.5 text-sm font-semibold text-slate-800 bg-slate-100 border-2 border-slate-700 rounded-md">Default View</a>
                <a href="#" class="block w-full text-left p-2.5 text-sm font-medium text-slate-700 border border-slate-200 rounded-md hover:bg-slate-100 hover:border-slate-300">Year End Summary</a>
                <a href="#" class="block w-full text-left p-2.5 text-sm font-medium text-slate-700 border border-slate-200 rounded-md hover:bg-slate-100 hover:border-slate-300">Active Hourly Employees</a>
            </div>
        </div>
    </div>
    <div class="flex-shrink-0 mt-auto pt-5 border-t border-slate-200 flex justify-end items-center gap-3">
        <button class="px-4 py-2 text-sm font-semibold bg-white text-red-600 border border-red-600 rounded-md hover:bg-red-600 hover:text-white transition-colors">Delete</button>
        <button class="px-4 py-2 text-sm font-semibold text-white bg-slate-700 rounded-md hover:bg-slate-800">Load Preset</button>
    </div>
</div>