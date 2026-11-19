<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <p class="mb-4 text-sm text-gray-600">
            Votre adresse courriel n’est pas encore vérifiée. Seul l’administrateur peut confirmer le code reçu par courriel.
        </p>

        <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            Se déconnecter
        </Link>
    </GuestLayout>
</template>
