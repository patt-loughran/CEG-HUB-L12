{{-- resources/views/components/general/export-modal.blade.php --}}
<div x-data="{ showModal: false, fileName: '' }"
     x-on:open-export-modal.window="showModal = true; fileName = $event.detail?.defaultName || ''"
     x-show="showModal"
     x-cloak
     class="fixed inset-0 z-50000 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    
    <!-- Modal Card -->
    <div x-on:click.away="showModal = false"
         x-on:keydown.escape.window="showModal = false"
         x-show="showModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl ring-1 ring-gray-900/5">
        
        <!-- Header -->
        <div class="flex items-start justify-between">
            <div class="flex gap-4">
                <!-- Icon: Squarish + Blue -->
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg border border-blue-100 bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                </div>
                
                <!-- Text -->
                <div>
                    <h3 class="text-base font-bold text-gray-900">Export Table Data</h3>
                    <p class="mt-1 text-sm text-gray-500">Compile the current view into a CSV file.</p>
                </div>
            </div>
            
            <!-- Close Button -->
            <button x-on:click="showModal = false" 
                    class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-500 transition-colors cursor-pointer">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                </svg>
            </button>
        </div>

        <!-- Body / Input -->
        <div class="mt-6">
            <label for="export_filename" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-500">
                Filename
            </label>
            
            <div class="flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-blue-600">
                <input type="text" 
                    id="export_filename"
                    x-model="fileName"
                    x-on:keydown.enter.prevent="if(fileName.trim()) { $dispatch('process-export', { name: fileName }); showModal = false; }"
                    class="block w-full border-0 bg-transparent py-2.5 pl-3 text-gray-900 placeholder:text-gray-400 focus:ring-0 focus:outline-none sm:text-sm sm:leading-6" 
                    placeholder="payroll-export">
                <span class="flex select-none items-center pr-3 text-gray-500 sm:text-sm">.csv</span>
            </div>
        </div>

        <!-- Footer / Actions -->
        <div class="mt-8 flex justify-end gap-3">
            <button x-on:click="showModal = false"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-all cursor-pointer">
                Cancel
            </button>
            
            <button x-on:click="if(fileName.trim()) { $dispatch('process-export', { name: fileName }); showModal = false; }"
                    x-bind:disabled="!fileName.trim()"
                    x-bind:class="fileName.trim() ? 'bg-blue-600 hover:bg-blue-500 cursor-pointer' : 'bg-blue-300 cursor-not-allowed'"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-all">
                Export Data
            </button>
        </div>
    </div>
</div>