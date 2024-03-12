<template>
    <Modal
        :show="true"
        class="relative flex flex-col h-full max-w-2xl overflow-hidden bg-white border rounded-lg shadow-lg nova-flexible-modal dark:bg-gray-800 border-gray"
    >
        <ModalHeader class="relative px-6 py-6 border-b border-gray">
            {{ __("Select Layout") }}

            <button
                @click.prevent="handleClose"
                type="button"
                class="text-gray-400 bg-white rounded-md nova-flexible-close hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                <span class="sr-only">Close</span>
                <svg
                    class="w-6 h-6"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </ModalHeader>

        <div class="relative flex-1 bg-white rounded-lg h-90p">
            <div class="flex flex-wrap px-2 py-4 border-b border-gray">
                <div class="w-1/2 px-4">
                    <SelectControl
                        class="w-full"
                        :placeholder="__('All')"
                        v-model:selected="filter.type"
                        @change="filter.type = $event"
                    >
                        <option value disabled="disabled">Select a tag</option>
                        <option value="all">All</option>
                        <option
                            v-for="tag in tags"
                            :key="tag"
                            :id="tag"
                            :value="tag"
                            v-html="tag"
                        ></option>
                    </SelectControl>
                </div>
                <div class="w-1/2 px-4">
                    <input
                        type="text"
                        id="search"
                        class="w-full form-control form-input form-control-bordered"
                        placeholder="Search layouts"
                        v-model="filter.search"
                    />
                </div>
            </div>
            <div class="px-4 py-4 nova-flexible-inner">
                <div
                    class="py-6 font-semibold text-center text-md"
                    v-if="isLoading"
                >
                    {{ __("Loading") }}...
                </div>

                <div
                    v-else-if="layouts.length > 0 && !isLoading"
                    class="mm-flex mm-flex-col mm-items-stretch mm-flex-1 mm-flex-grow mm-w-full mm-justify-self-stretch mm-gap-y-4"
                >
                    <div
                        v-for="(layout, index) in visibleLayouts"
                        :key="index"
                        class="shadow-md mm-grid mm-grid-cols-12 mm-w-full mm-border mm-border-gray-200 mm-bg-white group group-hover:border-primary-500 hover:border-primary-500 mm-cursor-pointer"
                        @click="handleConfirm(layout)"
                    >
                        <div class="mm-col-span-4" v-if="layout.image">
                            <div class="h-full">
                                <img
                                    class="mm-object-cover mm-object-center mm-h-full"
                                    :src="layout.image"
                                    :alt="layout.title"
                                />
                            </div>
                        </div>
                        <div
                            class="p-4 ml-4 mm-self-center mm-col-span-8 md:mm-col-span-8"
                        >
                            <div class="mm-leading-tight">
                                <p
                                    class="font-semibold mm-text-md text-primary-500"
                                    v-html="layout.title"
                                ></p>
                                <p
                                    class="mm-mt-2 group-hover:mm-text-primary-500"
                                    v-html="layout.description"
                                ></p>
                                <div class="mm-mt-2">
                                    <span
                                        v-for="tag in layout.tags"
                                        :key="tag"
                                        class="inline-block mr-2 mm-text-sm"
                                        :data-tag="tag"
                                    >
                                        #{{ tag }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script>
    export default {
        name: "SelectorModal",
        emits: ["confirm", "close"],

        props: [
            "layouts",
            "allowedToCreate",
            "allowedToDelete",
            "allowedToChangeOrder",
        ],

        data() {
            return {
                visibleLayouts: this.layouts,
                isLoading: false,
                modalOpen: false,
                tags: {},
                filter: {
                    type: "",
                    search: "",
                },
            };
        },

        mounted() {
            this.isLoading = false;
            this.loadTags();
        },

        methods: {
            loadTags() {
                const available_tags = [];
                var layouts = this.layouts;
                layouts.forEach(function (layout, layout_index) {
                    var tags = layout.tags;
                    if (tags) {
                        tags.forEach(function (tag, tag_index) {
                            if (!available_tags.includes(tag)) {
                                available_tags.push(tag);
                            }
                        });
                    }
                });

                this.tags = available_tags;
            },

            getLayouts() {
                this.isLoading = true;

                let layouts = [];
                let all_layouts = this.layouts;

                all_layouts.forEach((layout, layout_index) => {
                    layout.show = false;

                    let show_layout = this.displayLayout(layout);
                    let tags = layout.tags;
                    if (tags) {
                        let show = false;
                        tags.forEach((tag, tag_index) => {
                            show = this.displayFilter(tag);
                            if (show && show_layout) {
                                layout.show = true;
                            }
                        });
                    } else if (show_layout) {
                        layout.show = true;
                    } else {
                        layout.show = false;
                    }

                    if (layout.show) {
                        layouts.push(layout);
                    }
                });

                this.$nextTick(function () {
                    this.visibleLayouts = layouts;
                    this.isLoading = false;
                });
            },

            displayLayout(layout) {
                if (this.filter.search === "") {
                    return true;
                }

                let keyword = this.filter.search.toUpperCase();

                return (
                    layout.description.toUpperCase().indexOf(keyword) !== -1 ||
                    layout.name.toUpperCase().indexOf(keyword) !== -1 ||
                    layout.title.toUpperCase().indexOf(keyword) !== -1 ||
                    layout.tags.join(" ").toUpperCase().indexOf(keyword) !== -1
                );
            },

            displayFilter(tag) {
                return (
                    this.filter.type == "" ||
                    this.filter.type == "all" ||
                    this.filter.type == tag
                );
            },

            handleClose() {
                this.$emit("close");
            },

            handleConfirm(layout) {
                if (!layout) return;

                this.$emit("confirm", layout);
                this.clearFilter();
            },

            clearFilter() {
                this.filter.type = "";
                this.filter.search = "";
            },

            closeModal() {
                this.modalOpen = false;
                this.clearFilter();
                this.handleClose();
            },

            toggleModal() {
                this.modalOpen = !this.modalOpen;
                this.clearFilter();
            },
        },

        watch: {
            "filter.search": {
                handler(val) {
                    this.filter.search = val;
                    this.getLayouts();
                },
            },
            "filter.type": {
                handler(val) {
                    this.filter.type = val;
                    this.getLayouts();
                },
            },
        },
    };
</script>
<style>
    .mm-col-span-4 {
        grid-column: span 4 / span 4;
    }
    .mm-col-span-8 {
        grid-column: span 8 / span 8;
    }
    .mm-mt-2 {
        margin-top: 0.5rem;
    }
    .mm-flex {
        display: flex;
    }
    .mm-grid {
        display: grid;
    }
    .mm-h-full {
        height: 100%;
    }
    .mm-w-full {
        width: 100%;
    }
    .mm-flex-1 {
        flex: 1 1 0%;
    }
    .mm-flex-grow {
        flex-grow: 1;
    }
    .mm-cursor-pointer {
        cursor: pointer;
    }
    .mm-grid-cols-12 {
        grid-template-columns: repeat(12, minmax(0, 1fr));
    }
    .mm-flex-col {
        flex-direction: column;
    }
    .mm-items-stretch {
        align-items: stretch;
    }
    .mm-gap-y-4 {
        row-gap: 1rem;
    }
    .mm-self-center {
        align-self: center;
    }
    .mm-justify-self-stretch {
        justify-self: stretch;
    }
    .mm-border {
        border-width: 1px;
    }
    .mm-border-gray-200 {
        --tw-border-opacity: 1;
        border-color: rgb(229 231 235 / var(--tw-border-opacity));
    }
    .mm-bg-white {
        --tw-bg-opacity: 1;
        background-color: rgb(255 255 255 / var(--tw-bg-opacity));
    }
    .mm-object-cover {
        -o-object-fit: cover;
        object-fit: cover;
    }
    .mm-object-center {
        -o-object-position: center;
        object-position: center;
    }
    .mm-text-sm {
        font-size: 0.875rem;
        line-height: 1.25rem;
    }
    .mm-leading-tight {
        line-height: 1.25;
    }
    @media (min-width: 768px) {
        .md\:mm-col-span-8 {
            grid-column: span 8 / span 8;
        }
    }
</style>
<style scoped></style>
