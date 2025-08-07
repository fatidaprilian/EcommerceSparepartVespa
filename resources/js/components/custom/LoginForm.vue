<script setup>
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const emit = defineEmits(['switch-to-register', 'close-modal']);

defineProps({
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        preserveState: (page) => Object.keys(page.props.errors).length > 0,
        preserveScroll: true,
        onError: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
        {{ status }}
    </div>
    <form @submit.prevent="submit">
        <div class="grid gap-4">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input id="email" type="email" placeholder="m@example.com" required v-model="form.email" autocomplete="username" />
                <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
            </div>
            <div class="grid gap-2">
                <div class="flex items-center">
                    <Label for="password">Kata Sandi</Label>
                </div>
                <Input id="password" type="password" required v-model="form.password" autocomplete="current-password" />
                <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
            </div>
            <div class="flex items-center space-x-2 mt-2">
                <Checkbox id="remember-login" :checked="form.remember" @update:checked="val => form.remember = val" />
                <label for="remember-login" class="text-sm font-medium">Ingat saya</label>
            </div>
            <Button type="submit" class="w-full mt-4" :disabled="form.processing">
                Masuk
            </Button>
            <p class="text-center text-sm text-muted-foreground">
                Belum punya akun?
                <a @click="$emit('switch-to-register')" class="underline hover:text-primary cursor-pointer">
                    Daftar di sini
                </a>
            </p>
        </div>
    </form>
</template>
