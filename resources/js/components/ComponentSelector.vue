<template>
    <div class="z-20" v-if="layouts">
        <div class="relative" v-if="layouts.length > 1">
            <div
                v-if="isLayoutsDropdownOpen"
                class="absolute rounded-lg shadow-lg max-w-full mb-3 pin-b max-h-search overflow-y-auto border border-40"
            >
                <div id="layout-selector-overlay">
                    <div id="layout-selector" class="overflow-scroll shadow-lg">
                        <div class="flex mb-4 h-100-p">
                            <div
                                class="menu-container w-1/5 bg-grad-sidebar h-100-p pt-8"
                            >
                                <h4
                                    class="ml-8 mb-4 text-xs text-white-50% uppercase tracking-wide"
                                >
                                    Tags
                                </h4>
                                <ul class="list-reset mb-8">
                                    <li class="leading-tight mb-4 ml-8 text-sm">
                                        <a
                                            @click="toggleLayoutsTags"
                                            class="text-justify text-white no-underline dim cursor-pointer"
                                        >
                                            All
                                        </a>
                                    </li>
                                    <li
                                        v-for="tag in availableTags"
                                        :key="tag"
                                        class="leading-tight mb-4 ml-8 text-sm"
                                    >
                                        <a
                                            :id="tag"
                                            @click="toggleLayoutsTags"
                                            class="text-justify text-white no-underline dim cursor-pointer"
                                        >
                                            {{ tag }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div
                                class="components-container w-4/5 bg-gray-500 h-full p-3"
                            >
                                <div class="flex p-3">
                                    <div class="w-4/5">
                                        <div
                                            class="relative z-50 w-full max-w-xs"
                                        >
                                            <div class="relative">
                                                <div class="relative">
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        width="20"
                                                        height="20"
                                                        viewBox="0 0 20 20"
                                                        aria-labelledby="search"
                                                        role="presentation"
                                                        class="fill-current absolute search-icon-center ml-3 text-80"
                                                    >
                                                        <path
                                                            fill-rule="nonzero"
                                                            d="M14.32 12.906l5.387 5.387a1 1 0 0 1-1.414 1.414l-5.387-5.387a8 8 0 1 1 1.414-1.414zM8 14A6 6 0 1 0 8 2a6 6 0 0 0 0 12z"
                                                        ></path>
                                                    </svg>
                                                    <input
                                                        type="search"
                                                        v-model="message"
                                                        v-on:input="
                                                            handleComponentSearch(
                                                                message
                                                            )
                                                        "
                                                        placeholder="Search for your component here"
                                                        class="pl-search w-full form-global-search"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-1/5">
                                        <div class="close text-right">
                                            <button
                                                @click="
                                                    toggleLayoutsDropdownOrAddDefault
                                                "
                                                class="btn btn-default btn-primary inline-flex items-center relative"
                                            >
                                                Close
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap">
                                    <div
                                        v-for="layout in visibleLayouts"
                                        class="w-1/3 component-layout-column"
                                    >
                                        <div
                                            class="component-layout-selector max-w-sm rounded overflow-hidden shadow-lg cursor-pointer m-3"
                                            @click="addGroup(layout)"
                                        >
                                            <img
                                                class="w-full"
                                                :src="layout.image"
                                                :alt="layout.title"
                                            />
                                            <div class="px-6 py-4">
                                                <div class="font-bold mb-2">
                                                    {{ layout.title }}
                                                </div>
                                                <p
                                                    class="text-grey-darker text-base"
                                                >
                                                    {{ layout.description }}
                                                </p>
                                            </div>
                                            <div
                                                class="px-6 py-4"
                                                v-if="layout.tags"
                                            >
                                                <span
                                                    v-for="tag in layout.tags"
                                                    class="inline-block bg-grey-lighter rounded-full px-3 py-1 text-sm font-semibold text-grey-darker mr-2"
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
                </div>
            </div>
        </div>
        <button
            type="button"
            tabindex="0"
            class="btn btn-default btn-primary inline-flex items-center relative"
            @click="toggleLayoutsDropdownOrAddDefault"
            v-if="this.limitCounter != 0 && field.allowedToCreate"
        >
            <span>{{ field.button }}</span>
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
        ],

        data() {
            return {
                isLayoutsDropdownOpen: false,
                orginalLayouts: this.layouts,
                visibleLayouts: this.layouts,
            };
        },

        computed: {
            availableTags() {
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

                return available_tags;
            },
        },

        methods: {
            handleComponentSearch(search) {
                search = search.toUpperCase();
                var newVisableLayouts = [];

                if (search.length > 0) {
                    let layouts = this.orginalLayouts;
                    layouts.forEach(function (layout, layout_index) {
                        if (
                            layout.description.toUpperCase().indexOf(search) !==
                                -1 ||
                            layout.name.toUpperCase().indexOf(search) !== -1 ||
                            layout.title.toUpperCase().indexOf(search) !== -1 ||
                            layout.tags
                                .join(" ")
                                .toUpperCase()
                                .indexOf(search) !== -1
                        ) {
                            newVisableLayouts.push(layout);
                        }
                    });
                    this.visibleLayouts = newVisableLayouts;
                } else {
                    this.visibleLayouts = this.orginalLayouts;
                }
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
            },

            toggleLayoutsTags(event) {
                var clicked_tag = event.srcElement.id;

                for (let el of document.querySelectorAll(
                    ".component-layout-selector"
                ))
                    el.closest(".component-layout-column").style.display =
                        "none";

                if (clicked_tag) {
                    for (let el of document.querySelectorAll(
                        '[data-tag="' + clicked_tag + '"]'
                    )) {
                        el.closest(".component-layout-column").style.display =
                            "block";
                    }
                } else {
                    for (let el of document.querySelectorAll(
                        ".component-layout-selector"
                    ))
                        el.closest(".component-layout-column").style.display =
                            "block";
                }
            },

            /**
             * Append the given layout to flexible content's list
             */
            addGroup(layout) {
                if (!layout) return;

                this.$emit("addGroup", layout);

                this.isLayoutsDropdownOpen = false;
            },
        },
    };
</script>

<style type="text/css">
    /* Small (sm) */
    /*    @media (min-width: 640px) {
        #layout-selector {
            margin-top: 70px;
        }
    }*/

    /* Medium (md) */
    /*    @media (min-width: 768px) {
        #layout-selector {
            margin-top: 70px;
        }
    }*/

    @media (max-width: 1024px) {
        #layout-selector {
            margin-top: 70px;
        }
    }

    #layout-selector-overlay {
        background: #00000085;
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 10;
    }
    #layout-selector {
        position: fixed;
        top: 20px;
        left: 20px;
        bottom: 20px;
        right: 20px;
        background: #edf2f7;
        z-index: 12;
    }
    #layout-selector .menu-container {
    }
    #layout-selector .components-container {
        background: #f7fafc;
        min-height: 100%;
    }
    .h-100-p {
        min-height: 100%;
    }
    .overflow-scroll {
        overflow: scroll;
    }
</style>
