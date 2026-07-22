<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps<{
    order: {
        reference: string;
        status: string;
        status_label: string;
        type_label: string;
        customer_name: string;
        customer_email: string | null;
        shipping_address: string | null;
        shipping_phone: string | null;
        subtotal: string;
        total: string;
        placed_at: string | null;
        notes: string | null;
        items: Array<{
            name: string;
            unit: string;
            quantity: number;
            unit_price: string;
            line_total: string;
        }>;
    };
}>();
</script>

<template>
    <AppLayout>
        <Head :title="`Commande ${order.reference}`" />

        <section
            class="bg-stone-soft px-4 pb-16 sm:px-5 md:px-8 md:pb-24"
            :style="{ paddingTop: 'calc(var(--header-offset) + 1.5rem)' }"
        >
            <div class="mx-auto max-w-2xl space-y-6">
                <div>
                    <p class="text-xs tracking-[0.18em] text-brass uppercase">Commande confirmée</p>
                    <h1 class="font-display mt-2 text-3xl text-forest-900 sm:text-4xl">
                        {{ order.reference }}
                    </h1>
                    <p class="mt-2 text-sm text-ink-muted">
                        {{ order.placed_at }} · {{ order.status_label }} · {{ order.type_label }}
                    </p>
                </div>

                <div class="border border-forest-900/10 bg-white p-5 sm:p-6">
                    <p class="text-sm text-forest-800">
                        Merci {{ order.customer_name }}.
                        <span v-if="order.customer_email">
                            Un suivi sera envoyé à {{ order.customer_email }}.
                        </span>
                    </p>
                    <div v-if="order.shipping_address" class="mt-4 text-sm text-ink-muted">
                        <p class="font-medium text-forest-900">Livraison</p>
                        <p class="mt-1 whitespace-pre-line">{{ order.shipping_address }}</p>
                    </div>
                    <p v-if="order.notes" class="mt-3 text-sm text-ink-muted">Note : {{ order.notes }}</p>
                </div>

                <div class="border border-forest-900/10 bg-white">
                    <ul class="divide-y divide-forest-900/10">
                        <li
                            v-for="(item, index) in order.items"
                            :key="index"
                            class="flex items-start justify-between gap-4 px-5 py-4 text-sm"
                        >
                            <div>
                                <p class="font-medium text-forest-900">{{ item.name }}</p>
                                <p class="mt-1 text-ink-muted">
                                    {{ item.quantity }} × {{ item.unit_price }} / {{ item.unit }}
                                </p>
                            </div>
                            <p class="shrink-0 font-medium text-forest-900">{{ item.line_total }}</p>
                        </li>
                    </ul>
                    <div
                        class="flex items-center justify-between border-t border-forest-900/10 px-5 py-4"
                    >
                        <p class="text-sm text-ink-muted">Total</p>
                        <p class="font-display text-xl text-forest-900">{{ order.total }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link
                        href="/boutique"
                        class="inline-flex min-h-11 items-center justify-center bg-forest-900 px-5 text-sm font-semibold text-white"
                    >
                        Retour boutique
                    </Link>
                    <Link
                        href="/panier"
                        class="inline-flex min-h-11 items-center justify-center border border-forest-900/20 px-5 text-sm text-forest-900"
                    >
                        Voir le panier
                    </Link>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
