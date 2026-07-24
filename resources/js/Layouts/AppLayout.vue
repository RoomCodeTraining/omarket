<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import CartNavButton from '@/Components/Layout/CartNavButton.vue';
import FlashMessage from '@/Components/Layout/FlashMessage.vue';
import SiteFooter from '@/Components/Layout/SiteFooter.vue';
import UserNavAvatar from '@/Components/Layout/UserNavAvatar.vue';

type AuthUser = {
    id: number;
    name: string;
    email: string;
    role?: string | null;
    can_publish?: boolean;
};

type AppProps = {
    name?: string;
    logo_url?: string | null;
    primary_color?: string;
    partner_registration_enabled?: boolean;
    courses_enabled?: boolean;
    arrivals_enabled?: boolean;
};

const page = usePage();
const scrolled = ref(false);
const mobileOpen = ref(false);

const isHome = computed(() => page.url === '/' || page.url.split('?')[0] === '/');
const useDarkNav = computed(() => isHome.value && !scrolled.value && !mobileOpen.value);
const cartCount = computed(() => (page.props.cart as { count?: number } | undefined)?.count ?? 0);
const cargoCartCount = computed(
    () => (page.props.cargo_cart as { count?: number } | undefined)?.count ?? 0,
);

const authUser = computed(
    () => (page.props.auth as { user?: AuthUser | null } | undefined)?.user ?? null,
);
const isPartner = computed(() => authUser.value?.role === 'partner');

const app = computed(() => (page.props.app as AppProps | undefined) ?? {});
const storeName = computed(() => app.value.name ?? 'Ôhéfê Market');
const logoUrl = computed(() => app.value.logo_url ?? null);

function applyPrimaryColor(hex: string): void {
    const color = /^#[0-9A-Fa-f]{6}$/.test(hex) ? hex : '#0f2e24';
    const root = document.documentElement;

    root.style.setProperty('--color-forest-900', color);
    root.style.setProperty('--color-forest-950', `color-mix(in srgb, ${color} 82%, black)`);
    root.style.setProperty('--color-forest-800', `color-mix(in srgb, ${color} 88%, white)`);
    root.style.setProperty('--color-forest-700', `color-mix(in srgb, ${color} 72%, white)`);
    root.style.setProperty('--color-forest-600', `color-mix(in srgb, ${color} 58%, white)`);
}

watch(
    () => app.value.primary_color ?? '#0f2e24',
    (color) => applyPrimaryColor(color),
    { immediate: true },
);

const navLinks = computed(() => {
    const links: Array<{ href: string; label: string }> = [
        { href: '/boutique', label: 'Boutique' },
    ];

    if (app.value.arrivals_enabled !== false) {
        links.push({ href: '/arrivages', label: 'Arrivages' });
    }

    if (app.value.courses_enabled !== false) {
        links.push({ href: '/courses', label: 'Courses' });
    }

    if (!authUser.value && app.value.partner_registration_enabled !== false) {
        links.push({ href: '/partenaires/inscription', label: 'Partenaires' });
    }

    return links;
});

function isActive(href: string): boolean {
    return page.url === href || page.url.startsWith(`${href}?`);
}

function onScroll() {
    scrolled.value = window.scrollY > 24;
}

function closeMobile() {
    mobileOpen.value = false;
}

function logout() {
    closeMobile();
    router.post(isPartner.value ? '/partenaires/deconnexion' : '/deconnexion');
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && mobileOpen.value) {
        closeMobile();
    }
}

watch(mobileOpen, (open) => {
    document.body.classList.toggle('nav-locked', open);
});

watch(
    () => page.url,
    () => closeMobile(),
);

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('keydown', onKeydown);
    document.body.classList.remove('nav-locked');
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
            :style="{ paddingTop: 'env(safe-area-inset-top, 0px)' }"
        >
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3.5 sm:gap-6 sm:px-5 md:px-8 md:py-4">
                <Link
                    href="/"
                    class="font-display flex min-w-0 items-center gap-2.5 truncate text-lg tracking-tight transition-colors sm:text-xl md:text-2xl"
                    :class="useDarkNav ? 'text-white' : 'text-forest-900'"
                    @click="closeMobile"
                >
                    <img
                        v-if="logoUrl"
                        :src="logoUrl"
                        :alt="storeName"
                        class="h-8 w-auto max-w-[9rem] object-contain sm:h-9"
                    />
                    <span v-else>{{ storeName }}</span>
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

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <CartNavButton :dark="useDarkNav" @click="closeMobile" />
                    <Link
                        v-if="cargoCartCount > 0"
                        href="/panier-arrivage"
                        class="relative hidden h-11 items-center px-2 text-sm font-medium transition sm:inline-flex"
                        :class="
                            useDarkNav
                                ? 'text-white hover:bg-white/10'
                                : 'text-forest-900 hover:bg-forest-900/5'
                        "
                        :aria-label="`Panier arrivage, ${cargoCartCount} article${cargoCartCount > 1 ? 's' : ''}`"
                    >
                        Arrivage
                        <span
                            class="ml-1.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] font-bold"
                            :class="useDarkNav ? 'bg-brass text-forest-950' : 'bg-forest-900 text-white'"
                        >
                            {{ cargoCartCount > 99 ? '99+' : cargoCartCount }}
                        </span>
                    </Link>

                    <UserNavAvatar
                        v-if="authUser"
                        :name="authUser.name"
                        :email="authUser.email"
                        :role="authUser.role"
                        :can-publish="authUser.can_publish"
                        :dark="useDarkNav"
                    />
                    <Link
                        v-else
                        href="/connexion"
                        class="hidden rounded-sm bg-brass px-4 py-2.5 text-sm font-semibold text-forest-950 shadow-sm transition hover:bg-brass-light sm:inline-flex"
                        @click="closeMobile"
                    >
                        Connexion
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
                class="border-t border-forest-900/10 bg-stone-soft px-4 py-4 sm:px-5 md:hidden"
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
                        <span>Panier boutique</span>
                        <span
                            v-if="cartCount > 0"
                            class="inline-flex min-w-6 items-center justify-center bg-forest-900 px-1.5 py-0.5 text-xs font-bold text-white"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>
                    <Link
                        v-if="cargoCartCount > 0"
                        href="/panier-arrivage"
                        class="flex items-center justify-between px-2 py-3 text-base font-medium text-forest-900"
                        @click="closeMobile"
                    >
                        <span>Panier arrivage</span>
                        <span
                            class="inline-flex min-w-6 items-center justify-center bg-forest-900 px-1.5 py-0.5 text-xs font-bold text-white"
                        >
                            {{ cargoCartCount }}
                        </span>
                    </Link>

                    <template v-if="authUser">
                        <Link
                            v-if="isPartner"
                            href="/partenaires/espace"
                            class="mt-2 flex min-h-11 items-center gap-3 bg-forest-900 px-4 text-sm font-semibold text-white"
                            @click="closeMobile"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brass text-xs font-bold text-forest-950"
                                aria-hidden="true"
                            >
                                {{
                                    authUser.name
                                        .trim()
                                        .split(/\s+/)
                                        .slice(0, 2)
                                        .map((part) => part[0] ?? '')
                                        .join('')
                                        .toUpperCase() || '?'
                                }}
                            </span>
                            <span class="min-w-0 truncate">Mon espace</span>
                        </Link>
                        <Link
                            v-else
                            href="/compte"
                            class="mt-2 flex min-h-11 items-center justify-center bg-forest-900 px-4 text-sm font-semibold text-white"
                            @click="closeMobile"
                        >
                            Mon compte
                        </Link>
                        <button
                            type="button"
                            class="mt-2 flex min-h-11 w-full items-center justify-center border border-forest-900/15 px-4 text-sm font-semibold text-forest-900"
                            @click="logout"
                        >
                            Se déconnecter
                        </button>
                    </template>
                    <Link
                        v-else
                        href="/connexion"
                        class="mt-2 inline-flex min-h-11 items-center justify-center bg-brass px-4 text-sm font-semibold text-forest-950"
                        @click="closeMobile"
                    >
                        Connexion
                    </Link>
                </nav>
            </div>
        </header>

        <button
            v-if="mobileOpen"
            type="button"
            class="fixed inset-0 z-30 bg-forest-950/40 md:hidden"
            aria-label="Fermer le menu"
            @click="closeMobile"
        />

        <FlashMessage />

        <main id="contenu">
            <slot />
        </main>

        <SiteFooter />
    </div>
</template>
