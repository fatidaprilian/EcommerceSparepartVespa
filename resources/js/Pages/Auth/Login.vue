<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

// Impor komponen dari shadcn-vue dengan path yang benar
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk" />
    <div class="flex items-center justify-center min-h-screen bg-background font-sans">
        <Card class="w-full max-w-sm">
            <div class="flex justify-center pt-8">
                <h1 class="text-3xl font-bold text-primary">VespaParts</h1>
            </div>

            <CardHeader class="text-center">
                <CardTitle class="text-2xl">
                    Masuk
                </CardTitle>
                <CardDescription>
                    Masukkan email untuk masuk ke akun Anda.
                </CardDescription>
            </CardHeader>

            <CardContent>
                <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                    {{ status }}
                </div>
                <form @submit.prevent="submit">
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                placeholder="m@example.com"
                                required
                                v-model="form.email"
                                autocomplete="username"
                            />
                             <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                        </div>
                        <div class="grid gap-2">
                            <div class="flex items-center">
                                <Label for="password">Kata Sandi</Label>
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="ml-auto inline-block text-sm text-muted-foreground hover:text-primary hover:underline"
                                >
                                    Lupa kata sandi?
                                </Link>
                            </div>
                            <Input
                                id="password"
                                type="password"
                                required
                                v-model="form.password"
                                autocomplete="current-password"
                            />
                            <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                        </div>
                        <div class="flex items-center space-x-2 mt-2">
                            <Checkbox id="remember" :checked="form.remember" @update:checked="val => form.remember = val" />
                            <label
                                for="remember"
                                class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                            >
                                Ingat saya
                            </label>
                        </div>
                        <Button type="submit" class="w-full mt-4" :disabled="form.processing">
                            Masuk
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>