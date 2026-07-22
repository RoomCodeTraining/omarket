<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    name: string;
    email: string;
    role?: string | null;
    canPublish?: boolean;
    dark?: boolean;
}>();

const open = ref(false);
const root = ref<HTMLElement | null>(null);

const isPartner = computed(() => props.role === 'partner');

const initials = computed(() => {
    const parts = props.name.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0][0] ?? ''}${parts[1][0] ?? ''}`.toUpperCase();
});

const menuItems = computed(() => {
    if (isPartner.value) {
        return [
            { href: '/partenaires/espace', label: 'Mon espace', description: 'Produits et statut' },
            { href: '/boutique', label: 'Boutique', description: 'Catalogue en ligne' },
            { href: '/arrivages', label: 'Arrivages', description: 'Cargos et préventes' },
            { href: '/courses', label: 'Courses', description: 'Demandes personnalisées' },
        ];
    }

    return [
        { href: '/compte', label: 'Mon compte', description: 'Commandes et demandes' },
        { href: '/boutique', label: 'Boutique', description: 'Catalogue en ligne' },
        { href: '/panier', label: 'Panier', description: 'Vos articles' },
        { href: '/courses', label: 'Courses', description: 'Demandes personnalisées' },
    ];
});

const statusLabel = computed(() => {
    if (!isPartner.value) {
        return null;
    }

    return props.canPublish ? 'Publication autorisée' : 'En attente de validation';
});

function toggle() {
    open.value = !open.value;
}

function close() {
    open.value = false;
}

function logout() {
    close();
    router.post(isPartner.value ? '/partenaires/deconnexion' : '/deconnexion');
}

function onDocumentClick(event: MouseEvent) {
    if (!open.value || !root.value) {
        return;
    }

    if (!root.value.contains(event.target as Node)) {
        close();
    }
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && open.value) {
        close();
    }
}

watch(open, async (isOpen) => {
    if (isOpen) {
        await nextTick();
    }
});

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="inline-flex h-11 w-11 items-center justify-center rounded-full text-sm font-semibold tracking-wide transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
            :class="
                dark
                    ? 'bg-brass text-forest-950 shadow-[0_0_0_2px_rgba(255,255,255,0.35)] hover:bg-brass-light focus-visible:outline-white'
                    : 'bg-forest-900 text-white shadow-[0_0_0_2px_rgba(15,46,36,0.12)] hover:bg-forest-800 focus-visible:outline-forest-900'
            "
            :aria-expanded="open"
            aria-haspopup="menu"
            aria-controls="user-menu"
            :aria-label="`Menu compte de ${name}`"
            @click.stop="toggle"
        >
            <span aria-hidden="true">{{ initials }}</span>
        </button>

        <div
            v-show="open"
            id="user-menu"
            role="menu"
            aria-label="Menu compte"
            class="absolute right-0 z-50 mt-2 w-72 origin-top-right border border-forest-900/10 bg-white shadow-[0_20px_50px_-28px_rgba(10,31,24,0.55)]"
        >
            <div class="border-b border-forest-900/10 px-4 py-3">
                <p class="truncate text-sm font-semibold text-forest-900">{{ name }}</p>
                <p class="mt-0.5 truncate text-xs text-ink-muted">{{ email }}</p>
                <p
                    v-if="statusLabel"
                    class="mt-2 text-xs font-medium"
                    :class="canPublish ? 'text-forest-700' : 'text-brass'"
                >
                    {{ statusLabel }}
                </p>
            </div>

            <div class="py-1">
                <Link
                    v-for="item in menuItems"
                    :key="item.href"
                    :href="item.href"
                    role="menuitem"
                    class="block px-4 py-2.5 transition hover:bg-stone-soft"
                    @click="close"
                >
                    <span class="block text-sm font-medium text-forest-900">{{ item.label }}</span>
                    <span class="block text-xs text-ink-muted">{{ item.description }}</span>
                </Link>
            </div>

            <div class="border-t border-forest-900/10 p-2">
                <button
                    type="button"
                    role="menuitem"
                    class="flex min-h-11 w-full items-center px-3 text-left text-sm font-semibold text-red-700 transition hover:bg-red-50"
                    @click="logout"
                >
                    Se déconnecter
                </button>
            </div>
        </div>
    </div>
</template>
