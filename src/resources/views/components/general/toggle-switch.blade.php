@props([
    'options' => [],
    'model' => null // the Alpine.js property this toggle controls
])

<div class="p-0.5 bg-slate-200 rounded-md flex items-center text-sm">
    <template x-for="option in {{ json_encode($options) }}" :key="option.value">
        <button
            @click="{{ $model }} = option.value"
            type="button"
            class="px-2 py-0.5 rounded-sm cursor-pointer"
            x-bind:class="{
                'bg-white shadow-sm font-semibold text-slate-800': {{ $model }} === option.value,
                'text-slate-700 font-medium': {{ $model }} !== option.value
            }"
            x-text="option.label"
        ></button>
    </template>
</div>