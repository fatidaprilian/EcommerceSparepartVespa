<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Loader2, CheckCircle } from 'lucide-vue-next';

const emit = defineEmits(['close', 'address-saved']);

const form = useForm({
    recipient_name: '',
    phone_number: '',
    province: null,
    city: null,
    district: null,
    postal_code: '',
    full_address: '',
    label: 'Rumah',
});

const provinces = ref([]);
const cities = ref([]);
const districts = ref([]);
const isLoadingCities = ref(false);
const isLoadingDistricts = ref(false);
const isLoadingProvinces = ref(true);
const isSaving = ref(false);
const saveSuccess = ref(false);

onMounted(async () => {
    try {
        const response = await axios.get(route('api.provinces'));
        provinces.value = response.data.data;
    } catch (error) {
        console.error("Gagal mengambil data provinsi:", error);
        provinces.value = [];
    } finally {
        isLoadingProvinces.value = false;
    }
});

watch(() => form.province, async (newProvince) => {
    form.city = null;
    form.district = null;
    cities.value = [];
    districts.value = [];
    
    if (newProvince && newProvince.kode) {
        isLoadingCities.value = true;
        try {
            const response = await axios.get(route('api.cities', { provinceCode: newProvince.kode }));
            cities.value = response.data.data;
        } catch (error) {
            console.error("Gagal mengambil data kota:", error);
            cities.value = [];
        } finally {
            isLoadingCities.value = false;
        }
    }
});

watch(() => form.city, async (newCity) => {
    form.district = null;
    districts.value = [];
    
    if (newCity && newCity.kode) {
        isLoadingDistricts.value = true;
        try {
            const response = await axios.get(route('api.districts', { cityCode: newCity.kode }));
            districts.value = response.data.data;
        } catch (error) {
            console.error("Gagal mengambil data kecamatan:", error);
            districts.value = [];
        } finally {
            isLoadingDistricts.value = false;
        }
    }
});

const submitAddress = async () => {
    isSaving.value = true;
    
    try {
        // Gunakan axios langsung untuk response yang lebih kontrol
        const response = await axios.post(route('addresses.store'), form.data(), {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        });

        if (response.data.status === 'success') {
            saveSuccess.value = true;
            
            // Emit dengan data alamat baru
            setTimeout(() => {
                emit('address-saved', response.data.address);
            }, 1000); // Delay 1 detik untuk menampilkan success state
        }
    } catch (error) {
        console.error('Error saving address:', error);
        
        // Handle validation errors
        if (error.response && error.response.data && error.response.data.errors) {
            form.setError(error.response.data.errors);
        }
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <div class="max-h-[80vh] overflow-y-auto">
        <!-- Loading state untuk provinces -->
        <div v-if="isLoadingProvinces" class="flex items-center justify-center py-8">
            <Loader2 class="w-6 h-6 animate-spin mr-2" />
            <span>Memuat data...</span>
        </div>
        
        <!-- Success state -->
        <div v-else-if="saveSuccess" class="flex flex-col items-center justify-center py-8 text-green-600">
            <CheckCircle class="w-12 h-12 mb-4" />
            <h3 class="text-lg font-medium">Alamat Berhasil Disimpan!</h3>
            <p class="text-sm text-gray-500">Alamat baru sedang ditambahkan ke daftar...</p>
        </div>
        
        <!-- Form content -->
        <form v-else @submit.prevent="submitAddress" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <Label for="recipient_name">Nama Penerima <span class="text-red-500">*</span></Label>
                    <Input 
                        id="recipient_name" 
                        v-model="form.recipient_name" 
                        required 
                        :disabled="isSaving"
                        :class="{ 'border-red-500': form.errors.recipient_name }"
                    />
                    <p v-if="form.errors.recipient_name" class="text-red-500 text-sm mt-1">{{ form.errors.recipient_name }}</p>
                </div>
                <div>
                    <Label for="phone_number">Nomor Telepon <span class="text-red-500">*</span></Label>
                    <Input 
                        id="phone_number" 
                        v-model="form.phone_number" 
                        required 
                        :disabled="isSaving"
                        :class="{ 'border-red-500': form.errors.phone_number }"
                    />
                    <p v-if="form.errors.phone_number" class="text-red-500 text-sm mt-1">{{ form.errors.phone_number }}</p>
                </div>
            </div>

            <div>
                <Label for="province">Provinsi <span class="text-red-500">*</span></Label>
                <Select v-model="form.province" :disabled="isSaving">
                    <SelectTrigger :class="{ 'border-red-500': form.errors.province }">
                        <SelectValue placeholder="Pilih Provinsi" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="prov in provinces" :key="prov.kode" :value="prov">
                            {{ prov.nama }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="form.errors.province" class="text-red-500 text-sm mt-1">{{ form.errors.province }}</p>
            </div>

            <div>
                <Label for="city">Kota/Kabupaten <span class="text-red-500">*</span></Label>
                <Select v-model="form.city" :disabled="!form.province || isLoadingCities || isSaving">
                    <SelectTrigger :class="{ 'border-red-500': form.errors.city }">
                        <SelectValue :placeholder="isLoadingCities ? 'Memuat...' : 'Pilih Kota/Kabupaten'" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="city in cities" :key="city.kode" :value="city">
                            {{ city.nama }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="form.errors.city" class="text-red-500 text-sm mt-1">{{ form.errors.city }}</p>
            </div>

            <div>
                <Label for="district">Kecamatan <span class="text-red-500">*</span></Label>
                <Select v-model="form.district" :disabled="!form.city || isLoadingDistricts || isSaving">
                    <SelectTrigger :class="{ 'border-red-500': form.errors.district }">
                        <SelectValue :placeholder="isLoadingDistricts ? 'Memuat...' : 'Pilih Kecamatan'" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="district in districts" :key="district.kode" :value="district">
                            {{ district.nama }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="form.errors.district" class="text-red-500 text-sm mt-1">{{ form.errors.district }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <Label for="postal_code">Kode Pos <span class="text-red-500">*</span></Label>
                    <Input 
                        id="postal_code" 
                        v-model="form.postal_code" 
                        required 
                        :disabled="isSaving"
                        :class="{ 'border-red-500': form.errors.postal_code }"
                    />
                    <p v-if="form.errors.postal_code" class="text-red-500 text-sm mt-1">{{ form.errors.postal_code }}</p>
                </div>
                <div>
                    <Label for="label">Tandai Sebagai</Label>
                    <Input 
                        id="label" 
                        v-model="form.label" 
                        :disabled="isSaving"
                        placeholder="Contoh: Rumah, Kantor" 
                    />
                </div>
            </div>

            <div>
                <Label for="full_address">Alamat Lengkap <span class="text-red-500">*</span></Label>
                <textarea 
                    id="full_address" 
                    v-model="form.full_address" 
                    rows="3" 
                    required
                    :disabled="isSaving"
                    :class="{ 'border-red-500': form.errors.full_address }"
                    class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary/50 disabled:bg-gray-50"
                    placeholder="Nama jalan, gedung, nomor rumah, dll."
                ></textarea>
                <p v-if="form.errors.full_address" class="text-red-500 text-sm mt-1">{{ form.errors.full_address }}</p>
            </div>

            <div class="flex justify-end gap-4 pt-4 border-t">
                <Button type="button" variant="outline" :disabled="isSaving" @click="$emit('close')">
                    Batal
                </Button>
                <Button 
                    type="submit" 
                    :disabled="isSaving"
                >
                    <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin mr-2" />
                    {{ isSaving ? 'Menyimpan...' : 'Simpan Alamat' }}
                </Button>
            </div>
        </form>
    </div>
</template>