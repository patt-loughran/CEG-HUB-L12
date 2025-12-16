@props([
    'cellType',
    'placeholder',
    'accessor'
])

{{-- 

--}}
<div x-data="dropdownComponent( {{ $cellType }} )" class="relative">
    <input 
        x-model="{{ $accessor }}"
        x-ref="input"
        @focus="openDropdown()"
        @click.away="closeDropdown()"
        @keydown.escape.prevent="closeDropdown()"
        type="text"
        placeholder="{{ $placeholder }}"
        class="w-full px-4 py-2 text-gray-800 bg-white  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
    >
    <template x-teleport="body">
    <div
        x-show="isOpen" 
        x-transition 
        x-ref="panel"
        class="absolute w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto z-100"
    >
        <template x-for="option in filteredOptions()" :key="option">
            <div
                @click="selectOption(option)"
                class="px-4 py-2 cursor-pointer hover:bg-gray-100"
                x-text="option"
            ></div>
        </template>
    </div>
    </template>
</div>

@once
@push('scripts')
<script>
    // Component 2: The Reusable Child Dropdown
    function dropdownComponent(cellType) {
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


            selectOption(option) {
                if (this.cellType === 'project_code') {
                    this.row.project_code = option;
                }
                if (this.cellType === 'sub_project') {
                    this.row.sub_project = option;
                }
                if (this.cellType === 'activity_code') {
                    this.row.activity_code = option;
                }
                this.closeDropdown();
            },
            filteredOptions() {
                console.log("in filteredOptions()");
                if (this.cellType === 'project_code') {
                    console.log("for project_code");
                    const projectCodes = Object.keys(this.dropdownData || {});
                    
                    if (this.row.project_code === "") {
                        return projectCodes;
                    }
                    
                    return projectCodes.filter(
                        code => code.toLowerCase().includes(this.row.project_code.toLowerCase())
                    );
                }

                 if (this.cellType === 'sub_project') {
                    console.log("for sub_project");
                    const projectCode = this.row.project_code;
                    const subCodes = Object.keys(this.dropdownData[projectCode]["sub_projects"] || {});
                    
                    if (this.row.sub_project === "") {
                        return subCodes;
                    }
                    
                    return subCodes.filter(
                        code => code.toLowerCase().includes(this.row.sub_project.toLowerCase())
                    );
                }

                if (this.cellType === 'activity_code') {
                    console.log("for activity_code");
                    const projectCode = this.row.project_code;
                    const sub_project = this.row.sub_project;
                    const activityCodes = Object.values(this.dropdownData[projectCode]["sub_projects"][sub_project] || {});
                    
                    if (this.row.activity_code === "") {
                        return activityCodes;
                    }
                    
                    return activityCodes.filter(
                        code => code.toLowerCase().includes(this.row.activity_code.toLowerCase())
                    );
                }

            }
        }
    }
</script>
@endpush
@endonce