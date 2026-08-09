<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({ domains: Array, platform_domain: String, default_url: String });

const host = ref('');

function addDomain() {
    if (!host.value.trim()) return;
    router.post('/app/domains', { host: host.value }, {
        onSuccess: () => (host.value = ''),
    });
}

function verify(d) {
    router.post(`/app/domains/${d.id}/verify`);
}

function makePrimary(d) {
    router.post(`/app/domains/${d.id}/primary`);
}

function remove(d) {
    if (!confirm(`¿Eliminar ${d.host}?`)) return;
    router.delete(`/app/domains/${d.id}`);
}

function copy(text) {
    navigator.clipboard?.writeText(text);
}
</script>

<template>
    <AppLayout title="Dominios">
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="mb-1 text-sm font-semibold text-slate-800">Conectar dominio</h3>
                <p class="mb-4 text-xs text-slate-500">
                    Añade tu dominio o un subdominio. Tu URL gratuita es
                    <button @click="copy(default_url)" class="font-medium text-indigo-600 hover:underline">{{ default_url }}</button>
                    .
                </p>
                <div class="space-y-3 text-sm">
                    <input v-model="host" placeholder="ej. miempresa.com" class="w-full rounded-lg border border-slate-300 px-3 py-2" @keyup.enter="addDomain" />
                    <button @click="addDomain" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Agregar dominio</button>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2">
                <h3 class="mb-3 text-sm font-semibold text-slate-800">Tus dominios</h3>
                <div class="space-y-3">
                    <div v-for="d in domains" :key="d.id" class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-slate-800">{{ d.host }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                :class="d.status === 'verified' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">
                                {{ d.status }}
                            </span>
                            <span v-if="d.is_primary" class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-medium text-indigo-700">Principal</span>
                            <div class="ml-auto flex items-center gap-2">
                                <button v-if="!d.is_primary" @click="makePrimary(d)" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Hacer principal</button>
                                <button v-if="d.status !== 'verified'" class="text-xs font-medium text-slate-500 hover:text-slate-700">Verificar</button>
                                <button @click="remove(d)" class="text-xs text-red-500 hover:text-red-700">Eliminar</button>
                            </div>
                        </div>
                        <div v-if="d.status !== 'verified'" class="mt-3 rounded-lg bg-slate-50 p-3">
                            <p class="text-xs text-slate-600">Agrega este registro TXT en tu DNS (tipo <strong>TXT</strong>):</p>
                            <div class="mt-2 flex items-center gap-2 rounded bg-white p-2">
                                <code class="flex-1 break-all text-xs text-slate-700">{{ d.record_name }}</code>
                                <button @click="copy(d.record_name)" class="text-[11px] font-medium text-indigo-600 hover:underline">Copiar</button>
                            </div>
                            <div class="mt-2 flex items-center gap-2 rounded bg-white p-2">
                                <code class="flex-1 break-all text-xs text-slate-700">pdi-verify={{ d.verification_token }}</code>
                                <button @click="copy(`pdi-verify=${d.verification_token}`)" class="text-[11px] font-medium text-indigo-600 hover:underline">Copiar</button>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-[11px] text-slate-500" v-if="d.last_checked_at">Última comprobación: {{ new Date(d.last_checked_at).toLocaleString() }}</span>
                                <button @click="verify(d)" class="ml-auto rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-medium text-white">Verificar ahora</button>
                            </div>
                        </div>
                    </div>
                    <p v-if="!domains.length" class="py-6 text-center text-sm text-slate-400">
                        Sin dominios. Tu sitio se sirve en {{ platform_domain }}.
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
