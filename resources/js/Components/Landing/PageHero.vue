<script setup lang="ts">
import ResponsiveImage from '@/Components/Landing/ResponsiveImage.vue';

defineProps<{
    eyebrow?: string;
    title: string;
    description: string;
    webp: string;
    jpg: string;
    alt: string;
}>();
</script>

<template>
    <section
        class="relative isolate overflow-hidden bg-forest-950"
        :style="{ paddingTop: 'var(--header-offset)' }"
    >
        <div class="absolute inset-0">
            <ResponsiveImage
                :webp="webp"
                :jpg="jpg"
                :alt="alt"
                img-class="h-full w-full object-cover object-center opacity-45"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-forest-950 via-forest-950/70 to-forest-950/40" />
        </div>

        <div class="relative mx-auto max-w-6xl px-4 py-12 text-center sm:px-5 md:px-8 md:py-20">
            <p
                v-if="eyebrow"
                class="text-xs font-medium tracking-[0.22em] text-brass-light uppercase sm:text-sm"
            >
                {{ eyebrow }}
            </p>
            <h1 class="font-display mt-4 text-[clamp(1.85rem,6vw,3.75rem)] leading-[1.15] text-white sm:leading-tight">
                {{ title }}
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-white/75 sm:mt-5 md:text-lg">
                {{ description }}
            </p>
        </div>
    </section>
</template>
