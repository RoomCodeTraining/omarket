<script setup lang="ts">
defineProps<{
    webp: string;
    jpg: string;
    alt: string;
    imgClass?: string;
    loading?: 'lazy' | 'eager';
}>();
</script>

<template>
    <picture>
        <source :srcset="webp" type="image/webp" />
        <img
            :src="jpg"
            :alt="alt"
            :loading="loading ?? 'lazy'"
            :class="imgClass"
            decoding="async"
        />
    </picture>
</template>
