<template>
    <Modal :show="true">
        <form
            @submit.prevent="$emit('confirm')"
            class="mx-auto overflow-hidden bg-white rounded-lg shadow-lg dark:bg-gray-800"
        >
            <slot>
                <ModalHeader v-text="__('Delete Group')" />
                <ModalContent>
                    <p class="leading-normal" v-if="message">
                        {{ message }}
                    </p>
                    <p class="leading-normal" v-else>
                        {{ __("Are you sure you want to delete this group?") }}
                    </p>
                </ModalContent>
            </slot>

            <ModalFooter>
                <div class="ml-auto">
                    <link-button
                        type="button"
                        data-testid="cancel-button"
                        dusk="cancel-delete-button"
                        @click.prevent="this.$emit('close')"
                        class="mr-3"
                    >
                        {{ no }}
                    </link-button>

                    <danger-button
                        ref="confirmButton"
                        dusk="confirm-delete-button"
                        :processing="working"
                        :disabled="working"
                        type="submit"
                    >
                        {{ yes }}
                    </danger-button>
                </div>
            </ModalFooter>
        </form>
    </Modal>
</template>

<script>
    export default {
        props: ["message", "yes", "no"],

        emits: ["close", "confirm"],

        /**
         * Mount the component.
         */
        mounted() {
            this.$nextTick(() => {
                // this.$refs.confirmButton.button.focus()
            });
        },
    };
</script>
