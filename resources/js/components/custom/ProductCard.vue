<script setup>
import { Link } from '@inertiajs/vue3'; // 1. Impor Link
import { Button } from '@/components/ui/button';
import { ShoppingCart } from 'lucide-vue-next';

defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};
</script>

<template>
    <Link :href="route('products.show', product.slug)">
        <div class="border bg-card text-card-foreground rounded-xl overflow-hidden group transition-all duration-300 hover:shadow-xl hover:-translate-y-2 flex flex-col h-full">
            <div class="overflow-hidden aspect-square">
                <img :src="`/storage/${product.image_url}`" :alt="product.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
            </div>
            <div class="p-4 flex flex-col flex-grow">
                <h3 class="font-semibold truncate h-6">{{ product.name }}</h3>
                <p class="text-primary font-bold mt-2 text-lg">{{ formatCurrency(product.base_price) }}</p>
                <div class="w-full mt-auto pt-4">
                    <Button class="w-full pointer-events-none">
                        <ShoppingCart class="w-4 h-4 mr-2" />
                        Lihat Detail
                    </Button>
                </div>
            </div>
        </div>
    </Link>
</template>