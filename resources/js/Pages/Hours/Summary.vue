<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    rows: Array,
    rates: Object,
    period: String,
    date: String,
    startDate: String,
    endDate: String,
});

const period = ref(props.period);
const date = ref(props.date);

watch([period, date], ([nextPeriod, nextDate]) => {
    router.get(route('hours.summary'), { period: nextPeriod, date: nextDate }, { preserveState: true, replace: true });
});

const hours = (value) => Number(value || 0).toFixed(2);
</script>

<template>
    <Head title="Rapports d’heures" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Rapports d’heures</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4 rounded-md bg-white p-4 shadow-sm">
                    <fieldset>
                        <legend class="mb-2 text-xs font-semibold uppercase text-gray-500">Période</legend>
                        <div class="inline-flex overflow-hidden rounded border border-gray-300" role="group" aria-label="Période du rapport">
                            <button type="button" :aria-pressed="period === 'week'" :class="period === 'week' ? 'bg-emerald-700 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-4 py-2 text-sm" @click="period = 'week'">Semaine</button>
                            <button type="button" :aria-pressed="period === 'month'" :class="period === 'month' ? 'bg-emerald-700 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="border-l border-gray-300 px-4 py-2 text-sm" @click="period = 'month'">Mois</button>
                        </div>
                    </fieldset>
                    <label class="text-sm text-gray-700">Date de référence<input v-model="date" type="date" class="mt-1 block rounded border-gray-300 text-sm" /></label>
                    <p class="text-sm text-gray-500">Du {{ startDate }} au {{ endDate }}</p>
                </div>

                <p v-if="period === 'month'" class="text-sm text-gray-600">
                    Répartition du cycle historique : HS {{ rates.hs_12 }}% pour les 8 premières heures de jour,
                    HS {{ rates.hs_35 }}% pour les suivantes, HS {{ rates.hs_50 }}% pour les heures de nuit et de jour fériées,
                    HS {{ rates.hs_100 }}% pour les heures de nuit fériées. Les valeurs sont des heures, pas des montants salariaux.
                </p>

                <div class="overflow-x-auto rounded-md bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Employé</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Jour</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Nuit</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Férié jour</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Férié nuit</th>
                                <th v-if="period === 'month'" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">HS {{ rates.hs_12 }}%</th>
                                <th v-if="period === 'month'" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">HS {{ rates.hs_35 }}%</th>
                                <th v-if="period === 'month'" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">HS {{ rates.hs_50 }}%</th>
                                <th v-if="period === 'month'" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">HS {{ rates.hs_100 }}%</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in rows" :key="row.employee_id">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">{{ row.employee?.nom }} {{ row.employee?.prenom }}<span class="ml-2 text-gray-500">{{ row.employee?.matricule }}</span></td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ hours(row.h_jour) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ hours(row.h_nuit) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ hours(row.h_ferie_jour) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ hours(row.h_ferie_nuit) }}</td>
                                <td v-if="period === 'month'" class="px-4 py-3 text-right text-sm text-gray-700">{{ hours(row.hs_12) }}</td>
                                <td v-if="period === 'month'" class="px-4 py-3 text-right text-sm text-gray-700">{{ hours(row.hs_35) }}</td>
                                <td v-if="period === 'month'" class="px-4 py-3 text-right text-sm text-gray-700">{{ hours(row.hs_50) }}</td>
                                <td v-if="period === 'month'" class="px-4 py-3 text-right text-sm text-gray-700">{{ hours(row.hs_100) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ hours(row.total_heures) }}</td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td :colspan="period === 'month' ? 10 : 6" class="px-4 py-10 text-center text-sm text-gray-500">Aucune heure enregistrée sur cette période.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>