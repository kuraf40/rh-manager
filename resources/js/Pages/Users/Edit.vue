<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UserForm from './Form.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    user: Object,
    employees: Array,
    roles: Array,
});
</script>

<template>
    <Head title="Modifier un compte" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Modifier le compte</h2>
                <Link :href="route('users.index')" class="text-sm text-gray-600 hover:underline">Retour aux comptes</Link>
            </div>
        </template>
        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-md bg-white p-5 shadow-sm">
                    <UserForm :user="user" :employees="employees" :roles="roles" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>