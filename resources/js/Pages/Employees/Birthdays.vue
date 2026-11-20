<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    birthdays: Array,
});

const search = ref('');
const filteredBirthdays = computed(() => {
    const query = search.value.trim().toLocaleLowerCase('fr');

    if (!query) {
        return props.birthdays;
    }

    return props.birthdays.filter((birthday) => {
        const searchableValues = [
            birthday.nom,
            birthday.prenom,
            birthday.matricule,
            birthday.fonction,
            birthday.date.split('-').reverse().join('/'),
        ];

        return searchableValues.some((value) => String(value || '').toLocaleLowerCase('fr').includes(query));
    });
});
</script>

<template>
    <Head title="Anniversaires" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Anniversaires à venir</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-white p-4 shadow-sm">
                    <label for="birthday-search" class="sr-only">Rechercher un anniversaire</label>
                    <input
                        id="birthday-search"
                        v-model="search"
                        type="search"
                        placeholder="Nom, prénom, matricule ou fonction"
                        class="w-full max-w-md rounded border-gray-300 text-sm focus:border-emerald-700 focus:ring-emerald-700"
                    />
                    <span class="text-sm text-gray-500">{{ filteredBirthdays.length }} anniversaire(s)</span>
                </div>

                <div class="overflow-hidden rounded-md bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-600">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-600">Employé</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-600">Fonction</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-600">Dans</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="birthday in filteredBirthdays" :key="birthday.id">
                                <td class="whitespace-nowrap px-5 py-3 text-sm text-gray-700">
                                    {{ birthday.date.split('-').reverse().join('/') }}
                                </td>
                                <td class="px-5 py-3 text-sm font-medium text-gray-900">
                                    {{ birthday.nom }} {{ birthday.prenom }}
                                    <span class="ml-2 text-gray-500">{{ birthday.matricule }}</span>
                                </td>
                                <td class="px-5 py-3 text-sm text-gray-600">{{ birthday.fonction || '-' }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-gray-700">
                                    {{ birthday.daysUntil === 0 ? "Aujourd'hui" : `${birthday.daysUntil} j` }}
                                </td>
                            </tr>
                            <tr v-if="filteredBirthdays.length === 0">
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">
                                    {{ search ? 'Aucun anniversaire correspondant.' : 'Aucun anniversaire à afficher.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>