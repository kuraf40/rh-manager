<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    verification: Object,
});

const form = useForm({ code: '' });
const resendForm = useForm({});

const verify = () => {
    form.post(route('users.verification.verify', props.user.id), {
        onSuccess: () => form.reset(),
    });
};

const resend = () => {
    resendForm.post(route('users.verification.resend', props.user.id));
};

const actionLabels = {
    create: 'confirmer la création du compte',
    email: 'confirmer la nouvelle adresse courriel',
    password: 'confirmer le changement de mot de passe',
};
</script>

<template>
    <Head title="Vérification par courriel" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Vérification par courriel</h2>
                <Link :href="route('users.edit', user.id)" class="text-sm text-gray-600 hover:underline">Retour au compte</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-xl space-y-4 px-4 sm:px-6 lg:px-8">
                <section class="rounded-md bg-white p-6 shadow-sm">
                    <h3 class="font-medium text-gray-900">{{ actionLabels[verification.action] }}</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Le code a été envoyé à <strong>{{ verification.email }}</strong>. Saisissez-le ici pour finaliser l’opération concernant {{ user.name }}.
                    </p>

                    <form class="mt-5 space-y-4" @submit.prevent="verify">
                        <label for="verification-code" class="block text-sm font-medium text-gray-700">Code à 6 chiffres</label>
                        <input id="verification-code" v-model="form.code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required class="block w-full rounded border-gray-300 text-center text-lg tracking-[0.4em]" />
                        <InputError :message="form.errors.code" />
                        <div class="flex flex-wrap justify-between gap-3">
                            <button type="button" :disabled="resendForm.processing" class="text-sm text-emerald-800 underline disabled:opacity-50" @click="resend">
                                Renvoyer le code
                            </button>
                            <button type="submit" :disabled="form.processing" class="rounded bg-emerald-700 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                                {{ form.processing ? 'Vérification…' : 'Vérifier le code' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
