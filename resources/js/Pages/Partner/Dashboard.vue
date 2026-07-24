<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';
import ProductImage from '@/Components/Shop/ProductImage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

type Category = { id: number; name: string };
type AvailableCargo = {
    id: number;
    label: string;
    eta: string | null;
    status_label: string;
};
type PartnerProduct = {
    id: number;
    category_id: number;
    cargo_id: number | null;
    name: string;
    description: string | null;
    category: string | null;
    cargo_code: string | null;
    cargo_name: string | null;
    cargo_eta: string | null;
    price: string;
    price_amount: number;
    stock_quantity: number;
    unit: string;
    status: string;
    status_label: string;
    warehouse_deposited: boolean;
    warehouse_deposited_at: string | null;
    warehouse_deposited_quantity: number | null;
    ordered_quantity: number;
    deposit_deadline: string | null;
    needs_deposit: boolean;
    image_url: string;
};
type PartnerOrder = {
    id: number;
    reference: string;
    status: string;
    status_label: string;
    total: string;
    placed_at: string | null;
    items: Array<{ name: string; quantity: number; line_total: string }>;
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
    orders: PartnerOrder[];
    categories: Category[];
    available_cargos: AvailableCargo[];
    can_create_product: boolean;
    warehouse: {
        name: string;
        line1: string;
        line2: string;
        city: string;
        province: string;
        postal_code: string;
        country: string;
        phone: string;
        notes: string;
    };
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

const activeTab = ref<'products' | 'orders'>('products');
const modalOpen = ref(false);
const editingProduct = ref<PartnerProduct | null>(null);

const imageName = ref<string | null>(null);
const imagePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const submitError = ref<string | null>(null);
const imageUploading = ref(false);
const imageWarning = ref<string | null>(null);

const form = useForm({
    category_id: props.categories[0]?.id ?? 0,
    cargo_id: props.available_cargos[0]?.id ?? 0,
    name: '',
    description: '',
    price: null as number | null,
    stock_quantity: 0,
    unit: props.defaults.unit || 'unité',
});

const modalTitle = computed(() =>
    editingProduct.value ? 'Modifier le produit' : 'Nouveau produit',
);

const MAX_IMAGE_BYTES = 8 * 1024 * 1024;

function revokePreview() {
    if (imagePreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(imagePreview.value);
    }
    imagePreview.value = null;
}

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

function resetFormFields() {
    form.reset('name', 'description', 'price', 'stock_quantity');
    form.category_id = props.categories[0]?.id ?? 0;
    form.cargo_id = props.available_cargos[0]?.id ?? 0;
    form.unit = props.defaults.unit || 'unité';
    form.clearErrors();
}

function clearImage() {
    imageName.value = null;
    submitError.value = null;
    revokePreview();

    if (fileInput.value) {
        fileInput.value.value = '';
    }

    if (editingProduct.value) {
        imagePreview.value = editingProduct.value.image_url;
    }
}

function openCreateModal() {
    if (!props.can_create_product) {
        return;
    }

    editingProduct.value = null;
    resetFormFields();
    clearImage();
    submitError.value = null;
    imageWarning.value = null;
    modalOpen.value = true;
}

function openEditModal(product: PartnerProduct) {
    editingProduct.value = product;
    form.category_id = product.category_id;
    form.cargo_id = product.cargo_id ?? props.available_cargos[0]?.id ?? 0;
    form.name = product.name;
    form.description = product.description ?? '';
    form.price = product.price_amount;
    form.stock_quantity = product.stock_quantity;
    form.unit = product.unit;
    form.clearErrors();
    clearImage();
    imagePreview.value = product.image_url;
    imageName.value = null;
    submitError.value = null;
    imageWarning.value = null;
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    editingProduct.value = null;
    clearImage();
    submitError.value = null;
    imageWarning.value = null;
}

function onImageChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    submitError.value = null;
    imageWarning.value = null;
    imageName.value = null;
    revokePreview();

    if (!file) {
        if (editingProduct.value) {
            imagePreview.value = editingProduct.value.image_url;
        }
        return;
    }

    if (file.size > MAX_IMAGE_BYTES) {
        submitError.value = 'L’image ne doit pas dépasser 8 Mo.';
        input.value = '';
        return;
    }

    imageName.value = file.name;

    try {
        imagePreview.value = URL.createObjectURL(file);
    } catch {
        imagePreview.value = editingProduct.value?.image_url ?? null;
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
    let imageAttached = !file;

    if (file) {
        imageUploading.value = true;

        try {
            imageAttached = await uploadImageFile(file);
        } catch {
            imageAttached = false;
        }

        imageUploading.value = false;

        if (!imageAttached && !editingProduct.value) {
            imageWarning.value =
                'Le produit sera enregistré sans image si l’envoi échoue.';
        }
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            resetFormFields();
            if (file && !imageAttached) {
                imageWarning.value =
                    'Produit enregistré. L’image n’a pas pu être envoyée.';
            }
        },
        onError: () => {
            submitError.value = 'Corrige les champs indiqués, puis réessaie.';
        },
    };

    if (editingProduct.value) {
        form.put(`/partenaires/espace/produits/${editingProduct.value.id}`, options);
        return;
    }

    form.post('/partenaires/espace/produits', options);
}

function submitForReview(productId: number) {
    router.post(`/partenaires/espace/produits/${productId}/soumettre`, {}, { preserveScroll: true });
}

function unpublish(productId: number) {
    if (!confirm('Retirer ce produit de l’arrivage ?')) {
        return;
    }

    router.post(`/partenaires/espace/produits/${productId}/depublier`, {}, { preserveScroll: true });
}

function logout() {
    router.post('/partenaires/deconnexion');
}

function statusTone(status: string): string {
    if (status === 'published') {
        return 'text-emerald-700';
    }

    if (status === 'pending_review') {
        return 'text-amber-700';
    }

    if (status === 'archived') {
        return 'text-stone-500';
    }

    return 'text-forest-700';
}

watch(modalOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

onUnmounted(() => {
    revokePreview();
    document.body.style.overflow = '';
});
</script>

<template>
    <AppLayout>
        <Head title="Espace partenaire" />

        <section
            class="bg-stone-soft px-4 pb-20 sm:px-6 md:px-8"
            :style="{ paddingTop: 'calc(var(--header-offset) + 1.25rem)' }"
        >
            <div class="mx-auto max-w-6xl space-y-6">
                <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs tracking-[0.2em] text-brass uppercase">Espace partenaire</p>
                        <h1 class="font-display mt-1 text-3xl text-forest-900 sm:text-4xl">{{ partner.name }}</h1>
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ partner.can_publish ? 'Publication autorisée' : 'En attente de validation Ôhéfê' }}
                            <span v-if="partner.approved_at"> · depuis le {{ partner.approved_at }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="can_create_product"
                            type="button"
                            class="inline-flex min-h-11 items-center justify-center bg-forest-900 px-4 text-sm font-semibold text-white"
                            @click="openCreateModal"
                        >
                            Nouveau produit
                        </button>
                        <button
                            type="button"
                            class="inline-flex min-h-11 items-center justify-center border border-forest-900/20 px-4 text-sm text-forest-900"
                            @click="logout"
                        >
                            Déconnexion
                        </button>
                    </div>
                </header>

                <p
                    v-if="!can_create_product"
                    class="border border-amber-700/20 bg-amber-50 px-4 py-3 text-sm text-amber-950"
                >
                    Aucun cargo en mer pour le moment. Vous pourrez ajouter des produits uniquement
                    lorsqu’un cargo sera en transit — ils seront livrés avec cet arrivage (pas en boutique).
                </p>

                <p
                    v-if="flashSuccess"
                    class="border border-forest-900/10 bg-white px-4 py-3 text-sm text-forest-800"
                >
                    {{ flashSuccess }}
                </p>
                <p v-if="imageWarning && !modalOpen" class="text-sm text-amber-800">{{ imageWarning }}</p>

                <div class="flex flex-wrap items-baseline gap-x-6 gap-y-1 text-sm text-ink-muted">
                    <span><strong class="text-forest-900">{{ stats.total }}</strong> produits</span>
                    <span><strong class="text-forest-900">{{ stats.published }}</strong> publiés</span>
                    <span><strong class="text-forest-900">{{ stats.draft }}</strong> brouillons</span>
                    <span><strong class="text-forest-900">{{ stats.pending_review }}</strong> en revue</span>
                    <span><strong class="text-forest-900">{{ orders.length }}</strong> commandes</span>
                </div>

                <div class="flex gap-6 border-b border-forest-900/10 text-sm">
                    <button
                        type="button"
                        class="border-b-2 pb-3 font-medium transition"
                        :class="
                            activeTab === 'products'
                                ? 'border-forest-900 text-forest-900'
                                : 'border-transparent text-ink-muted'
                        "
                        @click="activeTab = 'products'"
                    >
                        Produits
                    </button>
                    <button
                        type="button"
                        class="border-b-2 pb-3 font-medium transition"
                        :class="
                            activeTab === 'orders'
                                ? 'border-forest-900 text-forest-900'
                                : 'border-transparent text-ink-muted'
                        "
                        @click="activeTab = 'orders'"
                    >
                        Commandes
                    </button>
                </div>

                <div v-if="activeTab === 'products'">
                    <div
                        v-if="products.length"
                        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <article
                            v-for="product in products"
                            :key="product.id"
                            class="flex flex-col border border-forest-900/10 bg-white"
                        >
                            <div class="aspect-[4/3] overflow-hidden bg-stone-soft">
                                <ProductImage
                                    :src="product.image_url"
                                    :alt="product.name"
                                    img-class="h-full w-full object-cover"
                                />
                            </div>
                            <div class="flex flex-1 flex-col gap-3 p-4">
                                <div>
                                    <h2 class="font-medium text-forest-900">{{ product.name }}</h2>
                                    <p class="mt-1 text-sm text-ink-muted">
                                        {{ product.price }} / {{ product.unit }} · stock
                                        {{ product.stock_quantity }}
                                    </p>
                                    <p v-if="product.cargo_code" class="mt-1 text-xs text-ink-muted">
                                        Cargo {{ product.cargo_code }}
                                        <span v-if="product.cargo_eta"> · ETA {{ product.cargo_eta }}</span>
                                    </p>
                                    <p class="mt-2 text-xs font-medium" :class="statusTone(product.status)">
                                        {{ product.status_label }}
                                    </p>
                                    <p
                                        v-if="product.warehouse_deposited || product.needs_deposit"
                                        class="mt-1 text-xs font-medium"
                                        :class="
                                            product.warehouse_deposited
                                                ? 'text-emerald-700'
                                                : 'text-amber-700'
                                        "
                                    >
                                        <template v-if="product.warehouse_deposited">
                                            Déposé
                                            <span v-if="product.warehouse_deposited_quantity != null">
                                                · {{ product.warehouse_deposited_quantity }}
                                                {{ product.unit }}
                                            </span>
                                            <span v-if="product.warehouse_deposited_at">
                                                · {{ product.warehouse_deposited_at }}
                                            </span>
                                        </template>
                                        <template v-else>
                                            Dépôt entrepôt requis ·
                                            {{ product.ordered_quantity }} {{ product.unit }}
                                            commandé{{ product.ordered_quantity > 1 ? 's' : '' }}
                                            <span v-if="product.deposit_deadline">
                                                · délai {{ product.deposit_deadline }}
                                            </span>
                                        </template>
                                    </p>
                                </div>
                                <div class="mt-auto flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex min-h-9 items-center border border-forest-900/15 px-3 text-xs font-medium text-forest-900"
                                        @click="openEditModal(product)"
                                    >
                                        Modifier
                                    </button>
                                    <button
                                        v-if="product.status === 'draft' && partner.can_publish"
                                        type="button"
                                        class="inline-flex min-h-9 items-center bg-forest-900 px-3 text-xs font-semibold text-white"
                                        @click="submitForReview(product.id)"
                                    >
                                        Soumettre
                                    </button>
                                    <button
                                        v-if="product.status === 'published'"
                                        type="button"
                                        class="inline-flex min-h-9 items-center border border-forest-900/15 px-3 text-xs font-medium text-forest-700"
                                        @click="unpublish(product.id)"
                                    >
                                        Dépublier
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div
                        v-else
                        class="border border-dashed border-forest-900/15 px-6 py-16 text-center"
                    >
                        <p class="text-forest-900">Aucun produit pour le moment.</p>
                        <button
                            v-if="can_create_product"
                            type="button"
                            class="mt-4 text-sm font-medium text-forest-800 underline"
                            @click="openCreateModal"
                        >
                            Créer le premier produit
                        </button>
                        <p v-else class="mt-4 text-sm text-ink-muted">
                            Attendez qu’un cargo passe en statut « En mer ».
                        </p>
                    </div>
                </div>

                <div v-else>
                    <div v-if="orders.length" class="space-y-3">
                        <article
                            v-for="order in orders"
                            :key="order.id"
                            class="border border-forest-900/10 bg-white px-4 py-4"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-forest-900">{{ order.reference }}</p>
                                    <p class="mt-1 text-sm text-ink-muted">
                                        {{ order.placed_at ?? '—' }} · {{ order.status_label }}
                                    </p>
                                </div>
                                <p class="text-sm font-medium text-forest-900">{{ order.total }}</p>
                            </div>
                            <ul class="mt-3 space-y-1 text-sm text-ink-muted">
                                <li v-for="(item, index) in order.items" :key="`${order.id}-${index}`">
                                    {{ item.quantity }}× {{ item.name }}
                                    <span class="text-forest-800">· {{ item.line_total }}</span>
                                </li>
                            </ul>
                        </article>
                    </div>
                    <p v-else class="border border-dashed border-forest-900/15 px-6 py-16 text-center text-ink-muted">
                        Aucune commande sur vos produits pour le moment.
                    </p>
                </div>

                <p class="text-sm">
                    <Link href="/arrivages" class="font-medium text-forest-800 underline">Voir les arrivages</Link>
                </p>
            </div>
        </section>

        <div
            v-if="modalOpen"
            class="fixed inset-0 z-50 flex items-end justify-center bg-forest-950/40 p-0 sm:items-center sm:p-6"
            @click.self="closeModal"
        >
            <div
                class="max-h-[92vh] w-full max-w-lg overflow-y-auto border border-forest-900/10 bg-white shadow-xl sm:max-h-[90vh]"
                role="dialog"
                aria-modal="true"
                :aria-label="modalTitle"
            >
                <div class="flex items-center justify-between border-b border-forest-900/10 px-5 py-4">
                    <h2 class="font-display text-2xl text-forest-900">{{ modalTitle }}</h2>
                    <button
                        type="button"
                        class="text-sm text-ink-muted hover:text-forest-900"
                        @click="closeModal"
                    >
                        Fermer
                    </button>
                </div>

                <form class="space-y-4 px-5 py-5" @submit="submitProduct">
                    <div>
                        <label class="text-xs font-medium text-ink-muted">Image</label>
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                            class="mt-2 block w-full text-sm text-ink-muted file:mr-3 file:border-0 file:bg-forest-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white"
                            @change="onImageChange"
                        />
                        <div v-if="imagePreview" class="mt-3 overflow-hidden border border-forest-900/10 bg-stone-soft">
                            <img
                                :src="imagePreview"
                                alt="Aperçu"
                                class="max-h-40 w-full object-cover"
                            />
                        </div>
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
                        <p
                            v-if="form.errors.image || form.errors.image_base64 || pageErrors.image"
                            class="mt-1 text-xs text-red-600"
                        >
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

                    <div v-if="!editingProduct">
                        <label class="text-xs font-medium text-ink-muted">Cargo en mer</label>
                        <select
                            v-model.number="form.cargo_id"
                            required
                            class="mt-1 h-11 w-full border border-forest-900/15 bg-white px-3"
                        >
                            <option
                                v-for="cargo in available_cargos"
                                :key="cargo.id"
                                :value="cargo.id"
                            >
                                {{ cargo.label }}{{ cargo.eta ? ` · ETA ${cargo.eta}` : '' }}
                            </option>
                        </select>
                        <p v-if="form.errors.cargo_id || pageErrors.cargo_id" class="mt-1 text-xs text-red-600">
                            {{ form.errors.cargo_id || pageErrors.cargo_id }}
                        </p>
                        <p class="mt-1 text-xs text-ink-muted">
                            Visible avec ce cargo, pas en boutique.
                        </p>
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

                    <div class="grid gap-3 sm:grid-cols-3">
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

                    <p v-if="submitError" class="text-sm text-red-600">{{ submitError }}</p>
                    <p v-if="imageWarning && modalOpen" class="text-sm text-amber-800">{{ imageWarning }}</p>

                    <button
                        type="submit"
                        class="inline-flex min-h-12 w-full items-center justify-center bg-forest-900 px-6 text-sm font-semibold text-white disabled:opacity-50"
                        :disabled="form.processing || imageUploading"
                    >
                        {{
                            imageUploading
                                ? 'Envoi de l’image…'
                                : form.processing
                                  ? 'Enregistrement…'
                                  : editingProduct
                                    ? 'Enregistrer'
                                    : 'Créer le brouillon'
                        }}
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
