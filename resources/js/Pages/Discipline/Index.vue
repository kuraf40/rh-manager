<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    employees: Array,
    actions: Object,
});

const form = useForm({
    type: 'demande_explication',
    employee_id: '',
    code: '',
    motif: '',
    resume: '',
    nb_jours: '',
    attribue_par: '',
});

const submit = () => {
    form.post(route('discipline.store'), {
        onSuccess: () => form.reset('motif', 'resume', 'nb_jours', 'code'),
    });
};

const getTypeBadge = (type) => {
    switch (type) {
        case 'demande_explication': return { label: "Demande d'explication", color: 'bg-blue-100 text-blue-800' };
        case 'avertissement': return { label: 'Avertissement', color: 'bg-yellow-100 text-yellow-800' };
        case 'blame': return { label: 'Blâme', color: 'bg-orange-100 text-orange-800' };
        case 'mise_a_pied': return { label: 'Mise à pied', color: 'bg-red-100 text-red-800' };
        default: return { label: type, color: 'bg-gray-100 text-gray-800' };
    }
};
</script>

<template>
    <Head title="Discipline & Sanctions" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Gestion disciplinaire</h2>
                <a :href="route('discipline.statistics')" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Statistiques par employé</a>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Formulaire d'enregistrement -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Enregistrer une action disciplinaire</h3>

                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type de Sanction</label>
                            <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500">
                                <option value="demande_explication">Demande d'explication</option>
                                <option value="avertissement">Avertissement</option>
                                <option value="blame">Blâme</option>
                                <option value="mise_a_pied">Mise à pied</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employé concerné</label>
                            <select v-model="form.employee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500" required>
                                <option value="" disabled>-- Sélectionner un employé --</option>
                                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                    {{ emp.nom }} {{ emp.prenom }} ({{ emp.matricule }})
                                </option>
                            </select>
                        </div>

                        <div v-if="form.type === 'mise_a_pied'">
                            <label class="block text-sm font-medium text-gray-700">Nombre de jours</label>
                            <input v-model="form.nb_jours" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ex: 3" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Attribué par</label>
                            <input v-model="form.attribue_par" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Nom du responsable" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Motif</label>
                            <textarea v-model="form.motif" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required placeholder="Détails du motif..."></textarea>
                        </div>

                        <div class="md:col-span-2 flex justify-end">
                            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                Enregistrer la sanction
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Historique des sanctions -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Historique des Sanctions</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employé</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motif</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Par</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="act in actions.data" :key="act.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getTypeBadge(act.type).color" class="px-2 py-1 text-xs font-semibold rounded-full">
                                        {{ getTypeBadge(act.type).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ act.employee ? act.employee.nom + ' ' + act.employee.prenom : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ act.motif }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ act.attribue_par || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ new Date(act.date_enregistrement).toLocaleDateString('fr-FR') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>