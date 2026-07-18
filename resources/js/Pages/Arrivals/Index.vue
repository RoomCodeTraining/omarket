<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHero from '@/Components/Landing/PageHero.vue';
import ProductImage from '@/Components/Shop/ProductImage.vue';

type CargoItem = {
    id: number;
    product_name: string | null;
    category: string | null;
    image_url: string;
    quantity_remaining: number;
    quantity_available: number;
    quantity_reserved: number;
    unit_price: string;
    unit: string | null;
};

type Cargo = {
    id: number;
    code: string;
    name: string;
    route: string;
    status_label: string;
    departure_at: string | null;
    estimated_arrival_at: string | null;
    notes: string | null;
    items: CargoItem[];
};

defineProps<{
    cargos: Cargo[];
    nextArrival?: {
        label: string;
        eta: string;
        name?: string;
        code?: string;
    } | null;
}>();

const activeItemId = ref<number | null>(null);

const form = useForm({
    cargo_item_id: 0,
    quantity: 1,
    guest_name: '',
    guest_email: '',
});

function openReserve(item: CargoItem) {
    activeItemId.value = item.id;
    form.cargo_item_id = item.id;
    form.quantity = 1;
    form.clearErrors();
}

function closeReserve() {
    activeItemId.value = null;
}

function submitReserve() {
    form.post('/reservations', {
        preserveScroll: true,
        onSuccess: () => {
            activeItemId.value = null;
            form.reset('quantity');
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Arrivages">
            <meta
                head-key="description"
                name="description"
                content="Réservez vos produits ivoiriens avant l’arrivée du cargo au Canada avec Ôhéfê Market."
            />
        </Head>

        <PageHero
            eyebrow="Arrivages"
            title="Réservez avant l’arrivée du cargo"
            description="Anticipez vos besoins : choisissez vos produits, réservez vos quantités, recevez une alerte à l’arrivée."
            webp="/images/landing/section-fresh.webp"
            jpg="/images/landing/section-fresh.jpg"
            alt="Produits frais pour arrivage"
        />

        <section class="bg-stone-soft px-5 py-14 md:px-8 md:py-20">
            <div class="mx-auto max-w-6xl space-y-10">
                <div
                    v-if="nextArrival"
                    class="grid gap-6 border border-forest-900/10 bg-white p-8 md:grid-cols-[1.2fr_0.8fr] md:p-10"
                >
                    <div>
                        <p class="text-sm tracking-[0.18em] text-brass uppercase">Prochain cargo ouvert</p>
                        <h2 class="font-display mt-3 text-3xl text-forest-900">
                            {{ nextArrival.name ?? nextArrival.label }}
                        </h2>
                        <p class="mt-3 text-ink-muted">
                            {{ nextArrival.label }} · Arrivée estimée
                            <span class="font-medium text-forest-900">{{ nextArrival.eta }}</span>
                        </p>
                    </div>
                    <div class="flex items-center md:justify-end">
                        <Link
                            href="/courses"
                            class="inline-flex min-h-12 items-center bg-forest-900 px-6 text-sm font-semibold text-white transition hover:bg-forest-800"
                        >
                            Produit manquant ? Course perso
                        </Link>
                    </div>
                </div>

                <div v-for="cargo in cargos" :key="cargo.id" class="bg-white">
                    <div class="border-b border-forest-900/10 px-6 py-5 md:px-8">
                        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                            <div>
                                <h3 class="font-display text-2xl text-forest-900">{{ cargo.name }}</h3>
                                <p class="mt-1 text-sm text-ink-muted">
                                    {{ cargo.route }} · Départ {{ cargo.departure_at ?? '—' }} · ETA
                                    {{ cargo.estimated_arrival_at ?? '—' }}
                                </p>
                            </div>
                            <span class="text-sm font-semibold text-forest-700">{{ cargo.status_label }}</span>
                        </div>
                    </div>

                    <div class="divide-y divide-forest-900/10">
                        <div v-for="item in cargo.items" :key="item.id" class="px-6 py-5 md:px-8">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="h-16 w-16 shrink-0 overflow-hidden bg-stone-soft">
                                        <ProductImage
                                            :src="item.image_url"
                                            :alt="item.product_name ?? 'Produit'"
                                            img-class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div>
                                        <p class="font-medium text-forest-900">{{ item.product_name }}</p>
                                        <p class="text-xs text-ink-muted">{{ item.category }}</p>
                                        <p class="mt-2 text-sm text-ink-muted">
                                            Restant {{ item.quantity_remaining }} / {{ item.quantity_available }}
                                            · {{ item.unit_price }} / {{ item.unit }}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="min-h-10 w-fit bg-brass px-4 text-sm font-semibold text-forest-950 transition hover:bg-brass-light disabled:opacity-40"
                                    :disabled="item.quantity_remaining <= 0"
                                    @click="openReserve(item)"
                                >
                                    Réserver
                                </button>
                            </div>

                            <form
                                v-if="activeItemId === item.id"
                                class="mt-5 grid gap-3 border border-forest-900/10 bg-stone-soft p-4 md:grid-cols-4"
                                @submit.prevent="submitReserve"
                            >
                                <div>
                                    <label class="text-xs font-medium text-ink-muted">Nom</label>
                                    <input
                                        v-model="form.guest_name"
                                        type="text"
                                        required
                                        class="mt-1 h-11 w-full border border-forest-900/15 bg-white px-3 text-sm"
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
                                        class="mt-1 h-11 w-full border border-forest-900/15 bg-white px-3 text-sm"
                                    />
                                    <p v-if="form.errors.guest_email" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.guest_email }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-ink-muted">Quantité</label>
                                    <input
                                        v-model.number="form.quantity"
                                        type="number"
                                        min="1"
                                        :max="item.quantity_remaining"
                                        required
                                        class="mt-1 h-11 w-full border border-forest-900/15 bg-white px-3 text-sm"
                                    />
                                    <p v-if="form.errors.quantity" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.quantity }}
                                    </p>
                                </div>
                                <div class="flex items-end gap-2">
                                    <button
                                        type="submit"
                                        class="h-11 flex-1 bg-forest-900 px-4 text-sm font-semibold text-white disabled:opacity-50"
                                        :disabled="form.processing"
                                    >
                                        Confirmer
                                    </button>
                                    <button
                                        type="button"
                                        class="h-11 px-3 text-sm text-ink-muted"
                                        @click="closeReserve"
                                    >
                                        Annuler
                                    </button>
                                </div>
                                <p
                                    v-if="form.errors.cargo_item_id"
                                    class="md:col-span-4 text-xs text-red-600"
                                >
                                    {{ form.errors.cargo_item_id }}
                                </p>
                            </form>
                        </div>
                    </div>
                </div>

                <div
                    v-if="!cargos.length"
                    class="border border-dashed border-forest-900/20 bg-white p-10 text-center text-ink-muted"
                >
                    Aucun cargo ouvert aux réservations pour le moment.
                </div>
            </div>
        </section>
    </AppLayout>
</template>
