<script setup>
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({
    agents: Array,
    allowed_tools: Array,
    tools_catalog: Array,
});

const flash = usePage().props.flash || {};
const success = computed(() => flash.success || '');

const editing = ref(null);

function startEdit(agent) {
    const copy = JSON.parse(JSON.stringify(agent));
    copy.keywords_text = (copy.trigger_keywords || []).join('\n');
    editing.value = reactive(copy);
}

function save() {
    if (!editing.value) return;
    const payload = {
        name: editing.value.name,
        description: editing.value.description,
        instructions: editing.value.instructions,
        trigger_keywords: (editing.value.keywords_text || '').split('\n').map((k) => k.trim()).filter(Boolean),
        tools: editing.value.tools || [],
        is_active: editing.value.is_active,
    };
    router.put(`/app/agents/${editing.value.id}`, payload, {
        preserveScroll: true,
        onFinish: () => (editing.value = null),
    });
}

function toggleActive(agent) {
    router.put(`/app/agents/${agent.id}`, {
        ...agent,
        tools: agent.tools || [],
        trigger_keywords: agent.trigger_keywords || [],
        is_active: !agent.is_active,
    }, { preserveScroll: true });
}

const toolLabel = (name) => props.tools_catalog?.find((t) => t.name === name)?.description || name;
const isAllowed = (name) => (props.allowed_tools || []).includes(name);
const permissionLabel = { read: 'Lectura', internal: 'Escritura interna', external: 'Externa', destructive: 'Destructiva' };

const agentRoles = { reception: 'Recepción', sales: 'Ventas', support: 'Soporte', booking: 'Agenda', followup: 'Seguimiento', assistant: 'General' };
</script>

<template>
    <AppLayout title="Agentes">
        <h1 class="text-xl font-bold text-slate-900">Agentes del asistente</h1>
        <p class="mt-1 text-sm text-slate-500">El chat elige automáticamente el agente según la intención del visitante. Activa los que tu negocio necesite.</p>

        <div v-if="success" class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ success }}
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            <div
                v-for="agent in agents"
                :key="agent.id"
                class="rounded-xl border bg-white p-5"
                :class="agent.is_active ? 'border-indigo-300' : 'border-slate-200'"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">
                            {{ agent.name }}
                            <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500">{{ agentRoles[agent.slug] || agent.slug }}</span>
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ agent.description }}</p>
                    </div>
                    <label class="flex shrink-0 cursor-pointer items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" :checked="agent.is_active" @change="toggleActive(agent)" class="rounded" />
                        {{ agent.is_active ? 'Activo' : 'Inactivo' }}
                    </label>
                </div>

                <div v-if="agent.trigger_keywords?.length" class="mt-3 flex flex-wrap gap-1">
                    <span v-for="kw in agent.trigger_keywords" :key="kw" class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] text-indigo-600 ring-1 ring-indigo-100">
                        {{ kw }}
                    </span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    <span v-for="tool in agent.tools" :key="tool" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-600">
                        {{ toolLabel(tool) }}
                    </span>
                    <span v-if="!agent.tools?.length" class="text-[11px] text-slate-400">Sin herramientas</span>
                </div>

                <button @click="startEdit(agent)" class="mt-4 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    Configurar
                </button>
            </div>
        </div>

        <div v-if="editing" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
            <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Configurar {{ editing.name }}</h2>
                        <p class="text-sm text-slate-500">{{ editing.description }}</p>
                    </div>
                    <button @click="editing = null" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-xs font-medium text-slate-500">Nombre</label>
                        <input v-model="editing.name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Instrucciones</label>
                        <textarea v-model="editing.instructions" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Palabras clave que activan este agente</label>
                        <p class="text-[11px] text-slate-400">Una por línea. Si el visitante menciona alguna, este agente atiende el mensaje.</p>
                        <textarea v-model="editing.keywords_text" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500">Herramientas</label>
                        <div class="mt-1 grid gap-2 sm:grid-cols-2">
                            <label v-for="tool in tools_catalog" :key="tool.name" class="flex items-start gap-2 text-sm" :class="!isAllowed(tool.name) ? 'opacity-50' : ''">
                                <input
                                    type="checkbox"
                                    :value="tool.name"
                                    :disabled="!isAllowed(tool.name)"
                                    v-model="editing.tools"
                                    class="mt-1 rounded accent-indigo-600"
                                />
                                <span>
                                    <span class="block font-medium text-slate-700">{{ tool.name }}</span>
                                    <span class="block text-[11px] text-slate-400">{{ tool.description }} · {{ permissionLabel[tool.permission] }}</span>
                                    <span v-if="!isAllowed(tool.name)" class="block text-[11px] font-medium text-amber-600">No incluida en tu plan</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button @click="editing = null" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button @click="save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Guardar agente</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
