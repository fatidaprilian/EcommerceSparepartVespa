<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/components/ui/toast/use-toast';
import axios from 'axios';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
});

// Definisikan event untuk memberi tahu parent (Welcome.vue) agar menutup modal
const emit = defineEmits(['close-modal']);

const { toast } = useToast();

// State untuk form
const verificationCode = ref('');
const errors = ref({});
const processing = ref(false);

// State untuk countdown kirim ulang
const countdown = ref(60);
const isResendDisabled = ref(true);
let timer;

const startCountdown = () => {
    isResendDisabled.value = true;
    countdown.value = 60;
    timer = setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            clearInterval(timer);
            isResendDisabled.value = false;
        }
    }, 1000);
};

const handleResend = () => {
    axios.post(route('verification.resend-otp'), { email: props.email })
        .then(response => {
            toast({
                title: 'Sukses',
                description: response.data.message,
            });
            startCountdown();
        })
        .catch(error => {
             toast({
                title: 'Error',
                description: 'Gagal mengirim ulang kode, coba lagi nanti.',
                variant: 'destructive',
            });
            // Tetap mulai countdown untuk mencegah spam klik
            startCountdown();
        });
};

// --- FUNGSI SUBMIT YANG TELAH DIREVISI MENGGUNAKAN AXIOS ---
const submit = () => {
    processing.value = true;
    errors.value = {}; // Bersihkan error sebelumnya

    axios.post(route('verification.verify-otp'), {
        email: props.email,
        verification_code: verificationCode.value,
    })
    .then(response => {
        // Jika backend mengembalikan status sukses
        if (response.data.status === 'success') {
            toast({
                title: 'Verifikasi Berhasil!',
                description: 'Anda akan dialihkan...',
            });

            // Tutup modal dan refresh halaman untuk memperbarui status login
            emit('close-modal');
            window.location.reload();
        }
    })
    .catch(error => {
        // Tangani error validasi dari Laravel (status 422)
        if (error.response && error.response.status === 422) {
            // Ambil pesan error dari respons JSON
            const validationErrors = error.response.data.errors;
            if (validationErrors && validationErrors.verification_code) {
                errors.value.verification_code = validationErrors.verification_code[0];
            } else {
                 errors.value.verification_code = 'Terjadi kesalahan validasi.';
            }
        } else {
            // Tangani error lainnya
            toast({
                title: 'Error',
                description: 'Gagal memverifikasi kode. Silakan coba lagi.',
                variant: 'destructive',
            });
        }
    })
    .finally(() => {
        processing.value = false;
    });
};

onMounted(() => {
    startCountdown();
});

onUnmounted(() => {
    clearInterval(timer);
});

</script>

<template>
    <form @submit.prevent="submit">
        <div class="grid gap-4">
            <div class="grid gap-2 text-center">
                <Label for="verification_code">Kode Verifikasi</Label>
                <p class="text-sm text-muted-foreground">
                    Kami telah mengirimkan kode ke <strong>{{ email }}</strong>
                </p>
                <Input
                    id="verification_code"
                    type="text"
                    placeholder="______"
                    class="text-center tracking-[1em]"
                    maxlength="6"
                    required
                    v-model="verificationCode"
                    autocomplete="one-time-code"
                    :disabled="processing"
                />
                <p v-if="errors.verification_code" class="text-sm text-destructive">{{ errors.verification_code }}</p>
            </div>

            <Button type="submit" class="w-full mt-4" :disabled="processing">
                <span v-if="processing">Memverifikasi...</span>
                <span v-else>Verifikasi & Masuk</span>
            </Button>

            <div class="text-center text-sm text-muted-foreground">
                <button
                    type="button"
                    @click="handleResend"
                    :disabled="isResendDisabled"
                    class="underline disabled:text-muted-foreground disabled:no-underline disabled:cursor-wait"
                >
                    <span v-if="isResendDisabled">Kirim ulang kode dalam ({{ countdown }}s)</span>
                    <span v-else>Kirim ulang kode</span>
                </button>
            </div>
        </div>
    </form>
</template>
