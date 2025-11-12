<template>
    <Modal
        :show="true"
        @close-via-escape="handleClose"
        class="relative flex flex-col h-full max-w-2xl overflow-hidden bg-white border rounded-lg shadow-lg nova-flexible-modal dark:bg-gray-800 border-gray"
        role="dialog"
        size="xl"
    >
        <ModalHeader class="relative px-6 py-6 border-b border-gray dark:bg-gray-800">
            {{ __("Select Layout") }}

            <button
                @click.prevent="handleClose"
                type="button"
                class="text-gray-400 bg-transparent rounded-md nova-flexible-close hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-300 dark:hover:text-gray-100"
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

        <div class="relative flex-1 bg-white dark:bg-gray-800 rounded-lg h-90p">
            <div class="flex flex-wrap px-2 py-4 border-b border-gray dark:border-gray-700">
                <div class="w-1/2 px-4">
                    <select
                        v-model="selectedTag"
                        class="w-full form-control form-input form-control-bordered dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700"
                    >
                        <option value="all">All</option>
                        <option
                            v-for="tag in tags"
                            :key="tag"
                            :value="tag"
                        >
                            {{ tag }}
                        </option>
                    </select>
                </div>
                <div class="w-1/2 px-4">
                    <input
                        type="text"
                        id="search"
                        class="w-full form-control form-input form-control-bordered dark:bg-gray-900 dark:text-gray-100"
                        placeholder="Search layouts"
                        v-model="searchText"
                    />
                </div>
            </div>
            <div class="px-4 py-4 nova-flexible-inner">
                <div
                    v-if="visibleLayouts.length > 0"
                    class="mm-flex mm-flex-col mm-items-stretch mm-flex-1 mm-flex-grow mm-w-full mm-justify-self-stretch mm-gap-y-4"
                >
                    <div
                        v-for="(layout, index) in visibleLayouts"
                        :key="index"
                        class="shadow-md mm-grid mm-grid-cols-12 mm-w-full mm-border mm-border-gray-200 dark:mm-border-gray-700 mm-bg-white dark:mm-bg-gray-900 group group-hover:border-primary-500 hover:border-primary-500 mm-cursor-pointer"
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
                                    class="mm-mt-2 dark:text-gray-300 group-hover:mm-text-primary-500"
                                    v-html="layout.description"
                                ></p>
                                <div class="mm-mt-2">
                                    <span
                                        v-for="tag in layout.tags"
                                        :key="tag"
                                        class="inline-block mr-2 mm-text-sm dark:text-gray-400"
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

        props: ["layouts"],

        data() {
            return {
                selectedTag: "all",
                searchText: "",
            };
        },

        computed: {
            tags() {
                const uniqueTags = [];
                this.layouts.forEach((layout) => {
                    if (layout.tags && Array.isArray(layout.tags)) {
                        layout.tags.forEach((tag) => {
                            if (!uniqueTags.includes(tag)) {
                                uniqueTags.push(tag);
                            }
                        });
                    }
                });
                return uniqueTags;
            },

            visibleLayouts() {
                return this.layouts.filter((layout) => {
                    // Filter by tag
                    const matchesTag =
                        this.selectedTag === "all" ||
                        (layout.tags &&
                            layout.tags.includes(this.selectedTag));

                    // Filter by search text
                    const matchesSearch =
                        this.searchText === "" ||
                        this.layoutMatchesSearch(layout, this.searchText);

                    return matchesTag && matchesSearch;
                });
            },
        },

        methods: {
            layoutMatchesSearch(layout, searchText) {
                const keyword = searchText.toUpperCase();
                const description = (layout.description || "").toUpperCase();
                const name = (layout.name || "").toUpperCase();
                const title = (layout.title || "").toUpperCase();
                const tags = layout.tags
                    ? layout.tags.join(" ").toUpperCase()
                    : "";

                return (
                    description.includes(keyword) ||
                    name.includes(keyword) ||
                    title.includes(keyword) ||
                    tags.includes(keyword)
                );
            },

            handleClose() {
                this.$emit("close");
            },

            handleConfirm(layout) {
                if (!layout) return;
                this.$emit("confirm", layout);
            },
        },
    };
</script>
<style>
    /* Fix modal backdrop z-index to prevent blocking interaction */
    .nova-flexible-modal {
        z-index: 9999 !important;
    }

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
    .dark .dark\:mm-bg-gray-900 {
        --tw-bg-opacity: 1;
        background-color: rgb(17 24 39 / var(--tw-bg-opacity));
    }
    .dark .dark\:mm-border-gray-700 {
        --tw-border-opacity: 1;
        border-color: rgb(55 65 81 / var(--tw-border-opacity));
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
