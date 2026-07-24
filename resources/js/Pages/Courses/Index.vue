<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHero from '@/Components/Landing/PageHero.vue';

type ProductLine = {
    label: string;
    quantity: number;
    budget: number | null;
};

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

function emptyLine(): ProductLine {
    return { label: '', quantity: 1, budget: null };
}

const form = useForm({
    guest_name: props.defaults.guest_name || authUser.value?.name || '',
    guest_email: props.defaults.guest_email || authUser.value?.email || '',
    items: [emptyLine()] as ProductLine[],
    description: '',
    has_supplier: false,
    supplier_name: '',
    supplier_contact: '',
});

const isLoggedIn = computed(() => props.is_authenticated || authUser.value !== null);

function addLine() {
    if (form.items.length >= 20) {
        return;
    }

    form.items.push(emptyLine());
}

function removeLine(index: number) {
    if (form.items.length <= 1) {
        return;
    }

    form.items.splice(index, 1);
}

function itemError(index: number, field: string): string | undefined {
    return form.errors[`items.${index}.${field}`];
}

function submit() {
    form.post('/courses', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('description', 'has_supplier', 'supplier_name', 'supplier_contact');
            form.items = [emptyLine()];
        },
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
            description="Un ou plusieurs produits introuvables ? Indiquez libellé, quantité et budget, recevez un devis."
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
                            Ajoutez chaque produit avec son libellé, sa quantité et un budget indicatif.
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

                        <div class="mt-8 space-y-4">
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-forest-900">Produits</h3>
                                    <p class="mt-0.5 text-xs text-ink-muted">1 à 20 produits par demande.</p>
                                </div>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-forest-800 underline disabled:opacity-40"
                                    :disabled="form.items.length >= 20"
                                    @click="addLine"
                                >
                                    + Ajouter un produit
                                </button>
                            </div>

                            <p v-if="form.errors.items" class="text-xs text-red-600">{{ form.errors.items }}</p>

                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="border border-forest-900/10 p-4"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs font-medium tracking-wide text-ink-muted uppercase">
                                        Produit {{ index + 1 }}
                                    </p>
                                    <button
                                        v-if="form.items.length > 1"
                                        type="button"
                                        class="text-xs font-medium text-red-700 underline"
                                        @click="removeLine(index)"
                                    >
                                        Retirer
                                    </button>
                                </div>

                                <div class="mt-3">
                                    <label class="text-xs font-medium text-ink-muted">Libellé</label>
                                    <input
                                        v-model="item.label"
                                        type="text"
                                        required
                                        placeholder="Ex. Capitaine fumé"
                                        class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                    />
                                    <p v-if="itemError(index, 'label')" class="mt-1 text-xs text-red-600">
                                        {{ itemError(index, 'label') }}
                                    </p>
                                </div>

                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="text-xs font-medium text-ink-muted">Quantité</label>
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            max="100"
                                            required
                                            class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                        />
                                        <p v-if="itemError(index, 'quantity')" class="mt-1 text-xs text-red-600">
                                            {{ itemError(index, 'quantity') }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-ink-muted">Budget max (CAD)</label>
                                        <input
                                            v-model.number="item.budget"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                            placeholder="Optionnel"
                                        />
                                        <p v-if="itemError(index, 'budget')" class="mt-1 text-xs text-red-600">
                                            {{ itemError(index, 'budget') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="text-xs font-medium text-ink-muted">Notes (optionnel)</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="mt-1 w-full border border-forest-900/15 px-3 py-2.5"
                                placeholder="Marque, format, contraintes globales…"
                            />
                            <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <fieldset class="mt-6 space-y-3 border border-forest-900/10 p-4">
                            <legend class="px-1 text-xs font-medium tracking-wide text-ink-muted uppercase">
                                Fournisseur
                            </legend>
                            <p class="text-sm text-ink-muted">
                                Avez-vous déjà un fournisseur en Côte d’Ivoire pour ces produits ?
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
                                    Vous listez les produits (libellé, quantité, budget) et précisez le fournisseur.
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
