<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductImage from '@/Components/Shop/ProductImage.vue';

type CartItem = {
    product_id: number;
    quantity: number;
    name: string;
    unit: string;
    price: string;
    line_total: string;
    stock_quantity: number;
    in_stock: boolean;
    image_url: string;
    category: string | null;
};

const props = defineProps<{
    items: CartItem[];
    total: string;
    count: number;
    can_checkout: boolean;
}>();

const page = usePage();
const authUser = computed(
    () =>
        (page.props.auth as { user?: { name: string; email: string } | null } | undefined)?.user ??
        null,
);

const createAccount = ref(false);

const form = useForm({
    guest_name: authUser.value?.name ?? '',
    guest_email: authUser.value?.email ?? '',
    shipping_phone: '',
    shipping_line1: '',
    shipping_line2: '',
    shipping_city: '',
    shipping_province: 'Québec',
    shipping_postal_code: '',
    shipping_country: 'CA',
    notes: '',
    create_account: false as boolean,
    password: '',
    password_confirmation: '',
});

watch(createAccount, (value) => {
    form.create_account = value;
    if (!value) {
        form.password = '';
        form.password_confirmation = '';
        form.clearErrors('password', 'password_confirmation');
    }
});

function updateQuantity(productId: number, quantity: number) {
    router.patch(`/panier/${productId}`, { quantity }, { preserveScroll: true });
}

function removeItem(productId: number) {
    router.delete(`/panier/${productId}`, { preserveScroll: true });
}

function submitCheckout() {
    form.create_account = createAccount.value;
    form.post('/panier/commander', { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <Head title="Panier" />

        <section
            class="bg-stone-soft px-4 pb-16 sm:px-5 md:px-8 md:pb-24"
            :style="{ paddingTop: 'calc(var(--header-offset) + 1.5rem)' }"
        >
            <div class="mx-auto max-w-5xl">
                <h1 class="font-display text-3xl text-forest-900 sm:text-4xl">Votre panier</h1>
                <p class="mt-2 text-ink-muted">{{ count }} article{{ count > 1 ? 's' : '' }}</p>

                <div v-if="items.length" class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                    <div class="space-y-4">
                        <article
                            v-for="item in items"
                            :key="item.product_id"
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
                                    <p class="text-xs tracking-wide text-ink-muted uppercase">
                                        {{ item.category }}
                                    </p>
                                    <h2 class="font-display mt-1 text-xl text-forest-900 sm:text-2xl">
                                        {{ item.name }}
                                    </h2>
                                    <p class="mt-1 text-sm text-ink-muted">
                                        {{ item.price }} / {{ item.unit }}
                                        <span v-if="!item.in_stock" class="text-brass">
                                            · stock insuffisant
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div
                                class="mt-4 flex flex-col gap-3 border-t border-forest-900/10 pt-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <label class="sr-only" :for="`qty-${item.product_id}`">Quantité</label>
                                    <input
                                        :id="`qty-${item.product_id}`"
                                        type="number"
                                        min="1"
                                        :max="item.stock_quantity"
                                        class="h-11 w-20 border border-forest-900/20 bg-stone-soft px-3 text-center"
                                        :value="item.quantity"
                                        @change="
                                            updateQuantity(
                                                item.product_id,
                                                Number(($event.target as HTMLInputElement).value),
                                            )
                                        "
                                    />
                                    <p class="font-semibold text-forest-900">{{ item.line_total }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex min-h-11 items-center justify-center px-3 text-sm font-medium text-ink-muted transition hover:text-forest-900"
                                    @click="removeItem(item.product_id)"
                                >
                                    Retirer
                                </button>
                            </div>
                        </article>
                    </div>

                    <aside class="border border-forest-900/10 bg-white p-5 sm:p-6 lg:sticky lg:top-28 lg:self-start">
                        <div>
                            <p class="text-sm text-ink-muted">Total estimé</p>
                            <p class="font-display text-2xl text-forest-900 sm:text-3xl">{{ total }}</p>
                        </div>

                        <p v-if="!can_checkout" class="mt-4 text-sm text-brass">
                            Corrigez les articles en rupture avant de valider.
                        </p>

                        <form class="mt-6 space-y-4" @submit.prevent="submitCheckout">
                            <template v-if="authUser">
                                <div class="border border-forest-900/10 bg-stone-soft px-4 py-3 text-sm">
                                    <p class="font-medium text-forest-900">{{ authUser.name }}</p>
                                    <p class="text-ink-muted">{{ authUser.email }}</p>
                                    <p class="mt-2 text-xs text-ink-muted">
                                        Commande rattachée à votre compte.
                                    </p>
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
                                <label class="flex items-start gap-3 text-sm text-forest-800">
                                    <input
                                        v-model="createAccount"
                                        type="checkbox"
                                        class="mt-1"
                                    />
                                    <span>Créer un compte pour suivre ma commande</span>
                                </label>
                                <div v-if="createAccount" class="space-y-3">
                                    <div>
                                        <label class="text-xs font-medium text-ink-muted">Mot de passe</label>
                                        <input
                                            v-model="form.password"
                                            type="password"
                                            autocomplete="new-password"
                                            class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                        />
                                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">
                                            {{ form.errors.password }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-ink-muted">
                                            Confirmer le mot de passe
                                        </label>
                                        <input
                                            v-model="form.password_confirmation"
                                            type="password"
                                            autocomplete="new-password"
                                            class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                        />
                                    </div>
                                </div>
                            </template>

                            <div class="border-t border-forest-900/10 pt-4">
                                <p class="text-xs font-medium tracking-wide text-ink-muted uppercase">
                                    Adresse de livraison
                                </p>
                                <div class="mt-3 space-y-3">
                                    <div>
                                        <label class="text-xs font-medium text-ink-muted">Téléphone</label>
                                        <input
                                            v-model="form.shipping_phone"
                                            type="tel"
                                            class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-ink-muted">Adresse</label>
                                        <input
                                            v-model="form.shipping_line1"
                                            type="text"
                                            required
                                            class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                            placeholder="123 rue Exemple"
                                        />
                                        <p v-if="form.errors.shipping_line1" class="mt-1 text-xs text-red-600">
                                            {{ form.errors.shipping_line1 }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-ink-muted">
                                            Complément (optionnel)
                                        </label>
                                        <input
                                            v-model="form.shipping_line2"
                                            type="text"
                                            class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                            placeholder="App. 4"
                                        />
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="text-xs font-medium text-ink-muted">Ville</label>
                                            <input
                                                v-model="form.shipping_city"
                                                type="text"
                                                required
                                                class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                            />
                                            <p v-if="form.errors.shipping_city" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.shipping_city }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-ink-muted">Province</label>
                                            <input
                                                v-model="form.shipping_province"
                                                type="text"
                                                required
                                                class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                            />
                                            <p v-if="form.errors.shipping_province" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.shipping_province }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="text-xs font-medium text-ink-muted">Code postal</label>
                                            <input
                                                v-model="form.shipping_postal_code"
                                                type="text"
                                                required
                                                class="mt-1 h-11 w-full border border-forest-900/15 px-3 uppercase"
                                            />
                                            <p v-if="form.errors.shipping_postal_code" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.shipping_postal_code }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-ink-muted">Pays</label>
                                            <input
                                                v-model="form.shipping_country"
                                                type="text"
                                                maxlength="2"
                                                class="mt-1 h-11 w-full border border-forest-900/15 px-3 uppercase"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-ink-muted">Note (optionnel)</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="mt-1 w-full border border-forest-900/15 px-3 py-2 text-sm"
                                    placeholder="Indications de livraison, préférences…"
                                />
                            </div>

                            <p v-if="form.errors.cart" class="text-sm text-red-600">{{ form.errors.cart }}</p>

                            <button
                                type="submit"
                                class="inline-flex min-h-12 w-full items-center justify-center bg-forest-900 px-6 text-sm font-semibold text-white disabled:opacity-50"
                                :disabled="!can_checkout || form.processing"
                            >
                                {{ form.processing ? 'Validation…' : 'Valider la commande' }}
                            </button>
                            <p class="text-xs leading-relaxed text-ink-muted">
                                Le paiement sécurisé arrivera ensuite. Votre commande est enregistrée dès
                                maintenant.
                            </p>
                        </form>
                    </aside>
                </div>

                <div
                    v-else
                    class="mt-10 border border-dashed border-forest-900/20 bg-white p-8 text-center sm:mt-12 sm:p-10"
                >
                    <p class="text-ink-muted">Votre panier est vide.</p>
                    <Link
                        href="/boutique"
                        class="mt-6 inline-flex min-h-12 w-full items-center justify-center bg-forest-900 px-6 text-sm font-semibold text-white sm:w-auto"
                    >
                        Continuer vos courses
                    </Link>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
