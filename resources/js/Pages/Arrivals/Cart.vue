<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductImage from '@/Components/Shop/ProductImage.vue';

type CartItem = {
    cargo_item_id: number;
    quantity: number;
    name: string;
    unit: string;
    price: string;
    line_total: string;
    quantity_remaining: number;
    available: boolean;
    image_url: string;
    category: string | null;
};

const props = defineProps<{
    items: CartItem[];
    total: string;
    count: number;
    can_checkout: boolean;
    checkout_requires_account: boolean;
    cargo: {
        id: number;
        code: string;
        name: string;
        route: string;
        estimated_arrival_at: string | null;
        status_label: string;
    } | null;
}>();

const page = usePage();
const authUser = computed(
    () =>
        (page.props.auth as { user?: { name: string; email: string } | null } | undefined)?.user ??
        null,
);

const createAccount = ref(props.checkout_requires_account && !authUser.value);

const form = useForm({
    guest_name: authUser.value?.name ?? '',
    guest_email: authUser.value?.email ?? '',
    notes: '',
    create_account: props.checkout_requires_account && !authUser.value,
    password: '',
    password_confirmation: '',
});

watch(createAccount, (value) => {
    form.create_account = !authUser.value && (value || props.checkout_requires_account);
    if (!form.create_account) {
        form.password = '';
        form.password_confirmation = '';
        form.clearErrors('password', 'password_confirmation');
    }
});

function updateQuantity(cargoItemId: number, quantity: number) {
    router.patch(`/panier-arrivage/${cargoItemId}`, { quantity }, { preserveScroll: true });
}

function removeItem(cargoItemId: number) {
    router.delete(`/panier-arrivage/${cargoItemId}`, { preserveScroll: true });
}

function clearCart() {
    router.delete('/panier-arrivage', { preserveScroll: true });
}

function submitCheckout() {
    form.create_account = !authUser.value && (createAccount.value || props.checkout_requires_account);
    if (authUser.value) {
        form.guest_name = authUser.value.name;
        form.guest_email = authUser.value.email;
        form.password = '';
        form.password_confirmation = '';
    }
    form.post('/panier-arrivage/commander', { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <Head title="Panier arrivage" />

        <section
            class="bg-stone-soft px-4 pb-16 sm:px-5 md:px-8 md:pb-24"
            :style="{ paddingTop: 'calc(var(--header-offset) + 1.5rem)' }"
        >
            <div class="mx-auto max-w-5xl">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="font-display text-3xl text-forest-900 sm:text-4xl">
                            Panier arrivage
                        </h1>
                        <p class="mt-2 text-ink-muted">
                            {{ count }} article{{ count > 1 ? 's' : '' }}
                            <span v-if="cargo"> · cargo {{ cargo.code }}</span>
                        </p>
                    </div>
                    <Link href="/arrivages" class="text-sm font-medium text-forest-800 underline">
                        Continuer les arrivages
                    </Link>
                </div>

                <div
                    v-if="cargo"
                    class="mt-6 border border-forest-900/10 bg-white px-4 py-4 sm:px-5"
                >
                    <p class="text-xs tracking-wide text-brass uppercase">{{ cargo.code }}</p>
                    <p class="mt-1 font-medium text-forest-900">{{ cargo.name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">
                        {{ cargo.route }}
                        <span v-if="cargo.estimated_arrival_at">
                            · ETA {{ cargo.estimated_arrival_at }}
                        </span>
                    </p>
                </div>

                <div v-if="items.length" class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                    <div class="space-y-4">
                        <article
                            v-for="item in items"
                            :key="item.cargo_item_id"
                            class="border border-forest-900/10 bg-white p-4 sm:p-5"
                        >
                            <div class="flex gap-3 sm:gap-4">
                                <div class="h-16 w-16 shrink-0 overflow-hidden bg-stone-soft sm:h-20 sm:w-20">
                                    <ProductImage
                                        :src="item.image_url"
                                        :alt="item.name"
                                        img-class="h-full w-full object-cover"
                                    />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <h2 class="font-medium text-forest-900">{{ item.name }}</h2>
                                            <p class="mt-1 text-sm text-ink-muted">
                                                {{ item.price }} / {{ item.unit }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs"
                                                :class="item.available ? 'text-ink-muted' : 'text-brass'"
                                            >
                                                {{
                                                    item.available
                                                        ? `${item.quantity_remaining} restants`
                                                        : 'Quantité indisponible'
                                                }}
                                            </p>
                                        </div>
                                        <p class="text-sm font-semibold text-forest-900">
                                            {{ item.line_total }}
                                        </p>
                                    </div>
                                    <div class="mt-4 flex flex-wrap items-center gap-3">
                                        <label class="sr-only" :for="`qty-${item.cargo_item_id}`">
                                            Quantité
                                        </label>
                                        <input
                                            :id="`qty-${item.cargo_item_id}`"
                                            type="number"
                                            min="1"
                                            :max="item.quantity_remaining"
                                            :value="item.quantity"
                                            class="h-10 w-20 border border-forest-900/15 px-2"
                                            @change="
                                                updateQuantity(
                                                    item.cargo_item_id,
                                                    Number(($event.target as HTMLInputElement).value),
                                                )
                                            "
                                        />
                                        <button
                                            type="button"
                                            class="text-sm text-ink-muted underline hover:text-forest-900"
                                            @click="removeItem(item.cargo_item_id)"
                                        >
                                            Retirer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <button
                            type="button"
                            class="text-sm font-medium text-ink-muted underline hover:text-forest-900"
                            @click="clearCart"
                        >
                            Vider le panier arrivage
                        </button>
                    </div>

                    <aside class="border border-forest-900/10 bg-white p-5 sm:p-6 lg:sticky lg:top-28 lg:self-start">
                        <div>
                            <p class="text-sm text-ink-muted">Total réservation</p>
                            <p class="font-display text-2xl text-forest-900 sm:text-3xl">{{ total }}</p>
                        </div>

                        <p class="mt-3 text-xs text-ink-muted">
                            Tous les articles doivent appartenir au même cargo.
                        </p>

                        <p v-if="!can_checkout" class="mt-4 text-sm text-brass">
                            Corrigez les articles indisponibles avant de valider.
                        </p>

                        <form class="mt-6 space-y-4" @submit.prevent="submitCheckout">
                            <template v-if="authUser">
                                <div class="border border-forest-900/10 bg-stone-soft px-4 py-3 text-sm">
                                    <p class="font-medium text-forest-900">{{ authUser.name }}</p>
                                    <p class="text-ink-muted">{{ authUser.email }}</p>
                                </div>
                            </template>
                            <template v-else>
                                <div>
                                    <label class="text-xs font-medium text-ink-muted">Nom</label>
                                    <input
                                        v-model="form.guest_name"
                                        type="text"
                                        required
                                        class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                    />
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-ink-muted">E-mail</label>
                                    <input
                                        v-model="form.guest_email"
                                        type="email"
                                        required
                                        class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                    />
                                </div>
                                <label
                                    v-if="!checkout_requires_account"
                                    class="flex items-start gap-3 text-sm text-forest-800"
                                >
                                    <input v-model="createAccount" type="checkbox" class="mt-1" />
                                    <span>Créer un compte</span>
                                </label>
                                <div v-if="createAccount || checkout_requires_account" class="space-y-3">
                                    <input
                                        v-model="form.password"
                                        type="password"
                                        placeholder="Mot de passe"
                                        class="h-11 w-full border border-forest-900/15 px-3"
                                    />
                                    <input
                                        v-model="form.password_confirmation"
                                        type="password"
                                        placeholder="Confirmer"
                                        class="h-11 w-full border border-forest-900/15 px-3"
                                    />
                                </div>
                            </template>

                            <div>
                                <label class="text-xs font-medium text-ink-muted">Notes (optionnel)</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="mt-1 w-full border border-forest-900/15 px-3 py-2"
                                />
                            </div>

                            <p v-if="form.errors.cart" class="text-xs text-red-600">{{ form.errors.cart }}</p>
                            <p v-if="form.errors.cargo_item_id" class="text-xs text-red-600">
                                {{ form.errors.cargo_item_id }}
                            </p>
                            <p v-if="form.errors.password" class="text-xs text-red-600">{{ form.errors.password }}</p>
                            <p v-if="form.errors.create_account" class="text-xs text-red-600">
                                {{ form.errors.create_account }}
                            </p>
                            <p v-if="form.errors.guest_email" class="text-xs text-red-600">
                                {{ form.errors.guest_email }}
                            </p>

                            <button
                                type="submit"
                                class="inline-flex min-h-12 w-full items-center justify-center bg-forest-900 px-6 text-sm font-semibold text-white disabled:opacity-50"
                                :disabled="!can_checkout || form.processing"
                            >
                                Valider la réservation
                            </button>
                        </form>
                    </aside>
                </div>

                <div
                    v-else
                    class="mt-10 border border-dashed border-forest-900/20 bg-white p-10 text-center"
                >
                    <p class="text-ink-muted">Votre panier arrivage est vide.</p>
                    <Link
                        href="/arrivages"
                        class="mt-4 inline-flex min-h-11 items-center justify-center bg-forest-900 px-5 text-sm font-semibold text-white"
                    >
                        Voir les arrivages
                    </Link>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
