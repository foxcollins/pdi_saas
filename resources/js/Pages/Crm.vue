<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({
    contacts: Array,
    pipeline: Object,
    inbox: Array,
    tasks: Array,
    notes: Array,
});

const tab = ref('inbox');
const newTask = ref({ title: '', description: '', due_at: '' });

const pipelineColumns = ['new', 'qualified', 'negotiation', 'won', 'lost'];
const columnLabel = { new: 'Nuevo', qualified: 'Calificado', negotiation: 'Negociaci\u00f3n', won: 'Ganado', lost: 'Perdido' };
const columnStyles = {
    new: 'border-blue-200 bg-blue-50',
    qualified: 'border-violet-200 bg-violet-50',
    negotiation: 'border-amber-200 bg-amber-50',
    won: 'border-green-200 bg-green-50',
    lost: 'border-red-200 bg-red-50',
};

function fmt(ts) {
    return ts ? new Date(ts).toLocaleString() : '-';
}

function fmtDate(ts) {
    return ts ? new Date(ts).toLocaleDateString() : '-';
}

function moveLead(leadId, status) {
    router.post(`/app/leads/${leadId}/status`, { status });
}

function resolveConversation(id) {
    router.post(`/app/crm/inbox/${id}/resolve`);
}

function toggleTask(taskId, done) {
    router.patch(`/app/crm/tasks/${taskId}`, { status: done ? 'done' : 'open' });
}

function createTask() {
    if (!newTask.value.title) return;
    router.post('/app/crm/tasks', newTask.value, {
        onSuccess: () => { newTask.value = { title: '', description: '', due_at: '' }; },
    });
}

function scoreClass(score) {
    if (score >= 80) return 'bg-green-100 text-green-700';
    if (score >= 50) return 'bg-amber-100 text-amber-700';
    return 'bg-slate-100 text-slate-600';
}
</script>

<template>
    <AppLayout title="CRM">
        <div class="mb-5 flex gap-1 rounded-xl border border-slate-200 bg-white p-1">
            <button
                v-for="t in [
                    { key: 'inbox', label: `Bandeja (${inbox.length})` },
                    { key: 'pipeline', label: 'Pipeline' },
                    { key: 'contacts', label: `Contactos (${contacts.length})` },
                    { key: 'tasks', label: `Tareas (${tasks.filter(t => t.status !== 'done').length})` },
                    { key: 'notes', label: `Notas (${notes.length})` },
                ]"
                :key="t.key"
                @click="tab = t.key"
                class="rounded-lg px-4 py-2 text-sm font-medium"
                :class="tab === t.key ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
            >
                {{ t.label }}
            </button>
        </div>

        <div v-if="tab === 'inbox'" class="space-y-3">
            <div v-for="c in inbox" :key="c.id" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold text-slate-800">{{ c.contact }}</h3>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                                Escalada
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ c.subject || 'Conversaci\u00f3n' }} &middot; {{ c.channel }} &middot; {{ fmt(c.escalated_at) }}
                        </p>
                        <p v-if="c.question" class="mt-2 rounded-lg bg-white px-3 py-2 text-sm text-slate-700">
                            <span class="font-medium text-slate-500">Pregunta sin responder:</span> {{ c.question }}
                        </p>
                        <p v-else-if="c.last_message" class="mt-2 rounded-lg bg-white px-3 py-2 text-sm text-slate-700">
                            {{ c.last_message }}
                        </p>
                    </div>
                    <button
                        @click="resolveConversation(c.id)"
                        class="shrink-0 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                    >
                        Marcar resuelta
                    </button>
                </div>
            </div>
            <p v-if="!inbox.length" class="rounded-xl border border-slate-200 bg-white py-12 text-center text-sm text-slate-400">
                Sin conversaciones escaladas. Las preguntas que el asistente no pueda responder aparecer\u00e1n aqu\u00ed para atenci\u00f3n humana.
            </p>
        </div>

        <div v-if="tab === 'pipeline'" class="grid gap-3 md:grid-cols-5">
            <div v-for="col in pipelineColumns" :key="col" class="rounded-xl border p-3" :class="columnStyles[col]">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    {{ columnLabel[col] }} ({{ (pipeline[col] || []).length }})
                </h3>
                <div class="space-y-2">
                    <div
                        v-for="l in pipeline[col] || []"
                        :key="l.id"
                        class="rounded-lg bg-white p-3 shadow-sm"
                    >
                        <p class="text-sm font-medium text-slate-800">{{ l.contact }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ l.intent || 'Sin intenci\u00f3n' }}</p>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="scoreClass(l.score)">{{ l.score }}</span>
                            <div class="flex gap-1">
                                <button
                                    v-for="target in pipelineColumns"
                                    :key="target"
                                    @click="moveLead(l.id, target)"
                                    class="rounded px-1.5 py-0.5 text-[10px] text-slate-500 hover:bg-slate-100"
                                    :title="`Mover a ${columnLabel[target]}`"
                                >
                                    {{ target[0].toUpperCase() }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <p v-if="!(pipeline[col] || []).length" class="py-4 text-center text-xs text-slate-400">Vac\u00edo</p>
                </div>
            </div>
        </div>

        <div v-if="tab === 'contacts'" class="rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3 font-medium">Contacto</th>
                            <th class="px-5 py-3 font-medium">Ciclo de vida</th>
                            <th class="px-5 py-3 font-medium">Conversaciones</th>
                            <th class="px-5 py-3 font-medium">Score</th>
                            <th class="px-5 py-3 font-medium">Estado lead</th>
                            <th class="px-5 py-3 font-medium">\u00daltima actividad</th>
                            <th class="px-5 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in contacts" :key="c.id" class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ c.name }}</p>
                                <p class="text-xs text-slate-400">{{ c.email || c.phone || '-' }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ c.lifecycle }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ c.conversations_count }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="scoreClass(c.score)">{{ c.score ?? 0 }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ c.lead_status ? columnLabel[c.lead_status] : '-' }}</td>
                            <td class="px-5 py-3 text-xs text-slate-400">{{ fmtDate(c.last_activity_at) }}</td>
                            <td class="px-5 py-3 text-right">
                                <Link :href="`/app/crm/contacts/${c.id}`" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                    Ver perfil
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!contacts.length">
                            <td colspan="7" class="py-12 text-center text-sm text-slate-400">
                                Sin contactos por ahora.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="tab === 'tasks'" class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="text-sm font-semibold text-slate-800">Nueva tarea</h2>
                <div class="mt-3 space-y-3">
                    <input v-model="newTask.title" type="text" placeholder="T\u00edtulo de la tarea"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <textarea v-model="newTask.description" rows="2" placeholder="Descripci\u00f3n"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                    <input v-model="newTask.due_at" type="date"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <button @click="createTask" class="w-full rounded-lg bg-indigo-600 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Crear tarea
                    </button>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white">
                <div class="divide-y divide-slate-100">
                    <div v-for="t in tasks" :key="t.id" class="flex items-center gap-3 px-5 py-3">
                        <input type="checkbox" :checked="t.status === 'done'" @change="toggleTask(t.id, $event.target.checked)" class="h-4 w-4" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-800" :class="{ 'line-through text-slate-400': t.status === 'done' }">
                                {{ t.title }}
                            </p>
                            <p class="text-xs text-slate-400">{{ t.contact || 'Sin contacto' }}{{ t.due_at ? ' \u00b7 ' + fmtDate(t.due_at) : '' }}</p>
                        </div>
                    </div>
                    <p v-if="!tasks.length" class="py-12 text-center text-sm text-slate-400">Sin tareas.</p>
                </div>
            </div>
        </div>

        <div v-if="tab === 'notes'" class="rounded-xl border border-slate-200 bg-white">
            <div class="divide-y divide-slate-100">
                <div v-for="n in notes" :key="n.id" class="px-5 py-3">
                    <p class="text-sm text-slate-700">{{ n.body }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ n.contact || 'Sin contacto' }} &middot; {{ fmt(n.created_at) }}</p>
                </div>
                <p v-if="!notes.length" class="py-12 text-center text-sm text-slate-400">Sin notas.</p>
            </div>
        </div>
    </AppLayout>
</template>
