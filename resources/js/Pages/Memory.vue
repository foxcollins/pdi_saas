<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({
    contacts: Array,
    retention_days: Number,
});

const pruneDays = ref('');
const confirming = ref(null);

function fmtDate(ts) {
    return ts ? new Date(ts).toLocaleDateString() : '-';
}

function setConsent(contactId, status) {
    router.post(`/app/memory/${contactId}/consent`, { consent_status: status });
}

function forget(contactId) {
    router.post(`/app/memory/${contactId}/forget`);
}

function prune() {
    const payload = pruneDays.value ? { days: pruneDays.value } : {};
    router.post('/app/memory/prune', payload, {
        onSuccess: () => { pruneDays.value = ''; },
    });
}

function join(list) {
    return Array.isArray(list) ? list.join(', ') : '';
}
</script>

<template>
    <AppLayout title="Memoria">
        <div class="mb-5 flex flex-wrap items-end gap-4 rounded-xl border border-slate-200 bg-white p-5">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Pol\u00edtica de retenci\u00f3n</h2>
                <p class="mt-1 text-xs text-slate-500">
                    La memoria de clientes se conserva {{ retention_days }} d\u00edas y luego se elimina autom\u00e1ticamente
                    (tarea diaria). Puedes podarla manualmente.
                </p>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <input v-model="pruneDays" type="number" min="1" placeholder="D\u00edas"
                    class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <button @click="prune" class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Podar memoria
                </button>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3 font-medium">Contacto</th>
                            <th class="px-5 py-3 font-medium">Consentimiento</th>
                            <th class="px-5 py-3 font-medium">Preferencias</th>
                            <th class="px-5 py-3 font-medium">Intereses</th>
                            <th class="px-5 py-3 font-medium">Registros</th>
                            <th class="px-5 py-3 font-medium">\u00daltima actividad</th>
                            <th class="px-5 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in contacts" :key="c.id" class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ c.name }}</p>
                                <p class="text-xs text-slate-400">{{ c.email || '-' }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                    :class="c.consent_status === 'granted' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'"
                                >
                                    {{ c.consent_status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ join(c.memory_summary.preferences) || '-' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ join(c.memory_summary.interests) || '-' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ c.entries_count }}</td>
                            <td class="px-5 py-3 text-xs text-slate-400">{{ fmtDate(c.last_activity_at) }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <select
                                        :value="c.consent_status"
                                        @change="setConsent(c.id, $event.target.value)"
                                        class="rounded-lg border border-slate-300 px-2 py-1 text-xs"
                                    >
                                        <option value="granted">Otorgado</option>
                                        <option value="withdrawn">Retirado</option>
                                    </select>
                                    <button
                                        v-if="confirming !== c.id"
                                        @click="confirming = c.id"
                                        class="rounded-lg bg-red-50 px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-100"
                                    >
                                        Olvidar
                                    </button>
                                    <button
                                        v-else
                                        @click="forget(c.id)"
                                        class="rounded-lg bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700"
                                    >
                                        \u00bfConfirmar?
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!contacts.length">
                            <td colspan="7" class="py-12 text-center text-sm text-slate-400">
                                Sin contactos con consentimiento de memoria. Las preferencias e intereses captados en chats aparecer\u00e1n aqu\u00ed.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
