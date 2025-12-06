<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Password from 'primevue/password';

defineOptions({ layout: AdminLayout });

interface Props {
    profile: {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        phone: string | null;
        avatar_url: string | null;
        preferred_language: string;
        notification_preferences: Record<string, boolean>;
    };
}

const props = defineProps<Props>();

const languageOptions = [
    { label: 'English', value: 'en' },
    { label: 'Fran\u00e7ais', value: 'fr' },
    { label: 'Deutsch', value: 'de' },
    { label: 'Italiano', value: 'it' },
];

const profileForm = useForm({
    first_name: props.profile.first_name,
    last_name: props.profile.last_name,
    phone: props.profile.phone ?? '',
    preferred_language: props.profile.preferred_language,
});

function submitProfile() {
    profileForm.patch(route('admin.settings.update'), {
        preserveScroll: true,
    });
}

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submitPassword() {
    passwordForm.post(route('admin.settings.password'), {
        preserveScroll: true,
        onSuccess: () => { passwordForm.reset(); },
    });
}
</script>

<template>
    <PageHeader title="Settings" description="Manage your profile and preferences" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Profile -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="mb-4 text-lg font-semibold">Profile</h3>
            <form @submit.prevent="submitProfile" class="flex flex-col gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Email</label>
                    <InputText :modelValue="profile.email" class="w-full" disabled />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium">First Name *</label>
                        <InputText v-model="profileForm.first_name" class="w-full" :invalid="!!profileForm.errors.first_name" />
                        <small v-if="profileForm.errors.first_name" class="text-red-500">{{ profileForm.errors.first_name }}</small>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Last Name *</label>
                        <InputText v-model="profileForm.last_name" class="w-full" :invalid="!!profileForm.errors.last_name" />
                        <small v-if="profileForm.errors.last_name" class="text-red-500">{{ profileForm.errors.last_name }}</small>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Phone</label>
                    <InputText v-model="profileForm.phone" class="w-full" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Preferred Language *</label>
                    <Select
                        v-model="profileForm.preferred_language"
                        :options="languageOptions"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full"
                    />
                </div>

                <div class="flex justify-end pt-2">
                    <Button type="submit" label="Save Profile" :loading="profileForm.processing" />
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="mb-4 text-lg font-semibold">Change Password</h3>
            <form @submit.prevent="submitPassword" class="flex flex-col gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Current Password *</label>
                    <Password
                        v-model="passwordForm.current_password"
                        :feedback="false"
                        toggleMask
                        class="w-full"
                        inputClass="w-full"
                        :invalid="!!passwordForm.errors.current_password"
                    />
                    <small v-if="passwordForm.errors.current_password" class="text-red-500">{{ passwordForm.errors.current_password }}</small>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">New Password *</label>
                    <Password
                        v-model="passwordForm.password"
                        toggleMask
                        class="w-full"
                        inputClass="w-full"
                        :invalid="!!passwordForm.errors.password"
                    />
                    <small v-if="passwordForm.errors.password" class="text-red-500">{{ passwordForm.errors.password }}</small>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Confirm Password *</label>
                    <Password
                        v-model="passwordForm.password_confirmation"
                        :feedback="false"
                        toggleMask
                        class="w-full"
                        inputClass="w-full"
                    />
                </div>

                <div class="flex justify-end pt-2">
                    <Button type="submit" label="Change Password" severity="warn" :loading="passwordForm.processing" />
                </div>
            </form>
        </div>
    </div>
</template>
