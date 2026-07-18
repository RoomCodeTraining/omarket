<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHero from '@/Components/Landing/PageHero.vue';

const form = useForm({
    guest_name: '',
    guest_email: '',
    title: '',
    description: '',
    quantity: 1,
    budget: null as number | null,
});

function submit() {
    form.post('/courses', {
        preserveScroll: true,
        onSuccess: () => form.reset('title', 'description', 'quantity', 'budget'),
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Courses personnalisées">
            <meta
                head-key="description"
                name="description"
                content="Demandez une course personnalisée en Côte d’Ivoire avec Ôhéfê Market — devis clair, puis commande."
            />
        </Head>

        <PageHero
            eyebrow="Courses"
            title="On achète pour vous en Côte d’Ivoire"
            description="Un produit introuvable dans la boutique ou les arrivages ? Décrivez-le, recevez un devis, validez."
            webp="/images/landing/section-community.webp"
            jpg="/images/landing/section-community.jpg"
            alt="Clients heureux après leurs courses"
        />

        <section class="bg-stone-soft px-4 py-12 sm:px-5 sm:py-14 md:px-8 md:py-20">
            <div class="mx-auto max-w-6xl">
                <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:gap-12">
                    <form
                        class="border border-forest-900/10 bg-white p-5 sm:p-6 md:p-8"
                        @submit.prevent="submit"
                    >
                        <h2 class="font-display text-2xl text-forest-900 sm:text-3xl">Nouvelle demande</h2>
                        <p class="mt-2 text-sm text-ink-muted">
                            Remplissez le formulaire — nous vous répondons avec un devis.
                        </p>

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-xs font-medium text-ink-muted">Nom</label>
                                <input
                                    v-model="form.guest_name"
                                    type="text"
                                    required
                                    class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                />
                                <p v-if="form.errors.guest_name" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.guest_name }}
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-ink-muted">E-mail</label>
                                <input
                                    v-model="form.guest_email"
                                    type="email"
                                    required
                                    class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                />
                                <p v-if="form.errors.guest_email" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.guest_email }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="text-xs font-medium text-ink-muted">Produit recherché</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="Ex. Capitaine fumé"
                                class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div class="mt-4">
                            <label class="text-xs font-medium text-ink-muted">Détails</label>
                            <textarea
                                v-model="form.description"
                                required
                                rows="4"
                                class="mt-1 w-full border border-forest-900/15 px-3 py-2.5"
                                placeholder="Quantité souhaitée, marque, format, contraintes…"
                            />
                            <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-xs font-medium text-ink-muted">Quantité</label>
                                <input
                                    v-model.number="form.quantity"
                                    type="number"
                                    min="1"
                                    required
                                    class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-ink-muted">Budget max (CAD)</label>
                                <input
                                    v-model.number="form.budget"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                    placeholder="Optionnel"
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="mt-8 inline-flex min-h-12 w-full items-center justify-center bg-forest-900 px-6 text-sm font-semibold text-white transition hover:bg-forest-800 disabled:opacity-50 sm:w-auto"
                            :disabled="form.processing"
                        >
                            Envoyer la demande
                        </button>
                    </form>

                    <div>
                        <h2 class="font-display text-2xl text-forest-900 sm:text-3xl">Comment ça marche</h2>
                        <ol class="mt-6 space-y-5">
                            <li class="flex gap-4">
                                <span class="font-display text-2xl text-brass">01</span>
                                <p class="pt-1 text-ink-muted">Vous décrivez le produit.</p>
                            </li>
                            <li class="flex gap-4">
                                <span class="font-display text-2xl text-brass">02</span>
                                <p class="pt-1 text-ink-muted">Nous établissons un devis clair.</p>
                            </li>
                            <li class="flex gap-4">
                                <span class="font-display text-2xl text-brass">03</span>
                                <p class="pt-1 text-ink-muted">Vous validez, on s’occupe du reste.</p>
                            </li>
                        </ol>

                        <Link
                            href="/boutique"
                            class="mt-8 inline-flex min-h-11 items-center text-sm font-semibold text-forest-700"
                        >
                            Retour boutique →
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
