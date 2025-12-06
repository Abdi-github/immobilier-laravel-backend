<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';

defineOptions({ layout: AuthLayout });

interface Props {
    status?: string;
}

const props = defineProps<Props>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Login" />

    <div class="rounded-xl bg-white p-8 shadow-sm">
        <h2 class="mb-6 text-center text-lg font-semibold text-gray-900">Sign in to your account</h2>

        <Message v-if="props.status" severity="success" class="mb-4">{{ props.status }}</Message>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <InputText
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="w-full"
                    :class="{ 'p-invalid': form.errors.email }"
                    autocomplete="email"
                    autofocus
                    data-testid="login-email"
                />
                <small v-if="form.errors.email" class="p-error">{{ form.errors.email }}</small>
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                <Password
                    id="password"
                    v-model="form.password"
                    class="w-full"
                    :class="{ 'p-invalid': form.errors.password }"
                    :feedback="false"
                    toggleMask
                    inputClass="w-full"
                    autocomplete="current-password"
                    data-testid="login-password"
                />
                <small v-if="form.errors.password" class="p-error">{{ form.errors.password }}</small>
            </div>

            <div class="flex items-center gap-2">
                <Checkbox v-model="form.remember" :binary="true" inputId="remember" data-testid="login-remember" />
                <label for="remember" class="text-sm text-gray-600">Remember me</label>
            </div>

            <Button
                type="submit"
                label="Sign in"
                class="w-full"
                :loading="form.processing"
                data-testid="login-submit"
            />
        </form>
    </div>
</template>
