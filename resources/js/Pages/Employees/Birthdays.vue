<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    birthdays: Array,
});
</script>

<template>
    <Head title="Anniversaires" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Anniversaires à venir</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-md bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-600">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-600">Employé</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-600">Fonction</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-600">Dans</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="birthday in birthdays" :key="birthday.id">
                                <td class="whitespace-nowrap px-5 py-3 text-sm text-gray-700">
                                    {{ birthday.date.split('-').reverse().join('/') }}
                                </td>
                                <td class="px-5 py-3 text-sm font-medium text-gray-900">
                                    {{ birthday.nom }} {{ birthday.prenom }}
                                    <span class="ml-2 text-gray-500">{{ birthday.matricule }}</span>
                                </td>
                                <td class="px-5 py-3 text-sm text-gray-600">{{ birthday.fonction || '-' }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-gray-700">
                                    {{ birthday.daysUntil === 0 ? "Aujourd'hui" : `${birthday.daysUntil} j` }}
                                </td>
                            </tr>
                            <tr v-if="birthdays.length === 0">
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">
                                    Aucun anniversaire à afficher.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>