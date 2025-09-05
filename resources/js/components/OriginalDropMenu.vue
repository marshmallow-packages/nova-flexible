<template>
    <div class="relative nova-flexible-dropdown" v-if="layouts">
        <div class="relative" v-if="layouts.length > 1">
            <div
                v-if="isLayoutsDropdownOpen"
                ref="dropdown"
                class="fixed rounded-lg shadow-lg w-64 max-h-60 overflow-y-auto border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800"
                style="z-index: 10000; min-width: 250px;"
            >
                <div>
                    <ul class="list-reset">
                        <li
                            v-for="layout in filteredLayouts"
                            class="border-b border-gray-100 dark:border-gray-700"
                            :key="'add-' + layout.name"
                        >
                            <a
                                :dusk="'add-' + layout.name"
                                @click="addGroup(layout)"
                                class="cursor-pointer flex items-center hover:bg-gray-50 dark:hover:bg-gray-900 block py-2 px-3 no-underline font-normal bg-white dark:bg-gray-800"
                            >
                                <div>
                                    <p class="text-90">{{ layout.title }}</p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <button
            ref="button"
            class="inline-flex items-center flex-shrink-0 px-4 text-sm font-bold text-white rounded shadow focus:outline-none focus:ring bg-primary-500 hover:bg-primary-400 active:bg-primary-600 dark:text-gray-800 h-9"
            dusk="toggle-layouts-dropdown-or-add-default"
            type="button"
            tabindex="0"
            @click="toggleLayoutsDropdownOrAddDefault"
            v-if="isBelowLayoutLimits"
        >
            {{ field.button }}
        </button>
    </div>
</template>

<script>
    export default {
        props: [
            "layouts",
            "field",
            "resourceName",
            "resourceId",
            "resource",
            "errors",
            "limitCounter",
            "allowedToCreate",
            "allowedToDelete",
            "allowedToChangeOrder",
            "limitPerLayoutCounter",
        ],

        emits: ["addGroup"],

        data() {
            return {
                isLayoutsDropdownOpen: false,
            };
        },

        computed: {
            filteredLayouts() {
                return this.layouts.filter((layout) => {
                    const count = this.limitPerLayoutCounter[layout.name];

                    return (
                        count === null ||
                        count > 0 ||
                        typeof count === "undefined"
                    );
                });
            },

            isBelowLayoutLimits() {
                return (
                    (this.limitCounter > 0 || this.limitCounter === null) &&
                    this.filteredLayouts.length > 0
                );
            },
        },

        mounted() {
            // Add click outside listener
            document.addEventListener('click', this.handleClickOutside);
            // Add scroll listener to close dropdown when scrolling
            window.addEventListener('scroll', this.handleScroll, true);
            window.addEventListener('resize', this.handleResize);
        },

        beforeUnmount() {
            // Remove listeners
            document.removeEventListener('click', this.handleClickOutside);
            window.removeEventListener('scroll', this.handleScroll, true);
            window.removeEventListener('resize', this.handleResize);
        },

        methods: {
            /**
             * Handle clicks outside the dropdown to close it
             */
            handleClickOutside(event) {
                if (this.isLayoutsDropdownOpen && this.$el && !this.$el.contains(event.target)) {
                    this.isLayoutsDropdownOpen = false;
                }
            },

            /**
             * Handle scroll events to close dropdown
             */
            handleScroll() {
                if (this.isLayoutsDropdownOpen) {
                    this.isLayoutsDropdownOpen = false;
                }
            },

            /**
             * Handle resize events to close dropdown
             */
            handleResize() {
                if (this.isLayoutsDropdownOpen) {
                    this.isLayoutsDropdownOpen = false;
                }
            },

            /**
             * Position the dropdown relative to the button
             */
            positionDropdown() {
                this.$nextTick(() => {
                    if (this.$refs.dropdown && this.$refs.button) {
                        const button = this.$refs.button;
                        const dropdown = this.$refs.dropdown;
                        const rect = button.getBoundingClientRect();
                        
                        dropdown.style.top = `${rect.bottom + 4}px`;
                        dropdown.style.left = `${rect.left}px`;
                    }
                });
            },

            /**
             * Display or hide the layouts choice dropdown if there are multiple layouts
             * or directly add the only available layout.
             */
            toggleLayoutsDropdownOrAddDefault(event) {
                if (this.layouts.length === 1) {
                    return this.addGroup(this.layouts[0]);
                }

                this.isLayoutsDropdownOpen = !this.isLayoutsDropdownOpen;
                
                if (this.isLayoutsDropdownOpen) {
                    this.positionDropdown();
                }
            },

            /**
             * Append the given layout to flexible content's list
             */
            addGroup(layout) {
                if (!layout) return;

                this.$emit("addGroup", layout);
                Nova.$emit("nova-flexible-content-add-group", layout);

                this.isLayoutsDropdownOpen = false;
            },
        },
    };
</script>

<style>
    .nova-flexible-dropdown {
        position: relative;
    }
    
    .nova-flexible-dropdown .fixed {
        /* Ensure dropdown appears above all Nova content */
        z-index: 10000 !important;
        /* Add subtle shadow for better visibility */
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
</style>
