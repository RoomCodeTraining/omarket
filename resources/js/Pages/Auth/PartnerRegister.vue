<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/partenaires/inscription');
}
</script>

<template>
    <AppLayout>
        <Head title="Devenir partenaire" />

        <section
            class="bg-stone-soft px-4 pb-16 sm:px-5 md:px-8 md:pb-24"
            :style="{ paddingTop: 'calc(var(--header-offset) + 1.5rem)' }"
        >
            <div class="mx-auto max-w-lg">
                <p class="text-sm tracking-[0.18em] text-brass uppercase">Partenaires</p>
                <h1 class="font-display mt-3 text-3xl text-forest-900 sm:text-4xl">
                    Créer un compte partenaire
                </h1>
                <p class="mt-3 text-sm leading-relaxed text-ink-muted">
                    Inscription libre. La publication de produits sera possible après validation
                    par Ôhéfê Market.
                </p>

                <form class="mt-8 space-y-4 border border-forest-900/10 bg-white p-5 sm:p-8" @submit.prevent="submit">
                    <div>
                        <label class="text-xs font-medium text-ink-muted">Nom / enseigne</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-ink-muted">E-mail</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-ink-muted">Mot de passe</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-ink-muted">Confirmer le mot de passe</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            class="mt-1 h-11 w-full border border-forest-900/15 px-3"
                        />
                    </div>

                    <button
                        type="submit"
                        class="inline-flex min-h-12 w-full items-center justify-center bg-forest-900 px-6 text-sm font-semibold text-white disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        Créer mon compte
                    </button>
                </form>

                <p class="mt-6 text-sm text-ink-muted">
                    Déjà partenaire ?
                    <Link href="/partenaires/connexion" class="font-semibold text-forest-800">Se connecter</Link>
                </p>
            </div>
        </section>
    </AppLayout>
</template>
