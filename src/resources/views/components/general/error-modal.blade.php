{{-- resources/views/components/general/error-modal.blade.php --}}
<div x-data="{ showModal: false, errorMessage: '' }"
     x-on:error-modal.window="showModal = true; errorMessage = $event.detail.message"
     x-show="showModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30 backdrop-blur-sm"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div x-on:click.away="showModal = false"
         class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl"
         x-show="showModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                   <x-general.icon name="warning" class="h-6 w-6" />
                </div>
                <h3 class="text-xl font-bold text-slate-800">An Error Occurred</h3>
            </div>
            <button x-on:click="showModal = false" class="text-2xl font-semibold text-slate-400 hover:text-slate-600">&times;</button>
        </div>

        <p class="mt-4 text-slate-600" x-text="errorMessage"></p>

        <div class="mt-6 flex justify-end">
            <button x-on:click="showModal = false"
                    class="rounded-md bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Close
            </button>
        </div>
    </div>
</div>