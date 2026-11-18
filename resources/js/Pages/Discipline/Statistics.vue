<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    employees: Array,
});
</script>

<template>
    <Head title="Statistiques disciplinaires" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Statistiques disciplinaires</h2>
                <Link :href="route('discipline.index')" class="text-sm text-gray-600 hover:underline">Retour à l’historique</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="overflow-x-auto rounded-md bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Employé</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Demandes</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Avertissements</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Blâmes</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Mises à pied</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="employee in employees" :key="employee.id">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ employee.nom }} {{ employee.prenom }}<span class="ml-2 text-gray-500">{{ employee.matricule }}</span></td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ employee.demande_explication_count }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ employee.avertissement_count }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ employee.blame_count }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ employee.mise_a_pied_count }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ employee.total_actions }}</td>
                            </tr>
                            <tr v-if="employees.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">Aucun employé.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>