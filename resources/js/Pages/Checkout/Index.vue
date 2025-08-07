<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';

// Layout & Komponen UI (Asumsi Anda sudah punya)
import NavBar from '@/components/custom/NavBar.vue';
import AddressForm from '@/components/custom/AddressForm.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

// Ikon untuk UI yang lebih menarik
import { PlusCircle, MapPin, Truck, Loader2, CheckCircle2, XCircle, Shield, Package, TicketPercent, Trash2 } from 'lucide-vue-next';

// --- PROPS ---
const props = defineProps({
    cartItems: Array,
    total: Number,
    userAddresses: Array,
    errors: Object,
});

// --- STATE MANAGEMENT ---

// Form utama untuk checkout
const form = useForm({
    user_address_id: null,
    shipping_option: null,
    // Tambahkan voucher ke form agar bisa dikirim ke backend saat submit
    voucher_code: '', 
});

// State untuk alamat
const isAddressModalOpen = ref(false);
const userAddresses = ref([...props.userAddresses]);
const isAddingAddress = ref(false);

// State untuk pengiriman
const shippingOptions = ref([]);
const isLoadingShipping = ref(false);

// State untuk Voucher
const voucherCode = ref('');
const voucherError = ref('');
const voucherSuccess = ref('');
const discountAmount = ref(0);
const appliedVoucherCode = ref('');
const isApplyingVoucher = ref(false);


// --- COMPUTED PROPERTIES ---

const selectedAddress = computed(() => {
    return userAddresses.value.find(addr => addr.id === form.user_address_id) || null;
});

const shippingCost = computed(() => form.shipping_option?.price || 0);

const grandTotal = computed(() => props.total + shippingCost.value);

const finalTotal = computed(() => {
    const total = grandTotal.value - discountAmount.value;
    return total > 0 ? total : 0; // Pastikan total tidak negatif
});

const canCheckout = computed(() => {
    return form.user_address_id && form.shipping_option && !form.processing && !isLoadingShipping.value;
});


// --- METHODS ---

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
};

const getCourierIcon = (courierName) => {
    const name = courierName.toLowerCase();
    if (name.includes('jne')) return '🚚';
    if (name.includes('pos')) return '📮';
    if (name.includes('tiki')) return '📦';
    return '🚛';
};

// Logika untuk mengambil opsi pengiriman (DENGAN FALLBACK)
const loadShippingOptions = async (address) => {
    // Menggunakan district_code sebagai fallback jika shipper_area_id tidak ada
    const destinationId = address?.shipper_area_id || address?.district_code;
    if (!destinationId) {
        shippingOptions.value = [];
        form.shipping_option = null;
        return;
    }
    isLoadingShipping.value = true;
    form.shipping_option = null;
    await new Promise(resolve => setTimeout(resolve, 500));

    try {
        const totalWeight = props.cartItems.reduce((sum, item) => sum + (item.quantity * 500), 0);
        const response = await axios.post(route('api.shipping.cost'), {
            destination_id: destinationId,
            weight: totalWeight,
        });
        shippingOptions.value = response.data?.data?.pricing || [];
        if (shippingOptions.value.length > 0) {
            form.shipping_option = [...shippingOptions.value].sort((a, b) => a.price - b.price)[0];
        }
    } catch (error) {
        // --- BAGIAN FALLBACK YANG DIKEMBALIKAN ---
        console.error("Gagal memuat API pengiriman (Shipper/RajaOngkir). Menggunakan data fallback.", error);
        shippingOptions.value = [
            { type: 'REG', courier: { company: 'JNE', name: 'Regular' }, price: 18000, min_day: 2, max_day: 4 },
            { type: 'YES', courier: { company: 'JNE', name: 'Yakin Esok Sampai' }, price: 30000, min_day: 1, max_day: 1 },
            { type: 'Pos Reguler', courier: { company: 'POS Indonesia', name: 'Pos Reguler' }, price: 15000, min_day: 2, max_day: 5 },
        ];
        if (shippingOptions.value.length > 0) {
            form.shipping_option = [...shippingOptions.value].sort((a, b) => a.price - b.price)[0];
        }
    } finally {
        isLoadingShipping.value = false;
    }
};

// Logika untuk menerapkan voucher
const applyVoucher = async () => {
    if (!voucherCode.value) return;
    isApplyingVoucher.value = true;
    voucherError.value = '';
    voucherSuccess.value = '';
    
    try {
        const response = await axios.post(route('vouchers.apply'), { code: voucherCode.value });
        voucherSuccess.value = response.data.message;
        discountAmount.value = response.data.discount_amount;
        appliedVoucherCode.value = voucherCode.value;
        form.voucher_code = voucherCode.value; // Simpan kode ke form utama
        voucherCode.value = '';
    } catch (error) {
        voucherError.value = error.response?.data?.message || 'Terjadi kesalahan.';
        discountAmount.value = 0;
        appliedVoucherCode.value = '';
        form.voucher_code = '';
    } finally {
        isApplyingVoucher.value = false;
    }
};

// Logika untuk menghapus voucher
const removeVoucher = () => {
    // Idealnya, panggil route backend untuk clear session voucher
    voucherCode.value = '';
    voucherError.value = '';
    voucherSuccess.value = '';
    discountAmount.value = 0;
    appliedVoucherCode.value = '';
    form.voucher_code = '';
};


// Meng-handle alamat baru
const handleAddressSaved = async (newAddress) => {
    isAddingAddress.value = true;
    userAddresses.value.push(newAddress);
    await nextTick();
    form.user_address_id = newAddress.id;
    setTimeout(() => {
        isAddingAddress.value = false;
        isAddressModalOpen.value = false;
    }, 1500);
};

// Mengirim pesanan ke backend
const submitOrder = () => {
    form.post(route('checkout.store'));
};

// Mengawasi perubahan alamat untuk memuat ulang opsi pengiriman
watch(() => form.user_address_id, (newId) => {
    const newAddress = userAddresses.value.find(addr => addr.id === newId);
    loadShippingOptions(newAddress);
});

// Inisialisasi alamat default saat halaman dimuat
const initializeSelectedAddress = () => {
    const primaryAddress = userAddresses.value.find(addr => addr.is_primary);
    if (primaryAddress) {
        form.user_address_id = primaryAddress.id;
    } else if (userAddresses.value.length > 0) {
        form.user_address_id = userAddresses.value[0].id;
    }
};

initializeSelectedAddress();
</script>

<template>
    <Head title="Checkout" />
    <div class="bg-gray-50 min-h-screen">
        <NavBar page-type="standard" />

        <main class="container mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-24">
            <div class="text-center">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Checkout</h1>
                <p class="mt-2 text-lg text-gray-600">Satu langkah lagi untuk mendapatkan sparepart impian Anda.</p>
            </div>

            <form @submit.prevent="submitOrder">
                <div class="mt-12 grid lg:grid-cols-5 gap-x-12">
                    <div class="lg:col-span-3 space-y-8">
                        
                        <!-- Alamat Pengiriman -->
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-3"><MapPin class="w-6 h-6 text-blue-500" /> Alamat Pengiriman</h2>
                                <Button type="button" variant="outline" size="sm" @click="isAddressModalOpen = true"><PlusCircle class="w-4 h-4 mr-2" /> Tambah Alamat</Button>
                            </div>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                <RadioGroup v-if="userAddresses.length > 0" v-model="form.user_address_id" class="divide-y divide-gray-200">
                                    <div v-for="address in userAddresses" :key="address.id" class="p-4 flex items-start space-x-4 cursor-pointer hover:bg-gray-50 transition-colors" :class="{ 'bg-blue-50 border-l-4 border-blue-500': form.user_address_id === address.id }">
                                        <RadioGroupItem :value="address.id" :id="`addr-${address.id}`" class="mt-1" />
                                        <Label :for="`addr-${address.id}`" class="flex-1 cursor-pointer">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-semibold text-gray-800">{{ address.recipient_name }}</span>
                                                <Badge v-if="address.is_primary" variant="default">Utama</Badge>
                                            </div>
                                            <p class="text-sm text-gray-600">{{ address.full_address }}, {{ address.district }}, {{ address.city }}, {{ address.province }} {{ address.postal_code }}</p>
                                            <p class="text-sm text-gray-500 mt-1">{{ address.phone_number }}</p>
                                        </Label>
                                    </div>
                                </RadioGroup>
                                <div v-else class="p-8 text-center text-gray-500">Belum ada alamat tersimpan.</div>
                            </div>
                        </section>

                        <!-- Metode Pengiriman -->
                        <section>
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-3 mb-4"><Truck class="w-6 h-6 text-green-500" /> Metode Pengiriman</h2>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden min-h-[150px] flex items-center justify-center">
                                <div v-if="isLoadingShipping" class="flex flex-col items-center p-8">
                                    <Loader2 class="w-8 h-8 animate-spin text-blue-500 mb-4" />
                                    <p class="text-gray-600">Mencari opsi pengiriman...</p>
                                </div>
                                <RadioGroup v-else-if="shippingOptions.length > 0" v-model="form.shipping_option" class="divide-y divide-gray-200 w-full">
                                    <div v-for="option in shippingOptions" :key="option.type" class="p-4 flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors" :class="{ 'bg-blue-50': form.shipping_option === option }">
                                        <div class="flex items-center space-x-4">
                                            <RadioGroupItem :value="option" :id="`ship-${option.type}`" />
                                            <Label :for="`ship-${option.type}`" class="cursor-pointer">
                                                <p class="font-semibold text-gray-800">{{ option.courier.company }} - {{ option.courier.name }}</p>
                                                <p class="text-sm text-gray-500">Estimasi {{ option.min_day }}-{{ option.max_day }} hari</p>
                                            </Label>
                                        </div>
                                        <p class="font-semibold text-gray-800">{{ formatCurrency(option.price) }}</p>
                                    </div>
                                </RadioGroup>
                                <div v-else class="p-8 text-center text-gray-500">Pilih alamat untuk melihat opsi pengiriman.</div>
                            </div>
                        </section>

                    </div>

                    <!-- Ringkasan Pesanan (Sticky) -->
                    <section class="lg:col-span-2">
                         <div class="sticky top-24 bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-900 border-b pb-4 mb-4">Ringkasan Pesanan</h2>
                            
                            <!-- Voucher Section -->
                            <div class="mb-4">
                                <Label for="voucher_code" class="flex items-center gap-2 font-medium"><TicketPercent class="w-5 h-5 text-gray-500"/> Kode Voucher</Label>
                                <div v-if="appliedVoucherCode" class="mt-2 flex items-center justify-between p-3 bg-green-50 rounded-md">
                                    <div class="flex items-center gap-2">
                                        <CheckCircle2 class="w-5 h-5 text-green-600"/>
                                        <p class="text-sm font-medium text-green-700">Voucher <span class="font-bold">{{ appliedVoucherCode }}</span> diterapkan!</p>
                                    </div>
                                    <button @click="removeVoucher" type="button" class="text-gray-500 hover:text-red-600"><Trash2 class="w-4 h-4"/></button>
                                </div>
                                <div v-else class="mt-1 flex rounded-md shadow-sm">
                                    <Input v-model="voucherCode" type="text" id="voucher_code" class="flex-1 rounded-r-none" placeholder="Masukkan kode" @keyup.enter="applyVoucher" />
                                    <Button @click="applyVoucher" :disabled="isApplyingVoucher" type="button" variant="outline" class="rounded-l-none border-l-0">
                                        <Loader2 v-if="isApplyingVoucher" class="w-4 h-4 animate-spin"/>
                                        <span v-else>Terapkan</span>
                                    </Button>
                                </div>
                                <Alert v-if="voucherError" variant="destructive" class="mt-2">
                                    <XCircle class="h-4 w-4" />
                                    <AlertTitle>Gagal</AlertTitle>
                                    <AlertDescription>{{ voucherError }}</AlertDescription>
                                </Alert>
                            </div>

                            <!-- Price Breakdown -->
                            <div class="space-y-2 border-t pt-4">
                                <div class="flex justify-between text-gray-600"><span>Subtotal</span><span class="font-medium">{{ formatCurrency(total) }}</span></div>
                                <div class="flex justify-between text-gray-600"><span>Ongkos Kirim</span><span class="font-medium">{{ formatCurrency(shippingCost) }}</span></div>
                                <div v-if="discountAmount > 0" class="flex justify-between text-green-600 transition-all">
                                    <span>Diskon Voucher</span>
                                    <span class="font-medium">- {{ formatCurrency(discountAmount) }}</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold text-gray-900 border-t pt-2 mt-2">
                                    <span>Total Akhir</span>
                                    <span>{{ formatCurrency(finalTotal) }}</span>
                                </div>
                            </div>
                            
                            <Button type="submit" size="lg" class="w-full mt-6" :disabled="!canCheckout">
                                <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin mr-2" />
                                {{ form.processing ? 'Memproses...' : 'Lanjutkan ke Pembayaran' }}
                            </Button>
                            <div class="mt-4 flex items-center justify-center gap-2 text-sm text-gray-500">
                                <Shield class="w-4 h-4"/><span>Transaksi Aman & Terenkripsi</span>
                            </div>
                         </div>
                    </section>
                </div>
            </form>
        </main>

        <!-- Modal untuk Tambah Alamat -->
        <Dialog v-model:open="isAddressModalOpen">
            <DialogContent class="sm:max-w-[600px]">
                <DialogHeader><DialogTitle>Tambah Alamat Baru</DialogTitle></DialogHeader>
                <div class="mt-4 max-h-[70vh] overflow-y-auto p-1">
                    <AddressForm @close="isAddressModalOpen = false" @address-saved="handleAddressSaved" />
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
