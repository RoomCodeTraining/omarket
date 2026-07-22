<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProductImage from '@/Components/Shop/ProductImage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

type Category = { id: number; name: string };
type PartnerProduct = {
    id: number;
    name: string;
    category: string | null;
    price: string;
    stock_quantity: number;
    unit: string;
    status: string;
    status_label: string;
    image_url: string;
};

const props = defineProps<{
    partner: {
        name: string;
        email: string;
        can_publish: boolean;
        approved_at: string | null;
    };
    stats: {
        total: number;
        draft: number;
        pending_review: number;
        published: number;
        stock_units: number;
    };
    products: PartnerProduct[];
    categories: Category[];
    defaults: {
        unit: string;
    };
}>();

const page = usePage();
const flashSuccess = computed(
    () => (page.props.flash as { success?: string | null } | undefined)?.success ?? null,
);
const pageErrors = computed(
    () => (page.props.errors as Record<string, string> | undefined) ?? {},
);
const csrfToken = computed(() => String((page.props as { csrf_token?: string }).csrf_token ?? ''));

const imageName = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const submitError = ref<string | null>(null);
const imageUploading = ref(false);
const imageWarning = ref<string | null>(null);

const form = useForm({
    category_id: props.categories[0]?.id ?? 0,
    name: '',
    description: '',
    price: null as number | null,
    stock_quantity: 0,
    unit: props.defaults.unit || 'unité',
});

const statsCards = computed(() => [
    { label: 'Produits', value: props.stats.total, hint: 'Tous statuts' },
    { label: 'Brouillons', value: props.stats.draft, hint: 'À finaliser' },
    { label: 'En revue', value: props.stats.pending_review, hint: 'Chez Ôhéfê' },
    { label: 'Publiés', value: props.stats.published, hint: 'En boutique' },
    { label: 'Unités stock', value: props.stats.stock_units, hint: 'Total déclaré' },
]);

const MAX_IMAGE_BYTES = 8 * 1024 * 1024;

function authHeaders(): Record<string, string> {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    const fromCookie = match?.[1] ? decodeURIComponent(match[1]) : '';

    return {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken.value,
        ...(fromCookie ? { 'X-XSRF-TOKEN': fromCookie } : {}),
    };
}

/** Browser streams the File — no FileReader (avoids iCloud/Photos permission errors). */
async function uploadImageFile(file: File): Promise<boolean> {
    const body = new FormData();
    body.append('image', file);

    const response = await fetch('/partenaires/espace/produits/image', {
        method: 'POST',
        credentials: 'same-origin',
        headers: authHeaders(),
        body,
    });

    return response.ok;
}

function onImageChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    submitError.value = null;
    imageWarning.value = null;
    imageName.value = null;

    if (!file) {
        return;
    }

    if (file.size > MAX_IMAGE_BYTES) {
        submitError.value = 'L’image ne doit pas dépasser 8 Mo.';
        input.value = '';
        return;
    }

    imageName.value = file.name;
}

function clearImage() {
    imageName.value = null;
    submitError.value = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

async function submitProduct(event: Event) {
    event.preventDefault();

    if (form.processing || imageUploading.value) {
        return;
    }

    submitError.value = null;
    imageWarning.value = null;

    const file = fileInput.value?.files?.[0] ?? null;
    let imageAttached = false;

    if (file) {
        imageUploading.value = true;

        try {
            imageAttached = await uploadImageFile(file);
        } catch {
            imageAttached = false;
        }

        imageUploading.value = false;

        if (!imageAttached) {
            imageWarning.value =
                'Produit enregistré sans image. Utilisez un JPEG/PNG depuis Téléchargements (pas Photos iCloud), ou ajoutez la photo plus tard en admin.';
        }
    }

    form.post('/partenaires/espace/produits', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'description', 'price', 'stock_quantity');
            form.unit = props.defaults.unit || 'unité';
            clearImage();
            if (!imageAttached && file) {
                imageWarning.value =
                    'Produit créé. L’image n’a pas pu être envoyée — choisissez un fichier local (Téléchargements), ou ajoutez-la en admin.';
            }
        },
        onError: () => {
            submitError.value = 'Corrige les champs indiqués, puis réessaie.';
            imageWarning.value = null;
        },
    });
}

function submitForReview(productId: number) {
    router.post(`/partenaires/espace/produits/${productId}/soumettre`, {}, { preserveScroll: true });
}

function logout() {
    router.post('/partenaires/deconnexion');
}

function statusTone(status: string): string {
    if (status === 'published') {
        return 'text-emerald-800';
    }

    if (status === 'pending_review') {
        return 'text-amber-800';
    }

    return 'text-forest-700';
}
</script>

<template>
    <AppLayout>
        <Head title="Espace partenaire" />

        <section
            class="bg-stone-soft px-4 pb-16 sm:px-5 md:px-8 md:pb-24"
            :style="{ paddingTop: 'calc(var(--header-offset) + 1.5rem)' }"
        >
            <div class="mx-auto max-w-6xl space-y-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm tracking-[0.18em] text-brass uppercase">Espace partenaire</p>
                        <h1 class="font-display mt-2 text-3xl text-forest-900 sm:text-4xl">{{ partner.name }}</h1>
                        <p class="mt-2 text-sm text-ink-muted">{{ partner.email }}</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex min-h-11 items-center justify-center border border-forest-900/20 px-4 text-sm font-medium text-forest-900"
                        @click="logout"
                    >
                        Déconnexion
                    </button>
                </div>

                <div
                    class="border px-5 py-4 text-sm"
                    :class="
                        partner.can_publish
                            ? 'border-forest-900/15 bg-white text-forest-800'
                            : 'border-brass/40 bg-white text-ink-muted'
                    "
                >
                    <p v-if="partner.can_publish">
                        Compte autorisé à publier
                        <span v-if="partner.approved_at"> depuis le {{ partner.approved_at }}</span>.
                        Ajoutez une image, puis soumettez chaque fiche pour validation Ôhéfê.
                    </p>
                    <p v-else>
                        Compte créé. Préparez vos brouillons (avec photo) ; la soumission sera
                        possible après validation par Ôhéfê.
                    </p>
                </div>

                <p
                    v-if="flashSuccess"
                    class="border border-forest-900/10 bg-white px-5 py-3 text-sm text-forest-800"
                >
                    {{ flashSuccess }}
                </p>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div
                        v-for="card in statsCards"
                        :key="card.label"
                        class="border border-forest-900/10 bg-white px-4 py-4"
                    >
                        <p class="text-[11px] tracking-[0.16em] text-ink-muted uppercase">{{ card.label }}</p>
                        <p class="font-display mt-2 text-3xl text-forest-900">{{ card.value }}</p>
                        <p class="mt-1 text-xs text-ink-muted">{{ card.hint }}</p>
                    </div>
                </div>

                <div class="grid gap-8 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                    <form
                        class="border border-forest-900/10 bg-white p-5 sm:p-6"
                        @submit="submitProduct"
                    >
                        <h2 class="font-display text-2xl text-forest-900">Nouveau produit</h2>
                        <p class="mt-2 text-sm text-ink-muted">
                            Brouillon hors boutique. Image optionnelle — fichier JPEG/PNG depuis
                            Téléchargements (évitez Photos iCloud).
                        </p>

                        <div class="mt-6 space-y-4">
                            <div>
                                <label class="text-xs font-medium text-ink-muted">Image produit</label>
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                                    class="mt-2 block w-full text-sm text-ink-muted file:mr-3 file:border-0 file:bg-forest-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white"
                                    @change="onImageChange"
                                />
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <p v-if="imageName" class="text-xs text-ink-muted">{{ imageName }}</p>
                                    <button
                                        v-if="imageName"
                                        type="button"
                                        class="text-xs font-medium text-forest-700 underline"
                                        @click="clearImage"
                                    >
                                        Retirer
                                    </button>
                                </div>
                                <p v-if="form.errors.image || form.errors.image_base64 || pageErrors.image" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.image || form.errors.image_base64 || pageErrors.image }}
                                </p>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-ink-muted">Catégorie</label>
                                <select
                                    v-model.number="form.category_id"
                                    required
                                    class="mt-1 h-11 w-full border border-forest-900/15 bg-white px-3"
                                >
                                    <option
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-ink-muted">Nom</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                />
                                <p v-if="form.errors.name || pageErrors.name" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.name || pageErrors.name }}
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-ink-muted">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 w-full border border-forest-900/15 px-3 py-2"
                                />
                            </div>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="text-xs font-medium text-ink-muted">Prix (CAD)</label>
                                    <input
                                        v-model.number="form.price"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        required
                                        class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                    />
                                    <p v-if="form.errors.price || pageErrors.price" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.price || pageErrors.price }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-ink-muted">Stock</label>
                                    <input
                                        v-model.number="form.stock_quantity"
                                        type="number"
                                        min="0"
                                        required
                                        class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                    />
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-ink-muted">Unité</label>
                                    <input
                                        v-model="form.unit"
                                        type="text"
                                        required
                                        class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                                    />
                                </div>
                            </div>
                        </div>

                        <p v-if="submitError" class="mt-4 text-sm text-red-600">{{ submitError }}</p>
                        <p v-if="imageWarning" class="mt-4 text-sm text-amber-800">{{ imageWarning }}</p>

                        <button
                            type="submit"
                            class="mt-6 inline-flex min-h-12 w-full items-center justify-center bg-forest-900 px-6 text-sm font-semibold text-white disabled:opacity-50"
                            :disabled="form.processing || imageUploading"
                        >
                            {{
                                imageUploading
                                    ? 'Envoi de l’image…'
                                    : form.processing
                                      ? 'Enregistrement…'
                                      : 'Enregistrer le brouillon'
                            }}
                        </button>
                    </form>

                    <div>
                        <div class="flex items-end justify-between gap-3">
                            <div>
                                <h2 class="font-display text-2xl text-forest-900">Mes produits</h2>
                                <p class="mt-1 text-sm text-ink-muted">Suivi de vos fiches et soumissions.</p>
                            </div>
                        </div>

                        <div v-if="products.length" class="mt-6 space-y-3">
                            <article
                                v-for="product in products"
                                :key="product.id"
                                class="border border-forest-900/10 bg-white p-4"
                            >
                                <div class="flex gap-4">
                                    <div
                                        class="h-20 w-20 shrink-0 overflow-hidden border border-forest-900/10 bg-stone-soft"
                                    >
                                        <ProductImage
                                            :src="product.image_url"
                                            :alt="product.name"
                                            img-class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                                        >
                                            <div class="min-w-0">
                                                <h3 class="truncate font-medium text-forest-900">{{ product.name }}</h3>
                                                <p class="mt-1 text-sm text-ink-muted">
                                                    {{ product.category ?? 'Sans catégorie' }} ·
                                                    {{ product.price }} / {{ product.unit }} · stock
                                                    {{ product.stock_quantity }}
                                                </p>
                                                <p class="mt-2 text-xs font-medium" :class="statusTone(product.status)">
                                                    {{ product.status_label }}
                                                </p>
                                            </div>
                                            <button
                                                v-if="product.status === 'draft' && partner.can_publish"
                                                type="button"
                                                class="inline-flex min-h-10 shrink-0 items-center justify-center border border-forest-900/20 px-3 text-xs font-semibold text-forest-900"
                                                @click="submitForReview(product.id)"
                                            >
                                                Soumettre
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <p v-else class="mt-6 text-sm text-ink-muted">Aucun produit pour le moment.</p>

                        <p class="mt-6 text-sm">
                            <Link href="/boutique" class="font-medium text-forest-800 underline">
                                Voir la boutique
                            </Link>
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
