<template>
    <component
        :dusk="currentField.attribute"
        :is="currentField.fullWidth ? 'FullWidthField' : 'DefaultField'"
        :field="currentField"
        :errors="errors"
        :show-help-text="showHelpText"
        full-width-content
        :fullWidthContent="true"
    >
        <template #field>
            <div v-if="order.length > 0">
                <form-nova-flexible-content-group
                    v-for="(group, index) in orderedGroups"
                    :dusk="currentField.attribute + '-' + index"
                    :key="group.key"
                    :field="currentField"
                    :group="group"
                    :index="index"
                    :resource-name="resourceName"
                    :resource-id="resourceId"
                    :errors="errors"
                    @move-up="moveUp(group.key)"
                    @move-down="moveDown(group.key)"
                    @remove="remove(group.key)"
                />
            </div>

            <!-- Simple dropdown menu for flexible-drop-menu component -->
            <component
                v-if="
                    currentField.menu.component === 'flexible-drop-menu' &&
                    this.limitCounter != 0 &&
                    currentField.allowedToCreate
                "
                :is="currentField.menu.component"
                :layouts="layouts"
                :field="currentField"
                :resource-name="resourceName"
                :resource-id="resourceId"
                :errors="errors"
                :limit-counter="limitCounter"
                :allowed-to-create="currentField.allowedToCreate"
                :allowed-to-delete="currentField.allowedToDelete"
                :allowed-to-change-order="currentField.allowedToChangeOrder"
                :limit-per-layout-counter="limitPerLayoutCounter"
                @addGroup="addGroup($event)"
            />

            <!-- Default button for modal-based selection -->
            <button
                class="inline-flex items-center flex-shrink-0 px-4 text-sm font-bold text-white rounded shadow focus:outline-none focus:ring bg-primary-500 hover:bg-primary-400 active:bg-primary-600 dark:text-gray-800 h-9"
                @click.prevent="toggleLayoutsDropdownOrAddDefault"
                v-if="
                    currentField.menu.component !== 'flexible-drop-menu' &&
                    this.limitCounter != 0 &&
                    currentField.allowedToCreate
                "
                v-text="currentField.button"
            ></button>

            <SelectorModal
                class="max-w-3xl nova-flexible-modal"
                v-if="modalOpen"
                @confirm="confirmModal($event)"
                @close="closeModal"
                :layouts="layouts"
                :limit-per-layout-counter="limitPerLayoutCounter"
                :is="currentField.menu.component"
                :field="currentField"
                :limit-counter="limitCounter"
                :errors="errors"
                :resource-name="resourceName"
                :resource-id="resourceId"
                @addGroup="addGroup($event)"
            />
        </template>
    </component>
</template>

<script>
    import "./../../css/modal.css";
    import FullWidthField from "./FullWidthField";
    import { DependentFormField, HandlesValidationErrors } from "laravel-nova";
    import Group from "../group";
    import SelectorModal from "./SelectorModal.vue";

    export default {
        mixins: [DependentFormField, HandlesValidationErrors],

        props: ["resourceName", "resourceId", "resource", "field"],

        components: { FullWidthField, SelectorModal },

        computed: {
            layouts() {
                return this.currentField.layouts || false;
            },
            orderedGroups() {
                return this.order.reduce((groups, key) => {
                    groups.push(this.groups[key]);
                    return groups;
                }, []);
            },
            limitCounter() {
                if (
                    this.currentField.limit === null ||
                    typeof this.currentField.limit == "undefined"
                ) {
                    return null;
                }

                return (
                    this.currentField.limit - Object.keys(this.groups).length
                );
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
             * Set the initial, internal value for the currentField.
             */
            setInitialValue() {
                this.value = this.currentField.value || [];
                this.files = {};

                this.populateGroups();
            },

            /**
             * Display or hide the layouts choice dropdown if there are multiple layouts
             * or directly add the only available layout.
             */
            toggleLayoutsDropdownOrAddDefault(event) {
                if (this.layouts.length === 1) {
                    return this.addGroup(this.layouts[0]);
                }

                this.modalOpen = true;
            },

            confirmModal(event) {
                let layout = this.getLayout(event.name);
                this.modalOpen = false;
                this.addGroup(layout);
            },

            closeModal(event) {
                this.modalOpen = false;
            },

            /**
             * Fill the given FormData object with the currentField's internal value.
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

                this.appendFieldAttribute(
                    formData,
                    this.currentField.attribute
                );
                formData.append(
                    this.currentField.attribute,
                    this.value.length ? JSON.stringify(this.value) : ""
                );

                // Append file uploads
                for (let file in this.files) {
                    formData.append(file, this.files[file]);
                }
            },

            /**
             * Register given currentField attribute into the parsable flexible fields register
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
             * Update the currentField's internal value.
             */
            handleChange(value) {
                this.value = value || [];
                this.files = {};

                this.populateGroups();
            },

            /**
             * Set the displayed layouts from the currentField's current value
             */
            populateGroups() {
                this.order.splice(0, this.order.length);
                this.groups = {};

                for (var i = 0; i < this.value.length; i++) {
                    this.addGroup(
                        this.getLayout(this.value[i].layout),
                        this.value[i].attributes,
                        this.value[i].key,
                        this.currentField.collapsed,
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
                        this.currentField,
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
    .nova-flexible-modal .inner .icon-selection i {
        font-size: 3rem;
    }

    /* Ensure flexible content group icons maintain proper size and alignment */
    form-nova-flexible-content-group svg,
    .group-control svg {
        width: 1rem !important;
        height: 1rem !important;
        font-size: 1rem !important;
    }

    /* Fix icon alignment within buttons */
    .group-control {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    /* Fix double border issue when layout has no fields */
    form-nova-flexible-content-group .grow {
        min-height: 0;
    }

    /* Hide bottom border when container is empty or has no visible content */
    form-nova-flexible-content-group .grow:empty,
    form-nova-flexible-content-group .grow:has(.hidden) {
        border-bottom: none !important;
        border-left: none !important;
        border-right: none !important;
        border-radius: 0 !important;
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
