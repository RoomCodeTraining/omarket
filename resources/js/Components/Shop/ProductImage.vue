<script setup lang="ts">
import { ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        src: string;
        alt: string;
        imgClass?: string;
    }>(),
    {
        imgClass: 'h-full w-full object-cover',
    },
);

const DEFAULT_IMAGE = '/images/products/default.svg';
const currentSrc = ref(props.src || DEFAULT_IMAGE);

watch(
    () => props.src,
    (value) => {
        currentSrc.value = value || DEFAULT_IMAGE;
    },
);

function onError() {
    if (currentSrc.value !== DEFAULT_IMAGE) {
        currentSrc.value = DEFAULT_IMAGE;
    }
}
</script>

<template>
    <img
        :src="currentSrc"
        :alt="alt"
        :class="imgClass"
        loading="lazy"
        decoding="async"
        @error="onError"
    />
</template>
