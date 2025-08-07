<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

// Impor komponen dari shadcn-vue
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Daftar" />
    <div class="flex items-center justify-center min-h-screen bg-background font-sans py-12">
        <Card class="w-full max-w-sm">
             <div class="flex justify-center pt-8">
                <h1 class="text-3xl font-bold text-primary">VespaParts</h1>
            </div>

            <CardHeader class="text-center">
                <CardTitle class="text-2xl">
                    Buat Akun Baru
                </CardTitle>
                <CardDescription>
                    Isi data di bawah ini untuk memulai.
                </CardDescription>
            </CardHeader>

            <CardContent>
                <form @submit.prevent="submit">
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="name">Nama</Label>
                            <Input
                                id="name"
                                type="text"
                                placeholder="Nama Lengkap Anda"
                                required
                                v-model="form.name"
                                autocomplete="name"
                            />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>

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
                            <Label for="password">Kata Sandi</Label>
                            <Input
                                id="password"
                                type="password"
                                required
                                v-model="form.password"
                                autocomplete="new-password"
                            />
                            <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">Konfirmasi Kata Sandi</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                required
                                v-model="form.password_confirmation"
                                autocomplete="new-password"
                            />
                            <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">{{ form.errors.password_confirmation }}</p>
                        </div>

                        <Button type="submit" class="w-full mt-4" :disabled="form.processing">
                            Daftar
                        </Button>
                    </div>
                </form>
            </CardContent>
            <CardFooter class="flex justify-center">
                 <p class="text-sm text-muted-foreground">
                    Sudah punya akun?
                    <Link :href="route('login')" class="underline hover:text-primary">
                        Masuk di sini
                    </Link>
                </p>
            </CardFooter>
        </Card>
    </div>
</template>