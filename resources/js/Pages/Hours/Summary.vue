<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
const formatDate = (value) => {
    const [year, month, day] = value.split('-');

    return `${day}/${month}/${year}`;
};
const reportTitle = computed(() => {
    const periodLabel = period.value === 'month' ? 'du mois' : 'de la semaine';

    return `Rapport ${periodLabel} du ${formatDate(props.startDate)} au ${formatDate(props.endDate)}`;
});

const printReport = () => window.print();

watch([period, date], ([nextPeriod, nextDate]) => {
    router.get(route('hours.summary'), { period: nextPeriod, date: nextDate }, { preserveState: true, replace: true });
});

const hours = (value) => Number(value || 0).toFixed(2);
const csvCell = (value) => {
    let cell = String(value ?? '');

    if (/^[\t\r ]*[=+\-@]/.test(cell)) {
        cell = `'${cell}`;
    }

    return `"${cell.replace(/"/g, '""')}"`;
};
const csvHours = (value) => Number(value || 0).toFixed(2).replace('.', ',');
const rateLabel = (value) => Number(value || 0).toLocaleString('fr-FR', { maximumFractionDigits: 2 });

const downloadCsv = () => {
    const headers = [
        'Employé',
        'Matricule',
        'Début de période',
        'Fin de période',
        'Jour (h)',
        'Nuit (h)',
        'Férié jour (h)',
        'Férié nuit (h)',
    ];

    if (period.value === 'month') {
        headers.push(
            `HS ${rateLabel(props.rates.hs_12)}% (h)`,
            `HS ${rateLabel(props.rates.hs_35)}% (h)`,
            `HS ${rateLabel(props.rates.hs_50)}% (h)`,
            `HS ${rateLabel(props.rates.hs_100)}% (h)`,
        );
    }

    headers.push('Total (h)');

    const records = props.rows.map((row) => {
        const values = [
            `${row.employee?.nom || ''} ${row.employee?.prenom || ''}`.trim(),
            row.employee?.matricule || '',
            props.startDate,
            props.endDate,
            csvHours(row.h_jour),
            csvHours(row.h_nuit),
            csvHours(row.h_ferie_jour),
            csvHours(row.h_ferie_nuit),
        ];

        if (period.value === 'month') {
            values.push(
                csvHours(row.hs_12),
                csvHours(row.hs_35),
                csvHours(row.hs_50),
                csvHours(row.hs_100),
            );
        }

        values.push(csvHours(row.total_heures));

        return values;
    });

    const csv = `\uFEFF${[headers, ...records].map((record) => record.map(csvCell).join(';')).join('\r\n')}`;
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    const link = document.createElement('a');

    link.href = url;
    link.download = `rapport-heures-${period.value}-${props.startDate}-${props.endDate}.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 0);
};
</script>

<template>
    <Head :title="reportTitle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">{{ reportTitle }}</h2>
                <div class="report-actions flex flex-wrap gap-2">
                    <Link :href="route('hours.index')" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Retour aux heures</Link>
                    <button type="button" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="printReport">Imprimer</button>
                    <button type="button" class="rounded bg-emerald-700 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-800" @click="downloadCsv">Télécharger CSV</button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="report-controls flex flex-wrap items-end justify-between gap-4 rounded-md bg-white p-4 shadow-sm">
                    <fieldset>
                        <legend class="mb-2 text-xs font-semibold uppercase text-gray-500">Période</legend>
                        <div class="inline-flex overflow-hidden rounded border border-gray-300" role="group" aria-label="Période du rapport">
                            <button type="button" :aria-pressed="period === 'week'" :class="period === 'week' ? 'bg-emerald-700 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-4 py-2 text-sm" @click="period = 'week'">Semaine</button>
                            <button type="button" :aria-pressed="period === 'month'" :class="period === 'month' ? 'bg-emerald-700 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="border-l border-gray-300 px-4 py-2 text-sm" @click="period = 'month'">Mois</button>
                        </div>
                    </fieldset>
                    <label class="text-sm text-gray-700">Date de référence<input v-model="date" type="date" class="mt-1 block rounded border-gray-300 text-sm" /></label>
                </div>
                <p class="text-sm text-gray-500">Période du {{ startDate }} au {{ endDate }}</p>

                <p v-if="period === 'month'" class="text-sm text-gray-600">
                    Répartition du cycle historique : HS {{ rates.hs_12 }}% pour les 8 premières heures de jour,
                    HS {{ rates.hs_35 }}% pour les suivantes, HS {{ rates.hs_50 }}% pour les heures de nuit et de jour fériées,
                    HS {{ rates.hs_100 }}% pour les heures de nuit fériées. Les valeurs sont des heures, pas des montants salariaux.
                </p>

                <div class="report-table-wrapper overflow-x-auto rounded-md bg-white shadow-sm">
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

<style>
@media print {
    nav,
    .report-actions,
    .report-controls {
        display: none !important;
    }

    .report-table-wrapper {
        overflow: visible !important;
        box-shadow: none !important;
    }
}
</style>