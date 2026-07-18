<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const year = new Date().getFullYear();

const appName = computed(() => (page.props.app as { name?: string } | undefined)?.name ?? 'Ôhéfê Market');
</script>

<template>
    <footer class="border-t border-white/10 bg-forest-950 text-stone">
        <div class="mx-auto max-w-6xl px-5 py-16 md:px-8 md:py-20">
            <div class="grid gap-12 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
                <div>
                    <Link href="/" class="font-display text-2xl text-white transition hover:text-brass-light">
                        {{ appName }}
                    </Link>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/60">
                        Produits ivoiriens authentiques, importés et livrés au Canada — boutique,
                        arrivages et courses personnalisées.
                    </p>
                    <a
                        href="mailto:hello@ohefe.market"
                        class="mt-6 inline-flex text-sm font-medium text-brass-light transition hover:text-white"
                    >
                        hello@ohefe.market
                    </a>
                </div>

                <div>
                    <p class="text-sm font-semibold tracking-wide text-white">Explorer</p>
                    <ul class="mt-4 space-y-3 text-sm text-white/55">
                        <li>
                            <Link href="/panier" class="transition hover:text-white">Panier</Link>
                        </li>
                        <li>
                            <Link href="/boutique" class="transition hover:text-white">Boutique</Link>
                        </li>
                        <li>
                            <Link href="/arrivages" class="transition hover:text-white">Arrivages</Link>
                        </li>
                        <li>
                            <Link href="/courses" class="transition hover:text-white">Courses</Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <p class="text-sm font-semibold tracking-wide text-white">Parcours</p>
                    <ul class="mt-4 space-y-3 text-sm text-white/55">
                        <li>
                            <Link href="/#parcours" class="transition hover:text-white">
                                Comment commander
                            </Link>
                        </li>
                        <li>
                            <Link href="/arrivages" class="transition hover:text-white">
                                Réserver un cargo
                            </Link>
                        </li>
                        <li>
                            <Link href="/courses" class="transition hover:text-white">
                                Demander un devis
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <p class="text-sm font-semibold tracking-wide text-white">Contact</p>
                    <ul class="mt-4 space-y-3 text-sm text-white/55">
                        <li>Canada · Diaspora & familles</li>
                        <li>Côte d’Ivoire · Approvisionnement</li>
                        <li>
                            <a href="mailto:hello@ohefe.market" class="transition hover:text-white">
                                Écrire à l’équipe
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="mt-14 flex flex-col gap-4 border-t border-white/10 pt-8 text-sm text-white/40 md:flex-row md:items-center md:justify-between"
            >
                <p>© {{ year }} {{ appName }}. Tous droits réservés.</p>
                <p class="tracking-wide">Côte d’Ivoire → Canada</p>
            </div>
        </div>
    </footer>
</template>
