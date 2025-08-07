<script setup>
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import axios from 'axios';

// Definisikan event yang akan dipancarkan ke komponen induk (Welcome.vue)
const emit = defineEmits(['switch-to-login', 'registration-successful']);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

// Fungsi submit yang telah direvisi
const submit = () => {
    // Non-aktifkan perilaku default Inertia dan gunakan axios untuk kontrol penuh
    // terhadap permintaan API dan penanganan respons JSON.
    form.processing = true; // Secara manual set status processing

    // Pastikan Anda memanggil rute API yang benar (dari routes/api.php)
    axios.post(route('api.register'), form.data())
        .then(response => {
            // Jika backend mengembalikan respons JSON dengan status sukses...
            if (response.data.status === 'success') {
                // ...pancarkan sinyal 'registration-successful' ke komponen induk (Welcome.vue)
                // dan kirim email yang baru didaftarkan sebagai payload.
                emit('registration-successful', response.data.email);
            }
        })
        .catch(error => {
            // Jika terjadi error validasi (status 422),
            // perbarui error di form secara manual.
            if (error.response && error.response.status === 422) {
                form.setError(error.response.data.errors);
            }
            // Reset field password agar pengguna tidak perlu mengetik ulang semuanya.
            form.reset('password', 'password_confirmation');
        })
        .finally(() => {
            // Setel kembali status processing setelah permintaan selesai.
            form.processing = false;
        });
};
</script>

<template>
    <form @submit.prevent="submit">
        <div class="grid gap-4">
            <div class="grid gap-2">
                <Label for="name-reg">Nama</Label>
                <Input
                    id="name-reg"
                    type="text"
                    placeholder="Nama Lengkap"
                    required
                    v-model="form.name"
                    :disabled="form.processing"
                />
                <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
            </div>

            <div class="grid gap-2">
                <Label for="email-reg">Email</Label>
                <Input
                    id="email-reg"
                    type="email"
                    placeholder="m@example.com"
                    required
                    v-model="form.email"
                    :disabled="form.processing"
                />
                <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
            </div>

            <div class="grid gap-2">
                <Label for="password-reg">Kata Sandi</Label>
                <Input
                    id="password-reg"
                    type="password"
                    required
                    v-model="form.password"
                    :disabled="form.processing"
                />
                <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation-reg">Konfirmasi Kata Sandi</Label>
                <Input
                    id="password_confirmation-reg"
                    type="password"
                    required
                    v-model="form.password_confirmation"
                    :disabled="form.processing"
                />
            </div>

            <Button type="submit" class="w-full mt-4" :disabled="form.processing">
                <span v-if="form.processing">Mendaftar...</span>
                <span v-else>Daftar</span>
            </Button>

            <p class="text-center text-sm text-muted-foreground">
                Sudah punya akun?
                <a @click="$emit('switch-to-login')" class="underline hover:text-primary cursor-pointer">
                    Masuk di sini
                </a>
            </p>
        </div>
    </form>
</template>