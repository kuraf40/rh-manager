<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UserForm from './Form.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    roles: Array,
});
</script>

<template>
    <Head title="Créer un compte" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Créer un compte</h2>
                <Link :href="route('users.index')" class="text-sm text-gray-600 hover:underline">Retour aux comptes</Link>
            </div>
        </template>
        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-md bg-white p-5 shadow-sm">
                    <UserForm :roles="roles" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>