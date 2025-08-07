<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

const props = defineProps({
    order: Object
});

const form = useForm({
    order_number: props.order.order_number,
    status: '',
});

const submitPayment = (status) => {
    form.status = status;
    form.post(route('payment.callback'));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};
</script>

<template>
    <Head title="Proses Pembayaran" />
    <div class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-center text-gray-800">Halaman Pembayaran (Simulasi)</h1>
            <p class="text-center text-gray-500 mt-2">Ini adalah halaman simulasi pembayaran Xendit.</p>

            <div class="mt-8 border-t pt-6">
                <div class="flex justify-between items-center text-gray-600">
                    <span>Order Number:</span>
                    <span class="font-mono">{{ order.order_number }}</span>
                </div>
                <div class="flex justify-between items-center text-lg font-bold text-gray-900 mt-2">
                    <span>Total Pembayaran:</span>
                    <span>{{ formatCurrency(order.total_amount) }}</span>
                </div>
            </div>

            <div class="mt-8 space-y-4">
                <p class="text-sm text-center text-gray-500">Silakan pilih status pembayaran untuk simulasi ini:</p>
                <Button @click="submitPayment('success')" class="w-full bg-green-600 hover:bg-green-700" :disabled="form.processing">
                    Simulasikan Pembayaran Berhasil
                </Button>
                <Button @click="submitPayment('failure')" class="w-full" variant="destructive" :disabled="form.processing">
                    Simulasikan Pembayaran Gagal
                </Button>
            </div>
        </div>
    </div>
</template>