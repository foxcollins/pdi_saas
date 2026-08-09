<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({ sources: Array });

const tab = ref('text');
const upload = ref(null);
const textForm = ref({ title: '', content: '' });
const urlForm = ref({ url: '', title: '' });

const statusStyles = {
    processed: 'bg-green-100 text-green-700',
    processing: 'bg-amber-100 text-amber-700',
    failed: 'bg-red-100 text-red-700',
    pending: 'bg-slate-100 text-slate-600',
};

function uploadFile() {
    if (!upload.value?.files?.[0]) return;
    const form = new FormData();
    form.append('file', upload.value.files[0]);
    form.append('title', textForm.value.title || upload.value.files[0].name);
    router.post('/app/knowledge/upload', form);
}

function addText() {
    router.post('/app/knowledge/text', textForm.value);
}

function addUrl() {
    router.post('/app/knowledge/url', urlForm.value);
}

function reprocess(source) {
    router.post(`/app/knowledge/${source.document_id}/reprocess`);
}

function remove(source) {
    if (!confirm('¿Eliminar esta fuente de conocimiento?')) return;
    router.delete(`/app/knowledge/${source.id}`);
}
</script>

<template>
    <AppLayout title="Base de conocimiento">
        <div class="mb-4 grid gap-4 lg:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="mb-3 flex gap-2 text-sm">
                    <button :class="tab === 'text' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'" class="rounded-lg px-3 py-1.5" @click="tab = 'text'">Texto</button>
                    <button :class="tab === 'url' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'" class="rounded-lg px-3 py-1.5" @click="tab = 'url'">URL</button>
                    <button :class="tab === 'file' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'" class="rounded-lg px-3 py-1.5" @click="tab = 'file'">Archivo</button>
                </div>

                <div v-if="tab === 'text'" class="space-y-3 text-sm">
                    <input v-model="textForm.title" placeholder="Título" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                    <textarea v-model="textForm.content" placeholder="Escribe tu contenido (preguntas, respuestas, políticas...)" rows="6" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    <button @click="addText" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Agregar</button>
                </div>

                <div v-else-if="tab === 'url'" class="space-y-3 text-sm">
                    <input v-model="urlForm.url" placeholder="https://..." class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                    <input v-model="urlForm.title" placeholder="Título (opcional)" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                    <button @click="addUrl" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Procesar URL</button>
                </div>

                <div v-else class="space-y-3 text-sm">
                    <input ref="upload" type="file" accept=".pdf,.txt,.md,.csv,.docx,.xlsx,.pptx" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs" />
                    <button @click="uploadFile" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Subir archivo</button>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2">
                <h3 class="mb-3 text-sm font-semibold text-slate-800">Fuentes ({{ sources.length }})</h3>
                <div class="divide-y divide-slate-100">
                    <div v-for="s in sources" :key="s.id" class="flex items-center gap-3 py-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-xs font-semibold uppercase text-slate-500">
                            {{ s.type.slice(0, 2) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-800">{{ s.title }}</p>
                            <p class="text-xs text-slate-400">
                                {{ s.type }} · {{ s.chunks }} chunks · {{ new Date(s.created_at).toLocaleDateString() }}
                                <span v-if="s.error" class="text-red-500">· {{ s.error }}</span>
                            </p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="statusStyles[s.status] || statusStyles.pending">
                            {{ s.status }}
                        </span>
                        <button v-if="s.document_id && s.status === 'failed'" @click="reprocess(s)" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                            Reprocesar
                        </button>
                        <button @click="remove(s)" class="text-xs text-red-500 hover:text-red-700">Eliminar</button>
                    </div>
                    <p v-if="!sources.length" class="py-6 text-center text-sm text-slate-400">Aún no hay fuentes. Agrega tu primer documento.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
