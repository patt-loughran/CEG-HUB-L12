<!--
  Dual Panel People Filter - Final Realistic Version
  Copy and paste this code block to see the final component.
-->

<!-- 
  Playground Wrapper: 
  This div provides a background and font to view the component clearly.
-->
<div class="bg-slate-100 p-8 font-sans">
  <div class="max-w-3xl mx-auto space-y-4">

    <h2 class="font-bold text-slate-800 text-lg">Final Component: Dual Panel with Summary View</h2>
    <div class="bg-white p-6 border border-slate-300 rounded-lg shadow-sm w-full flex flex-col" style="height: 600px;">
      <!-- Modal Header -->
      <div class="flex-shrink-0 flex justify-between items-center">
        <h3 class="font-bold text-slate-800 text-lg">Select Employees to Include</h3>
        <button class="p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 rounded-full" title="Close">
          <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      
      <!-- Main Content Area -->
      <div class="flex-1 grid grid-cols-2 gap-5 mt-4 overflow-hidden">
          
          <!-- Left Panel: Selection -->
          <div class="flex flex-col overflow-hidden">
              <div class="relative flex-shrink-0">
                  <svg class="w-5 h-5 text-slate-400 absolute left-3 inset-y-0 my-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
                  <input type="text" placeholder="Search for people or groups..." class="w-full bg-white border border-slate-300 rounded-md py-2 pl-10 pr-3 text-sm focus:outline-none focus:ring-1 focus:ring-slate-500">
              </div>
              <div class="flex-1 border border-slate-200 rounded-md mt-2 overflow-y-auto p-2 space-y-1">
                  <!-- Engineering Group (Partially Selected) -->
                  <details class="group" open>
                      <summary class="flex items-center gap-3 p-1.5 rounded-md cursor-pointer hover:bg-slate-100 list-none">
                          <input type="checkbox" class="h-4 w-4 rounded border-slate-400 text-slate-600 focus:ring-slate-500" onclick="this.checked=!this.checked" x-data x-init="$el.indeterminate = true;">
                          <span class="text-sm font-semibold text-slate-800">Engineering</span>
                      </summary>
                      <ul class="pl-5 mt-1 space-y-1 border-l ml-3.5">
                          <li class="p-1"><label class="flex items-center gap-3 w-full cursor-pointer"><input type="checkbox" checked class="h-4 w-4 rounded border-slate-300 text-slate-600"><span class="text-slate-700 text-sm">Aria Vance</span></label></li>
                          <li class="p-1"><label class="flex items-center gap-3 w-full cursor-pointer"><input type="checkbox" class="h-4 w-4 rounded border-slate-300"><span class="text-slate-700 text-sm">Ben Carter</span></label></li>
                          <li class="p-1"><label class="flex items-center gap-3 w-full cursor-pointer"><input type="checkbox" checked class="h-4 w-4 rounded border-slate-300 text-slate-600"><span class="text-slate-700 text-sm">Diana Evans</span></label></li>
                      </ul>
                  </details>
                  <!-- Marketing Group (Not Selected) -->
                  <details class="group">
                      <summary class="flex items-center gap-3 p-1.5 rounded-md cursor-pointer hover:bg-slate-100 list-none">
                          <input type="checkbox" class="h-4 w-4 rounded border-slate-400 text-slate-600 focus:ring-slate-500">
                          <span class="text-sm font-semibold text-slate-800">Marketing</span>
                      </summary>
                      <ul class="pl-5 mt-1 space-y-1 border-l ml-3.5">
                          <li class="p-1"><label class="flex items-center gap-3 w-full cursor-pointer"><input type="checkbox" class="h-4 w-4 rounded border-slate-300"><span class="text-slate-700 text-sm">Frank Green</span></label></li>
                          <li class="p-1"><label class="flex items-center gap-3 w-full cursor-pointer"><input type="checkbox" class="h-4 w-4 rounded border-slate-300"><span class="text-slate-700 text-sm">Grace Hall</span></label></li>
                      </ul>
                  </details>
                  <!-- Sales Group (Fully Selected) -->
                  <details class="group">
                      <summary class="flex items-center gap-3 p-1.5 rounded-md cursor-pointer hover:bg-slate-100 list-none">
                          <input type="checkbox" checked class="h-4 w-4 rounded border-slate-400 text-slate-600 focus:ring-slate-500">
                          <span class="text-sm font-semibold text-slate-800">Sales</span>
                      </summary>
                      <ul class="pl-5 mt-1 space-y-1 border-l ml-3.5">
                          <li class="p-1"><label class="flex items-center gap-3 w-full cursor-pointer"><input type="checkbox" checked class="h-4 w-4 rounded border-slate-300 text-slate-600"><span class="text-slate-700 text-sm">Henry Irwin</span></label></li>
                          <li class="p-1"><label class="flex items-center gap-3 w-full cursor-pointer"><input type="checkbox" checked class="h-4 w-4 rounded border-slate-300 text-slate-600"><span class="text-slate-700 text-sm">Ivy Jones</span></label></li>
                      </ul>
                  </details>
                   <!-- Design Group (Partially Selected) -->
                  <details class="group">
                      <summary class="flex items-center gap-3 p-1.5 rounded-md cursor-pointer hover:bg-slate-100 list-none">
                          <input type="checkbox" class="h-4 w-4 rounded border-slate-400 text-slate-600 focus:ring-slate-500" onclick="this.checked=!this.checked" x-data x-init="$el.indeterminate = true;">
                          <span class="text-sm font-semibold text-slate-800">Design</span>
                      </summary>
                      <ul class="pl-5 mt-1 space-y-1 border-l ml-3.5">
                          <li class="p-1"><label class="flex items-center gap-3 w-full cursor-pointer"><input type="checkbox" checked class="h-4 w-4 rounded border-slate-300 text-slate-600"><span class="text-slate-700 text-sm">Jack King</span></label></li>
                          <li class="p-1"><label class="flex items-center gap-3 w-full cursor-pointer"><input type="checkbox" class="h-4 w-4 rounded border-slate-300"><span class="text-slate-700 text-sm">Leo Miller</span></label></li>
                      </ul>
                  </details>
              </div>
          </div>
          
          <!-- Right Panel: Summary -->
          <div class="flex flex-col overflow-hidden">
              <label class="flex-shrink-0 text-xs font-semibold text-slate-500 uppercase tracking-wider">Current Filter (5)</label>
              <div class="flex-1 bg-slate-50/70 border border-slate-200 rounded-md mt-2 overflow-y-auto p-2 space-y-3">
                  <!-- Engineering selections -->
                  <div class="space-y-1">
                      <div class="flex items-center justify-between">
                          <h4 class="text-sm font-semibold text-slate-800">Engineering</h4>
                          <button class="px-2.5 py-1 text-xs font-semibold bg-slate-200 text-slate-700 rounded-full hover:bg-red-600 hover:text-white transition-colors">Clear</button>
                      </div>
                      <ul class="divide-y divide-slate-200">
                         <li class="flex items-center justify-between py-1.5 pl-2"><span class="text-slate-700 text-sm">Aria Vance</span><button class="p-1 text-slate-400 hover:text-red-600 rounded-full"><svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg></button></li>
                         <li class="flex items-center justify-between py-1.5 pl-2"><span class="text-slate-700 text-sm">Diana Evans</span><button class="p-1 text-slate-400 hover:text-red-600 rounded-full"><svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg></button></li>
                      </ul>
                  </div>
                   <!-- Sales selections -->
                   <div class="space-y-1">
                      <div class="flex items-center justify-between">
                          <h4 class="text-sm font-semibold text-slate-800">Sales</h4>
                          <button class="px-2.5 py-1 text-xs font-semibold bg-slate-200 text-slate-700 rounded-full hover:bg-red-600 hover:text-white transition-colors">Clear</button>
                      </div>
                      <ul class="divide-y divide-slate-200">
                         <li class="flex items-center justify-between py-1.5 pl-2"><span class="text-slate-700 text-sm">Entire Group</span><button class="p-1 text-slate-400 hover:text-red-600 rounded-full"><svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg></button></li>
                      </ul>
                  </div>
                  <!-- Design selections -->
                  <div class="space-y-1">
                      <div class="flex items-center justify-between"><h4 class="text-sm font-semibold text-slate-800">Design</h4><button class="px-2.5 py-1 text-xs font-semibold bg-slate-200 text-slate-700 rounded-full hover:bg-red-600 hover:text-white transition-colors">Clear</button></div>
                      <ul class="divide-y divide-slate-200"><li class="flex items-center justify-between py-1.5 pl-2"><span class="text-slate-700 text-sm">Jack King</span><button class="p-1 text-slate-400 hover:text-red-600 rounded-full"><svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg></button></li></ul>
                  </div>
              </div>
          </div>
      </div>
      
      <!-- Modal Footer -->
      <div class="flex-shrink-0 mt-auto pt-5 border-t border-slate-200 flex justify-end">
        <button class="px-5 py-2.5 text-sm font-semibold text-white bg-slate-700 rounded-md hover:bg-slate-800">Apply Filter</button>
      </div>
    </div>
    
  </div>
</div>