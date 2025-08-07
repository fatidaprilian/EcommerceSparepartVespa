<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import NavBar from '@/components/custom/NavBar.vue';
import { Button } from '@/components/ui/button';
import ProductCard from '@/components/custom/ProductCard.vue';
import EmblaCarousel from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';
import { ArrowRight, Instagram, Facebook, Twitter } from 'lucide-vue-next';
import LoginForm from '@/components/custom/LoginForm.vue';
import RegisterForm from '@/components/custom/RegisterForm.vue';
import OtpForm from '@/components/custom/OtpForm.vue';
import { Input } from '@/components/ui/input';

// Props dari backend, termasuk produk untuk "New Arrivals"
const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    newArrivals: Array,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// --- Logika Modal Otentikasi ---
const isAuthModalOpen = ref(false);
const authState = ref('login');
const emailForVerification = ref('');
function openAuthModal() { authState.value = 'login'; isAuthModalOpen.value = true; }
function handleRegistrationSuccess(email) { emailForVerification.value = email; authState.value = 'verify'; }
function closeModal() { isAuthModalOpen.value = false; }

// "Listener" untuk membuka modal jika ada pesan flash dari backend (misal: akses ditolak)
watch(() => page.props.flash, (flash) => {
    if (flash && flash.status === 'Anda harus login untuk mengakses halaman tersebut.') {
        openAuthModal();
    }
}, { deep: true });

// --- Logika Carousel untuk Hero Section ---
const emblaRef = ref(null);
let emblaApi = null;

// --- Logika untuk Animasi Scroll ---
const philosophySectionRef = ref(null);
const isPhilosophyVisible = ref(false);
let observer;

onMounted(() => {
    // Setup Carousel
    if (emblaRef.value) {
        emblaApi = EmblaCarousel(emblaRef.value, { loop: true, align: 'start' }, [Autoplay({ delay: 6000, stopOnInteraction: false })]);
    }

    // Setup Observer untuk section filosofi
    const options = {
        root: null,
        rootMargin: '0px',
        threshold: 0.3 // Picu animasi saat 30% section terlihat
    };

    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                isPhilosophyVisible.value = true;
                observer.unobserve(entry.target); // Hentikan observasi setelah terlihat
            }
        });
    }, options);

    if (philosophySectionRef.value) {
        observer.observe(philosophySectionRef.value);
    }
});

onUnmounted(() => {
    if (emblaApi) emblaApi.destroy();
    if (observer && philosophySectionRef.value) {
        observer.unobserve(philosophySectionRef.value);
    }
});


// --- Data Testimoni (bisa diganti dengan data dari backend) ---
const testimonials = ref([
    { name: 'Doni H.', location: 'Jakarta', quote: 'Kualitas dan detailnya luar biasa. Vespa saya terasa seperti baru kembali.', image: 'https://randomuser.me/api/portraits/men/34.jpg' },
    { name: 'Rina A.', location: 'Bandung', quote: 'Setiap bagian menceritakan sebuah kisah. Saya bangga menjadi bagian dari komunitas ini.', image: 'https://randomuser.me/api/portraits/women/22.jpg' },
]);
</script>

<template>
    <Head title="VespaParts - The Art of Riding" />
    <div class="bg-white font-sans text-gray-800">
        <NavBar @open-auth-modal="openAuthModal" :show-search="true" page-type="hero" />

        <Dialog v-model:open="isAuthModalOpen">
             <DialogContent class="sm:max-w-[425px]">
                 <DialogHeader class="text-center">
                     <DialogTitle class="text-3xl font-bold text-primary">VespaParts</DialogTitle>
                     <DialogDescription v-if="authState === 'login'"> Masukkan detail Anda untuk melanjutkan.</DialogDescription>
                     <DialogDescription v-else-if="authState === 'register'"> Buat akun untuk pengalaman eksklusif.</DialogDescription>
                     <DialogDescription v-else-if="authState === 'verify'"> Verifikasi untuk mengamankan akun Anda.</DialogDescription>
                 </DialogHeader>
                 <LoginForm v-if="authState === 'login'" @switch-to-register="authState = 'register'" @close-modal="closeModal" />
                 <RegisterForm v-else-if="authState === 'register'" @switch-to-login="authState = 'login'" @registration-successful="handleRegistrationSuccess" />
                 <OtpForm v-else-if="authState === 'verify'" :email="emailForVerification" @close-modal="closeModal" />
             </DialogContent>
        </Dialog>

        <main>
            <section class="h-screen flex items-center justify-center relative text-white -mt-20">
                <div class="absolute inset-0 z-0 embla" ref="emblaRef">
                    <div class="flex h-full embla__container">
                         <div class="embla__slide relative flex-[0_0_100%] h-full">
                             <img src="https://images.unsplash.com/photo-1558981403-c5f9899a28bc?q=80&w=2070&auto=format&fit=crop" alt="Classic Scooter" class="w-full h-full object-cover animate-zoom">
                         </div>
                         <div class="embla__slide relative flex-[0_0_100%] h-full">
                             <img src="https://images.unsplash.com/photo-1558981852-426c6c22a060?q=80&w=2071&auto=format&fit=crop" alt="Scooter on the road" class="w-full h-full object-cover animate-zoom">
                         </div>
                    </div>
                </div>
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="z-10 text-center animate-fade-in-up px-4">
                    <h1 class="text-4xl sm:text-5xl lg:text-7xl font-bold tracking-tighter leading-tight">
                        Elegance in Motion.
                    </h1>
                    <p class="mt-4 text-lg sm:text-xl text-white/90 max-w-2xl mx-auto">
                        Mendefinisikan ulang perjalanan Anda dengan suku cadang yang dibuat untuk jiwa petualang.
                    </p>
                    <Link :href="route('products.index')" class="mt-8 inline-block bg-white text-primary hover:bg-gray-200 rounded-full px-8 sm:px-10 py-5 sm:py-6 text-base font-semibold transition-transform duration-300 hover:scale-105">
                        Jelajahi Koleksi
                    </Link>
                </div>
            </section>

            <section class="py-20 sm:py-28 bg-[#F8F9FA]">
                 <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-12 sm:mb-16">
                        <p class="text-sm uppercase tracking-widest text-primary font-semibold">Belanja Sekarang</p>
                        <h2 class="text-3xl sm:text-4xl font-serif tracking-tight mt-2">Koleksi Terbaru</h2>
                    </div>
                     <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <ProductCard
                            v-for="product in newArrivals"
                            :key="product.id"
                            :product="product"
                        />
                    </div>
                 </div>
            </section>

            <section class="py-20 sm:py-32 bg-white overflow-hidden" ref="philosophySectionRef">
                 <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                     <div class="grid md:grid-cols-2 gap-12 lg:gap-20 items-center">
                        <div
                            class="order-2 md:order-1 transition-all duration-1000"
                            :class="isPhilosophyVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
                        >
                            <p
                                class="text-sm uppercase tracking-widest text-primary font-semibold transition-all duration-1000 delay-200"
                                :class="isPhilosophyVisible ? 'opacity-100' : 'opacity-0'"
                            >
                                Filosofi Kami
                            </p>
                            <h2
                                class="text-3xl sm:text-4xl font-serif tracking-tight mt-2 transition-all duration-1000 delay-300"
                                :class="isPhilosophyVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-5'"
                            >
                                The Art of The Part.
                            </h2>
                            <p
                                class="mt-6 text-base sm:text-lg text-gray-600 leading-relaxed transition-all duration-1000 delay-500"
                                :class="isPhilosophyVisible ? 'opacity-100' : 'opacity-0'"
                            >
                                Bagi kami, suku cadang bukan sekadar logam dan baut. Ini adalah detak jantung dari sebuah ikon. Setiap komponen yang kami kurasi adalah sebuah janji—janji akan kualitas, warisan, dan semangat abadi dari perjalanan Vespa.
                            </p>
                             <Button
                                variant="link"
                                class="mt-6 text-primary p-0 text-base hover:text-primary/80 transition-all duration-1000 delay-700"
                                :class="isPhilosophyVisible ? 'opacity-100' : 'opacity-0'"
                            >
                                Pelajari Cerita Kami <ArrowRight class="w-4 h-4 ml-2" />
                            </Button>
                        </div>
                        <div
                            class="order-1 md:order-2 transition-all duration-1000 delay-200"
                            :class="isPhilosophyVisible ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                        >
                             <img src="https://images.unsplash.com/photo-1619472322312-d877a6f2b184?q=80&w=1974&auto=format&fit=crop" alt="Our Philosophy" class="rounded-lg shadow-2xl"/>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-28 sm:py-28 bg-[#F8F9FA]">
                 <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-24">
                        <p class="text-sm uppercase tracking-widest text-primary font-semibold">Sorotan Komunitas</p>
                        <h2 class="text-3xl sm:text-4xl font-serif tracking-tight mt-2">Dari Garasi Mereka</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-24 md:gap-x-8">
                        <div v-for="testimonial in testimonials" :key="testimonial.name" class="bg-white p-8 sm:p-10 rounded-lg flex flex-col justify-center items-center text-center shadow-lg">
                           <img :src="testimonial.image" :alt="testimonial.name" class="w-24 h-24 rounded-full mb-6 border-4 border-white -mt-20 sm:-mt-24">
                           <p class="text-lg sm:text-xl italic text-gray-700 leading-relaxed">"{{ testimonial.quote }}"</p>
                           <p class="mt-6 font-bold text-lg">{{ testimonial.name }}</p>
                           <p class="text-sm text-gray-500">{{ testimonial.location }}</p>
                        </div>
                    </div>
                 </div>
            </section>
        </main>

        <footer class="bg-gray-900 text-white">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-center md:text-left">
                     <div>
                        <h3 class="text-lg font-semibold tracking-wider">VespaParts</h3>
                        <p class="mt-4 text-gray-400 text-sm">Merayakan seni berkendara dengan suku cadang berkualitas premium.</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold tracking-wider">Tetap Terhubung</h3>
                        <p class="mt-4 text-gray-400 text-sm">Dapatkan akses awal ke koleksi terbaru dan penawaran eksklusif.</p>
                        <div class="mt-4 flex max-w-sm mx-auto md:mx-0">
                            <Input type="email" placeholder="Alamat email Anda" class="bg-gray-800 border-gray-700 text-white rounded-r-none focus:ring-primary" />
                            <Button class="bg-primary hover:bg-primary/80 rounded-l-none">Daftar</Button>
                        </div>
                    </div>
                     <div>
                        <h3 class="text-lg font-semibold tracking-wider">Ikuti Kami</h3>
                        <p class="mt-4 text-gray-400 text-sm">Jadilah bagian dari perjalanan kami di media sosial.</p>
                        <div class="flex mt-4 space-x-5 justify-center md:justify-start">
                            <a href="#" class="text-gray-400 hover:text-white"><Instagram /></a>
                            <a href="#" class="text-gray-400 hover:text-white"><Facebook /></a>
                            <a href="#" class="text-gray-400 hover:text-white"><Twitter /></a>
                        </div>
                    </div>
                </div>
                <div class="mt-16 pt-8 border-t border-gray-800 text-center text-gray-500 text-sm">
                    <p>&copy; {{ new Date().getFullYear() }} VespaParts. The Art of Riding. All Rights Reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.embla { overflow: hidden; }
.embla__container { display: flex; }
.embla__slide { flex: 0 0 100%; min-width: 0; }

/* Animasi zoom subtle untuk gambar hero yang dinamis */
@keyframes zoom {
  from { transform: scale(1); }
  to { transform: scale(1.1); }
}
.animate-zoom {
  animation: zoom 12s infinite alternate ease-in-out;
}

/* Animasi masuk untuk konten teks hero */
@keyframes fade-in-up {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
  animation: fade-in-up 1s ease-out forwards;
}

/* Mendefinisikan font serif untuk judul, menciptakan hierarki tipografi */
.font-serif {
    font-family: 'Times New Roman', Times, serif;
}
</style>