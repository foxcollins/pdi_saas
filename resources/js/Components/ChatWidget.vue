<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    slug: { type: String, required: true },
    title: { type: String, default: 'Asistente virtual' },
    welcome: { type: String, default: 'Hola, ¿en qué puedo ayudarte?' },
    primary: { type: String, default: '#4f46e5' },
});

const open = ref(false);
const messages = ref([]);
const input = ref('');
const busy = ref(false);
const showProfile = ref(false);
const profile = ref({ name: '', email: '', phone: '' });

const STORAGE = 'pdi_chat_visitor';

onMounted(() => {
    const saved = localStorage.getItem(STORAGE);
    if (saved) {
        try {
            profile.value = JSON.parse(saved);
        } catch (e) {
            /* ignore */
        }
    }
    if (!profile.value.name && !profile.value.email && !profile.value.phone) {
        showProfile.value = true;
    }
    messages.value.push({ role: 'assistant', text: props.welcome });
});

function toggle() {
    open.value = !open.value;
}

function saveProfile() {
    localStorage.setItem(STORAGE, JSON.stringify(profile.value));
    showProfile.value = false;
}

async function send() {
    const text = input.value.trim();
    if (!text || busy.value) return;

    messages.value.push({ role: 'user', text });
    input.value = '';
    busy.value = true;

    const assistant = { role: 'assistant', text: '' };
    messages.value.push(assistant);

    try {
        const res = await fetch(`/api/chat/${props.slug}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
            body: JSON.stringify({ message: text, ...profile.value }),
        });

        if (!res.ok || !res.body) throw new Error('Error de conexión');

        const reader = res.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buffer += decoder.decode(value, { stream: true });

            const events = buffer.split('\n\n');
            buffer = events.pop() || '';

            for (const ev of events) {
                const line = ev.trim();
                if (!line.startsWith('data:')) continue;
                try {
                    const data = JSON.parse(line.slice(5));
                    if (data.type === 'chunk') {
                        assistant.text += data.text;
                    } else if (data.type === 'done') {
                        // fin
                    } else if (data.type === 'error') {
                        assistant.text = data.message || 'Ocurrió un error.';
                    }
                } catch (e) {
                    /* ignore malformed */
                }
            }
        }
    } catch (e) {
        assistant.text = 'No se pudo conectar con el asistente. Intenta de nuevo.';
    } finally {
        busy.value = false;
        const el = document.getElementById('chat-messages');
        if (el) el.scrollTop = el.scrollHeight;
    }
}
</script>

<template>
    <div>
        <button
            type="button"
            class="fixed bottom-5 right-5 z-50 flex h-14 w-14 items-center justify-center rounded-full text-white shadow-xl transition hover:scale-105"
            :style="{ backgroundColor: primary }"
            @click="toggle"
        >
            <svg v-if="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h4m-6 6l4-4h6a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2h1l3 4z" />
            </svg>
            <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div
            v-if="open"
            class="fixed bottom-24 right-5 z-50 flex w-80 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl md:w-96"
        >
            <div class="px-4 py-3 text-white" :style="{ backgroundColor: primary }">
                <p class="text-sm font-semibold">{{ title }}</p>
            </div>

            <div v-if="showProfile" class="border-b border-slate-100 p-4 text-sm">
                <p class="mb-2 font-medium text-slate-700">¿Cómo te llamamos?</p>
                <div class="space-y-2">
                    <input v-model="profile.name" type="text" placeholder="Nombre" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm" />
                    <input v-model="profile.email" type="email" placeholder="Email (opcional)" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm" />
                    <input v-model="profile.phone" type="text" placeholder="Teléfono (opcional)" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm" />
                    <button type="button" class="w-full rounded-lg py-1.5 text-sm font-medium text-white" :style="{ backgroundColor: primary }" @click="saveProfile">
                        Empezar a conversar
                    </button>
                </div>
            </div>

            <div id="chat-messages" class="max-h-80 flex-1 space-y-3 overflow-y-auto p-4">
                <div v-for="(m, i) in messages" :key="i" :class="m.role === 'user' ? 'ml-8' : 'mr-8'">
                    <div
                        :class="m.role === 'user' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-800'"
                        class="whitespace-pre-wrap rounded-2xl px-3 py-2 text-sm"
                        :style="m.role === 'user' ? { backgroundColor: primary } : {}"
                    >{{ m.text }}</div>
                </div>
                <div v-if="busy && !messages[messages.length - 1]?.text" class="flex items-center gap-1 text-sm text-slate-400">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay: 0.1s"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay: 0.2s"></span>
                </div>
            </div>

            <div class="flex items-center gap-2 border-t border-slate-100 p-3">
                <input
                    v-model="input"
                    type="text"
                    placeholder="Escribe tu consulta..."
                    class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    @keyup.enter="send"
                />
                <button type="button" :disabled="busy" class="rounded-lg px-3 py-2 text-white disabled:opacity-50" :style="{ backgroundColor: primary }" @click="send">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 6l6 6-6 6M5 12h14" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
