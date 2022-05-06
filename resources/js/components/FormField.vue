<template>
    <component
        :dusk="field.attribute"
        :is="field.fullWidth ? 'FullWidthField' : 'DefaultField'"
        :field="field"
        :errors="errors"
        full-width-content
        :show-help-text="showHelpText"
    >
        <template #field>
            <div v-if="order.length > 0">
                <form-nova-flexible-content-group
                    v-for="(group, index) in orderedGroups"
                    :dusk="field.attribute + '-' + index"
                    :key="group.key"
                    :field="field"
                    :group="group"
                    :index="index"
                    :resource-name="resourceName"
                    :resource-id="resourceId"
                    :resource="resource"
                    :errors="errors"
                    @move-up="moveUp(group.key)"
                    @move-down="moveDown(group.key)"
                    @remove="remove(group.key)"
                />
            </div>

            <button
                class="flex-shrink-0 shadow rounded focus:outline-none focus:ring bg-primary-500 hover:bg-primary-400 active:bg-primary-600 text-white dark:text-gray-800 inline-flex items-center font-bold px-4 h-9 text-sm flex-shrink-0"
                @click.prevent="openModal"
                v-if="this.limitCounter != 0 && field.allowedToCreate"
                v-text="field.button"
            ></button>

            <SelectorModal
                class="nova-flexible-modal max-w-3xl"
                v-if="modalOpen"
                :field="field"
                @confirm="confirmModal"
                @close="closeModal"
                :layouts="layouts"
                :is="field.menu.component"
                :limit-counter="limitCounter"
                :limit-per-layout-counter="limitPerLayoutCounter"
                :errors="errors"
                :resource-name="resourceName"
                :resource-id="resourceId"
                :resource="resource"
                @addGroup="addGroup($event)"
            />

            <component
                :layouts="layouts"
                :is="field.menu.component"
                :field="field"
                :limit-counter="limitCounter"
                :limit-per-layout-counter="limitPerLayoutCounter"
                :errors="errors"
                :resource-name="resourceName"
                :resource-id="resourceId"
                :resource="resource"
                @addGroup="addGroup($event)"
            />
        </template>
    </component>
</template>

<script>
    import FullWidthField from "./FullWidthField";
    import { FormField, HandlesValidationErrors } from "laravel-nova";
    import Group from "../group";
    import SelectorModal from "./SelectorModal.vue";

    export default {
        mixins: [FormField, HandlesValidationErrors],

        props: ["resourceName", "resourceId", "resource", "field"],

        components: { FullWidthField, SelectorModal },

        computed: {
            layouts() {
                return this.field.layouts || false;
            },
            orderedGroups() {
                return this.order.reduce((groups, key) => {
                    groups.push(this.groups[key]);
                    return groups;
                }, []);
            },

            limitCounter() {
                if (
                    this.field.limit === null ||
                    typeof this.field.limit == "undefined"
                ) {
                    return null;
                }

                return this.field.limit - Object.keys(this.groups).length;
            },

            limitPerLayoutCounter() {
                return this.layouts.reduce((layoutCounts, layout) => {
                    if (layout.limit === null) {
                        layoutCounts[layout.name] = null;

                        return layoutCounts;
                    }

                    let count = Object.values(this.groups).filter(
                        (group) => group.name === layout.name
                    ).length;

                    layoutCounts[layout.name] = layout.limit - count;

                    return layoutCounts;
                }, {});
            },
        },

        data: () => ({
            order: [],
            groups: {},
            files: {},
            modalOpen: false,
        }),

        methods: {
            /*
             * Set the initial, internal value for the field.
             */
            setInitialValue() {
                this.value = this.field.value || [];
                this.files = {};

                this.populateGroups();
            },

            openModal() {
                this.modalOpen = true;
            },
            confirmModal() {
                this.modalOpen = false;
            },
            closeModal() {
                this.modalOpen = false;
            },

            /**
             * Fill the given FormData object with the field's internal value.
             */
            fill(formData) {
                let key, group;

                this.value = [];
                this.files = {};

                for (var i = 0; i < this.order.length; i++) {
                    key = this.order[i];
                    group = this.groups[key].serialize();

                    // Only serialize the group's non-file attributes
                    this.value.push({
                        layout: group.layout,
                        key: group.key,
                        attributes: group.attributes,
                    });

                    // Attach the files for formData appending
                    this.files = { ...this.files, ...group.files };
                }

                this.appendFieldAttribute(formData, this.field.attribute);
                formData.append(
                    this.field.attribute,
                    this.value.length ? JSON.stringify(this.value) : ""
                );

                // Append file uploads
                for (let file in this.files) {
                    formData.append(file, this.files[file]);
                }
            },

            /**
             * Register given field attribute into the parsable flexible fields register
             */
            appendFieldAttribute(formData, attribute) {
                let registered = [];

                if (formData.has("___nova_flexible_content_fields")) {
                    registered = JSON.parse(
                        formData.get("___nova_flexible_content_fields")
                    );
                }

                registered.push(attribute);

                formData.set(
                    "___nova_flexible_content_fields",
                    JSON.stringify(registered)
                );
            },

            /**
             * Update the field's internal value.
             */
            handleChange(value) {
                this.value = value || [];
                this.files = {};

                this.populateGroups();
            },

            /**
             * Set the displayed layouts from the field's current value
             */
            populateGroups() {
                this.order.splice(0, this.order.length);
                this.groups = {};

                for (var i = 0; i < this.value.length; i++) {
                    this.addGroup(
                        this.getLayout(this.value[i].layout),
                        this.value[i].attributes,
                        this.value[i].key,
                        this.field.collapsed,
                        this.value[i].title_data
                    );
                }
            },

            /**
             * Retrieve layout definition from its name
             */
            getLayout(name) {
                if (!this.layouts) return;
                return this.layouts.find((layout) => layout.name == name);
            },

            /**
             * Append the given layout to flexible content's list
             */
            addGroup(layout, attributes, key, collapsed, resolved_title) {
                if (!layout) return;

                collapsed = collapsed || false;

                let fields =
                        attributes || JSON.parse(JSON.stringify(layout.fields)),
                    group = new Group(
                        layout.name,
                        layout.title,
                        fields,
                        this.field,
                        key,
                        collapsed,
                        layout,
                        resolved_title
                    );

                this.groups[group.key] = group;
                this.order.push(group.key);
            },

            /**
             * Move a group up
             */
            moveUp(key) {
                let index = this.order.indexOf(key);

                if (index <= 0) return;

                this.order.splice(index - 1, 0, this.order.splice(index, 1)[0]);
            },

            /**
             * Move a group down
             */
            moveDown(key) {
                let index = this.order.indexOf(key);

                if (index < 0 || index >= this.order.length - 1) return;

                this.order.splice(index + 1, 0, this.order.splice(index, 1)[0]);
            },

            /**
             * Remove a group
             */
            remove(key) {
                let index = this.order.indexOf(key);

                if (index < 0) return;

                this.order.splice(index, 1);
                delete this.groups[key];
            },
        },
    };
</script>

<style>
    .nova-flexible-modal .inner i {
        font-size: 3rem;
    }

    .display-icon i {
        font-size: 4rem;
    }

    .display-icon:hover .close-icon {
        display: block;
    }

    .close-icon {
        display: none;
        position: absolute;
        top: 0;
        right: 0;

        opacity: 0.75;
        cursor: pointer;

        transition: all 0.2s ease-in-out;

        transform: translate(50%, -50%);
    }

    .close-icon:hover {
        opacity: 1;
    }

    .close-icon i {
        font-size: 1.5rem !important;
    }

    .svg-inline--fa.fa-w-20 {
        width: 2.5em;
    }

    .svg-inline--fa.fa-w-18 {
        width: 2.25em;
    }

    .svg-inline--fa.fa-w-16 {
        width: 2em;
    }

    .svg-inline--fa.fa-w-12 {
        width: 1.5em;
    }

    .nova-flexible-inner {
        height: 90%;
        overflow: scroll;
    }

    .h-90p {
        height: 90%;
    }

    .nova-flexible-close {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        right: 1.5rem;
        font-size: 1.5rem;
        color: #3c4655;
    }

    .icon-name {
        display: block;
        font-size: 12px;
        margin-top: 0.5em;
        background: #fafafa;
        padding: 0.2em;
    }

    .border-red {
        border-color: #ff123b;
    }

    .mm-icon-box {
        outline: 1px solid #e0e0e0;
        outline-offset: -0.5rem;
    }

    .mm-icon-box:hover {
        outline: 1px solid #ff123b;
        color: #ff123b;
    }

    .border-gray {
        border-color: #e0e0e0;
    }

    @media (max-width: 900px) {
        .h-90p {
            height: 80%;
        }
    }
</style>
