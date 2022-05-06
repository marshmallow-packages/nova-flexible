<template>
    <FieldWrapper :stacked="field.stacked" v-if="field.visible" class="w-full">
        <div class="py-6 px-8 w-full">
            <div class="mb-6" v-if="fieldLabel">
                <FormLabel
                    :label-for="labelFor || field.attribute"
                    :class="{ 'mb-2': showHelpText && field.helpText }"
                >
                    {{ fieldLabel }}
                    <span v-if="field.required" class="text-red-500 text-sm">
                        {{ __("*") }}
                    </span>
                </FormLabel>

                <HelpText
                    class="help-text mt-2"
                    v-if="showHelpText"
                    v-html="field.helpText"
                />
            </div>

            <slot name="field" class="w-full" />

            <HelpText
                class="mt-2 help-text-error"
                v-if="showErrors && hasError"
            >
                {{ firstError }}
            </HelpText>
        </div>
    </FieldWrapper>
</template>

<script>
    import { mapProps } from "laravel-nova";
    import { HandlesValidationErrors, Errors } from "laravel-nova";

    export default {
        mixins: [HandlesValidationErrors],

        props: {
            field: { type: Object, required: true },
            fieldName: { type: String },
            showErrors: { type: Boolean, default: true },
            labelFor: { default: null },
            fullWidthContent: { type: Boolean, default: true },
            ...mapProps(["showHelpText"]),
        },
        computed: {
            fieldLabel() {
                // If the field name is purposefully an empty string, then let's show it as such
                if (this.fieldName === "") {
                    return "";
                }

                return (
                    this.fieldName ||
                    this.field.name ||
                    this.field.singularLabel
                );
            },
        },
    };
</script>
