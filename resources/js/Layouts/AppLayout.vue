<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Toaster } from '@/components/ui/toast';
import { useToast } from '@/components/ui/toast/use-toast';

const page = usePage();
const { toast } = useToast();

// Watcher ini akan "mendengarkan" setiap kali ada flash message dari backend
watch(() => page.props.flash, (flash) => {
    // Pastikan 'flash' tidak undefined sebelum diakses
    if (flash && flash.status) {
        toast({
            title: 'Notifikasi',
            description: flash.status,
            duration: 5000, // Tampilkan selama 5 detik
        });
    }
    if (flash && flash.error) {
            toast({
            title: 'Error',
            description: flash.error,
            variant: 'destructive',
            duration: 5000,
        });
    }
}, { deep: true });
</script>

<template>
    <div>
        <!-- Halaman kita (misal: Welcome.vue) akan dirender di sini -->
        <slot />

        <!-- Komponen ini akan menampilkan notifikasi toast di sudut layar -->
        <Toaster />
    </div>
</template>
