<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue'; // Import ref dan computed
import { Button } from '@/components/ui/button'; // Asumsi Anda punya komponen ini
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card'; // Contoh komponen Card

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    canUploadProof: {
        type: Boolean,
        default: false,
    },
});

// ### LOGIKA UNTUK PEMBAYARAN MANUAL ###
const showPaymentModal = ref(false);
const showUploadForm = ref(false);

const proofForm = useForm({
    proof: null,
    bank_name: '',
    account_name: '',
    transfer_date: '',
});

const handleProceedToUpload = () => {
    showPaymentModal.value = false;
    showUploadForm.value = true;
    // Scroll ke form upload untuk UX yang lebih baik
    setTimeout(() => {
        document.getElementById('upload-form-section')?.scrollIntoView({ behavior: 'smooth' });
    }, 100);
};

const submitProof = () => {
    proofForm.post(route('orders.upload-proof', props.order.order_number), {
        forceFormData: true, // Wajib untuk upload file
        onSuccess: () => proofForm.reset(),
    });
};


// ### FUNGSI FORMATTING (SUDAH ADA) ###
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

const grandTotal = computed(() => {
    return (props.order.total_amount || 0) + (props.order.shipping_cost || 0);
});

// ### FUNGSI UNTUK STYLE (SUDAH ADA) ###
const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800 ring-1 ring-inset ring-yellow-600/20',
        processing: 'bg-blue-100 text-blue-800 ring-1 ring-inset ring-blue-600/20',
        shipped: 'bg-indigo-100 text-indigo-800 ring-1 ring-inset ring-indigo-600/20',
        completed: 'bg-green-100 text-green-800 ring-1 ring-inset ring-green-600/20',
        cancelled: 'bg-red-100 text-red-800 ring-1 ring-inset ring-red-600/20',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getPaymentStatusClass = (status) => {
    const classes = {
        unpaid: 'bg-yellow-100 text-yellow-800 ring-1 ring-inset ring-yellow-600/20',
        pending_payment: 'bg-yellow-100 text-yellow-800 ring-1 ring-inset ring-yellow-600/20',
        pending_verification: 'bg-blue-100 text-blue-800 ring-1 ring-inset ring-blue-600/20',
        paid: 'bg-green-100 text-green-800 ring-1 ring-inset ring-green-600/20',
        payment_rejected: 'bg-red-100 text-red-800 ring-1 ring-inset ring-red-600/20',
        failed: 'bg-red-100 text-red-800 ring-1 ring-inset ring-red-600/20',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head :title="`Detail Pesanan #${order.order_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('orders.index')" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detail Pesanan
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Order #{{ order.order_number }}</CardTitle>
                            <p class="text-sm text-gray-500">
                                Dipesan pada: {{ formatDate(order.created_at) }}
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <span :class="getPaymentStatusClass(order.payment_status)" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full capitalize">
                                {{ order.payment_status.replace('_', ' ') }}
                            </span>
                            <span :class="getStatusClass(order.status)" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full capitalize">
                                {{ order.status }}
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold text-gray-800">Alamat Pengiriman</h4>
                            <address class="mt-2 text-sm text-gray-600 not-italic">
                                <strong class="block">{{ order.address.recipient_name }}</strong>
                                {{ order.address.phone_number }}<br>
                                {{ order.address.full_address }}<br>
                                {{ order.address.district }}, {{ order.address.city }}<br>
                                {{ order.address.province }}, {{ order.address.postal_code }}
                            </address>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Ringkasan Biaya</h4>
                            <dl class="mt-2 space-y-2 text-sm text-gray-600">
                                <div class="flex justify-between">
                                    <dt>Subtotal</dt>
                                    <dd class="font-medium text-gray-900">{{ formatCurrency(order.total_amount) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>Ongkos Kirim</dt>
                                    <dd class="font-medium text-gray-900">{{ formatCurrency(order.shipping_cost) }}</dd>
                                </div>
                                <div class="flex justify-between pt-2 border-t font-semibold text-base text-gray-900">
                                    <dt>Grand Total</dt>
                                    <dd>{{ formatCurrency(grandTotal) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </CardContent>
                </Card>

                <div v-if="canUploadProof">
                    <Card class="text-center">
                         <CardHeader>
                            <CardTitle>Menunggu Pembayaran</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-gray-600 mb-4">Silakan lakukan pembayaran untuk melanjutkan proses pesanan Anda.</p>
                             <Button @click="showPaymentModal = true" size="lg" class="w-full sm:w-auto">
                                Bayar Sekarang
                            </Button>
                        </CardContent>
                    </Card>
                </div>


                <Card v-if="showUploadForm" id="upload-form-section">
                    <CardHeader>
                        <CardTitle>Unggah Bukti Pembayaran</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submitProof" class="space-y-4">
                            <div>
                                <Label for="proof">File Bukti Transfer (JPG, PNG, PDF)</Label>
                                <Input type="file" @input="proofForm.proof = $event.target.files[0]" id="proof" class="mt-1" />
                                <progress v-if="proofForm.progress" :value="proofForm.progress.percentage" max="100" class="w-full mt-2"></progress>
                                <p v-if="proofForm.errors.proof" class="text-sm text-destructive mt-1">{{ proofForm.errors.proof }}</p>
                            </div>
                             <div>
                                <Label for="bank_name">Nama Bank Anda</Label>
                                <Input v-model="proofForm.bank_name" type="text" id="bank_name" placeholder="Contoh: BCA" />
                                <p v-if="proofForm.errors.bank_name" class="text-sm text-destructive mt-1">{{ proofForm.errors.bank_name }}</p>
                            </div>
                             <div>
                                <Label for="account_name">Nama Pemilik Rekening</Label>
                                <Input v-model="proofForm.account_name" type="text" id="account_name" placeholder="Contoh: Budi Santoso" />
                                <p v-if="proofForm.errors.account_name" class="text-sm text-destructive mt-1">{{ proofForm.errors.account_name }}</p>
                            </div>
                            <div>
                                <Label for="transfer_date">Tanggal Transfer</Label>
                                <Input v-model="proofForm.transfer_date" type="date" id="transfer_date" />
                                <p v-if="proofForm.errors.transfer_date" class="text-sm text-destructive mt-1">{{ proofForm.errors.transfer_date }}</p>
                            </div>
                            <Button type="submit" :disabled="proofForm.processing" class="w-full">
                                Kirim Bukti Pembayaran
                            </Button>
                        </form>
                    </CardContent>
                </Card>


                <Card>
                    <CardHeader><CardTitle>Item Pesanan</CardTitle></CardHeader>
                    <CardContent>
                        <ul role="list" class="divide-y divide-gray-200">
                            <li v-for="item in order.items" :key="item.id" class="flex py-4">
                                <div class="flex-shrink-0 h-24 w-24 rounded-md border border-gray-200">
                                    <img :src="item.product.image_url ? `/storage/${item.product.image_url}` : 'https://via.placeholder.com/150'" :alt="item.product.name" class="h-full w-full object-cover object-center">
                                </div>
                                <div class="ml-4 flex flex-1 flex-col">
                                    <div>
                                        <div class="flex justify-between text-base font-medium text-gray-900">
                                            <h3>{{ item.product.name }}</h3>
                                            <p class="ml-4">{{ formatCurrency(item.price * item.quantity) }}</p>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500">{{ formatCurrency(item.price) }} / item</p>
                                    </div>
                                    <div class="flex flex-1 items-end justify-between text-sm">
                                        <p class="text-gray-500">Kuantitas: {{ item.quantity }}</p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

            </div>
        </div>

        <div v-if="showPaymentModal" @click.self="showPaymentModal = false" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4 transition-opacity">
            <div class="bg-white p-6 sm:p-8 rounded-lg shadow-xl max-w-md w-full">
                <h3 class="text-xl font-bold mb-4 text-center">Instruksi Pembayaran</h3>
                <p class="mb-2 text-center text-gray-600">Silakan lakukan transfer sejumlah:</p>
                <p class="text-3xl font-bold text-red-600 mb-6 text-center tracking-tight">
                    {{ formatCurrency(grandTotal) }}
                </p>
                <div class="text-left mb-6 bg-gray-50 p-4 rounded-md">
                    <p class="font-semibold">Bank BCA</p>
                    <p>No. Rek: <span class="font-bold">1234567890</span></p>
                    <p>a/n: <span class="font-bold">PT Vespa Sejahtera</span></p>
                </div>
                <Button @click="handleProceedToUpload" class="w-full" size="lg">
                    Saya Sudah Transfer, Lanjutkan
                </Button>
                 <Button @click="showPaymentModal = false" variant="ghost" class="w-full mt-2 text-sm">
                    Nanti Saja
                </Button>
            </div>
        </div>

    </AuthenticatedLayout>
</template>