<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ title: { type: String, default: '' } });

const page = usePage();
const tenant = computed(() => page.props.tenant ?? null);
const user = computed(() => page.props.auth?.user ?? null);

const nav = [
    { label: 'Panel', href: '/app/dashboard', icon: 'M3 4h18v6H3zM3 14h8v6H3zM15 14h6v6h-6z' },
    { label: 'Analítica', href: '/app/analytics', icon: 'M4 20V4m0 16h16M8 16v-5m4 5V8m4 8v-3m4 3v-9' },
    { label: 'Diseñador', href: '/app/builder', icon: 'M12 3l8 4-8 4-8-4 8-4zM4 11v6l8 4 8-4v-6M12 15v6' },
    { label: 'Contenido', href: '/app/content', icon: 'M4 6h16M4 10h16M4 14h10M4 18h6' },
    { label: 'Conocimiento', href: '/app/knowledge', icon: 'M12 6v12m-6-6h12' },
    { label: 'Asistente IA', href: '/app/assistant', icon: 'M12 3a7 7 0 017 7v1a5 5 0 01-5 5h-2a5 5 0 01-5-5v-1a7 7 0 017-7zm-3 16h6' },
    { label: 'Dominios', href: '/app/domains', icon: 'M3 7l9-4 9 4-9 4-9-4zm0 6l9 4 9-4M3 19l9 4 9-4' },
    { label: 'Conversaciones', href: '/app/chats', icon: 'M4 5h16v10H8l-4 4V5z' },
    { label: 'Leads', href: '/app/leads', icon: 'M5 12a7 7 0 1114 0 7 7 0 01-14 0zm7 3v-3m0-3h.01' },
    { label: 'CRM', href: '/app/crm', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zm-8 6a5 5 0 00-5 5v1h18v-1a5 5 0 00-5-5H8z' },
    { label: 'Memoria', href: '/app/memory', icon: 'M4 6h16v12H4zM8 3h8v3H8zM7 10h.01M11 10h.01M15 10h.01M8 14h.01M11 14h.01M15 14h.01' },
    { label: 'Plan', href: '/app/billing', icon: 'M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6' },
    { label: 'Integraciones', href: '/app/integrations', icon: 'M9 12h6m-6 4h6m2-9a2 2 0 012 2v10a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2h2M9 5a2 2 0 012-2h2a2 2 0 012 2v2H9V5z' },
    { label: 'Tools', href: '/app/tools', icon: 'M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z' },
];

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <aside class="fixed inset-y-0 left-0 z-40 w-60 bg-slate-900 text-slate-100 hidden md:flex md:flex-col">
            <div class="px-5 py-5 border-b border-slate-800">
                <p class="font-bold text-lg tracking-tight">PDI <span class="text-indigo-400">SAAS</span></p>
                <p class="text-xs text-slate-400 truncate">{{ tenant?.name ?? 'Sin tenant' }}</p>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800 hover:text-white"
                    :class="{ 'bg-slate-800 text-white': $page.url.startsWith(item.href) }"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    {{ item.label }}
                </Link>
            </nav>
            <div class="px-4 py-4 border-t border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-xs font-bold uppercase">
                        {{ (user?.name ?? 'U')[0] }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium truncate">{{ user?.name }}</p>
                        <p class="text-[10px] text-slate-400 truncate">{{ user?.email }}</p>
                    </div>
                    <button @click="logout" title="Cerrar sesión" class="text-slate-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <div class="md:pl-60">
            <header class="sticky top-0 z-30 flex items-center justify-between h-14 bg-white border-b border-slate-200 px-5">
                <div>
                    <h1 class="text-sm font-semibold text-slate-800">{{ title }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="`/site/${tenant?.slug}`"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Ver sitio
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6v6M10 14L20 4" />
                        </svg>
                    </a>
                    <Link href="/app/builder" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                        Editar sitio
                    </Link>
                </div>
            </header>
            <main class="p-5 md:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
