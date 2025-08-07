<script setup>
import { Head, Link } from '@inertiajs/vue3';
import NavBar from '@/components/custom/NavBar.vue';
import ProductCard from '@/components/custom/ProductCard.vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

// Menerima props 'products' yang dikirim dari ProductController
// Data ini sudah termasuk informasi pagination dari Laravel
defineProps({
    products: {
        type: Object,
        required: true,
    }
});
</script>

<template>
    <Head title="Katalog Produk" />
    <div class="bg-white font-sans text-gray-800">
        <NavBar page-type="standard" />

        <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Koleksi Kami</h1>
                <p class="mt-4 text-lg leading-8 text-gray-600">
                    Jelajahi semua suku cadang berkualitas premium untuk menyempurnakan Vespa Anda.
                </p>
            </div>

            <div v-if="products.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <ProductCard
                    v-for="product in products.data"
                    :key="product.id"
                    :product="product"
                />
            </div>
            <div v-else class="text-center py-16">
                <p class="text-gray-500">Belum ada produk untuk ditampilkan.</p>
            </div>

            <div v-if="products.links.length > 3" class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6 mt-12">
                <div class="flex flex-1 justify-between sm:hidden">
                    <Link :href="products.prev_page_url" :disabled="!products.prev_page_url" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Previous</Link>
                    <Link :href="products.next_page_url" :disabled="!products.next_page_url" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Next</Link>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Menampilkan
                            <span class="font-medium">{{ products.from }}</span>
                            -
                            <span class="font-medium">{{ products.to }}</span>
                            dari
                            <span class="font-medium">{{ products.total }}</span>
                            hasil
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <Link
                                v-for="(link, index) in products.links"
                                :key="index"
                                :href="link.url"
                                class="relative inline-flex items-center px-4 py-2 text-sm font-semibold"
                                :class="{
                                    'bg-primary text-white focus-visible:outline-primary': link.active,
                                    'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0': !link.active,
                                    'rounded-l-md': index === 0,
                                    'rounded-r-md': index === products.links.length - 1,
                                }"
                                v-html="link.label"
                            >
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>

        </main>
    </div>
</template>