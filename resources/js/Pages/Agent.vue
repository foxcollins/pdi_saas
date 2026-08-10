<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({ agent: Object });
const form = reactive(JSON.parse(JSON.stringify(props.agent || {})));
const saving = ref(false);

function save() {
    saving.value = true;
    router.put('/app/assistant', form, {
        preserveScroll: true,
        onFinish: () => (saving.value = false),
    });
}
</script>

<template>
    <AppLayout title="Asistente IA">
        <div class="mx-auto max-w-3xl">
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Personalidad del tenant</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Configura tu asistente</h2>
                        <p class="mt-1 text-sm text-slate-500">Define cómo se presenta y cómo atiende a tus visitantes.</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input v-model="form.is_active" type="checkbox" class="rounded" />
                        Asistente activo
                    </label>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-medium text-slate-500">Nombre del asistente</label>
                        <input v-model="form.name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. Sofía" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Idioma</label>
                        <select v-model="form.language" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="español">Español</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Tono</label>
                        <select v-model="form.tone" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option>profesional y cercano</option>
                            <option>formal y técnico</option>
                            <option>amable y casual</option>
                            <option>comercial y persuasivo</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Mensaje de bienvenida</label>
                        <input v-model="form.welcome" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="mt-4">
                    <label class="text-xs font-medium text-slate-500">Instrucciones adicionales</label>
                    <textarea v-model="form.instructions" rows="5" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Describe qué debe priorizar el asistente..."></textarea>
                    <p class="mt-1 text-xs text-slate-400">Las reglas de seguridad y aislamiento siempre se mantienen activas.</p>
                </div>

                <div class="mt-4">
                    <label class="text-xs font-medium text-slate-500">Cuándo derivar a una persona</label>
                    <textarea v-model="form.escalation" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                </div>

                <div class="mt-6 flex justify-end">
                    <button @click="save" :disabled="saving" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                        {{ saving ? 'Guardando...' : 'Guardar asistente' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
