<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import CartNavButton from '@/Components/Layout/CartNavButton.vue';
import FlashMessage from '@/Components/Layout/FlashMessage.vue';
import SiteFooter from '@/Components/Layout/SiteFooter.vue';

const page = usePage();
const scrolled = ref(false);
const mobileOpen = ref(false);

const isHome = computed(() => page.url === '/' || page.url.split('?')[0] === '/');
const useDarkNav = computed(() => isHome.value && !scrolled.value && !mobileOpen.value);
const cartCount = computed(() => (page.props.cart as { count?: number } | undefined)?.count ?? 0);

const navLinks = [
    { href: '/boutique', label: 'Boutique' },
    { href: '/arrivages', label: 'Arrivages' },
    { href: '/courses', label: 'Courses' },
] as const;

function isActive(href: string): boolean {
    return page.url === href || page.url.startsWith(`${href}?`);
}

function onScroll() {
    scrolled.value = window.scrollY > 24;
}

function closeMobile() {
    mobileOpen.value = false;
}

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
});
</script>

<template>
    <div class="min-h-screen bg-stone-soft text-ink antialiased">
        <a
            href="#contenu"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-forest-900 focus:px-4 focus:py-2 focus:text-white"
        >
            Aller au contenu
        </a>

        <header
            class="fixed inset-x-0 top-0 z-40 border-b transition-all duration-300"
            :class="
                useDarkNav
                    ? 'border-white/15 bg-forest-950/80 shadow-[0_8px_30px_rgba(0,0,0,0.25)] backdrop-blur-md'
                    : 'border-forest-900/10 bg-stone-soft/95 shadow-sm backdrop-blur-md'
            "
        >
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-5 py-3.5 md:px-8 md:py-4">
                <Link
                    href="/"
                    class="font-display text-xl tracking-tight transition-colors md:text-2xl"
                    :class="useDarkNav ? 'text-white' : 'text-forest-900'"
                    @click="closeMobile"
                >
                    Ôhéfê Market
                </Link>

                <nav
                    class="hidden items-center gap-8 text-sm font-medium md:flex"
                    aria-label="Navigation principale"
                >
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="transition"
                        :class="[
                            useDarkNav ? 'text-white/90 hover:text-brass-light' : 'text-ink-muted hover:text-forest-700',
                            isActive(link.href) && (useDarkNav ? 'text-brass-light' : 'text-forest-900'),
                        ]"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
                    <CartNavButton :dark="useDarkNav" @click="closeMobile" />
                    <Link
                        href="/boutique"
                        class="hidden rounded-sm bg-brass px-4 py-2.5 text-sm font-semibold text-forest-950 shadow-sm transition hover:bg-brass-light sm:inline-flex"
                        @click="closeMobile"
                    >
                        Commencer
                    </Link>
                    <button
                        type="button"
                        class="inline-flex h-11 w-11 items-center justify-center border text-sm font-medium transition md:hidden"
                        :class="
                            useDarkNav
                                ? 'border-white/30 text-white'
                                : 'border-forest-900/20 text-forest-900'
                        "
                        :aria-expanded="mobileOpen"
                        aria-controls="menu-mobile"
                        aria-label="Ouvrir le menu"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <span aria-hidden="true">{{ mobileOpen ? '✕' : 'Menu' }}</span>
                    </button>
                </div>
            </div>

            <div
                v-show="mobileOpen"
                id="menu-mobile"
                class="border-t border-forest-900/10 bg-stone-soft px-5 py-4 md:hidden"
            >
                <nav class="flex flex-col gap-1" aria-label="Navigation mobile">
                    <Link
                        v-for="link in navLinks"
                        :key="`mobile-${link.href}`"
                        :href="link.href"
                        class="px-2 py-3 text-base font-medium text-forest-900 transition hover:text-forest-700"
                        :class="isActive(link.href) && 'text-brass'"
                        @click="closeMobile"
                    >
                        {{ link.label }}
                    </Link>
                    <Link
                        href="/panier"
                        class="flex items-center justify-between px-2 py-3 text-base font-medium text-forest-900"
                        @click="closeMobile"
                    >
                        <span>Panier</span>
                        <span
                            v-if="cartCount > 0"
                            class="inline-flex min-w-6 items-center justify-center bg-forest-900 px-1.5 py-0.5 text-xs font-bold text-white"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>
                    <Link
                        href="/boutique"
                        class="mt-2 inline-flex min-h-11 items-center justify-center bg-brass px-4 text-sm font-semibold text-forest-950"
                        @click="closeMobile"
                    >
                        Commencer
                    </Link>
                </nav>
            </div>
        </header>

        <FlashMessage />

        <main id="contenu">
            <slot />
        </main>

        <SiteFooter />
    </div>
</template>
