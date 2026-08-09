<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({ profile: Object });

const form = reactive(JSON.parse(JSON.stringify(props.profile || {})));
const saving = ref(false);

function addItem(key) {
    if (!form[key]) form[key] = [];
    const base = {
        services: { title: '', description: '' },
        products: { title: '', description: '', price: '', image_url: '' },
        branches: { name: '', address: '', phone: '', city: '' },
        schedule: { day: '', hours: '' },
        faqs: { question: '', answer: '' },
        team: { name: '', role: '', bio: '' },
        certifications: { name: '', issuer: '', year: '' },
    }[key] || { title: '' };
    form[key].push({ ...base });
}

function save() {
    saving.value = true;
    router.put('/app/content', form, {
        onFinish: () => (saving.value = false),
    });
}
</script>

<template>
    <AppLayout title="Contenido del negocio">
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-slate-500">El perfil de negocio alimenta tu web, el chat y la IA.</p>
            <button @click="save" :disabled="saving" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                {{ saving ? 'Guardando...' : 'Guardar' }}
            </button>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="mb-4 text-sm font-semibold text-slate-800">Información básica</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Nombre</label>
                        <input v-model="form.name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Eslogan</label>
                        <input v-model="form.tagline" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Descripción</label>
                        <textarea v-model="form.description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Industria</label>
                        <input v-model="form.industry" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Logo URL</label>
                        <input v-model="form.logo_url" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="mb-4 text-sm font-semibold text-slate-800">Contacto</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Email</label>
                        <input v-model="form.contact.email" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Teléfono</label>
                        <input v-model="form.contact.phone" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Dirección</label>
                        <input v-model="form.contact.address" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Ciudad</label>
                        <input v-model="form.contact.city" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500">WhatsApp</label>
                        <input v-model="form.contact.whatsapp" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500">Redes (instagram, facebook, linkedin, x)</label>
                        <input v-model="form.social.instagram" placeholder="instagram" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                        <input v-model="form.social.facebook" placeholder="facebook" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                        <input v-model="form.social.linkedin" placeholder="linkedin" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                </div>
            </section>

            <template v-for="cfg in [
                { key: 'services', label: 'Servicios' },
                { key: 'products', label: 'Productos' },
                { key: 'branches', label: 'Sucursales' },
                { key: 'schedule', label: 'Horarios' },
                { key: 'team', label: 'Equipo' },
                { key: 'faqs', label: 'Preguntas frecuentes' },
                { key: 'certifications', label: 'Certificaciones' },
            ]" :key="cfg.key">
                <section class="rounded-xl border border-slate-200 bg-white p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-800">{{ cfg.label }}</h3>
                        <button @click="addItem(cfg.key)" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">+ Añadir</button>
                    </div>
                    <div class="space-y-3">
                        <div v-for="(item, i) in form[cfg.key] || []" :key="i" class="rounded-lg border border-slate-200 p-3">
                            <div v-for="(v, k) in item" :key="k" class="mb-1.5">
                                <label class="block text-[11px] capitalize text-slate-400">{{ k }}</label>
                                <input
                                    v-model="item[k]"
                                    :class="{ 'min-h-16': k === 'description' || k === 'answer' || k === 'bio' }"
                                    :type="k === 'year' ? 'number' : 'text'"
                                    class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-xs"
                                />
                            </div>
                            <button @click="form[cfg.key].splice(i, 1)" class="text-xs text-red-500 hover:text-red-700">Quitar</button>
                        </div>
                        <p v-if="!(form[cfg.key] || []).length" class="text-xs text-slate-400">Sin elementos.</p>
                    </div>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
