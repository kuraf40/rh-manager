<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    parameters: Object,
});

const rateFields = [
    { key: 'hs_12', label: 'HS_12' },
    { key: 'hs_35', label: 'HS_35' },
    { key: 'hs_50', label: 'HS_50' },
    { key: 'hs_100', label: 'HS_100' },
];

const form = useForm({
    hs_12: props.parameters.hs_12,
    hs_35: props.parameters.hs_35,
    hs_50: props.parameters.hs_50,
    hs_100: props.parameters.hs_100,
});

const submit = () => form.put(route('hours.settings.update'));
</script>

<template>
    <Head title="Taux de majoration" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Taux de majoration</h2>
                <Link :href="route('hours.summary')" class="text-sm text-gray-600 hover:underline">Retour aux rapports</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form class="space-y-5 rounded-md bg-white p-5 shadow-sm" @submit.prevent="submit">
                    <p class="text-sm text-gray-600">
                        Ces taux s’affichent sur les paliers calculés dans le rapport mensuel (cycle du 20 au 19).
                        Le classement hérité est le suivant :
                        HS_12 = les 8 premières heures de jour, HS_35 = les heures de jour suivantes,
                        HS_50 = les heures de nuit et les heures de jour fériées, HS_100 = les heures de nuit fériées.
                        Le rapport affiche des heures et ne calcule pas de montant salarial.
                    </p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label v-for="field in rateFields" :key="field.key" class="text-sm font-medium text-gray-700">
                            Taux de la catégorie {{ field.label }}
                            <div class="mt-1 flex items-center gap-2">
                                <input v-model="form[field.key]" type="number" min="0" max="999.99" step="0.01" required class="block w-full rounded border-gray-300 text-sm" />
                                <span class="text-sm text-gray-500">%</span>
                            </div>
                            <span v-if="form.errors[field.key]" class="text-xs text-red-700">{{ form.errors[field.key] }}</span>
                        </label>
                    </div>
                    <div class="flex justify-end border-t border-gray-100 pt-4">
                        <button type="submit" :disabled="form.processing" class="rounded bg-emerald-700 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">{{ form.processing ? 'Enregistrement…' : 'Enregistrer les taux' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>