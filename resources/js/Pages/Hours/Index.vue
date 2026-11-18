<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    employees: Array,
    recentHours: Array,
});

const canViewReports = ['admin', 'rh'].includes(usePage().props.auth.user.role);

const form = useForm({
    employee_id: '',
    date_enreg: new Date().toISOString().substr(0, 10),
    heure_debut: '08:00',
    heure_fin: '17:00',
    pause_minutes: 60,
    h_jour: 8,
    h_nuit: 0,
    h_ferie_jour: 0,
    h_ferie_nuit: 0,
});

const expectedHours = computed(() => {
    if (!form.heure_debut || !form.heure_fin) return 0;

    const toMinutes = (value) => {
        const [hours, minutes] = value.split(':').map(Number);
        return hours * 60 + minutes;
    };
    let minutes = toMinutes(form.heure_fin) - toMinutes(form.heure_debut);

    if (minutes <= 0) minutes += 24 * 60;

    return Math.max(0, (minutes - Number(form.pause_minutes || 0)) / 60);
});

const submit = () => {
    form.post(route('hours.store'), {
        onSuccess: () => form.reset('h_jour', 'h_nuit', 'h_ferie_jour', 'h_ferie_nuit'),
    });
};
</script>

<template>
    <Head title="Enregistrement des Heures" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Saisie des heures</h2>
                <div class="flex flex-wrap gap-2">
                    <Link v-if="canViewReports" :href="route('hours.summary')" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Rapports semaine / mois</Link>
                    <Link v-if="canViewReports" :href="route('hours.settings')" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Taux</Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Formulaire de Saisie -->
                <div class="rounded-md bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-base font-semibold text-gray-900">Nouvelle saisie</h3>

                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employé</label>
                            <select v-model="form.employee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="" disabled>-- Sélectionner un employé --</option>
                                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                    {{ emp.nom }} {{ emp.prenom }} ({{ emp.matricule }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date</label>
                            <input v-model="form.date_enreg" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Heure de début / fin</label>
                            <div class="flex gap-2">
                                <input v-model="form.heure_debut" type="time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                                <input v-model="form.heure_fin" type="time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Pause (minutes)</label>
                            <input v-model="form.pause_minutes" type="number" min="0" max="720" step="15" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                            <p class="mt-1 text-xs text-gray-500">Durée nette calculée: {{ expectedHours.toFixed(2) }} h</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 md:col-span-3 lg:grid-cols-4">
                            <label class="text-sm font-medium text-gray-700">Jour<input v-model="form.h_jour" type="number" min="0" max="24" step="0.25" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" /></label>
                            <label class="text-sm font-medium text-gray-700">Nuit<input v-model="form.h_nuit" type="number" min="0" max="24" step="0.25" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" /></label>
                            <label class="text-sm font-medium text-gray-700">Férié jour<input v-model="form.h_ferie_jour" type="number" min="0" max="24" step="0.25" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" /></label>
                            <label class="text-sm font-medium text-gray-700">Férié nuit<input v-model="form.h_ferie_nuit" type="number" min="0" max="24" step="0.25" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" /></label>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 md:col-span-3">
                            <p v-if="form.errors.h_jour" class="text-sm text-red-700">{{ form.errors.h_jour }}</p>
                            <button type="submit" :disabled="form.processing" class="rounded bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50">
                                {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tableau Récapitulatif -->
                <div class="overflow-x-auto rounded-md bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-base font-semibold text-gray-900">Dernières saisies</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employé</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plage Horaire</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total (h)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="item in recentHours" :key="item.id">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ item.employee ? item.employee.nom + ' ' + item.employee.prenom : 'Inconnu' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ item.date_enreg }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ item.heure_debut }} - {{ item.heure_fin }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-green-600">{{ item.total_heures }} h</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>