<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({
    channels: Array,
    limits: Object,
    usage: Object,
});

const flash = usePage().props.flash || {};
const success = computed(() => flash.success || '');

const activeForm = ref(null);
const form = ref({});

function openForm(channel) {
    activeForm.value = channel.key;
    form.value = {};
}

function closeForm() {
    activeForm.value = null;
    form.value = {};
}

function save(channel) {
    router.post(`/app/integrations/${channel.key}`, form.value, {
        preserveScroll: true,
        onSuccess: closeForm,
    });
}

function disable(channel) {
    if (!confirm(`¿Desactivar el canal ${channel.label}?`)) return;
    router.post(`/app/integrations/${channel.key}/disable`, {}, { preserveScroll: true });
}

function copy(url) {
    navigator.clipboard?.writeText(url);
}
</script>

<template>
    <AppLayout title="Integraciones">
        <h1 class="text-xl font-bold text-slate-900">Integraciones</h1>
        <p class="mt-1 text-sm text-slate-500">Conecta canales de mensajería para que tu asistente responda en WhatsApp, Messenger, Instagram, Telegram o email.</p>

        <div v-if="success" class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ success }}
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            <div v-for="channel in channels" :key="channel.key" class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <path :d="channel.icon" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">{{ channel.label }}</p>
                            <p class="text-xs" :class="channel.connected && channel.status === 'active' ? 'text-green-600' : 'text-slate-400'">
                                {{ channel.connected && channel.status === 'active' ? 'Conectado' : 'Sin conectar' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button
                            v-if="channel.connected && channel.status === 'active'"
                            @click="disable(channel)"
                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                        >
                            Desactivar
                        </button>
                        <button
                            @click="activeForm === channel.key ? closeForm() : openForm(channel)"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white"
                            :class="channel.connected ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-slate-800 hover:bg-slate-900'"
                        >
                            {{ channel.connected ? 'Configurar' : 'Conectar' }}
                        </button>
                    </div>
                </div>

                <div v-if="channel.connected && channel.status === 'active'" class="mt-4 rounded-lg bg-slate-50 p-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-[11px] font-medium text-slate-500">Webhook URL</p>
                        <button @click="copy(channel.webhook_url)" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">Copiar</button>
                    </div>
                    <code class="mt-1 block break-all text-[11px] text-slate-700">{{ channel.webhook_url }}</code>
                    <p v-if="channel.last_sync_at" class="mt-2 text-[11px] text-slate-400">Última sincronización: {{ channel.last_sync_at }}</p>
                </div>

                <form v-if="activeForm === channel.key" @submit.prevent="save(channel)" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                    <div v-for="(field, key) in channel.fields" :key="key">
                        <label class="block text-xs font-medium text-slate-600">
                            {{ field.label }}
                            <span v-if="field.required" class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form[key]"
                            :type="field.type || 'text'"
                            :required="field.required"
                            class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none"
                        />
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Guardar
                        </button>
                        <button type="button" @click="closeForm" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Consumo de canales</p>
            <p class="mt-1 text-xl font-bold text-slate-900">
                {{ usage.channels }}<span class="text-sm font-medium text-slate-400"> / {{ limits.channels ?? '8' }} canales</span>
            </p>
        </div>
    </AppLayout>
</template>
