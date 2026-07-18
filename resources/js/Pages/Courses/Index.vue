<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHero from '@/Components/Landing/PageHero.vue';

type Example = {
    id: number;
    title: string;
    description: string;
    quantity: number;
    budget: string | null;
    status_label: string;
    quote: {
        amount: string;
        status_label: string;
        message: string | null;
    } | null;
};

defineProps<{
    examples: Example[];
}>();

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

        <section class="bg-stone-soft px-5 py-14 md:px-8 md:py-20">
            <div class="mx-auto max-w-6xl">
                <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
                    <form class="border border-forest-900/10 bg-white p-6 md:p-8" @submit.prevent="submit">
                        <h2 class="font-display text-3xl text-forest-900">Nouvelle demande</h2>
                        <p class="mt-2 text-sm text-ink-muted">
                            Remplissez le formulaire — nous vous répondons avec un devis.
                        </p>

                        <div class="mt-8 grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-medium text-ink-muted">Nom</label>
                                <input
                                    v-model="form.guest_name"
                                    type="text"
                                    required
                                    class="mt-1 h-11 w-full border border-forest-900/15 px-3 text-sm"
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
                                    class="mt-1 h-11 w-full border border-forest-900/15 px-3 text-sm"
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
                                class="mt-1 h-11 w-full border border-forest-900/15 px-3 text-sm"
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
                                class="mt-1 w-full border border-forest-900/15 px-3 py-2 text-sm"
                                placeholder="Quantité souhaitée, marque, format, contraintes…"
                            />
                            <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-medium text-ink-muted">Quantité</label>
                                <input
                                    v-model.number="form.quantity"
                                    type="number"
                                    min="1"
                                    required
                                    class="mt-1 h-11 w-full border border-forest-900/15 px-3 text-sm"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-ink-muted">Budget max (CAD)</label>
                                <input
                                    v-model.number="form.budget"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="mt-1 h-11 w-full border border-forest-900/15 px-3 text-sm"
                                    placeholder="Optionnel"
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="mt-8 inline-flex min-h-12 items-center bg-forest-900 px-6 text-sm font-semibold text-white transition hover:bg-forest-800 disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            Envoyer la demande
                        </button>
                    </form>

                    <div>
                        <h2 class="font-display text-3xl text-forest-900">Comment ça marche</h2>
                        <ol class="mt-6 space-y-5">
                            <li class="flex gap-4">
                                <span class="font-display text-2xl text-brass">01</span>
                                <p class="text-ink-muted">Vous décrivez le produit.</p>
                            </li>
                            <li class="flex gap-4">
                                <span class="font-display text-2xl text-brass">02</span>
                                <p class="text-ink-muted">Nous établissons un devis clair.</p>
                            </li>
                            <li class="flex gap-4">
                                <span class="font-display text-2xl text-brass">03</span>
                                <p class="text-ink-muted">Vous validez, on s’occupe du reste.</p>
                            </li>
                        </ol>

                        <div class="mt-10 space-y-4">
                            <h3 class="font-medium text-forest-900">Dernières demandes (démo)</h3>
                            <article
                                v-for="example in examples"
                                :key="example.id"
                                class="border border-forest-900/10 bg-white p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-medium text-forest-900">{{ example.title }}</p>
                                    <span class="text-xs font-semibold text-forest-700">
                                        {{ example.status_label }}
                                    </span>
                                </div>
                                <p
                                    v-if="example.quote"
                                    class="mt-2 text-sm text-ink-muted"
                                >
                                    Devis {{ example.quote.amount }}
                                </p>
                            </article>
                        </div>

                        <Link href="/boutique" class="mt-8 inline-flex text-sm font-semibold text-forest-700">
                            Retour boutique →
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
