<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { CheckCircle } from 'lucide-vue-next';

</script>

<template>
    <Head title="Pesanan Berhasil" />
    <div class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
         <div class="w-full max-w-lg bg-white rounded-lg shadow-lg p-8 text-center">
             <CheckCircle class="mx-auto h-16 w-16 text-green-500" />
             <h1 class="mt-4 text-2xl font-bold text-gray-800">Terima Kasih!</h1>
             <p class="mt-2 text-gray-600">Pesanan Anda telah berhasil kami terima dan akan segera kami proses.</p>
             <p class="mt-1 text-sm text-gray-500">Anda akan menerima notifikasi lebih lanjut mengenai status pesanan Anda.</p>
             <div class="mt-8">
                 <Link :href="route('dashboard')">
                    <Button>Lanjut ke Dashboard</Button>
                 </Link>
             </div>
         </div>
    </div>
</template>