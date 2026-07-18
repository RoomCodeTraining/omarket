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
    <section class="relative isolate overflow-hidden bg-forest-950 pt-24">
        <div class="absolute inset-0">
            <ResponsiveImage
                :webp="webp"
                :jpg="jpg"
                :alt="alt"
                img-class="h-full w-full object-cover opacity-45"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-forest-950 via-forest-950/70 to-forest-950/40" />
        </div>

        <div class="relative mx-auto max-w-6xl px-5 py-16 text-center md:px-8 md:py-24">
            <p
                v-if="eyebrow"
                class="text-sm font-medium tracking-[0.22em] text-brass-light uppercase"
            >
                {{ eyebrow }}
            </p>
            <h1 class="font-display mt-4 text-[clamp(2.25rem,5vw,3.75rem)] leading-tight text-white">
                {{ title }}
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-white/75">
                {{ description }}
            </p>
        </div>
    </section>
</template>
