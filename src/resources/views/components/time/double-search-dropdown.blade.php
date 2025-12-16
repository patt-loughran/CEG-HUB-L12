    
@props([
    'cellType',
    'placeholder',
    'accessor'
])

{{--
This component is an enhanced dropdown that displays two lines of text for each option:
- A primary value (e.g., a code) in bold.
- A secondary value (e.g., a name or description) in smaller, grey text.
--}}
<div x-data="doubleDropdownComponent( {{ $cellType }} )" class="relative">
    <input
        x-model="{{ $accessor }}"
        x-ref="input"
        @focus="openDropdown()"
        @click.away="closeDropdown()"
        @keydown.escape.prevent="closeDropdown()"
        type="text"
        placeholder="{{ $placeholder }}"
        class="w-full px-4 py-2 text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
    >
    <template x-teleport="body">
    <div
        x-show="isOpen"
        x-transition
        x-ref="panel"
        class="absolute w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto z-100"
    >
        {{-- MODIFIED PART: The template for each option is now a two-line display --}}
        <template x-for="option in filteredOptions()" :key="option.code">
            <div
                @click="selectOption(option)"
                class="px-4 py-2 cursor-pointer hover:bg-gray-100 flex flex-col"
            >
                {{-- Line 1: The code, displayed in bold --}}
                <strong class="text-sm font-bold" x-text="option.code"></strong>
                {{-- Line 2: The name/description, in smaller grey text --}}
                <span class="text-sm text-gray-500" x-text="option.name"></span>
            </div>
        </template>
    </div>
    </template>
</div>

@once
@push('scripts')
<script>
    // Component: The new JavaScript logic for the double search dropdown.
    function doubleDropdownComponent(cellType) {
        return {
            cellType: null,
            isOpen: null,

            init() {
                this.cellType = cellType;
                this.isOpen = false;

                // Create a single, bound instance of the reposition function
                this.boundReposition = this.reposition.bind(this);

                // Watch the 'isOpen' state to add/remove event listeners
                this.$watch('isOpen', isOpen => {
                    if (isOpen) {
                        // Wait for the panel to be visible before positioning it
                        this.$nextTick(() => this.reposition());
                        window.addEventListener('scroll', this.boundReposition, true); // Use capture phase
                        window.addEventListener('resize', this.boundReposition);
                    } else {
                        window.removeEventListener('scroll', this.boundReposition, true);
                        window.removeEventListener('resize', this.boundReposition);
                    }
                });
            },

            openDropdown() {
                this.isOpen = true;
            },

            closeDropdown() {
                this.isOpen = false;
            },

            reposition() {
                // If the dropdown is not open, do nothing.
                if (!this.isOpen) return;

                const input = this.$refs.input;
                const panel = this.$refs.panel;
                
                // Get position and dimensions of the input field relative to the viewport
                const inputRect = input.getBoundingClientRect();

                // Set the panel's width to match the input's width
                panel.style.width = `${inputRect.width}px`;
                
                // --- Flipping Logic ---
                const panelHeight = panel.offsetHeight; // Get the actual height of the panel
                const spaceBelow = window.innerHeight - inputRect.bottom;
                const spaceAbove = inputRect.top;

                let top, left;

                // If not enough space below AND there's more space above, place it on top.
                if (spaceBelow < panelHeight && spaceAbove > spaceBelow) {
                    // Position Above
                    top = inputRect.top + window.scrollY - panelHeight - 4; // 4px gap
                } else {
                    // Position Below (Default)
                    top = inputRect.top + window.scrollY + inputRect.height + 4; // 4px gap
                }
                
                left = inputRect.left + window.scrollX;

                // Apply the calculated styles to the panel
                panel.style.top = `${top}px`;
                panel.style.left = `${left}px`;
            },

            /**
             * MODIFIED: This function now accepts an object {code, name}
             * and assigns the code to the input model.
             */
            selectOption(option) {
                if (this.cellType === 'project_code') {
                    this.row.project_code = option.code;
                }
                // No other cell types are handled by this component, but could be added.
                this.closeDropdown();
            },

            /**
             * MODIFIED: This function transforms the controller's data into an array
             * of {code, name} objects and filters on both properties.
             */
            filteredOptions() {
                if (this.cellType === 'project_code') {
                    // 1. Transform the data from the controller into the format we need.
                    const allProjects = Object.keys(this.dropdownData || {}).map(code => {
                        return {
                            code: code,
                            name: this.dropdownData[code].project_name 
                        };
                    });

                    const searchTerm = this.row.project_code;

                    // 2. If the input is empty, show all projects.
                    if (!searchTerm) {
                        return allProjects;
                    }
                    
                    // 3. Filter the projects array. An option is included if the search term
                    //    is found in either its code or its name.
                    return allProjects.filter(
                        project => project.code.toLowerCase().includes(searchTerm.toLowerCase()) ||
                                   project.name.toLowerCase().includes(searchTerm.toLowerCase())
                    );
                }

                // Fallback for any other cellType, returning an empty array.
                return [];
            }
        }
    }
</script>
@endpush
@endonce

  