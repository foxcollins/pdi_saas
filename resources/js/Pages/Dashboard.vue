<script setup>
import AppLayout from '../Components/AppLayout.vue';

defineProps({
    metrics: Object,
    recent_conversations: Array,
});

const formatDate = (v) => (v ? new Date(v).toLocaleString('es', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—');
</script>

<template>
    <AppLayout title="Panel">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="(m, i) in [
                { label: 'Conversaciones', value: metrics.conversations, sub: `${metrics.open_conversations} abiertas` },
                { label: 'Mensajes hoy', value: metrics.messages_today, sub: `${metrics.messages} en total` },
                { label: 'Leads captados', value: metrics.leads, sub: `${metrics.new_leads} hoy` },
                { label: 'Documentos de conocimiento', value: `${metrics.ready_documents}/${metrics.documents}`, sub: `${metrics.ai_runs} llamadas IA` },
            ]" :key="i" class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-xs font-medium text-slate-500">{{ m.label }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ m.value }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ m.sub }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-semibold text-slate-800">Conversaciones recientes</h2>
                <div v-if="recent_conversations.length" class="mt-4 space-y-3">
                    <div v-for="c in recent_conversations" :key="c.id" class="rounded-lg border border-slate-100 p-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-slate-800">{{ c.contact || 'Visitante' }}</p>
                            <span class="text-xs text-slate-400">{{ formatDate(c.created_at) }}</span>
                        </div>
                        <p class="mt-1 truncate text-sm text-slate-500">{{ c.preview || 'Sin mensajes' }}</p>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-400">Todavía no hay conversaciones. Comparte tu sitio para empezar.</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-semibold text-slate-800">Inteligencia</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Mensajes IA</dt><dd class="font-medium">{{ metrics.chat_messages }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Visitas</dt><dd class="font-medium">{{ metrics.page_views }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Costo IA del mes</dt><dd class="font-medium">USD {{ metrics.ai_cost_month.toFixed(4) }}</dd></div>
                </dl>
            </div>
        </div>
    </AppLayout>
</template>
