<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({
    plan: Object,
    plans: Array,
    limits: Object,
    usage: Object,
});

const flash = usePage().props.flash || {};
const success = computed(() => flash.success || '');

const currency = (v) => `USD ${Number(v).toLocaleString('en', { minimumFractionDigits: 2 })}`;

const usagePercent = computed(() => Math.min(100, Math.round((props.usage.monthly_messages / Math.max(1, props.limits.ai.monthly_messages)) * 100)));

const aiFeatures = [
    { key: 'monthly_messages', label: 'Mensajes IA / mes' },
    { key: 'daily_messages', label: 'Mensajes IA / día' },
    { key: 'per_minute', label: 'Consultas por minuto' },
    { key: 'max_tokens', label: 'Máx. tokens por respuesta' },
];

function changePlan(slug) {
    if (!confirm(`¿Cambiar al plan ${slug.toUpperCase()}?`)) return;
    router.put('/app/billing', { plan: slug });
}
</script>

<template>
    <AppLayout title="Plan">
        <h1 class="text-xl font-bold text-slate-900">Plan y consumo</h1>
        <p class="mt-1 text-sm text-slate-500">Gestiona tu suscripción y revisa el uso de IA de tu tenant.</p>

        <div v-if="success" class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ success }}
        </div>

        <div class="mt-5 rounded-xl border border-slate-200 bg-white p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Plan actual</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ plan?.name ?? 'Sin plan' }}</p>
                    <p v-if="plan" class="text-sm text-slate-500">{{ currency(plan.price_monthly) }} / mes</p>
                </div>
                <div class="w-full max-w-xs rounded-lg bg-indigo-50 p-4">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-indigo-900">Mensajes IA usados</span>
                        <span class="text-indigo-700">{{ usage.monthly_messages }}/{{ limits.ai.monthly_messages }}</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-indigo-100">
                        <div class="h-full rounded-full bg-indigo-600 transition-all" :style="{ width: `${usagePercent}%` }"></div>
                    </div>
                    <p class="mt-2 text-[11px] text-indigo-700">Costo estimado este mes: {{ currency(usage.monthly_cost) }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-slate-100 p-4">
                    <p class="text-xs text-slate-400">Documentos de conocimiento</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ usage.documents }}<span class="text-sm font-medium text-slate-400"> / {{ limits.docs ?? '∞' }}</span></p>
                </div>
                <div class="rounded-lg border border-slate-100 p-4">
                    <p class="text-xs text-slate-400">Páginas publicadas</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ usage.pages }}<span class="text-sm font-medium text-slate-400"> / {{ limits.pages ?? '∞' }}</span></p>
                </div>
                <div class="rounded-lg border border-slate-100 p-4">
                    <p class="text-xs text-slate-400">Canales activos</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ usage.channels }}<span class="text-sm font-medium text-slate-400"> / {{ limits.channels ?? '∞' }}</span></p>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-800">Límites de IA de tu plan</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="f in aiFeatures" :key="f.key" class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs text-slate-500">{{ f.label }}</dt>
                    <dd class="mt-1 text-lg font-bold text-slate-900">{{ limits.ai[f.key] }}</dd>
                </div>
            </dl>
        </div>

        <div class="mt-6">
            <h2 class="text-sm font-semibold text-slate-800">Planes disponibles</h2>
            <div class="mt-3 grid gap-4 md:grid-cols-4">
                <div
                    v-for="p in plans"
                    :key="p.slug"
                    class="rounded-xl border p-5"
                    :class="plan?.slug === p.slug ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-slate-200'"
                >
                    <p class="text-sm font-semibold text-slate-800">{{ p.name }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ currency(p.price_monthly) }}</p>
                    <p class="text-xs text-slate-400">/mes</p>
                    <ul class="mt-4 space-y-2 text-xs text-slate-500">
                        <li>{{ p.limits.docs }} documentos</li>
                        <li>{{ p.limits.pages }} páginas</li>
                        <li>{{ p.limits.channels }} canales</li>
                        <li>{{ p.limits.monthly_messages.toLocaleString() }} mensajes IA/mes</li>
                    </ul>
                    <button
                        v-if="plan?.slug !== p.slug"
                        @click="changePlan(p.slug)"
                        class="mt-4 w-full rounded-lg border border-indigo-600 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50"
                    >
                        Cambiar a {{ p.name }}
                    </button>
                    <p v-else class="mt-4 rounded-lg bg-indigo-50 py-2 text-center text-xs font-medium text-indigo-700">Plan actual</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
