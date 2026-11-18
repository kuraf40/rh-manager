<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    employees: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingEmployeeId = ref(null);

const form = useForm({
    matricule: '',
    nom: '',
    prenom: '',
    site: 'Cotonou',
    naissance: '',
    genre: 'H',
    fonction: '',
    embauche: '',
    poste_specifique: '',
    hierarchie: '',
    categorie: '',
    actif: true,
});

watch(search, (value) => {
    router.get(route('employees.index'), { search: value }, { preserveState: true, replace: true });
});

const dateForInput = (value) => {
    if (!value) return '';
    if (/^\d{4}-\d{2}-\d{2}/.test(value)) return value.slice(0, 10);

    const [day, month, year] = value.split('/');
    return year ? `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}` : '';
};

const openCreate = () => {
    editingEmployeeId.value = null;
    form.reset();
    form.site = 'Cotonou';
    form.genre = 'H';
    form.actif = true;
    showModal.value = true;
};

const openEdit = (employee) => {
    editingEmployeeId.value = employee.id;
    form.defaults({
        matricule: employee.matricule,
        nom: employee.nom,
        prenom: employee.prenom,
        site: employee.site,
        naissance: dateForInput(employee.naissance),
        genre: employee.genre,
        fonction: employee.fonction || '',
        embauche: dateForInput(employee.embauche),
        poste_specifique: employee.poste_specifique || '',
        hierarchie: employee.hierarchie || '',
        categorie: employee.categorie || '',
        actif: employee.actif,
    });
    form.reset();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingEmployeeId.value = null;
    form.reset();
};

const submit = () => {
    const options = {
        onSuccess: () => {
            closeModal();
        },
    };

    if (editingEmployeeId.value) {
        form.put(route('employees.update', editingEmployeeId.value), options);
    } else {
        form.post(route('employees.store'), options);
    }
};

const toggleStatus = (employee) => {
    router.patch(route('employees.status', employee.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Gestion des Employés" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Gestion des employés</h2>
                <div class="flex flex-wrap gap-2">
                    <a :href="route('employees.birthdays')" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Anniversaires</a>
                    <button @click="openCreate" class="rounded bg-emerald-700 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-800">
                        Nouvel employé
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-white p-4 shadow-sm">
                    <label for="employee-search" class="sr-only">Rechercher un employé</label>
                    <input id="employee-search" v-model="search" type="search" placeholder="Nom, prénom ou matricule" class="w-full max-w-md rounded border-gray-300 text-sm focus:border-emerald-700 focus:ring-emerald-700" />
                    <span class="text-sm text-gray-500">{{ employees.total }} employé(s)</span>
                </div>

                <div class="overflow-x-auto rounded-md bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Matricule</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Employé</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Fonction / Site</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Statut</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="emp in employees.data" :key="emp.id">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">{{ emp.matricule }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800">{{ emp.nom }} {{ emp.prenom }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ emp.fonction || 'Non renseignée' }}<span class="block text-xs text-gray-500">{{ emp.site }}</span></td>
                                <td class="px-4 py-3">
                                    <span :class="emp.actif ? 'bg-emerald-50 text-emerald-800' : 'bg-gray-100 text-gray-600'" class="rounded px-2 py-1 text-xs font-medium">
                                        {{ emp.actif ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <button type="button" class="mr-3 text-sm font-medium text-emerald-800 hover:underline" @click="openEdit(emp)">Modifier</button>
                                    <button type="button" class="text-sm text-gray-600 hover:underline" @click="toggleStatus(emp)">{{ emp.actif ? 'Désactiver' : 'Réactiver' }}</button>
                                </td>
                            </tr>
                            <tr v-if="employees.data.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">Aucun employé trouvé.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <nav v-if="employees.links.length > 3" class="flex flex-wrap justify-end gap-1" aria-label="Pagination">
                    <a v-for="link in employees.links" :key="link.label" :href="link.url || '#'
                        " :aria-current="link.active ? 'page' : undefined" :class="link.active ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'" class="rounded border px-3 py-2 text-sm" @click="!link.url && $event.preventDefault()">
                        {{ link.label.replace('&laquo; Previous', 'Précédent').replace('Next &raquo;', 'Suivant') }}
                    </a>
                </nav>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4" @click.self="closeModal">
            <section class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-md bg-white p-5 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="employee-form-title">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <h3 id="employee-form-title" class="text-lg font-semibold text-gray-900">{{ editingEmployeeId ? 'Modifier l’employé' : 'Nouvel employé' }}</h3>
                    <button type="button" class="rounded px-2 py-1 text-sm text-gray-600 hover:bg-gray-100" @click="closeModal">Fermer</button>
                </div>
                <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <label class="text-sm text-gray-700">Matricule<input v-model="form.matricule" required maxlength="20" class="mt-1 block w-full rounded border-gray-300 text-sm" /><span v-if="form.errors.matricule" class="text-xs text-red-700">{{ form.errors.matricule }}</span></label>
                    <label class="text-sm text-gray-700">Site<input v-model="form.site" required maxlength="50" class="mt-1 block w-full rounded border-gray-300 text-sm" /><span v-if="form.errors.site" class="text-xs text-red-700">{{ form.errors.site }}</span></label>
                    <label class="text-sm text-gray-700">Nom<input v-model="form.nom" required maxlength="100" class="mt-1 block w-full rounded border-gray-300 text-sm" /><span v-if="form.errors.nom" class="text-xs text-red-700">{{ form.errors.nom }}</span></label>
                    <label class="text-sm text-gray-700">Prénom<input v-model="form.prenom" required maxlength="100" class="mt-1 block w-full rounded border-gray-300 text-sm" /><span v-if="form.errors.prenom" class="text-xs text-red-700">{{ form.errors.prenom }}</span></label>
                    <label class="text-sm text-gray-700">Date de naissance<input v-model="form.naissance" type="date" class="mt-1 block w-full rounded border-gray-300 text-sm" /></label>
                    <label class="text-sm text-gray-700">Genre<select v-model="form.genre" class="mt-1 block w-full rounded border-gray-300 text-sm"><option value="H">Homme</option><option value="F">Femme</option></select></label>
                    <label class="text-sm text-gray-700">Fonction<input v-model="form.fonction" maxlength="150" class="mt-1 block w-full rounded border-gray-300 text-sm" /></label>
                    <label class="text-sm text-gray-700">Date d’embauche<input v-model="form.embauche" type="date" class="mt-1 block w-full rounded border-gray-300 text-sm" /></label>
                    <label class="text-sm text-gray-700">Poste spécifique<input v-model="form.poste_specifique" maxlength="150" class="mt-1 block w-full rounded border-gray-300 text-sm" /></label>
                    <label class="text-sm text-gray-700">Hiérarchie<input v-model="form.hierarchie" maxlength="150" class="mt-1 block w-full rounded border-gray-300 text-sm" /></label>
                    <label class="text-sm text-gray-700">Catégorie<input v-model="form.categorie" maxlength="50" class="mt-1 block w-full rounded border-gray-300 text-sm" /></label>
                    <label class="flex items-center gap-2 self-end pb-2 text-sm text-gray-700"><input v-model="form.actif" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-700" />Actif</label>
                    <div class="col-span-full flex justify-end gap-2 border-t border-gray-100 pt-4">
                        <button type="button" class="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700" @click="closeModal">Annuler</button>
                        <button type="submit" :disabled="form.processing" class="rounded bg-emerald-700 px-3 py-2 text-sm font-medium text-white disabled:opacity-50">{{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}</button>
                    </div>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>