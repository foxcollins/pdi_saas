<script setup>
import { computed } from 'vue';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({
    metrics: Object,
    ai_usage: Object,
    unanswered_questions: Array,
    recent_conversations: Array,
});

const formatDate = (v) => (v ? new Date(v).toLocaleString('es', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—');
const usagePercent = computed(() => Math.min(100, Math.round((props.ai_usage.monthly_messages / Math.max(1, props.ai_usage.monthly_limit)) * 100)));
</script>

<template>
    <AppLayout title="Panel">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="(m, i) in [
                { label: 'Conversaciones', value: metrics.conversations, sub: `${metrics.open_conversations} abiertas` },
                { label: 'Mensajes hoy', value: metrics.messages_today, sub: `${metrics.messages} en total` },
                { label: 'Leads captados', value: metrics.leads, sub: `${metrics.new_leads} hoy` },
                { label: 'Sin respuesta', value: metrics.unanswered_today, sub: `${metrics.unanswered_total} en total` },
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
                <div class="mt-4 rounded-lg bg-indigo-50 p-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-indigo-900">Uso de IA este mes</span>
                        <span class="text-indigo-700">{{ ai_usage.monthly_messages }}/{{ ai_usage.monthly_limit }}</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-indigo-100">
                        <div class="h-full rounded-full bg-indigo-600 transition-all" :style="{ width: `${usagePercent}%` }"></div>
                    </div>
                    <p class="mt-2 text-[11px] text-indigo-700">{{ ai_usage.daily_messages }} mensajes usados hoy · máximo {{ ai_usage.max_tokens }} tokens por respuesta</p>
                </div>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Mensajes IA</dt><dd class="font-medium">{{ metrics.chat_messages }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Visitas</dt><dd class="font-medium">{{ metrics.page_views }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Documentos listos</dt><dd class="font-medium">{{ metrics.ready_documents }}/{{ metrics.documents }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Costo IA del mes</dt><dd class="font-medium">USD {{ ai_usage.monthly_cost.toFixed(4) }}</dd></div>
                </dl>

                <h3 class="mt-6 text-xs font-semibold uppercase tracking-wide text-slate-400">Preguntas sin respuesta</h3>
                <div v-if="unanswered_questions.length" class="mt-2 space-y-2">
                    <div v-for="(q, i) in unanswered_questions" :key="i" class="rounded-lg bg-slate-50 p-2">
                        <p class="text-xs text-slate-600">{{ q.question }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-400">{{ formatDate(q.created_at) }}</p>
                    </div>
                </div>
                <p v-else class="mt-2 text-xs text-slate-400">Sin preguntas sin respuesta. Añade contenido a tu base de conocimiento si aparece alguna.</p>
            </div>
        </div>
    </AppLayout>
</template>
