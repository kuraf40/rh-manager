<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    employees: Array,
    recentHours: Array,
    today: String,
});

const canViewReports = ['admin', 'rh'].includes(usePage().props.auth.user.role);

const form = useForm({
    employee_id: '',
    date_enreg: props.today,
    vacation: '07:00-15:00',
    heure_debut: '10:00',
    heure_fin: '11:00',
    type_jour: 'non_ferie',
    forcer: false,
});

const expectedHours = computed(() => {
    if (!form.heure_debut || !form.heure_fin) {
        return 0;
    }

    const toMinutes = (value) => {
        const [hours, minutes] = value.split(':').map(Number);
        return hours * 60 + minutes;
    };

    let minutes = toMinutes(form.heure_fin) - toMinutes(form.heure_debut);

    if (minutes <= 0) {
        minutes += 24 * 60;
    }

    return minutes / 60;
});

const formatHours = (value) => Number(value || 0).toFixed(2);

const submit = (force = false) => {
    form.forcer = force;
    form.post(route('hours.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('employee_id', 'heure_debut', 'heure_fin', 'type_jour', 'forcer'),
        onFinish: () => {
            form.forcer = false;
        },
    });
};
</script>

<template>
    <Head title="Heures supplémentaires" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Heures supplémentaires</h2>
                <div class="flex flex-wrap gap-2">
                    <Link v-if="canViewReports" :href="route('hours.summary')" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Rapports semaine / mois</Link>
                    <Link v-if="canViewReports" :href="route('hours.settings')" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Taux</Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-md bg-white p-5 shadow-sm">
                    <h3 class="mb-2 text-base font-semibold text-gray-900">Nouvelle saisie</h3>
                    <p class="mb-5 text-sm text-gray-600">
                        Les heures doivent correspondre à une pause autorisée ou à une période hors vacation.
                        Le total et sa répartition jour/nuit sont calculés automatiquement.
                    </p>

                    <form class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3" @submit.prevent="submit()">
                        <div>
                            <label for="employee_id" class="block text-sm font-medium text-gray-700">Magasinier</label>
                            <select
                                id="employee_id"
                                v-model="form.employee_id"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="" disabled>-- Sélectionner un magasinier --</option>
                                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                    {{ employee.nom }} {{ employee.prenom }} ({{ employee.matricule }})
                                </option>
                            </select>
                            <p v-if="form.errors.employee_id" class="mt-1 text-sm text-red-700">{{ form.errors.employee_id }}</p>
                            <p v-if="employees.length === 0" class="mt-1 text-sm text-amber-700">Aucun magasinier actif n’est disponible.</p>
                        </div>

                        <div>
                            <label for="date_enreg" class="block text-sm font-medium text-gray-700">Date</label>
                            <input
                                id="date_enreg"
                                v-model="form.date_enreg"
                                type="date"
                                :max="today"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.date_enreg" class="mt-1 text-sm text-red-700">{{ form.errors.date_enreg }}</p>
                        </div>

                        <div>
                            <label for="vacation" class="block text-sm font-medium text-gray-700">Vacation habituelle</label>
                            <select
                                id="vacation"
                                v-model="form.vacation"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="07:00-15:00">07 h à 15 h</option>
                                <option value="15:00-23:00">15 h à 23 h</option>
                                <option value="23:00-07:00">23 h à 07 h</option>
                            </select>
                            <p v-if="form.errors.vacation" class="mt-1 text-sm text-red-700">{{ form.errors.vacation }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Plage des heures supplémentaires</label>
                            <div class="mt-1 flex gap-2">
                                <input
                                    v-model="form.heure_debut"
                                    aria-label="Heure de début"
                                    type="time"
                                    step="1800"
                                    required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <input
                                    v-model="form.heure_fin"
                                    aria-label="Heure de fin"
                                    type="time"
                                    step="1800"
                                    required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Saisie par tranches de 30 minutes. Durée estimée : {{ expectedHours.toFixed(2) }} h.</p>
                            <p v-if="form.errors.heure_debut" class="mt-1 text-sm text-red-700">{{ form.errors.heure_debut }}</p>
                            <p v-if="form.errors.heure_fin" class="mt-1 text-sm text-red-700">{{ form.errors.heure_fin }}</p>
                        </div>

                        <fieldset class="md:col-span-2">
                            <legend class="block text-sm font-medium text-gray-700">Type de jour</legend>
                            <div class="mt-2 flex flex-wrap gap-5">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input v-model="form.type_jour" type="radio" value="non_ferie" class="border-gray-300 text-emerald-700 focus:ring-emerald-600" />
                                    Non férié
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input v-model="form.type_jour" type="radio" value="ferie" class="border-gray-300 text-emerald-700 focus:ring-emerald-600" />
                                    Férié
                                </label>
                            </div>
                            <p v-if="form.errors.type_jour" class="mt-1 text-sm text-red-700">{{ form.errors.type_jour }}</p>
                        </fieldset>

                        <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4 md:col-span-2 lg:col-span-3">
                            <button
                                v-if="!form.errors.forcer"
                                type="submit"
                                :disabled="form.processing || employees.length === 0"
                                class="rounded bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Enregistrement…' : 'Enregistrer les heures' }}
                            </button>

                            <div v-else class="flex flex-wrap items-center gap-3" role="alert">
                                <p class="text-sm text-amber-800">{{ form.errors.forcer }}</p>
                                <button
                                    type="button"
                                    :disabled="form.processing"
                                    class="rounded bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800 disabled:opacity-50"
                                    @click="submit(true)"
                                >
                                    Confirmer l’enregistrement
                                </button>
                                <button type="button" class="text-sm text-gray-600 underline" @click="form.clearErrors('forcer')">
                                    Annuler
                                </button>
                            </div>
                        </div>
                    </form>
                </section>

                <section class="overflow-x-auto rounded-md bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-base font-semibold text-gray-900">Dernières saisies</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Magasinier</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Vacation</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Jour</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Horaires</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Jour</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Nuit</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Férié jour</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Férié nuit</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="item in recentHours" :key="item.id">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ item.employee ? `${item.employee.nom} ${item.employee.prenom}` : 'Inconnu' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ item.date_enreg }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ item.vacation || '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                    {{ item.type_jour === 'ferie' ? 'Férié' : item.type_jour === 'non_ferie' ? 'Non férié' : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ item.heure_debut }} - {{ item.heure_fin }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ formatHours(item.h_jour) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ formatHours(item.h_nuit) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ formatHours(item.h_ferie_jour) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ formatHours(item.h_ferie_nuit) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ formatHours(item.total_heures) }} h</td>
                            </tr>
                            <tr v-if="recentHours.length === 0">
                                <td colspan="10" class="px-4 py-10 text-center text-sm text-gray-500">Aucune heure supplémentaire enregistrée.</td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
