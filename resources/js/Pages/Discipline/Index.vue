<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    employees: Array,
    actions: Object,
    search: String,
});

const form = useForm({
    type: 'demande_explication',
    employee_id: '',
    motif: '',
    resume: '',
    nb_jours: '',
    attribue_par: '',
});

const editingActionId = ref(null);
const search = ref(props.search || '');
let searchTimeout;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const query = value.trim();

        router.get(route('discipline.index'), query ? { search: query } : {}, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }, 300);
});

onBeforeUnmount(() => clearTimeout(searchTimeout));

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            editingActionId.value = null;
            form.reset();
        },
    };

    if (editingActionId.value) {
        form.put(route('discipline.update', editingActionId.value), options);

        return;
    }

    form.post(route('discipline.store'), options);
};

const startEditing = (action) => {
    editingActionId.value = action.id;
    form.clearErrors();
    Object.assign(form, {
        type: action.type,
        employee_id: action.employee_id,
        motif: action.motif || '',
        resume: action.resume || '',
        nb_jours: action.nb_jours || '',
        attribue_par: action.attribue_par || '',
    });
};

const cancelEditing = () => {
    editingActionId.value = null;
    form.reset();
    form.clearErrors();
};

const deleteAction = (action) => {
    const employeeName = action.employee ? `${action.employee.nom} ${action.employee.prenom}` : 'cet employé';

    if (!window.confirm(`Supprimer cette action disciplinaire pour ${employeeName} ?`)) {
        return;
    }

    router.delete(route('discipline.destroy', action.id), { preserveScroll: true });
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
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ editingActionId ? 'Modifier l’action disciplinaire' : 'Enregistrer une action disciplinaire' }}</h3>
                            <p v-if="editingActionId" class="text-sm text-gray-600">La date d’enregistrement d’origine est conservée.</p>
                        </div>
                        <button v-if="editingActionId" type="button" class="text-sm text-gray-600 underline" @click="cancelEditing">Annuler la modification</button>
                    </div>

                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type de Sanction</label>
                            <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500">
                                <option value="demande_explication">Demande d'explication</option>
                                <option value="avertissement">Avertissement</option>
                                <option value="blame">Blâme</option>
                                <option value="mise_a_pied">Mise à pied</option>
                            </select>
                            <p v-if="form.errors.type" class="mt-1 text-sm text-red-700">{{ form.errors.type }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employé concerné</label>
                            <select v-model="form.employee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500" required>
                                <option value="" disabled>-- Sélectionner un employé --</option>
                                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                    {{ emp.nom }} {{ emp.prenom }} ({{ emp.matricule }})
                                </option>
                            </select>
                            <p v-if="form.errors.employee_id" class="mt-1 text-sm text-red-700">{{ form.errors.employee_id }}</p>
                        </div>

                        <div v-if="form.type === 'mise_a_pied'">
                            <label class="block text-sm font-medium text-gray-700">Nombre de jours</label>
                            <input v-model="form.nb_jours" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ex: 3" />
                            <p v-if="form.errors.nb_jours" class="mt-1 text-sm text-red-700">{{ form.errors.nb_jours }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Attribué par</label>
                            <input v-model="form.attribue_par" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Nom du responsable" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Motif</label>
                            <textarea v-model="form.motif" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required placeholder="Détails du motif..."></textarea>
                            <p v-if="form.errors.motif" class="mt-1 text-sm text-red-700">{{ form.errors.motif }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Résumé</label>
                            <textarea v-model="form.resume" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                            <p v-if="form.errors.resume" class="mt-1 text-sm text-red-700">{{ form.errors.resume }}</p>
                        </div>

                        <div class="md:col-span-2 flex flex-wrap justify-end gap-3">
                            <button type="submit" :disabled="form.processing" class="rounded-md bg-red-600 px-4 py-2 text-white hover:bg-red-700 disabled:opacity-50">
                                {{ form.processing ? 'Enregistrement…' : editingActionId ? 'Enregistrer les modifications' : 'Enregistrer la sanction' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Historique des sanctions -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-medium text-gray-900">Historique des sanctions ({{ actions.total }})</h3>
                        <label class="sr-only" for="disciplinary-history-search">Rechercher dans l’historique des sanctions</label>
                        <input
                            id="disciplinary-history-search"
                            v-model="search"
                            type="search"
                            placeholder="Employé, matricule, type, motif…"
                            class="w-full max-w-md rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700"
                        />
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employé</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motif</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Par</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
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
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <div class="flex justify-end gap-3">
                                        <button type="button" aria-label="Modifier l’action disciplinaire" class="inline-flex h-8 w-8 items-center justify-center rounded text-emerald-700 hover:bg-emerald-50" @click="startEditing(act)">
                                            <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.651 1.651a2.121 2.121 0 0 1 0 3L9.75 17.9 5 19l1.1-4.75 8.763-8.763a2.121 2.121 0 0 1 3 0Z" />
                                            </svg>
                                        </button>
                                        <button type="button" aria-label="Supprimer l’action disciplinaire" class="inline-flex h-8 w-8 items-center justify-center rounded text-red-700 hover:bg-red-50" @click="deleteAction(act)">
                                            <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18m-2 0-.867 13.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 6m4 0V4h6v2m-5 4v7m4-7v7" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="actions.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Aucune action disciplinaire enregistrée.</td>
                            </tr>
                        </tbody>
                    </table>
                    <nav v-if="actions.links.length > 3" class="mt-4 flex flex-wrap justify-end gap-1" aria-label="Pagination des actions disciplinaires">
                        <Link
                            v-for="link in actions.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :aria-current="link.active ? 'page' : undefined"
                            :class="link.active ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                            class="rounded border px-3 py-2 text-sm"
                            :aria-label="link.label.includes('Previous') ? 'Page précédente' : link.label.includes('Next') ? 'Page suivante' : `Page ${link.label}`"
                            @click="!link.url && $event.preventDefault()"
                        >
                            {{ link.label.includes('Previous') ? '←' : link.label.includes('Next') ? '→' : link.label }}
                        </Link>
                    </nav>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>