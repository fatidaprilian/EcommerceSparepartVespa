<script setup>
import { ref, watch, computed } from 'vue'; // Tambahkan computed
import { Head, Link, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import NavBar from '@/components/custom/NavBar.vue';
import { Button } from '@/components/ui/button';
import { ShoppingCart, Minus, Plus, Trash2 } from 'lucide-vue-next'; // Tambahkan Trash2

const props = defineProps({
    cartItems: Array,
    total: Number,
});

// Buat state lokal untuk menangani update UI instan
const localCartItems = ref(JSON.parse(JSON.stringify(props.cartItems)));

// Hitung total secara lokal agar UI responsif
const localTotal = computed(() => {
    return localCartItems.value.reduce((sum, item) => sum + (item.final_price * item.quantity), 0);
});


// Sinkronkan state lokal jika props dari server berubah
watch(() => props.cartItems, (newItems) => {
    localCartItems.value = JSON.parse(JSON.stringify(newItems));
}, { deep: true });

const formatCurrency = (value) => {
    if (typeof value !== 'number') {
        return 'Rp 0';
    }
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

// Fungsi debounced untuk mengirim pembaruan ke server
const submitUpdate = debounce((item) => {
    const form = useForm({ quantity: item.quantity });
    form.patch(route('cart.update', item.id), {
        preserveState: true,
        preserveScroll: true,
    });
}, 800); // Jeda 800ms sebelum kirim

const updateQuantity = (item, newQuantity) => {
    const quantity = Math.max(1, parseInt(newQuantity) || 1);
    item.quantity = quantity; // Update UI lokal secara instan

    // Update subtotal lokal juga
    const foundItem = localCartItems.value.find(i => i.id === item.id);
    if (foundItem) {
        foundItem.subtotal = foundItem.final_price * quantity;
    }

    submitUpdate(item); // Panggil fungsi debounced
};


const removeItem = (item) => {
    const form = useForm({});
    form.delete(route('cart.remove', item.id), {
        preserveScroll: true,
    });
};

const preventNonNumeric = (event) => {
    if (!/^[0-9]$/.test(event.key) && !['Backspace', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', 'Delete'].includes(event.key)) {
        event.preventDefault();
    }
};
</script>

<template>
    <Head title="Keranjang Belanja" />
    <div class="bg-gray-50 min-h-screen">
        <NavBar page-type="standard" />

        <main class="container mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-20">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Keranjang Belanja</h1>

            <div v-if="localCartItems.length > 0" class="mt-8 grid lg:grid-cols-3 gap-12 items-start">
                <section class="lg:col-span-2">
                    <ul role="list" class="divide-y divide-gray-200 border-y border-gray-200">
                        <li v-for="item in localCartItems" :key="item.id" class="flex py-6">
                            <div class="flex-shrink-0">
                                <img :src="item.image_url ? `/storage/${item.image_url}` : 'https://placehold.co/128x128/e2e8f0/adb5bd?text=Vespa'" :alt="item.name" class="h-24 w-24 rounded-md object-cover object-center sm:h-32 sm:w-32" />
                            </div>

                            <div class="ml-4 flex flex-1 flex-col justify-between sm:ml-6">
                                <div>
                                    <div class="flex justify-between">
                                        <h3 class="text-base font-medium text-gray-900">
                                            <Link :href="route('products.show', item.slug)">{{ item.name }}</Link>
                                        </h3>
                                        <p class="ml-4 text-base font-medium text-gray-900">{{ formatCurrency(item.subtotal) }}</p>
                                    </div>
                                    <!-- PERBAIKAN DI SINI: Gunakan item.final_price -->
                                    <p class="mt-1 text-sm text-gray-500">{{ formatCurrency(item.final_price) }}</p>
                                </div>

                                <div class="mt-4 flex flex-1 items-end justify-between">
                                    <div class="flex items-center border border-gray-300 rounded-md">
                                        <button type="button" @click="updateQuantity(item, item.quantity - 1)" class="px-3 py-1.5 text-gray-600 hover:bg-gray-100 rounded-l-md disabled:opacity-50" :disabled="item.quantity <= 1">
                                            <Minus class="w-4 h-4" />
                                        </button>
                                        <input
                                            type="text"
                                            :value="item.quantity"
                                            @input="updateQuantity(item, $event.target.value)"
                                            @keydown="preventNonNumeric"
                                            class="w-12 text-center border-0 focus:ring-0 appearance-none bg-transparent"
                                            style="-moz-appearance: textfield;"
                                        />
                                        <button type="button" @click="updateQuantity(item, item.quantity + 1)" class="px-3 py-1.5 text-gray-600 hover:bg-gray-100 rounded-r-md">
                                            <Plus class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <button @click="removeItem(item)" type="button" class="ml-4 text-sm font-medium text-red-600 hover:text-red-500 flex items-center gap-1">
                                        <Trash2 class="w-4 h-4" />
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

                <section class="rounded-lg bg-white border border-gray-200 shadow-sm p-6 lg:col-span-1 h-fit sticky top-24">
                    <h2 class="text-lg font-medium text-gray-900">Ringkasan Pesanan</h2>
                    <dl class="mt-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-600">Subtotal</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ formatCurrency(localTotal) }}</dd>
                        </div>
                        <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                            <dt class="text-base font-medium text-gray-900">Total Pesanan</dt>
                            <dd class="text-base font-medium text-gray-900">{{ formatCurrency(localTotal) }}</dd>
                        </div>
                    </dl>
                    <div class="mt-6">
                        <Link :href="route('checkout.index')">
                            <Button class="w-full" size="lg">Lanjutkan ke Checkout</Button>
                        </Link>
                    </div>
                </section>
            </div>
            <div v-else class="text-center py-20">
                <ShoppingCart class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">Keranjang Anda Kosong</h3>
                <p class="mt-1 text-sm text-gray-500">Ayo, temukan sparepart Vespa terbaik untukmu!</p>
                <div class="mt-6">
                    <Link :href="route('products.index')">
                        <Button>Mulai Belanja</Button>
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
<style scoped>
/* Sembunyikan panah di input number */
input[type='number']::-webkit-inner-spin-button,
input[type='number']::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type='number'] {
  -moz-appearance: textfield;
}
</style>
