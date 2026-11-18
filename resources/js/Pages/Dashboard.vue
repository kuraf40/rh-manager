<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

// Récupération des props envoyées depuis le DashboardController
defineProps({
    totalEmployees: {
        type: Number,
        default: 0,
    },
    totalHours: {
        type: Number,
        default: 0,
    },
    totalSanctions: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tableau de bord
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Carte 1 : Employés Actifs -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500">
                        <div class="text-sm font-medium text-gray-500">Total Employés Actifs</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ totalEmployees }}</div>
                    </div>

                    <!-- Carte 2 : Heures Enregistrées -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                        <div class="text-sm font-medium text-gray-500">Total Heures Travaillées</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ totalHours }} h</div>
                    </div>

                    <!-- Carte 3 : Sanctions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-red-500">
                        <div class="text-sm font-medium text-gray-500">Sanctions Enregistrées</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ totalSanctions }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>