<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const roleLabels = {
    admin: 'Admin',
    rh: 'RH',
    manager: 'Manager',
};

watch(search, (value) => {
    router.get(route('users.index'), { search: value }, { preserveState: true, replace: true });
});

const deleteUser = (user) => {
    if (window.confirm(`Supprimer le compte de ${user.name} ?`)) {
        router.delete(route('users.destroy', user.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Comptes utilisateurs" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Comptes utilisateurs</h2>
                <Link :href="route('users.create')" class="rounded bg-emerald-700 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-800">Créer un compte</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-white p-4 shadow-sm">
                    <label for="user-search" class="sr-only">Rechercher un compte</label>
                    <input id="user-search" v-model="search" type="search" placeholder="Nom ou adresse courriel" class="w-full max-w-md rounded border-gray-300 text-sm focus:border-emerald-700 focus:ring-emerald-700" />
                    <span class="text-sm text-gray-500">{{ users.total }} compte(s)</span>
                </div>

                <div class="overflow-x-auto rounded-md bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Nom</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Courriel</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Rôle</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Employé associé</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="user in users.data" :key="user.id">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">{{ user.name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ user.email }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ roleLabels[user.role] || user.role }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ user.employee ? `${user.employee.nom} ${user.employee.prenom} (${user.employee.matricule})` : 'Aucun' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <Link :href="route('users.edit', user.id)" :aria-label="`Modifier le compte de ${user.name}`" class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded text-emerald-800 hover:bg-emerald-50">
                                        <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.651 1.651a2.121 2.121 0 0 1 0 3L9.75 17.9 5 19l1.1-4.75 8.763-8.763a2.121 2.121 0 0 1 3 0Z" />
                                        </svg>
                                    </Link>
                                    <button type="button" :aria-label="`Supprimer le compte de ${user.name}`" class="inline-flex h-8 w-8 items-center justify-center rounded text-red-700 hover:bg-red-50" @click="deleteUser(user)">
                                        <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18m-2 0-.867 13.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 6m4 0V4h6v2m-5 4v7m4-7v7" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">Aucun compte trouvé.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <nav v-if="users.links.length > 3" class="flex flex-wrap justify-end gap-1" aria-label="Pagination">
                    <Link v-for="link in users.links" :key="link.label" :href="link.url || '#'
                        " :aria-label="link.label.includes('Previous') ? 'Page précédente' : link.label.includes('Next') ? 'Page suivante' : `Page ${link.label}`" :aria-current="link.active ? 'page' : undefined" :class="link.active ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'" class="rounded border px-3 py-2 text-sm" @click="!link.url && $event.preventDefault()">
                        {{ link.label.includes('Previous') ? '←' : link.label.includes('Next') ? '→' : link.label }}
                    </Link>
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>