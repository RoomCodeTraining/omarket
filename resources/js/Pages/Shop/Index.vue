<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHero from '@/Components/Landing/PageHero.vue';
import ProductImage from '@/Components/Shop/ProductImage.vue';

type Category = { id: number; name: string; slug: string };
type Product = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    stock_quantity: number;
    unit: string;
    is_featured: boolean;
    in_stock: boolean;
    image_url: string;
    category: Category | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedProducts = {
    data: Product[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    categories: Category[];
    products: PaginatedProducts;
    filters: { category: string | null };
    stats: { products: number; in_stock: number };
}>();

const addingId = ref<number | null>(null);

function selectCategory(slug: string | null) {
    router.get(
        '/boutique',
        slug ? { category: slug } : {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['products', 'filters', 'stats'],
        },
    );
}

function goToPage(url: string | null) {
    if (!url) {
        return;
    }

    router.get(url, {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['products', 'filters', 'stats'],
    });
}

function addToCart(product: Product) {
    if (!product.in_stock || addingId.value) {
        return;
    }

    addingId.value = product.id;
    router.post(
        '/panier',
        { product_id: product.id, quantity: 1 },
        {
            preserveScroll: true,
            only: ['cart', 'flash', 'errors'],
            onFinish: () => {
                addingId.value = null;
            },
        },
    );
}

function pageLabel(label: string): string {
    if (label.includes('Previous') || label.includes('&laquo;')) {
        return 'Précédent';
    }

    if (label.includes('Next') || label.includes('&raquo;')) {
        return 'Suivant';
    }

    return label;
}
</script>

<template>
    <AppLayout>
        <Head title="Boutique">
            <meta
                head-key="description"
                name="description"
                content="Catalogue Ôhéfê Market — produits ivoiriens disponibles immédiatement au Canada."
            />
        </Head>

        <PageHero
            eyebrow="Boutique"
            title="Produits disponibles maintenant"
            description="Commandez ce qui est déjà en stock. Simple, rapide, livré avec soin."
            webp="/images/landing/section-shopper.webp"
            jpg="/images/landing/section-shopper.jpg"
            alt="Shopping de produits frais"
        />

        <section class="bg-stone-soft px-4 py-12 sm:px-5 sm:py-14 md:px-8 md:py-20">
            <div class="mx-auto max-w-6xl">
                <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between md:gap-6">
                    <div>
                        <h2 class="font-display text-2xl text-forest-900 sm:text-3xl">Catalogue</h2>
                        <p class="mt-2 text-sm text-ink-muted sm:text-base">
                            {{ stats.in_stock }} produits en stock · {{ stats.products }} au catalogue
                        </p>
                    </div>

                    <div
                        class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0"
                        role="tablist"
                        aria-label="Filtrer par catégorie"
                    >
                        <button
                            type="button"
                            class="min-h-11 shrink-0 px-4 text-sm font-medium whitespace-nowrap transition"
                            :class="
                                !filters.category
                                    ? 'bg-forest-900 text-white'
                                    : 'border border-forest-900/15 text-forest-900 hover:border-forest-900'
                            "
                            @click="selectCategory(null)"
                        >
                            Tous
                        </button>
                        <button
                            v-for="category in categories"
                            :key="category.id"
                            type="button"
                            class="min-h-11 shrink-0 px-4 text-sm font-medium whitespace-nowrap transition"
                            :class="
                                filters.category === category.slug
                                    ? 'bg-forest-900 text-white'
                                    : 'border border-forest-900/15 text-forest-900 hover:border-forest-900'
                            "
                            @click="selectCategory(category.slug)"
                        >
                            {{ category.name }}
                        </button>
                    </div>
                </div>

                <div
                    v-if="products.data.length"
                    class="mt-8 grid gap-4 sm:mt-10 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3"
                >
                    <article
                        v-for="product in products.data"
                        :key="product.id"
                        class="flex flex-col overflow-hidden border border-forest-900/10 bg-white transition hover:-translate-y-0.5 hover:shadow-[0_20px_40px_-28px_rgba(15,46,36,0.35)]"
                    >
                        <div class="aspect-[4/3] overflow-hidden bg-stone-soft">
                            <ProductImage
                                :src="product.image_url"
                                :alt="product.name"
                                img-class="h-full w-full object-cover"
                            />
                        </div>
                        <div class="flex flex-1 flex-col p-4 sm:p-6">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-xs tracking-wide text-ink-muted uppercase">
                                    {{ product.category?.name ?? 'Produit' }}
                                </p>
                                <span
                                    class="shrink-0 text-xs font-semibold"
                                    :class="product.in_stock ? 'text-forest-700' : 'text-brass'"
                                >
                                    {{ product.in_stock ? 'En stock' : 'Rupture' }}
                                </span>
                            </div>
                            <h3 class="font-display mt-3 text-xl text-forest-900 sm:text-2xl">
                                {{ product.name }}
                            </h3>
                            <p class="mt-3 line-clamp-3 flex-1 text-sm leading-relaxed text-ink-muted">
                                {{ product.description }}
                            </p>
                            <div
                                class="mt-6 flex flex-col gap-4 border-t border-forest-900/10 pt-4 sm:flex-row sm:items-end sm:justify-between"
                            >
                                <div>
                                    <p class="text-lg font-semibold text-forest-900">{{ product.price }}</p>
                                    <p class="text-xs text-ink-muted">
                                        / {{ product.unit }}
                                        <span v-if="product.in_stock"> · {{ product.stock_quantity }} dispo</span>
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="min-h-11 w-full bg-forest-900 px-4 text-sm font-semibold text-white transition hover:bg-forest-800 disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto sm:min-w-28"
                                    :disabled="!product.in_stock || addingId === product.id"
                                    @click="addToCart(product)"
                                >
                                    <span v-if="addingId === product.id" class="inline-flex items-center gap-2">
                                        <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white" />
                                        Ajout
                                    </span>
                                    <span v-else>
                                        {{ product.in_stock ? 'Ajouter' : 'Indisponible' }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="mt-10 border border-dashed border-forest-900/20 bg-white p-8 text-center sm:mt-12 sm:p-10">
                    <p class="text-ink-muted">Aucun produit dans cette catégorie pour le moment.</p>
                </div>

                <div
                    v-if="products.last_page > 1"
                    class="mt-10 flex flex-col items-center gap-4 sm:mt-12"
                >
                    <p class="text-sm text-ink-muted">
                        {{ products.from }}–{{ products.to }} sur {{ products.total }}
                    </p>
                    <nav class="flex flex-wrap items-center justify-center gap-2" aria-label="Pagination">
                        <button
                            v-for="(link, index) in products.links"
                            :key="`${link.label}-${index}`"
                            type="button"
                            class="min-h-10 min-w-10 px-3 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
                            :class="
                                link.active
                                    ? 'bg-forest-900 text-white'
                                    : 'border border-forest-900/15 text-forest-900 hover:border-forest-900'
                            "
                            :disabled="!link.url"
                            :aria-current="link.active ? 'page' : undefined"
                            @click="goToPage(link.url)"
                        >
                            {{ pageLabel(link.label) }}
                        </button>                    </nav>
                </div>

                <div class="mt-10 flex flex-col gap-3 sm:mt-12 sm:flex-row sm:flex-wrap">
                    <Link
                        href="/arrivages"
                        class="inline-flex min-h-12 items-center justify-center bg-brass px-6 text-sm font-semibold text-forest-950 transition hover:bg-brass-light"
                    >
                        Voir les arrivages
                    </Link>
                    <Link
                        href="/courses"
                        class="inline-flex min-h-12 items-center justify-center border border-forest-900/20 px-6 text-sm font-semibold text-forest-900 transition hover:border-forest-900"
                    >
                        Demander une course
                    </Link>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
