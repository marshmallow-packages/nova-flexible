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
                    <Button
                        variant="link"
                        state="mellow"
                        data-testid="cancel-button"
                        dusk="cancel-delete-button"
                        @click.prevent="$emit('close')"
                        class="mr-3"
                    >
                        {{ no }}
                    </Button>

                    <Button
                        ref="confirmButton"
                        dusk="confirm-delete-button"
                        :loading="working"
                        state="danger"
                        type="submit"
                        :label="yes"
                    />
                </div>
            </ModalFooter>
        </form>
    </Modal>
</template>

<script>
    import { Button } from "laravel-nova-ui";

    export default {
        components: { Button },

        props: ["message", "yes", "no"],

        emits: ["close", "confirm"],

        data() {
            return {
                working: false,
            };
        },

        methods: {
            handleClose() {
                this.$emit("close");
                this.working = false;
            },
            handleConfirm() {
                this.$emit("confirm");
                this.working = true;
            },
        },
    };
</script>
