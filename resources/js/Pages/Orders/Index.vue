<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        shipped: 'bg-indigo-100 text-indigo-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Riwayat Pesanan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Pesanan Saya
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 space-y-6">
                        <div v-if="orders.data.length > 0">
                            <ul role="list" class="divide-y divide-gray-200">
                                <li v-for="order in orders.data" :key="order.id">
                                    <Link :href="route('orders.show', order.order_number)" class="block hover:bg-gray-50">
                                        <div class="p-4 sm:p-6">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-medium text-primary truncate">
                                                    Order #{{ order.order_number }}
                                                </p>
                                                <div class="ml-2 flex-shrink-0 flex">
                                                    <p :class="getStatusClass(order.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                        {{ order.status }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="mt-2 sm:flex sm:justify-between">
                                                <div class="sm:flex">
                                                    <p class="flex items-center text-sm text-gray-500">
                                                        {{ formatDate(order.created_at) }}
                                                    </p>
                                                    <p class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6">
                                                        {{ order.items.length }} item
                                                    </p>
                                                </div>
                                                <div class="mt-2 flex items-center text-sm text-gray-900 font-semibold sm:mt-0">
                                                    <p>
                                                        {{ formatCurrency(order.total_amount) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                         <div v-else class="text-center py-12">
                            <p class="text-gray-500">Anda belum memiliki riwayat pesanan.</p>
                            <Link :href="route('products.index')" class="mt-4 inline-block text-primary hover:underline">
                                Mulai Belanja Sekarang
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>