<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import NavBar from '@/components/custom/NavBar.vue';
import { Button } from '@/components/ui/button';
import { ShoppingCart, Minus, Plus } from 'lucide-vue-next';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const selectedQuantity = ref(1);

const form = useForm({
    quantity: 1,
});

const addToCart = () => {
    // Sync form quantity dengan selectedQuantity sebelum submit
    form.quantity = selectedQuantity.value;
    form.post(route('cart.add', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Aksi setelah sukses
        },
    });
};

const increment = () => {
    selectedQuantity.value++;
    form.quantity = selectedQuantity.value;
};

const decrement = () => {
    if (selectedQuantity.value > 1) {
        selectedQuantity.value--;
        form.quantity = selectedQuantity.value;
    }
};

// Handle manual input di text field
const handleQuantityInput = (event) => {
    let value = parseInt(event.target.value);
    
    // Jika bukan angka atau kosong, set ke 1
    if (isNaN(value) || value < 1) {
        value = 1;
    }
    
    selectedQuantity.value = value;
    form.quantity = value;
    
    // Update tampilan input
    event.target.value = value;
};

// Prevent input selain angka
const preventNonNumeric = (event) => {
    // Allow: backspace, delete, tab, escape, enter
    if ([46, 8, 9, 27, 13].indexOf(event.keyCode) !== -1 ||
        // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
        (event.keyCode === 65 && event.ctrlKey === true) ||
        (event.keyCode === 67 && event.ctrlKey === true) ||
        (event.keyCode === 86 && event.ctrlKey === true) ||
        (event.keyCode === 88 && event.ctrlKey === true)) {
        return;
    }
    // Ensure that it is a number and stop the keypress
    if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && (event.keyCode < 96 || event.keyCode > 105)) {
        event.preventDefault();
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};
</script>

<template>
    <Head :title="product.name" />
    <div class="bg-white">
        <NavBar page-type="standard" />

        <main class="container mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-20">
            <div class="grid md:grid-cols-2 gap-12 lg:gap-16">
                <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden">
                    <img
                        :src="`/storage/${product.image_url}`"
                        :alt="product.name"
                        class="w-full h-full object-cover"
                    />
                </div>

                <div class="flex flex-col justify-center">
                    <div>
                        <p v-if="product.category" class="text-sm font-medium text-primary">
                            {{ product.category.name }}
                        </p>
                        <h1 class="text-3xl lg:text-4xl font-bold tracking-tight text-gray-900 mt-2">
                            {{ product.name }}
                        </h1>
                    </div>

                    <div class="mt-4">
                        <p class="text-3xl tracking-tight text-gray-900">
                            {{ formatCurrency(product.base_price) }}
                        </p>
                    </div>

                    <div class="mt-6">
                        <h3 class="sr-only">Deskripsi</h3>
                        <div class="space-y-6 text-base text-gray-700" v-html="product.description"></div>
                    </div>

                    <form @submit.prevent="addToCart" class="mt-8 flex items-center gap-4">
                        <div class="flex items-center border border-gray-300 rounded-md">
                            <button 
                                type="button" 
                                @click="decrement" 
                                :disabled="selectedQuantity <= 1"
                                class="px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-l-md disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Minus class="h-4 w-4" />
                            </button>
                            <input 
                                type="text" 
                                :value="selectedQuantity"
                                @input="handleQuantityInput"
                                @keydown="preventNonNumeric"
                                class="w-16 text-center border-0 border-x border-gray-300 focus:ring-0 focus:border-gray-300 appearance-none"
                                style="-moz-appearance: textfield;"
                                min="1"
                            />
                            <button 
                                type="button" 
                                @click="increment" 
                                class="px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-r-md"
                            >
                                <Plus class="h-4 w-4" />
                            </button>
                        </div>

                        <Button type="submit" size="lg" class="flex-1" :disabled="form.processing">
                            <span v-if="form.processing">Menambahkan...</span>
                            <span v-else class="flex items-center justify-center">
                                <ShoppingCart class="w-5 h-5 mr-2" />
                                Tambah ke Keranjang
                            </span>
                        </Button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
/* Hide number input arrows in WebKit browsers */
input[type="text"]::-webkit-outer-spin-button,
input[type="text"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Hide number input arrows in Firefox */
input[type="text"] {
  -moz-appearance: textfield;
}
</style>