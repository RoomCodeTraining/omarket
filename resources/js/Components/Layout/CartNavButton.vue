<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    dark?: boolean;
}>();

const page = usePage();
const bump = ref(false);

const cartCount = computed(() => (page.props.cart as { count?: number } | undefined)?.count ?? 0);

watch(cartCount, (next, prev) => {
    if (next > (prev ?? 0)) {
        bump.value = true;
        window.setTimeout(() => {
            bump.value = false;
        }, 600);
    }
});
</script>

<template>
    <Link
        href="/panier"
        class="group relative inline-flex h-11 w-11 items-center justify-center transition"
        :class="
            dark
                ? 'text-white hover:bg-white/10'
                : 'text-forest-900 hover:bg-forest-900/5'
        "
        :aria-label="`Panier, ${cartCount} article${cartCount > 1 ? 's' : ''}`"
    >
        <svg
            class="h-[1.35rem] w-[1.35rem] transition duration-300 group-hover:scale-105"
            :class="bump && 'animate-cart-bump'"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
        >
            <path
                d="M6.5 8.5h11l-.7 9.1a1.5 1.5 0 0 1-1.5 1.4H8.7a1.5 1.5 0 0 1-1.5-1.4L6.5 8.5Z"
                stroke="currentColor"
                stroke-width="1.6"
                stroke-linejoin="round"
            />
            <path
                d="M9 8.5V7a3 3 0 0 1 6 0v1.5"
                stroke="currentColor"
                stroke-width="1.6"
                stroke-linecap="round"
            />
        </svg>

        <span
            class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] font-bold leading-none transition"
            :class="[
                cartCount > 0 ? 'scale-100 opacity-100' : 'pointer-events-none scale-50 opacity-0',
                bump && 'animate-cart-badge',
                dark ? 'bg-brass text-forest-950' : 'bg-forest-900 text-white',
            ]"
        >
            {{ cartCount > 99 ? '99+' : cartCount }}
        </span>
    </Link>
</template>
