<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHero from '@/Components/Landing/PageHero.vue';
import ProductImage from '@/Components/Shop/ProductImage.vue';

type CargoCard = {
    id: number;
    code: string;
    name: string;
    route: string;
    status: string;
    status_label: string;
    departure_at: string | null;
    estimated_arrival_at: string | null;
    products_count: number;
    accepts_reservations: boolean;
};

type ArrivalProduct = {
    id: number;
    cargo_id: number;
    cargo_code: string;
    cargo_name: string;
    estimated_arrival_at: string | null;
    product_name: string | null;
    description: string | null;
    category: string | null;
    image_url: string;
    quantity_remaining: number;
    unit_price: string;
    unit: string | null;
    is_partner: boolean;
    partner_name: string | null;
    can_reserve: boolean;
};

const props = defineProps<{
    cargos: CargoCard[];
    products: ArrivalProduct[];
    filters: { cargo: number | null };
    cargo_cart: { count: number; cargo_id: number | null };
}>();

const page = usePage();
const sharedCargoCartCount = computed(
    () => (page.props.cargo_cart as { count?: number } | undefined)?.count ?? props.cargo_cart.count,
);

const addingId = ref<number | null>(null);

function selectCargo(cargoId: number | null) {
    router.get(
        '/arrivages',
        cargoId ? { cargo: cargoId } : {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['products', 'filters', 'cargos', 'cargo_cart'],
        },
    );
}

function addToCart(product: ArrivalProduct) {
    if (!product.can_reserve || addingId.value) {
        return;
    }

    addingId.value = product.id;
    router.post(
        '/panier-arrivage',
        { cargo_item_id: product.id, quantity: 1 },
        {
            preserveScroll: true,
            onFinish: () => {
                addingId.value = null;
            },
        },
    );
}
</script>

<template>
    <AppLayout>
        <Head title="Arrivages">
            <meta
                head-key="description"
                name="description"
                content="Réservez plusieurs produits du même cargo avant son arrivée au Canada avec Ôhéfê Market."
            />
        </Head>

        <PageHero
            eyebrow="Arrivages"
            title="Prochains cargos"
            description="Choisissez un arrivage, ajoutez plusieurs produits au panier — une seule commande par cargo."
            webp="/images/landing/section-fresh.webp"
            jpg="/images/landing/section-fresh.jpg"
            alt="Produits frais pour arrivage"
        />

        <section class="bg-stone-soft px-4 py-12 sm:px-5 sm:py-14 md:px-8 md:py-20">
            <div class="mx-auto max-w-6xl space-y-10 sm:space-y-12">
                <div
                    v-if="sharedCargoCartCount > 0"
                    class="flex flex-col gap-3 border border-forest-900/10 bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                >
                    <p class="text-sm text-forest-900">
                        <span class="font-medium">{{ sharedCargoCartCount }}</span>
                        article{{ sharedCargoCartCount > 1 ? 's' : '' }} dans le panier arrivage
                        <span v-if="cargo_cart.cargo_id" class="text-ink-muted">
                            · un seul cargo par commande
                        </span>
                    </p>
                    <Link
                        href="/panier-arrivage"
                        class="inline-flex min-h-11 items-center justify-center bg-forest-900 px-5 text-sm font-semibold text-white"
                    >
                        Voir le panier arrivage
                    </Link>
                </div>

                <div>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="font-display text-2xl text-forest-900 sm:text-3xl">
                                Arrivages à venir
                            </h2>
                            <p class="mt-2 text-sm text-ink-muted">
                                Sélectionnez un cargo pour filtrer les produits.
                            </p>
                        </div>
                        <button
                            v-if="filters.cargo"
                            type="button"
                            class="text-sm font-medium text-forest-800 underline"
                            @click="selectCargo(null)"
                        >
                            Voir tous les produits
                        </button>
                    </div>

                    <div
                        v-if="cargos.length"
                        class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <button
                            v-for="cargo in cargos"
                            :key="cargo.id"
                            type="button"
                            class="border p-5 text-left transition sm:p-6"
                            :class="
                                filters.cargo === cargo.id
                                    ? 'border-forest-900 bg-forest-900 text-white'
                                    : 'border-forest-900/10 bg-white hover:border-forest-900/30'
                            "
                            @click="selectCargo(filters.cargo === cargo.id ? null : cargo.id)"
                        >
                            <p
                                class="text-xs tracking-[0.16em] uppercase"
                                :class="filters.cargo === cargo.id ? 'text-brass-light' : 'text-brass'"
                            >
                                {{ cargo.code }}
                            </p>
                            <h3 class="font-display mt-2 text-xl sm:text-2xl">{{ cargo.name }}</h3>
                            <p
                                class="mt-2 text-sm"
                                :class="filters.cargo === cargo.id ? 'text-white/75' : 'text-ink-muted'"
                            >
                                {{ cargo.route }}
                            </p>
                            <div
                                class="mt-5 flex items-end justify-between gap-3 border-t pt-4"
                                :class="
                                    filters.cargo === cargo.id
                                        ? 'border-white/20'
                                        : 'border-forest-900/10'
                                "
                            >
                                <div class="text-sm">
                                    <p :class="filters.cargo === cargo.id ? 'text-white/70' : 'text-ink-muted'">
                                        Arrivée estimée
                                    </p>
                                    <p class="font-medium">
                                        {{ cargo.estimated_arrival_at ?? 'Bientôt' }}
                                    </p>
                                </div>
                                <p class="text-right text-sm font-semibold">
                                    {{ cargo.products_count }}
                                    produit{{ cargo.products_count > 1 ? 's' : '' }}
                                </p>
                            </div>
                        </button>
                    </div>
                    <div
                        v-else
                        class="mt-6 border border-dashed border-forest-900/20 bg-white p-8 text-center text-ink-muted"
                    >
                        Aucun cargo ouvert pour le moment.
                    </div>
                </div>

                <div>
                    <h2 class="font-display text-2xl text-forest-900 sm:text-3xl">
                        Produits à réserver
                    </h2>
                    <p class="mt-2 text-sm text-ink-muted">
                        Ajoutez plusieurs articles — uniquement s’ils appartiennent au même cargo.
                    </p>

                    <div
                        v-if="products.length"
                        class="mt-8 grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3"
                    >
                        <article
                            v-for="product in products"
                            :key="product.id"
                            class="flex flex-col overflow-hidden border border-forest-900/10 bg-white transition hover:-translate-y-0.5 hover:shadow-[0_20px_40px_-28px_rgba(15,46,36,0.35)]"
                        >
                            <div class="aspect-[4/3] overflow-hidden bg-stone-soft">
                                <ProductImage
                                    :src="product.image_url"
                                    :alt="product.product_name ?? 'Produit'"
                                    img-class="h-full w-full object-cover"
                                />
                            </div>
                            <div class="flex flex-1 flex-col p-4 sm:p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-xs tracking-wide text-ink-muted uppercase">
                                        {{ product.category ?? 'Produit' }}
                                    </p>
                                    <span class="shrink-0 text-xs font-semibold text-forest-700">
                                        {{ product.cargo_code }}
                                    </span>
                                </div>
                                <h3 class="font-display mt-3 text-xl text-forest-900">
                                    {{ product.product_name }}
                                </h3>
                                <p class="mt-2 text-xs text-ink-muted">
                                    Cargo {{ product.cargo_code }}
                                    <span v-if="product.estimated_arrival_at">
                                        · arrive le {{ product.estimated_arrival_at }}
                                    </span>
                                </p>
                                <p
                                    v-if="product.is_partner && product.partner_name"
                                    class="mt-1 text-xs text-ink-muted"
                                >
                                    Partenaire : {{ product.partner_name }}
                                </p>
                                <p
                                    v-if="product.description"
                                    class="mt-3 line-clamp-2 flex-1 text-sm text-ink-muted"
                                >
                                    {{ product.description }}
                                </p>
                                <div
                                    class="mt-5 flex flex-col gap-3 border-t border-forest-900/10 pt-4 sm:flex-row sm:items-end sm:justify-between"
                                >
                                    <div>
                                        <p class="text-lg font-semibold text-forest-900">
                                            {{ product.unit_price }}
                                        </p>
                                        <p class="text-xs text-ink-muted">
                                            / {{ product.unit }}
                                            · {{ product.quantity_remaining }} restant{{
                                                product.quantity_remaining > 1 ? 's' : ''
                                            }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="min-h-11 w-full bg-forest-900 px-4 text-sm font-semibold text-white transition hover:bg-forest-800 disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
                                        :disabled="!product.can_reserve || addingId === product.id"
                                        @click="addToCart(product)"
                                    >
                                        <span v-if="addingId === product.id">Ajout…</span>
                                        <span v-else>
                                            {{ product.can_reserve ? 'Ajouter' : 'Indisponible' }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div
                        v-else
                        class="mt-8 border border-dashed border-forest-900/20 bg-white p-8 text-center text-ink-muted"
                    >
                        Aucun produit à réserver
                        {{ filters.cargo ? 'pour ce cargo' : 'pour le moment' }}.
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
