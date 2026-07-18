<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

export type HeroSlide = {
    id: string;
    webp: string;
    jpg: string;
    alt: string;
    caption: string;
};

const props = withDefaults(
    defineProps<{
        slides: HeroSlide[];
        intervalMs?: number;
        modelValue?: number;
    }>(),
    {
        intervalMs: 6500,
        modelValue: 0,
    },
);

const emit = defineEmits<{
    'update:modelValue': [index: number];
}>();

const active = ref(props.modelValue);
const paused = ref(false);
const progress = ref(0);
let timer: ReturnType<typeof setInterval> | null = null;
let progressTimer: ReturnType<typeof setInterval> | null = null;

const current = computed(() => props.slides[active.value] ?? props.slides[0]);

function setActive(index: number) {
    if (!props.slides.length) {
        return;
    }
    active.value = (index + props.slides.length) % props.slides.length;
    emit('update:modelValue', active.value);
    restartProgress();
}

function next() {
    setActive(active.value + 1);
}

function prev() {
    setActive(active.value - 1);
}

function clearTimers() {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
    if (progressTimer) {
        clearInterval(progressTimer);
        progressTimer = null;
    }
}

function restartProgress() {
    progress.value = 0;
    if (progressTimer) {
        clearInterval(progressTimer);
    }
    if (paused.value) {
        return;
    }
    const tick = 50;
    const steps = props.intervalMs / tick;
    progressTimer = setInterval(() => {
        progress.value = Math.min(100, progress.value + 100 / steps);
    }, tick);
}

function startAutoplay() {
    clearTimers();
    if (paused.value || props.slides.length < 2) {
        return;
    }
    restartProgress();
    timer = setInterval(next, props.intervalMs);
}

function pause() {
    paused.value = true;
    clearTimers();
}

function resume() {
    paused.value = false;
    startAutoplay();
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowRight') {
        next();
    }
    if (event.key === 'ArrowLeft') {
        prev();
    }
}

watch(
    () => props.modelValue,
    (value) => {
        if (value !== active.value) {
            active.value = value;
            restartProgress();
        }
    },
);

onMounted(() => {
    startAutoplay();
    window.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    clearTimers();
    window.removeEventListener('keydown', onKeydown);
});

watch(
    () => props.slides.length,
    () => startAutoplay(),
);

defineExpose({ current, next, prev });
</script>

<template>
    <div
        class="absolute inset-0"
        role="region"
        aria-roledescription="carousel"
        aria-label="Visuels d’accueil Ôhéfê Market"
        @mouseenter="pause"
        @mouseleave="resume"
        @focusin="pause"
        @focusout="resume"
    >
        <div
            v-for="(slide, index) in slides"
            :key="slide.id"
            class="absolute inset-0 transition-opacity duration-[1200ms] ease-out"
            :class="index === active ? 'opacity-100' : 'opacity-0'"
            :aria-hidden="index !== active"
        >
            <picture>
                <source :srcset="slide.webp" type="image/webp" />
                <img
                    :src="slide.jpg"
                    :alt="slide.alt"
                    class="h-full w-full object-cover object-center transition-transform duration-[7000ms] ease-out will-change-transform md:object-[center_28%]"
                    :class="index === active ? 'scale-[1.06]' : 'scale-100'"
                    :fetchpriority="index === 0 ? 'high' : 'low'"
                    :loading="index === 0 ? 'eager' : 'lazy'"
                    width="2400"
                    height="1600"
                />
            </picture>
        </div>

        <div
            class="absolute inset-0 bg-gradient-to-t from-forest-950 via-forest-950/50 to-forest-950/30"
        />
        <div class="absolute inset-0 bg-forest-950/25" />

        <button
            type="button"
            class="absolute top-1/2 left-4 z-10 hidden h-12 w-12 -translate-y-1/2 items-center justify-center border border-white/25 bg-forest-950/20 text-white backdrop-blur-sm transition hover:border-white hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white md:flex"
            aria-label="Image précédente"
            @click="prev"
        >
            <span aria-hidden="true">←</span>
        </button>
        <button
            type="button"
            class="absolute top-1/2 right-4 z-10 hidden h-12 w-12 -translate-y-1/2 items-center justify-center border border-white/25 bg-forest-950/20 text-white backdrop-blur-sm transition hover:border-white hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white md:flex"
            aria-label="Image suivante"
            @click="next"
        >
            <span aria-hidden="true">→</span>
        </button>

        <div class="absolute inset-x-0 bottom-0 z-10 flex gap-1.5 px-4 pb-3 sm:px-5 sm:pb-4 md:px-8 md:pb-5">
            <button
                v-for="(slide, index) in slides"
                :key="`dot-${slide.id}`"
                type="button"
                class="relative flex min-h-8 flex-1 items-center sm:min-h-11"
                :aria-label="`Aller à l’image ${index + 1}`"
                :aria-current="index === active ? 'true' : undefined"
                @click="setActive(index)"
            >
                <span class="relative h-0.5 w-full overflow-hidden bg-white/20 sm:h-1">
                    <span
                        class="absolute inset-y-0 left-0 bg-brass transition-[width] duration-75 ease-linear"
                        :style="{
                            width:
                                index < active
                                    ? '100%'
                                    : index === active
                                      ? `${progress}%`
                                      : '0%',
                        }"
                    />
                </span>
            </button>
        </div>
    </div>
</template>
