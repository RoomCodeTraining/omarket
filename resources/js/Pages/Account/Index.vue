<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

type OrderRow = {
    id: number;
    reference: string;
    status: string;
    status_label: string;
    type_label: string;
    total: string;
    placed_at: string | null;
    items_count: number;
};

type CustomRequestItemRow = {
    label: string;
    quantity: number;
    budget: string | null;
};

type CustomRequestRow = {
    id: number;
    title: string;
    description: string;
    quantity: number;
    budget: string | null;
    items: CustomRequestItemRow[];
    status: string;
    status_label: string;
    created_at: string | null;
    latest_quote: {
        amount: string;
        status: string;
        status_label: string;
    } | null;
};

defineProps<{
    client: { name: string; email: string };
    orders: OrderRow[];
    custom_requests: CustomRequestRow[];
}>();

const tab = ref<'orders' | 'requests'>('orders');

function logout() {
    router.post('/deconnexion');
}

function statusTone(status: string): string {
    if (status === 'delivered' || status === 'accepted' || status === 'quoted') {
        return 'text-emerald-700';
    }

    if (status === 'cancelled' || status === 'rejected') {
        return 'text-red-700';
    }

    if (status === 'pending_payment' || status === 'submitted' || status === 'in_review') {
        return 'text-amber-700';
    }

    return 'text-forest-700';
}
</script>

<template>
    <AppLayout>
        <Head title="Mon compte" />

        <section
            class="bg-stone-soft px-4 pb-16 sm:px-5 md:px-8 md:pb-24"
            :style="{ paddingTop: 'calc(var(--header-offset) + 1.5rem)' }"
        >
            <div class="mx-auto max-w-4xl space-y-6">
                <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs tracking-[0.18em] text-brass uppercase">Mon compte</p>
                        <h1 class="font-display mt-1 text-3xl text-forest-900 sm:text-4xl">{{ client.name }}</h1>
                        <p class="mt-1 text-sm text-ink-muted">{{ client.email }}</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex min-h-11 items-center justify-center border border-forest-900/20 px-4 text-sm text-forest-900"
                        @click="logout"
                    >
                        Déconnexion
                    </button>
                </header>

                <div class="flex gap-6 border-b border-forest-900/10 text-sm">
                    <button
                        type="button"
                        class="border-b-2 pb-3 font-medium"
                        :class="
                            tab === 'orders'
                                ? 'border-forest-900 text-forest-900'
                                : 'border-transparent text-ink-muted'
                        "
                        @click="tab = 'orders'"
                    >
                        Commandes ({{ orders.length }})
                    </button>
                    <button
                        type="button"
                        class="border-b-2 pb-3 font-medium"
                        :class="
                            tab === 'requests'
                                ? 'border-forest-900 text-forest-900'
                                : 'border-transparent text-ink-muted'
                        "
                        @click="tab = 'requests'"
                    >
                        Demandes ({{ custom_requests.length }})
                    </button>
                </div>

                <div v-if="tab === 'orders'">
                    <div v-if="orders.length" class="space-y-3">
                        <article
                            v-for="order in orders"
                            :key="order.id"
                            class="border border-forest-900/10 bg-white px-4 py-4"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-forest-900">{{ order.reference }}</p>
                                    <p class="mt-1 text-sm text-ink-muted">
                                        {{ order.placed_at ?? '—' }} · {{ order.type_label }} ·
                                        {{ order.items_count }} article{{ order.items_count > 1 ? 's' : '' }}
                                    </p>
                                    <p class="mt-2 text-xs font-medium" :class="statusTone(order.status)">
                                        {{ order.status_label }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-forest-900">{{ order.total }}</p>
                                    <Link
                                        :href="`/commandes/${order.id}`"
                                        class="mt-2 inline-block text-xs font-medium text-forest-800 underline"
                                    >
                                        Voir le détail
                                    </Link>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div
                        v-else
                        class="border border-dashed border-forest-900/15 px-6 py-14 text-center"
                    >
                        <p class="text-ink-muted">Aucune commande pour le moment.</p>
                        <Link href="/boutique" class="mt-4 inline-block text-sm font-medium text-forest-800 underline">
                            Aller à la boutique
                        </Link>
                    </div>
                </div>

                <div v-else>
                    <div v-if="custom_requests.length" class="space-y-3">
                        <article
                            v-for="request in custom_requests"
                            :key="request.id"
                            class="border border-forest-900/10 bg-white px-4 py-4"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-medium text-forest-900">{{ request.title }}</p>
                                    <p class="mt-1 text-sm text-ink-muted">
                                        {{ request.created_at }} · {{ request.items.length }} produit{{ request.items.length > 1 ? 's' : '' }}
                                        <span v-if="request.budget"> · budget total {{ request.budget }}</span>
                                    </p>
                                    <ul v-if="request.items.length" class="mt-2 space-y-1 text-sm text-ink-muted">
                                        <li v-for="(item, index) in request.items" :key="index">
                                            {{ item.label }} × {{ item.quantity }}
                                            <span v-if="item.budget"> · {{ item.budget }}</span>
                                        </li>
                                    </ul>
                                    <p v-else-if="request.description" class="mt-2 line-clamp-2 text-sm text-ink-muted">
                                        {{ request.description }}
                                    </p>
                                    <p class="mt-2 text-xs font-medium" :class="statusTone(request.status)">
                                        {{ request.status_label }}
                                    </p>
                                </div>
                                <div v-if="request.latest_quote" class="text-right text-sm">
                                    <p class="text-ink-muted">Dernier devis</p>
                                    <p class="font-medium text-forest-900">{{ request.latest_quote.amount }}</p>
                                    <p class="mt-1 text-xs" :class="statusTone(request.latest_quote.status)">
                                        {{ request.latest_quote.status_label }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div
                        v-else
                        class="border border-dashed border-forest-900/15 px-6 py-14 text-center"
                    >
                        <p class="text-ink-muted">Aucune demande personnalisée.</p>
                        <Link href="/courses" class="mt-4 inline-block text-sm font-medium text-forest-800 underline">
                            Faire une demande
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
