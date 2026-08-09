<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({ leads: Array });

const statusOrder = ['new', 'qualified', 'negotiation', 'won', 'lost'];
const statusLabel = { new: 'Nuevo', qualified: 'Calificado', negotiation: 'Negociación', won: 'Ganado', lost: 'Perdido' };
const statusStyles = {
    new: 'bg-blue-100 text-blue-700',
    qualified: 'bg-violet-100 text-violet-700',
    negotiation: 'bg-amber-100 text-amber-700',
    won: 'bg-green-100 text-green-700',
    lost: 'bg-red-100 text-red-700',
};

function setStatus(lead, status) {
    router.post(`/app/leads/${lead.id}/status`, { status });
}

function scoreClass(score) {
    if (score >= 80) return 'bg-green-100 text-green-700';
    if (score >= 50) return 'bg-amber-100 text-amber-700';
    return 'bg-slate-100 text-slate-600';
}
</script>

<template>
    <AppLayout title="Leads">
        <div class="rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3 font-medium">Contacto</th>
                            <th class="px-5 py-3 font-medium">Fuente</th>
                            <th class="px-5 py-3 font-medium">Intención</th>
                            <th class="px-5 py-3 font-medium">Score</th>
                            <th class="px-5 py-3 font-medium">Estado</th>
                            <th class="px-5 py-3 font-medium">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="l in leads" :key="l.id" class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ l.contact }}</p>
                                <p class="text-xs text-slate-400">{{ l.email || l.phone || '—' }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ l.source || '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ l.intent || '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="scoreClass(l.score)">{{ l.score ?? 0 }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <select
                                    :value="l.status"
                                    @change="setStatus(l, $event.target.value)"
                                    class="rounded-lg border border-slate-300 px-2 py-1 text-xs"
                                >
                                    <option v-for="s in statusOrder" :key="s" :value="s">{{ statusLabel[s] }}</option>
                                </select>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-400">{{ new Date(l.created_at).toLocaleDateString() }}</td>
                        </tr>
                        <tr v-if="!leads.length">
                            <td colspan="6" class="py-12 text-center text-sm text-slate-400">
                                Sin leads por ahora. Los leads de formularios y chats aparecerán aquí.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
