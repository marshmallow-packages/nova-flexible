<template>
    <div class="relative pb-1 mb-4" :id="group.key">
        <div class="w-full shrink">
            <div :class="titleStyle" v-if="group.title">
                <div
                    class="box-content flex items-center h-8 h-full leading-normal"
                    :class="{
                        'border-b border-gray-200 dark:border-gray-700 ':
                            !collapsed,
                    }"
                >
                    <button
                        dusk="expand-group"
                        type="button"
                        class="block w-8 h-8 text-center border-r border-gray-200 shrink-0 group-control btn dark:border-gray-700 tw-text-center"
                        :title="__('Expand')"
                        @click.prevent="expand"
                        v-if="collapsed"
                    >
                        <Icon
                            name="plus"
                            class="tw-h-4 tw-w-4 tw-inline-block"
                        />
                    </button>
                    <button
                        dusk="collapse-group"
                        type="button"
                        class="block w-8 h-8 border-r border-gray-200 group-control btn dark:border-gray-700 tw-text-center"
                        :title="__('Collapse')"
                        @click.prevent="collapse"
                        v-else
                    >
                        <Icon
                            name="minus"
                            class="tw-h-4 tw-w-4 tw-inline-block"
                        />
                    </button>

                    <p class="px-4 text-80 grow">
                        <span class="mr-3 font-semibold">#{{ index + 1 }}</span>
                        <span v-if="group.title_from_content">
                            <span class="mr-3 font-semibold">{{
                                group.title_from_content
                            }}</span>
                        </span>
                        {{ group.title }}
                    </p>

                    <div class="flex" v-if="!readonly">
                        <button
                            dusk="move-up-group"
                            type="button"
                            class="block w-8 h-8 border-l border-gray-200 group-control btn dark:border-gray-700"
                            :title="__('Move up')"
                            @click.prevent="moveUp"
                        >
                            <Icon
                                name="arrow-up"
                                class="tw-h-4 tw-w-4 tw-inline-block"
                            />
                        </button>
                        <button
                            dusk="move-down-group"
                            type="button"
                            class="block w-8 h-8 border-l border-gray-200 group-control btn dark:border-gray-700"
                            :title="__('Move down')"
                            @click.prevent="moveDown"
                        >
                            <Icon
                                name="arrow-down"
                                class="tw-h-4 tw-w-4 tw-inline-block"
                            />
                        </button>
                        <button
                            dusk="delete-group"
                            type="button"
                            class="block w-8 h-8 border-l border-gray-200 group-control btn dark:border-gray-700"
                            :title="__('Delete')"
                            @click.prevent="confirmRemove"
                        >
                            <Icon
                                name="trash"
                                class="tw-h-4 tw-w-4 tw-inline-block"
                            />
                        </button>
                        <delete-flexible-content-group-modal
                            v-if="removeMessage"
                            @confirm="remove"
                            @close="removeMessage = false"
                            :message="field.confirmRemoveMessage"
                            :yes="field.confirmRemoveYes"
                            :no="field.confirmRemoveNo"
                        />
                    </div>
                </div>
            </div>
            <div :class="containerStyle">
                <component
                    v-for="(item, index) in group.fields"
                    :key="index"
                    :is="'form-' + item.component"
                    :resource-name="resourceName"
                    :resource-id="resourceId"
                    :resource="resource"
                    :field="item"
                    :errors="errors"
                    :show-help-text="item.helpText != null"
                    :class="{
                        'remove-bottom-border':
                            index == group.fields.length - 1,
                    }"
                />
            </div>
        </div>
    </div>
</template>

<script>
    import BehavesAsPanel from "nova-mixins/BehavesAsPanel";
    import { Icon } from "laravel-nova-ui";

    export default {
        mixins: [BehavesAsPanel],

        components: {
            Icon,
        },

        props: ["errors", "group", "index", "field"],

        emits: ["move-up", "move-down", "remove"],

        data() {
            return {
                removeMessage: false,
                collapsed: this.group.collapsed,
                deletion_not_allowed: this.group.deletion_not_allowed,
                readonly: this.group.readonly,
            };
        },

        computed: {
            titleStyle() {
                let classes = [
                    "border-t",
                    "border-r",
                    "border-l",
                    "border-gray-200",
                    "dark:border-gray-700",
                    "rounded-t-lg",
                ];
                if (this.collapsed) {
                    classes.push("border-b rounded-b-lg");
                }
                return classes;
            },
            containerStyle() {
                let classes = [
                    "grow",
                    "border-b",
                    "border-r",
                    "border-l",
                    "border-gray-200",
                    "dark:border-gray-700",
                    "rounded-b-lg",
                ];
                if (!this.group.title) {
                    classes.push("border-t");
                    classes.push("rounded-tr-lg");
                }
                if (this.collapsed) {
                    classes.push("hidden");
                }
                return classes;
            },
        },

        methods: {
            /**
             * Move this group up
             */
            moveUp() {
                this.$emit("move-up");
            },

            /**
             * Move this group down
             */
            moveDown() {
                this.$emit("move-down");
            },

            /**
             * Remove this group
             */
            remove() {
                this.$emit("remove");
            },

            /**
             * Confirm remove message
             */
            confirmRemove() {
                if (this.field.confirmRemove) {
                    this.removeMessage = true;
                } else {
                    this.remove();
                }
            },

            /**
             * Expand fields
             */
            expand() {
                this.collapsed = false;
            },

            /**
             * Collapse fields
             */
            collapse() {
                this.collapsed = true;
            },
        },
        watch: {
            index(index) {
                Nova.$emit("flexible-content-order-changed", index);
            },
        },
    };
</script>

<style>
    .group-control:focus {
        outline: none;
    }
    .group-control:hover {
        color: rgb(var(--colors-primary-400));
    }
    .confirm-message {
        position: absolute;
        overflow: visible;
        right: 38px;
        bottom: 0;
        width: auto;
        border-radius: 4px;
        padding: 6px 7px;
        border: 1px solid #b7cad6;
        background-color: var(--20);
        white-space: nowrap;
    }
    [dir="rtl"] .confirm-message {
        right: auto;
        left: 35px;
    }

    .confirm-message .text-danger {
        color: #ee3f22;
    }

    .closebtn {
        /*color: #B7CAD6;*/
    }

    .rounded-l {
        border-top-left-radius: 0.25rem; /* 4px */
        border-bottom-left-radius: 0.25rem; /* 4px */
    }

    .rounded-t-lg {
        border-top-right-radius: 0.5rem; /* 8px */
        border-top-left-radius: 0.5rem; /* 8px */
    }

    .rounded-b-lg {
        border-bottom-left-radius: 0.5rem; /* 8px */
        border-bottom-right-radius: 0.5rem; /* 8px */
    }

    .box-content {
        box-sizing: content-box;
    }

    .grow {
        flex-grow: 1;
    }

    .grow-0 {
        flex-grow: 0;
    }

    .shrink {
        flex-shrink: 1;
    }

    .shrink-0 {
        flex-shrink: 0;
    }
</style>
