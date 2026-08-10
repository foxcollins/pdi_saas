<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({
    range_days: Number,
    totals: Object,
    by_channel: Array,
    leads_by_status: Array,
    leads_by_source: Array,
    trend: Array,
});

const selected = ref('conversations');

const metricLabel = {
    conversations: 'Conversaciones',
    messages: 'Mensajes',
    leads: 'Leads',
    unanswered: 'Sin respuesta',
};

const colors = {
    conversations: '#6366f1',
    messages: '#0ea5e9',
    leads: '#10b981',
    unanswered: '#f43f5e',
};

const chartWidth = 720;
const chartHeight = 200;
const padX = 8;
const padY = 16;

const chartPoints = computed(() => {
    const data = props.trend;
    const values = data.map((d) => d[selected.value]);
    const max = Math.max(1, ...values);
    const stepX = data.length > 1 ? (chartWidth - padX * 2) / (data.length - 1) : 0;

    return data.map((d, i) => {
        const x = padX + stepX * i;
        const y = chartHeight - padY - (d[selected.value] / max) * (chartHeight - padY * 2);
        return { x, y, value: d[selected.value], date: d.date };
    });
});

const areaPath = computed(() => {
    if (!chartPoints.value.length) return '';
    const line = chartPoints.value.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x},${p.y}`).join(' ');
    return `${line} L${chartPoints.value[chartPoints.value.length - 1].x},${chartHeight - padY} L${chartPoints.value[0].x},${chartHeight - padY} Z`;
});

const formatDate = (v) => new Date(v).toLocaleDateString('es', { day: '2-digit', month: 'short' });
const currency = (v) => `USD ${Number(v).toFixed(2)}`;

function setRange(days) {
    router.get('/app/analytics', { days }, { preserveState: true });
}
</script>

<template>
    <AppLayout title="Analítica">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Analítica</h1>
                <p class="text-sm text-slate-500">Evolución de conversaciones, leads y uso de IA.</p>
            </div>
            <div class="flex rounded-lg border border-slate-200 bg-white p-1">
                <button
                    v-for="d in [7, 14, 30]"
                    :key="d"
                    @click="setRange(d)"
                    class="rounded-md px-3 py-1 text-xs font-medium"
                    :class="range_days === d ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:text-slate-800'"
                >
                    {{ d }} días
                </button>
            </div>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div v-for="(label, key) in metricLabel" :key="key" class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-xs font-medium text-slate-500">{{ label }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ totals[key] }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-xs font-medium text-slate-500">Costo IA del mes</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ currency(totals.ai_cost_month) }}</p>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-800">Tendencia</h2>
                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="(label, key) in metricLabel"
                        :key="key"
                        @click="selected = key"
                        class="rounded-md px-2.5 py-1 text-xs font-medium"
                        :class="selected === key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                    >
                        {{ label }}
                    </button>
                </div>
            </div>

            <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="mt-4 w-full">
                <path :d="areaPath" :fill="colors[selected]" opacity="0.12" />
                <polyline
                    v-if="chartPoints.length"
                    :points="chartPoints.map((p) => `${p.x},${p.y}`).join(' ')"
                    :stroke="colors[selected]"
                    fill="none"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <circle
                    v-for="p in chartPoints"
                    :key="p.date"
                    :cx="p.x"
                    :cy="p.y"
                    r="3"
                    :fill="colors[selected]"
                >
                    <title>{{ formatDate(p.date) }}: {{ p.value }}</title>
                </circle>
            </svg>

            <div v-if="trend.length" class="mt-2 flex justify-between text-[11px] text-slate-400">
                <span>{{ formatDate(trend[0].date) }}</span>
                <span>{{ formatDate(trend[trend.length - 1].date) }}</span>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-semibold text-slate-800">Por canal</h2>
                <div v-if="by_channel.length" class="mt-4 space-y-3">
                    <div v-for="c in by_channel" :key="c.channel" class="flex items-center justify-between text-sm">
                        <span class="capitalize text-slate-600">{{ c.channel }}</span>
                        <span class="font-medium text-slate-900">{{ c.total }}</span>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-400">Sin conversaciones todavía.</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-semibold text-slate-800">Leads por estado</h2>
                <div v-if="leads_by_status.length" class="mt-4 space-y-3">
                    <div v-for="l in leads_by_status" :key="l.status" class="flex items-center justify-between text-sm">
                        <span class="capitalize text-slate-600">{{ l.status }}</span>
                        <span class="font-medium text-slate-900">{{ l.total }}</span>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-400">Sin leads todavía.</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-semibold text-slate-800">Leads por origen</h2>
                <div v-if="leads_by_source.length" class="mt-4 space-y-3">
                    <div v-for="l in leads_by_source" :key="l.source" class="flex items-center justify-between text-sm">
                        <span class="capitalize text-slate-600">{{ l.source }}</span>
                        <span class="font-medium text-slate-900">{{ l.total }}</span>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-400">Sin leads todavía.</p>
            </div>
        </div>
    </AppLayout>
</template>
