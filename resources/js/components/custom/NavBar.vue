<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3'; // 1. Impor 'router'
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { ShoppingCart, Search, Menu, X, UserCircle, LayoutDashboard } from 'lucide-vue-next';

const props = defineProps({
    showSearch: {
        type: Boolean,
        default: true,
    },
    pageType: {
        type: String,
        default: 'hero', // 'hero' atau 'standard'
    }
});

const emit = defineEmits(['open-auth-modal']);

const page = usePage();
const user = computed(() => page.props.auth.user);
const cartCount = computed(() => page.props.cart.count);

const isMobileMenuOpen = ref(false);
const isScrolled = ref(false);

const useSolidColors = computed(() => {
    return props.pageType === 'standard' || isScrolled.value;
});

const getInitials = (name) => {
    if (!name) return '';
    const names = name.split(' ');
    if (names.length > 1) { return names[0][0] + names[names.length - 1][0]; }
    return name.substring(0, 2);
};

const handleScroll = () => {
    isScrolled.value = window.scrollY > 10;
};

onMounted(() => {
    handleScroll();
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

// 2. Buat fungsi baru untuk menangani klik pada ikon keranjang
const handleCartClick = () => {
    if (user.value) {
        // Jika user sudah login, arahkan ke halaman keranjang
        router.visit(route('cart.index'));
    } else {
        // Jika belum login, panggil event untuk membuka modal di Welcome.vue
        emit('open-auth-modal');
    }
};
</script>

<template>
    <header :class="[
        'sticky top-0 z-50 transition-all duration-300',
        pageType === 'hero' && !isScrolled ? 'bg-gradient-to-b from-black/50 to-transparent' : ''
    ]">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex-shrink-0">
                    <Link href="/" :class="['text-2xl font-bold transition-colors duration-300', useSolidColors ? 'text-primary' : 'text-white']">VespaParts</Link>
                </div>

                <div class="hidden md:flex items-center gap-3">
                    <div v-if="showSearch" class="relative">
                        <Input type="text" placeholder="Cari..."
                               :class="['pl-10 pr-4 w-52 h-10 rounded-full border transition-all duration-300 ease-in-out focus:w-64',
                               useSolidColors ? 'bg-gray-100 border-gray-200 text-gray-800 focus:bg-white focus:border-primary' : 'bg-white/20 border-white/30 text-white placeholder-gray-300 focus:bg-white focus:text-gray-800']" />
                        <Search :class="['h-5 w-5 absolute left-3 top-1/2 -translate-y-1/2 transition-colors duration-300', useSolidColors ? 'text-gray-500' : 'text-gray-300']" />
                    </div>

                    <button @click="handleCartClick" type="button" class="relative">
                        <Button variant="ghost" size="icon" :class="['transition-colors duration-300 rounded-full', useSolidColors ? 'text-gray-700 hover:text-primary hover:bg-gray-100' : 'text-white hover:bg-white/20']">
                            <ShoppingCart class="h-6 w-6" />
                            <span class="sr-only">Keranjang Belanja</span>
                        </Button>
                        <transition
                            enter-active-class="transition-transform duration-200 ease-out"
                            enter-from-class="scale-0"
                            enter-to-class="scale-100"
                        >
                            <div v-if="cartCount > 0" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
                                {{ cartCount }}
                            </div>
                        </transition>
                    </button>

                    <div v-if="!user">
                        <Button @click="$emit('open-auth-modal')"
                                :variant="useSolidColors ? 'default' : 'outline'"
                                :class="['transition-all duration-300 rounded-full',
                                         !useSolidColors && 'bg-transparent text-white border-white hover:bg-white hover:text-primary']">
                            Masuk
                        </Button>
                    </div>
                    <div v-else>
                         <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="relative h-11 w-11 rounded-full flex items-center justify-center bg-transparent transition-transform duration-300 hover:scale-110">
                                    <Avatar class="h-11 w-11 border-2" :class="useSolidColors ? 'border-primary' : 'border-white'">
                                        <AvatarFallback :class="['font-semibold', useSolidColors ? 'bg-primary/20 text-primary' : 'bg-white/20 text-white']">{{ getInitials(user.name) }}</AvatarFallback>
                                    </Avatar>
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56" align="end">
                                <DropdownMenuLabel class="font-normal">
                                    <div class="flex flex-col space-y-1">
                                        <p class="text-sm font-medium leading-none">{{ user.name }}</p>
                                        <p class="text-xs leading-none text-muted-foreground">{{ user.email }}</p>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem><Link :href="route('dashboard')" class="w-full">Dashboard</Link></DropdownMenuItem>
                                <DropdownMenuItem><Link :href="route('orders.index')" class="w-full">Pesanan Saya</Link></DropdownMenuItem>
                                <DropdownMenuItem><Link :href="route('profile.edit')" class="w-full">Profil</Link></DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem>
                                    <Link :href="route('logout')" method="post" as="button" class="w-full text-left text-red-600 font-medium">Keluar</Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <div class="md:hidden">
                    <button @click="toggleMobileMenu" :class="['transition-all duration-300 rounded-full z-[60] h-12 w-12 flex items-center justify-center', useSolidColors && !isMobileMenuOpen ? 'text-gray-700' : 'text-white']">
                        <Menu v-if="!isMobileMenuOpen" class="h-7 w-7" />
                        <X v-else class="h-7 w-7" />
                    </button>
                </div>
            </div>
        </nav>
    </header>
</template>