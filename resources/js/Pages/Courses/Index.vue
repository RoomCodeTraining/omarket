<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHero from '@/Components/Landing/PageHero.vue';

const props = defineProps<{
    defaults: {
        guest_name: string;
        guest_email: string;
    };
    is_authenticated: boolean;
}>();

const page = usePage();
const authUser = computed(
    () =>
        (page.props.auth as { user?: { name: string; email: string } | null } | undefined)?.user ??
        null,
);

const form = useForm({
    guest_name: props.defaults.guest_name || authUser.value?.name || '',
    guest_email: props.defaults.guest_email || authUser.value?.email || '',
    title: '',
    description: '',
    quantity: 1,
    budget: null as number | null,
    has_supplier: false,
    supplier_name: '',
    supplier_contact: '',
});

const isLoggedIn = computed(() => props.is_authenticated || authUser.value !== null);

function submit() {
    form.post('/courses', {
        preserveScroll: true,
        onSuccess: () =>
            form.reset(
                'title',
                'description',
                'quantity',
                'budget',
                'has_supplier',
                'supplier_name',
                'supplier_contact',
            ),
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

                        <div
                            v-if="isLoggedIn"
                            class="mt-6 border border-forest-900/10 bg-stone-soft px-4 py-3 text-sm"
                        >
                            <p class="font-medium text-forest-900">
                                {{ form.guest_name || authUser?.name }}
                            </p>
                            <p class="text-ink-muted">{{ form.guest_email || authUser?.email }}</p>
                            <p class="mt-2 text-xs text-ink-muted">
                                Demande liée à votre compte.
                                <Link href="/compte" class="font-medium text-forest-800 underline">
                                    Voir mes demandes
                                </Link>
                            </p>
                        </div>

                        <div v-else class="mt-8 grid gap-4 sm:grid-cols-2">
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
                            <p class="sm:col-span-2 text-xs text-ink-muted">
                                Déjà un compte ?
                                <Link href="/connexion" class="font-medium text-forest-800 underline">
                                    Connectez-vous
                                </Link>
                                pour retrouver vos demandes.
                            </p>
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

                        <fieldset class="mt-6 space-y-3 border border-forest-900/10 p-4">
                            <legend class="px-1 text-xs font-medium tracking-wide text-ink-muted uppercase">
                                Fournisseur
                            </legend>
                            <p class="text-sm text-ink-muted">
                                Avez-vous déjà un fournisseur en Côte d’Ivoire pour ce produit ?
                            </p>
                            <div class="flex flex-col gap-2 sm:flex-row sm:gap-6">
                                <label class="flex items-center gap-2 text-sm text-forest-900">
                                    <input
                                        v-model="form.has_supplier"
                                        type="radio"
                                        :value="true"
                                        class="accent-forest-900"
                                    />
                                    Oui, j’ai un fournisseur
                                </label>
                                <label class="flex items-center gap-2 text-sm text-forest-900">
                                    <input
                                        v-model="form.has_supplier"
                                        type="radio"
                                        :value="false"
                                        class="accent-forest-900"
                                    />
                                    Non, je n’en ai pas
                                </label>
                            </div>
                            <p v-if="form.errors.has_supplier" class="text-xs text-red-600">
                                {{ form.errors.has_supplier }}
                            </p>
                            <div v-if="form.has_supplier" class="grid gap-3 pt-1">
                                <div>
                                    <label class="text-xs font-medium text-ink-muted">Nom du fournisseur</label>
                                    <input
                                        v-model="form.supplier_name"
                                        type="text"
                                        required
                                        class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                        placeholder="Ex. Marché de Treichville"
                                    />
                                    <p v-if="form.errors.supplier_name" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.supplier_name }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-ink-muted">
                                        Contact / détails (optionnel)
                                    </label>
                                    <input
                                        v-model="form.supplier_contact"
                                        type="text"
                                        class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                        placeholder="Téléphone, quartier, WhatsApp…"
                                    />
                                </div>
                            </div>
                        </fieldset>

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
                                <p class="pt-1 text-ink-muted">
                                    Vous décrivez le produit et précisez si vous avez un fournisseur.
                                </p>
                            </li>
                            <li class="flex gap-4">
                                <span class="font-display text-2xl text-brass">02</span>
                                <p class="pt-1 text-ink-muted">
                                    Nous validons la demande, puis envoyons un devis clair.
                                </p>
                            </li>
                            <li class="flex gap-4">
                                <span class="font-display text-2xl text-brass">03</span>
                                <p class="pt-1 text-ink-muted">
                                    Après validation, la course part sur un cargo — vous êtes informé.
                                </p>
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
