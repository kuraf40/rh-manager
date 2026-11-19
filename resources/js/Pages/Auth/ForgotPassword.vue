<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    status: {
        type: String,
    },
    email: {
        type: String,
        default: '',
    },
});

const form = useForm({
    email: props.email,
    code: '',
    password: '',
    password_confirmation: '',
});

watch(() => props.email, (email) => {
    if (email) {
        form.email = email;
    }
});

const requestCode = () => {
    form.post(route('password.email'));
};

const resetPassword = () => {
    form.post(route('password.verify'), {
        onFinish: () => form.reset('code', 'password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Mot de passe oublié" />

        <div class="mb-4 text-sm text-gray-600">
            La récupération du mot de passe est réservée à l’administrateur. Un code sera envoyé à son adresse courriel.
        </div>

        <div
            v-if="status"
            class="mb-4 text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form v-if="!email" @submit.prevent="requestCode">
            <div>
                <InputLabel for="email" value="Adresse courriel administrateur" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="email"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Envoyer le code
                </PrimaryButton>
            </div>
        </form>

        <form v-else class="space-y-4" @submit.prevent="resetPassword">
            <input v-model="form.email" type="hidden" />
            <div>
                <InputLabel for="code" value="Code reçu par courriel" />
                <TextInput id="code" v-model="form.code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.code" />
            </div>
            <div>
                <InputLabel for="password" value="Nouveau mot de passe" />
                <TextInput id="password" v-model="form.password" type="password" minlength="12" required autocomplete="new-password" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>
            <div>
                <InputLabel for="password_confirmation" value="Confirmer le mot de passe" />
                <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" minlength="12" required autocomplete="new-password" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>
            <div class="flex justify-end">
                <div class="flex items-center gap-4">
                    <button type="button" class="text-sm text-emerald-800 underline disabled:opacity-50" :disabled="form.processing" @click="requestCode">
                        Renvoyer le code
                    </button>
                    <Link :href="route('password.request', { new: 1 })" class="text-sm text-gray-600 underline">
                        Changer d’adresse
                    </Link>
                    <PrimaryButton :disabled="form.processing">
                        Vérifier le code et modifier
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
