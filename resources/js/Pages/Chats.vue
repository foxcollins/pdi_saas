<script setup>
import { ref } from 'vue';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({ conversations: Array });

const openId = ref(null);

const statusStyles = {
    active: 'bg-green-100 text-green-700',
    pending: 'bg-amber-100 text-amber-700',
    closed: 'bg-slate-100 text-slate-600',
};

function toggle(id) {
    openId.value = openId.value === id ? null : id;
}

function fmt(ts) {
    return ts ? new Date(ts).toLocaleString() : '—';
}
</script>

<template>
    <AppLayout title="Conversaciones">
        <div class="rounded-xl border border-slate-200 bg-white">
            <div class="divide-y divide-slate-100">
                <div v-for="c in conversations" :key="c.id" class="border-b border-slate-100 last:border-0">
                    <button @click="toggle(c.id)" class="flex w-full items-center gap-3 px-5 py-4 text-left hover:bg-slate-50">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                            {{ (c.contact || 'V')[0] }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-800">{{ c.contact }}</p>
                            <p class="truncate text-xs text-slate-400">{{ c.email || 'Sin email' }} · {{ c.channel }}</p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="statusStyles[c.status] || statusStyles.pending">
                            {{ c.status }}
                        </span>
                        <span class="text-xs text-slate-400">{{ fmt(c.started_at) }}</span>
                    </button>

                    <div v-if="openId === c.id" class="space-y-2 px-5 pb-5">
                        <div
                            v-for="(m, i) in c.messages"
                            :key="i"
                            class="max-w-[75%] rounded-xl px-3 py-2 text-sm"
                            :class="m.direction === 'outbound'
                                ? 'ml-auto bg-indigo-600 text-white'
                                : m.author_type === 'agent'
                                    ? 'bg-violet-100 text-violet-900'
                                    : 'bg-slate-100 text-slate-700'"
                        >
                            <p class="mb-0.5 text-[10px] opacity-60">{{ m.author_type }} · {{ fmt(m.created_at) }}</p>
                            <p class="whitespace-pre-wrap">{{ m.content }}</p>
                        </div>
                    </div>
                </div>

                <p v-if="!conversations.length" class="py-12 text-center text-sm text-slate-400">
                    Aún no hay conversaciones. Cuando un visitante chatee en tu sitio, aparecerá aquí.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
