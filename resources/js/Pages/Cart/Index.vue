<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductImage from '@/Components/Shop/ProductImage.vue';

type CartItem = {
    product_id: number;
    quantity: number;
    name: string;
    unit: string;
    price: string;
    line_total: string;
    stock_quantity: number;
    in_stock: boolean;
    image_url: string;
    category: string | null;
};

defineProps<{
    items: CartItem[];
    total: string;
    count: number;
}>();

function updateQuantity(productId: number, quantity: number) {
    router.patch(`/panier/${productId}`, { quantity }, { preserveScroll: true });
}

function removeItem(productId: number) {
    router.delete(`/panier/${productId}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <Head title="Panier" />

        <section class="bg-stone-soft px-5 pt-28 pb-16 md:px-8 md:pb-24">
            <div class="mx-auto max-w-4xl">
                <h1 class="font-display text-4xl text-forest-900">Votre panier</h1>
                <p class="mt-2 text-ink-muted">{{ count }} article{{ count > 1 ? 's' : '' }}</p>

                <div v-if="items.length" class="mt-10 space-y-4">
                    <article
                        v-for="item in items"
                        :key="item.product_id"
                        class="flex flex-col gap-4 border border-forest-900/10 bg-white p-5 md:flex-row md:items-center md:justify-between"
                    >
                        <div class="flex items-center gap-4">
                            <div class="h-20 w-20 shrink-0 overflow-hidden bg-stone-soft">
                                <ProductImage
                                    :src="item.image_url"
                                    :alt="item.name"
                                    img-class="h-full w-full object-cover"
                                />
                            </div>
                            <div>
                                <p class="text-xs tracking-wide text-ink-muted uppercase">
                                    {{ item.category }}
                                </p>
                                <h2 class="font-display mt-1 text-2xl text-forest-900">{{ item.name }}</h2>
                                <p class="mt-1 text-sm text-ink-muted">
                                    {{ item.price }} / {{ item.unit }}
                                    <span v-if="!item.in_stock" class="text-brass"> · stock insuffisant</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <label class="sr-only" :for="`qty-${item.product_id}`">Quantité</label>
                            <input
                                :id="`qty-${item.product_id}`"
                                type="number"
                                min="1"
                                :max="item.stock_quantity"
                                class="h-11 w-20 border border-forest-900/20 bg-stone-soft px-3 text-center"
                                :value="item.quantity"
                                @change="
                                    updateQuantity(
                                        item.product_id,
                                        Number(($event.target as HTMLInputElement).value),
                                    )
                                "
                            />
                            <p class="min-w-24 text-right font-semibold text-forest-900">
                                {{ item.line_total }}
                            </p>
                            <button
                                type="button"
                                class="text-sm font-medium text-ink-muted underline-offset-2 hover:text-forest-900 hover:underline"
                                @click="removeItem(item.product_id)"
                            >
                                Retirer
                            </button>
                        </div>
                    </article>

                    <div class="flex flex-col items-start justify-between gap-4 border border-forest-900/10 bg-white p-6 md:flex-row md:items-center">
                        <div>
                            <p class="text-sm text-ink-muted">Total estimé</p>
                            <p class="font-display text-3xl text-forest-900">{{ total }}</p>
                        </div>
                        <p class="max-w-sm text-sm text-ink-muted">
                            Le paiement sécurisé arrive à la prochaine étape. Votre panier est déjà
                            enregistré pour cette session.
                        </p>
                    </div>
                </div>

                <div v-else class="mt-12 border border-dashed border-forest-900/20 bg-white p-10 text-center">
                    <p class="text-ink-muted">Votre panier est vide.</p>
                    <Link
                        href="/boutique"
                        class="mt-6 inline-flex min-h-12 items-center bg-forest-900 px-6 text-sm font-semibold text-white"
                    >
                        Continuer vos courses
                    </Link>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
