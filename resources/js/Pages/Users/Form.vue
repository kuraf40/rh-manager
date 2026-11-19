<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    employees: Array,
    roles: Array,
});

const roleLabels = {
    admin: 'Admin',
    rh: 'RH',
    manager: 'Manager',
};

const form = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    password: '',
    password_confirmation: '',
    role: props.user && props.roles.includes(props.user.role) ? props.user.role : '',
    employee_id: props.user?.employee?.id || '',
});

const submit = () => {
    if (props.user) {
        form.put(route('users.update', props.user.id));
    } else {
        form.post(route('users.store'));
    }
};
</script>

<template>
    <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="submit">
        <label class="text-sm text-gray-700">Nom<input v-model="form.name" required maxlength="255" autocomplete="name" class="mt-1 block w-full rounded border-gray-300 text-sm" /><span v-if="form.errors.name" class="text-xs text-red-700">{{ form.errors.name }}</span></label>
        <label class="text-sm text-gray-700">Adresse courriel<input v-model="form.email" required type="email" autocomplete="email" class="mt-1 block w-full rounded border-gray-300 text-sm" /><span v-if="form.errors.email" class="text-xs text-red-700">{{ form.errors.email }}</span></label>
        <label class="text-sm text-gray-700">Rôle<select v-model="form.role" required class="mt-1 block w-full rounded border-gray-300 text-sm"><option value="" disabled>-- Sélectionner un rôle --</option><option v-for="role in roles" :key="role" :value="role">{{ roleLabels[role] || role }}</option></select><span v-if="form.errors.role" class="text-xs text-red-700">{{ form.errors.role }}</span></label>
        <label class="text-sm text-gray-700">Employé associé<select v-model="form.employee_id" class="mt-1 block w-full rounded border-gray-300 text-sm"><option value="">Aucun</option><option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.nom }} {{ employee.prenom }} ({{ employee.matricule }})</option></select><span v-if="form.errors.employee_id" class="text-xs text-red-700">{{ form.errors.employee_id }}</span></label>
        <label class="text-sm text-gray-700">{{ user ? 'Nouveau mot de passe (facultatif)' : 'Mot de passe' }}<input v-model="form.password" type="password" :required="!user" minlength="12" autocomplete="new-password" class="mt-1 block w-full rounded border-gray-300 text-sm" /><span v-if="form.errors.password" class="text-xs text-red-700">{{ form.errors.password }}</span></label>
        <label class="text-sm text-gray-700">Confirmation du mot de passe<input v-model="form.password_confirmation" type="password" :required="!user && !!form.password" minlength="12" autocomplete="new-password" class="mt-1 block w-full rounded border-gray-300 text-sm" /></label>
        <div class="col-span-full flex justify-end gap-2 border-t border-gray-100 pt-4">
            <button type="submit" :disabled="form.processing" class="rounded bg-emerald-700 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">{{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}</button>
        </div>
    </form>
</template>