<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import HeroSlider, { type HeroSlide } from '@/Components/Landing/HeroSlider.vue';
import ResponsiveImage from '@/Components/Landing/ResponsiveImage.vue';
import ProductImage from '@/Components/Shop/ProductImage.vue';

type FeaturedProduct = {
    id: number;
    name: string;
    slug: string;
    price: string;
    unit: string;
    in_stock: boolean;
    category: string | null;
    image_url: string;
};

const props = defineProps<{
    nextArrival?: {
        label: string;
        name?: string;
        eta: string;
        items_count?: number;
        status?: string;
    } | null;
    featuredProducts?: FeaturedProduct[];
}>();

const activeSlide = ref(0);
const addingId = ref<number | null>(null);

const slides: HeroSlide[] = [
    {
        id: 'shopper',
        webp: '/images/landing/hero-shopper.webp',
        jpg: '/images/landing/hero-shopper.jpg',
        alt: 'Cliente souriante avec un panier rempli de fruits et légumes frais',
        caption: 'Fraîcheur sélectionnée, livrée au Canada.',
    },
    {
        id: 'friends',
        webp: '/images/landing/slide-friends.webp',
        jpg: '/images/landing/slide-friends.jpg',
        alt: 'Deux amies prenant un selfie dans un supermarché moderne',
        caption: 'Les saveurs d’Abidjan, partagées ici.',
    },
    {
        id: 'fresh',
        webp: '/images/landing/slide-fresh.webp',
        jpg: '/images/landing/slide-fresh.jpg',
        alt: 'Sélection soignée de produits frais au marché',
        caption: 'Du marché ivoirien à votre table.',
    },
];

const activeCaption = computed(() => slides[activeSlide.value]?.caption ?? '');
const featured = computed(() => props.featuredProducts ?? []);

const paths = [
    {
        title: 'Boutique',
        text: 'Produits déjà disponibles, prêts à commander.',
        href: '/boutique',
        cta: 'Explorer',
        image: {
            webp: '/images/landing/section-shopper.webp',
            jpg: '/images/landing/section-shopper.jpg',
            alt: 'Shopping de produits frais',
        },
    },
    {
        title: 'Arrivages',
        text: 'Réservez avant l’arrivée du prochain cargo.',
        href: '/arrivages',
        cta: 'Réserver',
        image: {
            webp: '/images/landing/section-fresh.webp',
            jpg: '/images/landing/section-fresh.jpg',
            alt: 'Produits frais pour arrivage',
        },
    },
    {
        title: 'Courses',
        text: 'Un produit manquant ? On l’achète pour vous.',
        href: '/courses',
        cta: 'Demander',
        image: {
            webp: '/images/landing/section-community.webp',
            jpg: '/images/landing/section-community.jpg',
            alt: 'Clients heureux après leurs courses',
        },
    },
] as const;

function addToCart(product: FeaturedProduct) {
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
</script>

<template>
    <AppLayout>
        <Head title="Accueil">
            <meta
                head-key="description"
                name="description"
                content="Ôhéfê Market — produits ivoiriens authentiques au Canada. Boutique, réservations sur arrivage et courses personnalisées."
            />
        </Head>

        <!-- Hero -->
        <section class="relative isolate min-h-[72svh] overflow-hidden bg-forest-950 md:min-h-[68svh]">
            <HeroSlider v-model="activeSlide" :slides="slides" />

            <div
                class="relative z-20 mx-auto flex min-h-[72svh] max-w-3xl flex-col items-center justify-center px-5 pb-20 pt-28 text-center md:min-h-[68svh] md:px-8 md:pb-24"
            >
                <p
                    class="animate-fade-up text-sm font-medium tracking-[0.28em] text-brass-light uppercase"
                >
                    Côte d’Ivoire → Canada
                </p>

                <h1
                    class="animate-fade-up animation-delay-150 font-display mt-5 text-[clamp(2.75rem,7.5vw,5.5rem)] leading-[0.92] text-white"
                >
                    Ôhéfê Market
                </h1>

                <p
                    class="animate-fade-up animation-delay-300 mt-6 max-w-lg text-lg leading-relaxed text-white/80 md:text-xl"
                >
                    {{ activeCaption }}
                </p>

                <div
                    class="animate-fade-up animation-delay-450 mt-10 flex flex-wrap items-center justify-center gap-3"
                >
                    <Link
                        href="/boutique"
                        class="inline-flex min-h-12 items-center justify-center bg-brass px-8 text-sm font-semibold text-forest-950 transition hover:bg-brass-light focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    >
                        Voir la boutique
                    </Link>
                    <Link
                        href="/arrivages"
                        class="inline-flex min-h-12 items-center justify-center border border-white/40 px-8 text-sm font-semibold text-white transition hover:border-white hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    >
                        Prochain arrivage
                    </Link>
                </div>
            </div>
        </section>

        <!-- Promise strip: one job, no stats clutter -->
        <section class="border-b border-forest-900/10 bg-white">
            <div
                class="mx-auto grid max-w-6xl gap-8 px-5 py-10 md:grid-cols-3 md:gap-6 md:px-8 md:py-12"
            >
                <p class="text-center text-sm leading-relaxed text-ink-muted md:text-left">
                    <span class="block font-display text-xl text-forest-900">Authentique</span>
                    Produits sourcés en Côte d’Ivoire, sélectionnés avec soin.
                </p>
                <p class="text-center text-sm leading-relaxed text-ink-muted md:text-left">
                    <span class="block font-display text-xl text-forest-900">Flexible</span>
                    Stock immédiat, réservation cargo ou course sur mesure.
                </p>
                <p class="text-center text-sm leading-relaxed text-ink-muted md:text-left">
                    <span class="block font-display text-xl text-forest-900">Proche</span>
                    Une équipe qui comprend la diaspora et ses envies.
                </p>
            </div>
        </section>

        <!-- Featured products -->
        <section v-if="featured.length" class="bg-stone-soft px-5 py-18 md:px-8 md:py-24">
            <div class="mx-auto max-w-6xl">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-sm tracking-[0.2em] text-brass uppercase">Sélection</p>
                        <h2 class="font-display mt-2 text-3xl text-forest-900 md:text-4xl">
                            Coups de cœur du moment
                        </h2>
                    </div>
                    <Link
                        href="/boutique"
                        class="text-sm font-semibold text-forest-700 transition hover:text-forest-900"
                    >
                        Tout le catalogue →
                    </Link>
                </div>

                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="product in featured"
                        :key="product.id"
                        class="group flex flex-col overflow-hidden border border-forest-900/8 bg-white transition duration-300 hover:border-forest-900/20"
                    >
                        <div class="aspect-[4/3] overflow-hidden bg-stone-soft">
                            <ProductImage
                                :src="product.image_url"
                                :alt="product.name"
                                img-class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            />
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            <p class="text-xs tracking-wide text-ink-muted uppercase">
                                {{ product.category }}
                            </p>
                            <h3 class="font-display mt-3 text-xl text-forest-900 transition group-hover:text-forest-800">
                                {{ product.name }}
                            </h3>
                            <p class="mt-auto pt-6 text-sm font-semibold text-forest-900">
                                {{ product.price }}
                                <span class="font-normal text-ink-muted">/ {{ product.unit }}</span>
                            </p>
                            <button
                                type="button"
                                class="mt-4 min-h-10 w-full border border-forest-900/15 text-sm font-semibold text-forest-900 transition hover:bg-forest-900 hover:text-white disabled:opacity-40"
                                :disabled="!product.in_stock || addingId === product.id"
                                @click="addToCart(product)"
                            >
                                {{
                                    !product.in_stock
                                        ? 'Rupture'
                                        : addingId === product.id
                                          ? 'Ajout…'
                                          : 'Ajouter au panier'
                                }}
                            </button>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Paths: editorial, not card grid -->
        <section id="parcours" class="bg-forest-950 px-5 py-18 text-white md:px-8 md:py-24">
            <div class="mx-auto max-w-6xl">
                <div class="max-w-2xl">
                    <p class="text-sm tracking-[0.2em] text-brass-light uppercase">Parcours</p>
                    <h2 class="font-display mt-3 text-3xl md:text-4xl">
                        Trois façons de commander
                    </h2>
                    <p class="mt-4 text-lg text-white/65">
                        Choisissez selon votre besoin — stock, anticipé, ou sur mesure.
                    </p>
                </div>

                <div class="mt-12 grid gap-4 md:grid-cols-3">
                    <Link
                        v-for="(path, index) in paths"
                        :key="path.title"
                        :href="path.href"
                        class="group relative block min-h-[22rem] overflow-hidden"
                    >
                        <ResponsiveImage
                            :webp="path.image.webp"
                            :jpg="path.image.jpg"
                            :alt="path.image.alt"
                            img-class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105"
                        />
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-forest-950 via-forest-950/45 to-transparent"
                        />
                        <div class="absolute inset-x-0 bottom-0 p-6 md:p-7">
                            <span class="font-display text-4xl text-white/25">
                                {{ String(index + 1).padStart(2, '0') }}
                            </span>
                            <h3 class="font-display mt-2 text-2xl text-white">{{ path.title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-white/70">{{ path.text }}</p>
                            <span
                                class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-brass-light transition group-hover:gap-3"
                            >
                                {{ path.cta }}
                                <span aria-hidden="true">→</span>
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Next arrival highlight -->
        <section id="arrivages" class="bg-stone-soft">
            <div class="mx-auto grid max-w-6xl md:grid-cols-2">
                <div class="relative min-h-[20rem] md:min-h-[28rem]">
                    <ResponsiveImage
                        webp="/images/landing/section-fresh.webp"
                        jpg="/images/landing/section-fresh.jpg"
                        alt="Produits frais sélectionnés pour un arrivage"
                        img-class="absolute inset-0 h-full w-full object-cover"
                    />
                </div>

                <div class="flex flex-col justify-center px-5 py-14 md:px-12 md:py-20">
                    <p class="text-sm tracking-[0.2em] text-brass uppercase">Arrivages</p>
                    <h2 class="font-display mt-3 text-3xl text-forest-900 md:text-4xl">
                        Réservez avant l’arrivée
                    </h2>
                    <p class="mt-4 max-w-md text-lg leading-relaxed text-ink-muted">
                        Anticipez : bloquez vos quantités sur le cargo, on vous prévient dès que
                        c’est disponible au Canada.
                    </p>

                    <div
                        v-if="nextArrival"
                        class="mt-8 border border-forest-900/10 bg-white p-6"
                    >
                        <p class="text-xs tracking-[0.18em] text-ink-muted uppercase">
                            {{ nextArrival.status ?? 'Prochain cargo' }}
                        </p>
                        <p class="font-display mt-2 text-2xl text-forest-900">
                            {{ nextArrival.name ?? nextArrival.label }}
                        </p>
                        <p class="mt-2 text-sm text-ink-muted">
                            {{ nextArrival.label }}
                            <span class="text-forest-900"> · ETA {{ nextArrival.eta }}</span>
                            <span v-if="nextArrival.items_count">
                                · {{ nextArrival.items_count }} produits
                            </span>
                        </p>
                    </div>

                    <Link
                        href="/arrivages"
                        class="mt-8 inline-flex min-h-12 w-fit items-center bg-forest-900 px-7 text-sm font-semibold text-white transition hover:bg-forest-800"
                    >
                        Voir les arrivages
                    </Link>
                </div>
            </div>
        </section>

        <!-- Custom course -->
        <section id="courses" class="bg-white">
            <div class="mx-auto grid max-w-6xl md:grid-cols-2">
                <div class="order-2 flex flex-col justify-center px-5 py-14 md:order-1 md:px-12 md:py-20">
                    <p class="text-sm tracking-[0.2em] text-brass uppercase">Courses</p>
                    <h2 class="font-display mt-3 text-3xl text-forest-900 md:text-4xl">
                        On cherche pour vous
                    </h2>
                    <p class="mt-4 max-w-md text-lg leading-relaxed text-ink-muted">
                        Décrivez le produit. Nous établissons un devis. Vous validez. On importe.
                    </p>
                    <Link
                        href="/courses"
                        class="mt-8 inline-flex min-h-12 w-fit items-center border border-forest-900 bg-transparent px-7 text-sm font-semibold text-forest-900 transition hover:bg-forest-900 hover:text-white"
                    >
                        Faire une demande
                    </Link>
                </div>
                <div class="relative order-1 min-h-[20rem] md:order-2 md:min-h-[28rem]">
                    <ResponsiveImage
                        webp="/images/landing/section-community.webp"
                        jpg="/images/landing/section-community.jpg"
                        alt="Amies heureuses lors de leurs courses"
                        img-class="absolute inset-0 h-full w-full object-cover"
                    />
                </div>
            </div>
        </section>

        <!-- Closing -->
        <section class="relative isolate overflow-hidden bg-forest-950">
            <ResponsiveImage
                webp="/images/landing/hero-shopper.webp"
                jpg="/images/landing/hero-shopper.jpg"
                alt=""
                img-class="absolute inset-0 h-full w-full object-cover opacity-40"
            />
            <div class="absolute inset-0 bg-forest-950/60" />
            <div
                class="relative mx-auto flex max-w-3xl flex-col items-center px-5 py-24 text-center md:py-32"
            >
                <h2 class="font-display text-3xl text-white md:text-5xl">
                    Le goût d’Abidjan, chez vous.
                </h2>
                <p class="mt-5 max-w-md text-white/70">
                    Boutique, arrivages et courses — tout commence ici.
                </p>
                <div class="mt-10 flex flex-wrap justify-center gap-3">
                    <Link
                        href="/boutique"
                        class="inline-flex min-h-12 items-center bg-brass px-8 text-sm font-semibold text-forest-950 transition hover:bg-brass-light"
                    >
                        Commencer
                    </Link>
                    <Link
                        href="/courses"
                        class="inline-flex min-h-12 items-center border border-white/35 px-8 text-sm font-semibold text-white transition hover:bg-white/10"
                    >
                        Demander une course
                    </Link>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
