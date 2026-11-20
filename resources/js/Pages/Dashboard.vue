<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
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
    monthlyHours: {
        type: Array,
        default: () => [],
    },
    sanctionsByType: {
        type: Array,
        default: () => [],
    },
});

const maximumMonthlyHours = computed(() => Math.max(
    0,
    ...props.monthlyHours.map((month) => Number(month.totalHours)),
));
const maximumSanctions = computed(() => Math.max(
    0,
    ...props.sanctionsByType.map((action) => Number(action.total)),
));
const formatHours = (value) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(value || 0));
const formatMonth = (value) => {
    const [year, month] = value.split('-').map(Number);

    return new Intl.DateTimeFormat('fr-FR', { month: 'short' }).format(new Date(year, month - 1, 1));
};
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
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="overflow-hidden rounded-lg border-l-4 border-blue-500 bg-white p-6 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Employés actifs</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ totalEmployees }}</div>
                    </div>

                    <div class="overflow-hidden rounded-lg border-l-4 border-emerald-500 bg-white p-6 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Heures supplémentaires enregistrées</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ totalHours }} h</div>
                    </div>

                    <div class="overflow-hidden rounded-lg border-l-4 border-red-500 bg-white p-6 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Actions disciplinaires</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ totalSanctions }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900">Heures supplémentaires par mois</h3>
                        <p class="mt-1 text-sm text-gray-500">Évolution sur les six derniers mois</p>
                        <div class="mt-6 flex h-56 items-end justify-around gap-3 border-b border-gray-200 pb-2" role="img" aria-label="Graphique des heures supplémentaires par mois">
                            <div v-for="month in monthlyHours" :key="month.month" class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2">
                                <span class="text-xs text-gray-600">{{ formatHours(month.totalHours) }} h</span>
                                <div
                                    class="w-full max-w-12 rounded-t bg-emerald-600 transition-all"
                                    :style="{ height: maximumMonthlyHours > 0 ? `${Math.max((month.totalHours / maximumMonthlyHours) * 100, 2)}%` : '0%' }"
                                    :aria-label="`${formatHours(month.totalHours)} heures en ${formatMonth(month.month)}`"
                                ></div>
                                <span class="text-xs capitalize text-gray-500">{{ formatMonth(month.month) }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900">Sanctions par type</h3>
                        <p class="mt-1 text-sm text-gray-500">Répartition de l’historique disciplinaire</p>
                        <ul class="mt-6 space-y-5">
                            <li v-for="action in sanctionsByType" :key="action.type">
                                <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-gray-700">{{ action.label }}</span>
                                    <span class="tabular-nums text-gray-600">{{ action.total }}</span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-gray-100" role="img" :aria-label="`${action.total} ${action.label}`">
                                    <div
                                        class="h-full rounded-full bg-red-500 transition-all"
                                        :style="{ width: maximumSanctions > 0 ? `${(action.total / maximumSanctions) * 100}%` : '0%' }"
                                    ></div>
                                </div>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>