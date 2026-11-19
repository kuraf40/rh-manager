<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Informations du profil</h2>
            <p class="mt-1 text-sm text-gray-600">
                Une modification de l’adresse courriel devra être confirmée par code.
            </p>
        </header>

        <form class="mt-6 space-y-6" @submit.prevent="form.patch(route('profile.update'))">
            <div>
                <InputLabel for="name" value="Nom" />
                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autocomplete="name" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Adresse courriel" />
                <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required autocomplete="email" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
                <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Enregistré.</p>
            </div>
        </form>
    </section>
</template>
