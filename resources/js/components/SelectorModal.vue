<template>
    <Modal
        :show="true"
        class="max-w-2xl flex flex-col h-full relative nova-flexible-modal bg-white border bg-white dark:bg-gray-800 rounded-lg shadow-lg border-gray overflow-hidden"
    >
        <ModalHeader class="px-6 py-6 border-b relative border-gray">
            {{ __("Select Layout") }}

            <a
                href="#"
                class="nova-flexible-close"
                @click.prevent="handleClose"
            >
                <i class="fa fa-times"></i>
            </a>
        </ModalHeader>

        <div class="rounded-lg flex-1 relative h-90p bg-white">
            <div class="flex px-2 py-4 flex-wrap border-b border-gray">
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
                        class="w-full form-control form-input form-input-bordered"
                        placeholder="Search layouts"
                        v-model="filter.search"
                    />
                </div>
            </div>
            <div class="px-4 py-4 nova-flexible-inner">
                <div
                    class="py-6 text-center text-md font-semibold"
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
                        class="mm-grid mm-grid-cols-12 mm-w-full mm-border mm-border-gray-200 mm-bg-white group group-hover:border-primary-500 hover:border-primary-500 shadow-md mm-cursor-pointer"
                        @click="handleConfirm(layout)"
                    >
                        <div class="mm-col-span-4">
                            <div class="h-full">
                                <img
                                    class="mm-object-cover mm-object-center mm-h-full"
                                    :src="layout.image"
                                    :alt="layout.title"
                                />
                            </div>
                        </div>
                        <div
                            class="mm-self-center mm-col-span-8 p-4 ml-4 md:mm-col-span-8"
                        >
                            <div class="mm-leading-tight">
                                <p
                                    class="mm-text-md text-primary-500 font-semibold"
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
                                        class="inline-block mm-text-sm mr-2"
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

<style scoped></style>
