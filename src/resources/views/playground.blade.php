<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
<div class="min-h-screen flex items-center justify-center">

{{-- resources/views/components/general/export-modal.blade.php --}}
<div x-data="{ showModal: true, fileName: '' }"
     x-on:open-export-modal.window="showModal = true; fileName = ''"
     x-show="showModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <!-- Modal Card -->
    <div x-on:click.away="showModal = false"
         x-show="showModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="relative w-full max-w-2xl rounded-2xl bg-white p-8 shadow-xl">

        <!-- Header -->
        <div class="flex items-start justify-between">
            <div class="flex gap-4">
                <!-- Icon -->
                <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                   <x-general.icon name="download" class="h-8 w-8" />
                </div>
                <!-- Text -->
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Export Table Data</h3>
                    <p class="mt-1 text-sm font-medium text-gray-500">
                        This will compile the current table view into a downloadable file.
                    </p>
                </div>
            </div>
            
            <!-- Close Button (X) -->
            <button x-on:click="showModal = false" class="text-2xl font-semibold text-slate-400 hover:text-slate-600 cursor-pointer">&times;</button>
        </div>

        <!-- Body / Input -->
        <div class="mt-8">
            <label for="export_filename" class="block text-sm font-semibold text-gray-700">Filename</label>
            <div class="mt-2">
                <input type="text" 
                       id="export_filename"
                       x-model="fileName"
                       x-on:keydown.enter="$dispatch('process-export', { name: fileName }); showModal = false"
                       class="block w-full rounded-lg border-0 py-2.5 px-4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6" 
                       placeholder="spreadsheet-export-v1">
            </div>
        </div>

        <!-- Footer / Actions -->
        <div class="mt-8 flex justify-end gap-3">
            <button x-on:click="showModal = false"
                    class="rounded-lg px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 border border-gray-300 shadow-sm transition-all cursor-pointer">
                Cancel
            </button>
            
            <button x-on:click="$dispatch('process-export', { name: fileName }); showModal = false"
                    class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition-all cursor-pointer">
                Export Data
            </button>
        </div>

    </div>
</div>


</div>
<script>

</script>
 @stack('scripts')
</body>
</html>



<!-- <!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
<div class="min-h-screen flex items-center justify-center">


    <div x-data="simpleComponent()">
        <ul>
            <template x-for="num in nums">
                <li x-text="num"></li>
            </template>
        </ul>
    </div>


</div>

<script>
    // Component 1: The Parent Table
    function simpleComponent() {
        return {
            nums: [1,2,3,4]
        }
    }
</script>
 @stack('scripts')
</body>
</html> -->