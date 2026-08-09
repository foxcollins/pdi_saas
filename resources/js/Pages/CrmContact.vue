<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({ contact: Object });

const noteBody = ref('');

function addNote() {
    if (!noteBody.value.trim()) return;
    router.post('/app/crm/notes', { contact_id: props.contact.id, body: noteBody.value }, {
        onSuccess: () => { noteBody.value = ''; },
    });
}

function toggleTask(taskId, done) {
    router.patch(`/app/crm/tasks/${taskId}`, { status: done ? 'done' : 'open' });
}

function fmt(ts) {
    return ts ? new Date(ts).toLocaleString() : '-';
}

function fmtDate(ts) {
    return ts ? new Date(ts).toLocaleDateString() : '-';
}

const statusLabel = { new: 'Nuevo', qualified: 'Calificado', negotiation: 'Negociaci\u00f3n', won: 'Ganado', lost: 'Perdido' };
</script>

<template>
    <AppLayout :title="contact.name">
        <Link href="/app/crm" class="mb-4 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800">
            &larr; Volver al CRM
        </Link>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-1">
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="text-sm font-semibold text-slate-800">Contacto</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="text-slate-800">{{ contact.email || '-' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Tel\u00e9fono</dt><dd class="text-slate-800">{{ contact.phone || '-' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Ciclo de vida</dt><dd class="text-slate-800">{{ contact.lifecycle }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Consentimiento</dt><dd class="text-slate-800">{{ contact.consent_status }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">\u00daltima actividad</dt><dd class="text-slate-800">{{ fmtDate(contact.last_activity_at) }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="text-sm font-semibold text-slate-800">Notas</h2>
                    <div class="mt-3 space-y-2">
                        <div v-for="n in contact.notes" :key="n.id" class="rounded-lg bg-slate-50 p-2">
                            <p class="text-sm text-slate-700">{{ n.body }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ fmt(n.created_at) }}</p>
                        </div>
                        <p v-if="!contact.notes.length" class="text-xs text-slate-400">Sin notas.</p>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <input v-model="noteBody" type="text" placeholder="Agregar nota..."
                            class="flex-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm" />
                        <button @click="addNote" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                            Agregar
                        </button>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="text-sm font-semibold text-slate-800">Tareas</h2>
                    <div class="mt-3 space-y-2">
                        <div v-for="t in contact.tasks" :key="t.id" class="flex items-center gap-2">
                            <input type="checkbox" :checked="t.status === 'done'" @change="toggleTask(t.id, $event.target.checked)" class="h-4 w-4" />
                            <span class="text-sm text-slate-700" :class="{ 'line-through text-slate-400': t.status === 'done' }">{{ t.title }}</span>
                        </div>
                        <p v-if="!contact.tasks.length" class="text-xs text-slate-400">Sin tareas.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="text-sm font-semibold text-slate-800">Leads</h2>
                    <div class="mt-3 space-y-2">
                        <div v-for="l in contact.leads" :key="l.id" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                            <span class="text-slate-700">{{ l.source }}<span v-if="l.intent"> \u00b7 {{ l.intent }}</span></span>
                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-medium text-indigo-700">{{ statusLabel[l.status] || l.status }}</span>
                        </div>
                        <p v-if="!contact.leads.length" class="text-xs text-slate-400">Sin leads.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="text-sm font-semibold text-slate-800">Conversaciones</h2>
                    <div v-if="contact.conversations.length" class="mt-3 space-y-4">
                        <div v-for="c in contact.conversations" :key="c.id" class="rounded-lg border border-slate-100 p-3">
                            <p class="mb-2 text-xs font-medium text-slate-500">
                                {{ c.channel }} <span v-if="c.subject">&middot; {{ c.subject }}</span> &middot; {{ c.status }}
                            </p>
                            <div class="space-y-2">
                                <div
                                    v-for="(m, i) in c.messages"
                                    :key="i"
                                    class="max-w-[80%] rounded-xl px-3 py-2 text-sm"
                                    :class="m.direction === 'in'
                                        ? 'bg-slate-100 text-slate-700'
                                        : m.author_type === 'agent'
                                            ? 'bg-violet-100 text-violet-900'
                                            : 'ml-auto bg-indigo-600 text-white'"
                                >
                                    <p class="text-xs">{{ m.content }}</p>
                                    <p class="mt-0.5 text-[10px] opacity-60">{{ m.author_type }} &middot; {{ fmt(m.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-3 text-xs text-slate-400">Sin conversaciones.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
