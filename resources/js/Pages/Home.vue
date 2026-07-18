<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import HeroSlider, { type HeroSlide } from '@/Components/Landing/HeroSlider.vue';
import ResponsiveImage from '@/Components/Landing/ResponsiveImage.vue';

defineProps<{
    nextArrival?: {
        label: string;
        eta: string;
    } | null;
}>();

const activeSlide = ref(0);

const slides: HeroSlide[] = [
    {
        id: 'shopper',
        webp: '/images/landing/hero-shopper.webp',
        jpg: '/images/landing/hero-shopper.jpg',
        alt: 'Cliente souriante avec un panier rempli de fruits et légumes frais',
        caption: 'Des courses fraîches, simples et agréables.',
    },
    {
        id: 'friends',
        webp: '/images/landing/slide-friends.webp',
        jpg: '/images/landing/slide-friends.jpg',
        alt: 'Deux amies prenant un selfie dans un supermarché moderne',
        caption: 'Le plaisir de retrouver les saveurs d’ici, ensemble.',
    },
    {
        id: 'fresh',
        webp: '/images/landing/slide-fresh.webp',
        jpg: '/images/landing/slide-fresh.jpg',
        alt: 'Sélection soignée de produits frais au marché',
        caption: 'Une sélection soignée, du marché jusqu’à votre table.',
    },
];

const activeCaption = computed(() => slides[activeSlide.value]?.caption ?? '');

const steps = [
    {
        id: 'boutique',
        title: 'Boutique',
        text: 'Commandez les produits déjà disponibles, prêts à partir.',
        href: '/boutique',
        cta: 'Voir le catalogue',
        image: {
            webp: '/images/landing/section-shopper.webp',
            jpg: '/images/landing/section-shopper.jpg',
            alt: 'Shopping de produits frais en magasin',
        },
    },
    {
        id: 'arrivage',
        title: 'Arrivages',
        text: 'Réservez votre part avant l’arrivée du cargo au Canada.',
        href: '/arrivages',
        cta: 'Réserver un arrivage',
        image: {
            webp: '/images/landing/section-fresh.webp',
            jpg: '/images/landing/section-fresh.jpg',
            alt: 'Produits frais préparés pour l’arrivage',
        },
    },
    {
        id: 'course',
        title: 'Courses',
        text: 'Un produit manquant ? On l’achète pour vous en Côte d’Ivoire.',
        href: '/courses',
        cta: 'Demander une course',
        image: {
            webp: '/images/landing/section-community.webp',
            jpg: '/images/landing/section-community.jpg',
            alt: 'Clients heureux après leurs courses',
        },
    },
] as const;
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

        <section class="relative isolate min-h-[78svh] overflow-hidden bg-forest-950 md:min-h-[72svh]">
            <HeroSlider v-model="activeSlide" :slides="slides" />

            <div
                class="relative z-20 mx-auto flex min-h-[78svh] max-w-3xl flex-col items-center justify-center px-5 pb-16 pt-24 text-center md:min-h-[72svh] md:px-8 md:pb-20"
            >
                <p
                    class="animate-fade-up text-sm font-medium tracking-[0.22em] text-brass-light uppercase"
                >
                    Côte d’Ivoire → Canada
                </p>

                <h1
                    class="animate-fade-up animation-delay-150 font-display mt-5 text-[clamp(2.5rem,7vw,5rem)] leading-[0.95] text-white"
                >
                    Ôhéfê Market
                </h1>

                <p
                    class="animate-fade-up animation-delay-300 mt-6 max-w-xl text-lg leading-relaxed text-white/80 md:text-xl"
                >
                    Le goût authentique ivoirien, livré chez vous — en stock, sur arrivage, ou sur
                    demande.
                </p>

                <p
                    :key="activeCaption"
                    class="animate-fade-in mt-4 max-w-md text-sm font-medium tracking-wide text-brass-light/90"
                >
                    {{ activeCaption }}
                </p>

                <div class="animate-fade-up animation-delay-450 mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Link
                        href="/boutique"
                        class="inline-flex min-h-12 items-center justify-center bg-brass px-7 text-sm font-semibold text-forest-950 transition hover:bg-brass-light focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    >
                        Découvrir la boutique
                    </Link>
                    <Link
                        href="/arrivages"
                        class="inline-flex min-h-12 items-center justify-center border border-white/35 px-7 text-sm font-semibold text-white transition hover:border-white hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    >
                        Voir le prochain arrivage
                    </Link>
                </div>
            </div>
        </section>

        <!-- Parcours -->
        <section id="parcours" class="bg-stone-soft px-5 py-20 md:px-8 md:py-28">
            <div class="mx-auto max-w-6xl">
                <div class="max-w-2xl">
                    <h2 class="font-display text-3xl text-forest-900 md:text-4xl">
                        Trois façons d’obtenir vos produits
                    </h2>
                    <p class="mt-4 text-lg text-ink-muted">
                        Choisissez le parcours qui vous convient — simple, clair, sans surprise.
                    </p>
                </div>

                <ol class="mt-14 grid gap-8 md:grid-cols-3">
                    <li
                        v-for="(step, index) in steps"
                        :key="step.id"
                        class="group flex flex-col overflow-hidden bg-white shadow-[0_1px_0_rgba(15,46,36,0.06)] transition duration-500 hover:-translate-y-1 hover:shadow-[0_24px_48px_-24px_rgba(15,46,36,0.35)]"
                    >
                        <div class="aspect-[4/3] overflow-hidden">
                            <ResponsiveImage
                                :webp="step.image.webp"
                                :jpg="step.image.jpg"
                                :alt="step.image.alt"
                                img-class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                            />
                        </div>
                        <div class="flex flex-1 flex-col border-t border-forest-900/10 p-6">
                            <span
                                class="font-display text-4xl text-forest-900/15 transition group-hover:text-brass"
                                aria-hidden="true"
                            >
                                {{ String(index + 1).padStart(2, '0') }}
                            </span>
                            <h3 class="mt-2 font-display text-2xl text-forest-900">
                                {{ step.title }}
                            </h3>
                            <p class="mt-3 flex-1 text-base leading-relaxed text-ink-muted">
                                {{ step.text }}
                            </p>
                            <Link
                                :href="step.href"
                                class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-forest-700 transition hover:text-forest-900"
                            >
                                {{ step.cta }}
                                <span aria-hidden="true" class="transition group-hover:translate-x-1">→</span>
                            </Link>
                        </div>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Arrivages -->
        <section id="arrivages" class="relative overflow-hidden bg-forest-900 text-white">
            <div class="grid md:grid-cols-2">
                <div class="relative min-h-[22rem] md:min-h-full">
                    <ResponsiveImage
                        webp="/images/landing/section-fresh.webp"
                        jpg="/images/landing/section-fresh.jpg"
                        alt="Produits frais sélectionnés pour un arrivage"
                        img-class="absolute inset-0 h-full w-full object-cover"
                    />
                    <div class="absolute inset-0 bg-forest-950/25" />
                </div>

                <div class="relative flex flex-col justify-center px-5 py-16 md:px-12 md:py-24">
                    <div
                        class="pointer-events-none absolute -right-16 top-10 h-56 w-56 rounded-full bg-brass/15 blur-3xl"
                        aria-hidden="true"
                    />
                    <h2 class="font-display relative text-3xl md:text-4xl">
                        Réservez avant l’arrivée
                    </h2>
                    <p class="relative mt-4 max-w-xl text-lg leading-relaxed text-white/70">
                        Chaque cargo a une date estimée. Vous réservez votre quantité ; on vous
                        prévient dès que c’est disponible au Canada.
                    </p>

                    <div class="relative mt-8 border border-white/15 bg-white/5 p-6 backdrop-blur-sm">
                        <p class="text-sm tracking-[0.18em] text-brass-light uppercase">
                            Prochain cargo
                        </p>
                        <p class="font-display mt-3 text-2xl text-white md:text-3xl">
                            {{ nextArrival?.label ?? 'Abidjan → Montréal' }}
                        </p>
                        <p class="mt-2 text-white/65">
                            Arrivée estimée :
                            <span class="font-medium text-white">
                                {{ nextArrival?.eta ?? 'Bientôt annoncée' }}
                            </span>
                        </p>
                    </div>

                    <Link
                        href="/arrivages"
                        class="relative mt-8 inline-flex min-h-12 w-fit items-center bg-white px-7 text-sm font-semibold text-forest-900 transition hover:bg-stone"
                    >
                        Explorer les arrivages
                    </Link>
                </div>
            </div>
        </section>

        <!-- Courses / communauté -->
        <section id="courses" class="bg-stone-soft">
            <div class="grid md:grid-cols-2">
                <div class="order-2 flex flex-col justify-center px-5 py-16 md:order-1 md:px-12 md:py-24">
                    <h2 class="font-display text-3xl text-forest-900 md:text-4xl">
                        Une course personnalisée
                    </h2>
                    <p class="mt-4 max-w-xl text-lg leading-relaxed text-ink-muted">
                        Décrivez le produit recherché. Nous établissons un devis, vous validez, puis
                        on s’occupe de l’achat et de l’import.
                    </p>
                    <Link
                        href="/courses"
                        class="mt-8 inline-flex min-h-12 w-fit items-center justify-center bg-forest-900 px-7 text-sm font-semibold text-white transition hover:bg-forest-800"
                    >
                        Faire une demande
                    </Link>
                </div>

                <div class="relative order-1 min-h-[22rem] md:order-2 md:min-h-[28rem]">
                    <ResponsiveImage
                        webp="/images/landing/section-community.webp"
                        jpg="/images/landing/section-community.jpg"
                        alt="Amies heureuses lors de leurs courses"
                        img-class="absolute inset-0 h-full w-full object-cover"
                    />
                </div>
            </div>
        </section>

        <!-- Bandeau final immersif -->
        <section class="relative isolate min-h-[50vh] overflow-hidden bg-forest-950">
            <ResponsiveImage
                webp="/images/landing/hero-shopper.webp"
                jpg="/images/landing/hero-shopper.jpg"
                alt=""
                img-class="absolute inset-0 h-full w-full object-cover opacity-55"
            />
            <div class="absolute inset-0 bg-forest-950/55" />
            <div
                class="relative mx-auto flex min-h-[50vh] max-w-6xl flex-col items-start justify-center px-5 py-20 md:px-8"
            >
                <h2 class="font-display max-w-2xl text-3xl text-white md:text-5xl">
                    Prêt à retrouver le vrai goût d’Abidjan ?
                </h2>
                <Link
                    href="/boutique"
                    class="mt-8 inline-flex min-h-12 items-center bg-brass px-7 text-sm font-semibold text-forest-950 transition hover:bg-brass-light"
                >
                    Commencer maintenant
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
