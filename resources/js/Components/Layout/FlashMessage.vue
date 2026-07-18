<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const visible = ref(false);
const leaving = ref(false);
let hideTimer: ReturnType<typeof setTimeout> | null = null;

const success = computed(() => (page.props.flash as { success?: string | null })?.success ?? null);
const error = computed(() => (page.props.flash as { error?: string | null })?.error ?? null);
const message = computed(() => success.value || error.value);
const isSuccess = computed(() => Boolean(success.value));
const isCartMessage = computed(() => Boolean(success.value?.toLowerCase().includes('panier')));

function show() {
    if (hideTimer) {
        clearTimeout(hideTimer);
    }
    leaving.value = false;
    visible.value = true;
    hideTimer = setTimeout(() => {
        leaving.value = true;
        setTimeout(() => {
            visible.value = false;
            leaving.value = false;
        }, 280);
    }, 4200);
}

function dismiss() {
    if (hideTimer) {
        clearTimeout(hideTimer);
    }
    leaving.value = true;
    setTimeout(() => {
        visible.value = false;
        leaving.value = false;
    }, 280);
}

watch(
    message,
    (value) => {
        if (value) {
            show();
        }
    },
    { immediate: true },
);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="visible && message"
            class="pointer-events-none fixed inset-x-0 top-0 z-[60] flex justify-center px-4 pt-[5.5rem] sm:justify-end sm:px-6"
            role="status"
            aria-live="polite"
        >
            <div
                class="pointer-events-auto flex w-full max-w-md items-start gap-3 border px-4 py-3.5 shadow-[0_20px_50px_-24px_rgba(10,31,24,0.55)] transition duration-300"
                :class="[
                    leaving ? 'translate-y-[-0.75rem] opacity-0' : 'animate-toast-in',
                    isSuccess
                        ? 'border-forest-900/10 bg-white text-forest-900'
                        : 'border-red-200 bg-white text-red-700',
                ]"
            >
                <span
                    class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center"
                    :class="isSuccess ? 'bg-forest-900 text-brass-light' : 'bg-red-50 text-red-600'"
                    aria-hidden="true"
                >
                    <svg v-if="isSuccess" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M5 12.5 9.5 17 19 7.5"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M12 8v5m0 3h.01M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-sm leading-snug font-medium">{{ message }}</p>
                    <Link
                        v-if="isCartMessage"
                        href="/panier"
                        class="mt-2 inline-flex text-xs font-semibold tracking-wide text-forest-700 uppercase underline-offset-2 hover:underline"
                        @click="dismiss"
                    >
                        Voir le panier
                    </Link>
                </div>

                <button
                    type="button"
                    class="shrink-0 p-1 text-ink-muted transition hover:text-forest-900"
                    aria-label="Fermer la notification"
                    @click="dismiss"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path
                            d="M6 6l12 12M18 6 6 18"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </Teleport>
</template>
