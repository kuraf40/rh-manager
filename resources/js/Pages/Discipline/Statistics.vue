<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    employees: Array,
});

const search = ref('');
const normalize = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();
const filteredEmployees = computed(() => {
    const query = normalize(search.value.trim());

    if (!query) {
        return props.employees;
    }

    return props.employees.filter((employee) => normalize([
        employee.nom,
        employee.prenom,
        employee.matricule,
        employee.fonction,
    ].join(' ')).includes(query));
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
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-white p-4 shadow-sm">
                    <label for="disciplinary-statistics-search" class="sr-only">Rechercher un employé</label>
                    <input id="disciplinary-statistics-search" v-model="search" type="search" placeholder="Nom, prénom, matricule ou fonction" class="w-full max-w-md rounded border-gray-300 text-sm focus:border-emerald-700 focus:ring-emerald-700" />
                    <span class="text-sm text-gray-500">{{ filteredEmployees.length }} / {{ employees.length }} employé(s)</span>
                </div>
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
                            <tr v-for="employee in filteredEmployees" :key="employee.id">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ employee.nom }} {{ employee.prenom }}<span class="ml-2 text-gray-500">{{ employee.matricule }}</span></td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ employee.demande_explication_count }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ employee.avertissement_count }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ employee.blame_count }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ employee.mise_a_pied_count }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ employee.total_actions }}</td>
                            </tr>
                            <tr v-if="filteredEmployees.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">{{ search ? 'Aucun employé correspondant.' : 'Aucun employé.' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>