<script setup>
import { reactive, ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';
import BlockRenderer from '../Components/blocks/BlockRenderer.vue';

const props = defineProps({
    site: Object,
    page: Object,
    catalog: Object,
});

const flash = usePage().props.flash || {};

const sections = ref([...(props.page?.sections || [])]);
const theme = reactive({ ...(props.site?.theme || {}) });
const selected = ref(null);

watch(
    () => props.page?.sections,
    (value) => {
        sections.value = JSON.parse(JSON.stringify(value || []));
        selected.value = null;
    },
    { deep: true },
);

watch(
    () => props.site?.theme,
    (value) => {
        Object.keys(theme).forEach((key) => delete theme[key]);
        Object.assign(theme, value || {});
    },
    { deep: true },
);

const blocksCatalog = computed(() => props.catalog.blocks);
const templates = computed(() => props.catalog.templates);

const saving = ref(false);
const saved = ref(false);
const saveError = ref('');
const mediaBusy = ref(false);
const mediaError = ref('');
const previewMode = ref('desktop');

const aiModal = ref(false);
const aiForm = reactive({ company_name: '', industry: '', description: '', services: '', style: '' });
const refineModal = ref(false);
const refineText = ref('');
const aiBusy = ref(false);

function select(i) {
    selected.value = i;
}

function addBlock(type) {
    const blockDef = blocksCatalog.value[type];
    if (!blockDef) return;
    const variants = blockDef.variants || {};
    const variantKey = Object.keys(variants)[0];
    const variant = variants[variantKey] || {};

    sections.value.push({
        id: crypto.randomUUID(),
        type,
        variant: variantKey,
        content: JSON.parse(JSON.stringify(variant.content || {})),
        settings: {},
    });
    selected.value = sections.value.length - 1;
}

function duplicateBlock(i) {
    const copy = JSON.parse(JSON.stringify(sections.value[i]));
    copy.id = crypto.randomUUID();
    sections.value.splice(i + 1, 0, copy);
    selected.value = i + 1;
}

function removeBlock(i) {
    sections.value.splice(i, 1);
    if (selected.value === i) selected.value = null;
}

function move(i, dir) {
    const j = i + dir;
    if (j < 0 || j >= sections.value.length) return;
    const tmp = sections.value[i];
    sections.value[i] = sections.value[j];
    sections.value[j] = tmp;
    selected.value = j;
}

function changeVariant(i, variantKey) {
    const blockDef = blocksCatalog.value[sections.value[i].type];
    const variant = blockDef?.variants?.[variantKey] || {};
    sections.value[i].variant = variantKey;
    sections.value[i].content = JSON.parse(JSON.stringify(variant.content || {}));
}

function mediaTarget(block) {
    const content = block?.content || {};

    if (Object.prototype.hasOwnProperty.call(content, 'image')) {
        return { set: (url) => (content.image = url) };
    }

    if (Object.prototype.hasOwnProperty.call(content, 'logo')) {
        return { set: (url) => (content.logo = url) };
    }

    if (Array.isArray(content.items)) {
        const item = content.items.find((value) => value && (Object.prototype.hasOwnProperty.call(value, 'image') || Object.prototype.hasOwnProperty.call(value, 'photo')));
        if (item) {
            const key = Object.prototype.hasOwnProperty.call(item, 'image') ? 'image' : 'photo';
            return { set: (url) => (item[key] = url) };
        }
    }

    if (Array.isArray(content.images) && content.images[0]) {
        return { set: (url) => (content.images[0].url = url) };
    }

    return null;
}

async function uploadMedia(event) {
    const file = event.target.files?.[0];
    const target = selected.value !== null ? mediaTarget(sections.value[selected.value]) : null;
    if (!file || !target) return;

    mediaBusy.value = true;
    mediaError.value = '';
    try {
        const form = new FormData();
        form.append('file', file);
        const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
        const res = await fetch('/app/media', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            body: form,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.media?.url) {
            throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'No se pudo subir la imagen.');
        }
        target.set(data.media.url);
    } catch (error) {
        mediaError.value = error.message || 'No se pudo subir la imagen.';
    } finally {
        mediaBusy.value = false;
        event.target.value = '';
    }
}

async function save() {
    saving.value = true;
    saved.value = false;
    saveError.value = '';
    try {
        const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
        const res = await fetch('/app/builder/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ page: { ...props.page, sections: sections.value }, theme }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'No se pudo guardar el sitio.');
        }
        if (data.ok) {
            saved.value = true;
            setTimeout(() => (saved.value = false), 2500);
        }
    } catch (error) {
        saveError.value = error.message || 'No se pudo guardar el sitio.';
    } finally {
        saving.value = false;
    }
}

function publish() {
    router.post('/app/builder/publish');
}

function applyTemplate(templateSlug) {
    if (!confirm('¿Aplicar este template? Se reemplazarán las secciones actuales.')) return;
    router.post('/app/builder/template', { template: templateSlug });
}

async function generateWithAi() {
    aiBusy.value = true;
    try {
        const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
        const res = await fetch('/app/ai/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(aiForm),
        });
        const data = await res.json();
        if (data.ok) {
            sections.value = data.page.sections || [];
            Object.assign(theme, data.theme || {});
            selected.value = null;
            aiModal.value = false;
            save();
        }
    } finally {
        aiBusy.value = false;
    }
}

async function refine() {
    aiBusy.value = true;
    try {
        const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
        const res = await fetch('/app/ai/refine', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ instruction: refineText.value }),
        });
        const data = await res.json();
        if (data.ok) {
            sections.value = data.page.sections || [];
            Object.assign(theme, data.theme || {});
            refineModal.value = false;
            refineText.value = '';
            save();
        }
    } finally {
        aiBusy.value = false;
    }
}
</script>

<template>
    <AppLayout title="Diseñador de sitio">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button @click="aiModal = true" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700">
                    ✨ Crear con IA
                </button>
                <button @click="refineModal = true" class="rounded-lg border border-violet-300 px-4 py-2 text-sm font-medium text-violet-700 hover:bg-violet-50">
                    Refinar con IA
                </button>
                <button @click="applyTemplate(site.template)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Reset template
                </button>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium" :class="site.status === 'live' ? 'text-green-700' : 'text-amber-700'">
                    {{ site.status === 'live' ? 'Publicado' : 'Borrador' }}
                </span>
                <button @click="publish" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    {{ site.status === 'live' ? 'Despublicar' : 'Publicar' }}
                </button>
                <button @click="save" :disabled="saving" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                    {{ saving ? 'Guardando...' : saved ? '✓ Guardado' : 'Guardar' }}
                </button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[240px_1fr_320px]">
            <aside class="rounded-xl border border-slate-200 bg-white p-3">
                <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Bloques</p>
                <div class="space-y-1">
                    <button
                        v-for="(def, type) in blocksCatalog"
                        :key="type"
                        @click="addBlock(type)"
                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                    >
                        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="def.icon" />
                        </svg>
                        {{ def.label }}
                    </button>
                </div>

                <p class="mt-5 px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Templates</p>
                <div class="space-y-1">
                    <button
                        v-for="(t, slug) in templates"
                        :key="slug"
                        @click="applyTemplate(slug)"
                        class="w-full rounded-lg px-3 py-2 text-left text-sm"
                        :class="site.template === slug ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-300' : 'text-slate-700 hover:bg-slate-100'"
                    >
                        <span class="block font-medium">{{ t.label }}</span>
                        <span class="block text-xs text-slate-400">{{ t.industry.join(', ') }}</span>
                    </button>
                </div>
            </aside>

            <div class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vista previa</p>
                    <div class="flex rounded-lg border border-slate-200 bg-white p-1 text-xs">
                        <button v-for="mode in [{ key: 'desktop', label: 'Desktop' }, { key: 'tablet', label: 'Tablet' }, { key: 'mobile', label: 'Móvil' }]" :key="mode.key" @click="previewMode = mode.key" class="rounded-md px-2 py-1" :class="previewMode === mode.key ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-slate-100'">
                            {{ mode.label }}
                        </button>
                    </div>
                </div>
                <div class="mx-auto min-w-0 transition-all" :class="{ 'max-w-full': previewMode === 'desktop', 'max-w-[768px]': previewMode === 'tablet', 'max-w-[390px]': previewMode === 'mobile' }">
                <div class="min-w-0 space-y-4">
                    <div
                        v-for="(block, i) in sections"
                        :key="block.id || i"
                        class="relative"
                        @click="select(i)"
                    >
                        <div class="absolute -top-3 right-2 z-40 flex items-center gap-1 rounded-full border border-slate-300 bg-white px-2 py-1 shadow">
                            <button title="Subir" class="text-slate-500 hover:text-slate-800" @click.stop="move(i, -1)">▲</button>
                            <button title="Bajar" class="text-slate-500 hover:text-slate-800" @click.stop="move(i, 1)">▼</button>
                            <button title="Duplicar" class="text-slate-500 hover:text-slate-800" @click.stop="duplicateBlock(i)">⧉</button>
                            <button title="Eliminar" class="text-red-500 hover:text-red-700" @click.stop="removeBlock(i)">✕</button>
                        </div>
                        <BlockRenderer :block="block" :theme="theme" :editable="true" :selected="selected === i" />
                    </div>

                    <div
                        v-if="!sections.length"
                        class="rounded-xl border-2 border-dashed border-slate-300 p-12 text-center text-sm text-slate-400"
                    >
                        Añade bloques desde el panel izquierdo.
                    </div>
                </div>
                </div>
            </div>

            <aside class="rounded-xl border border-slate-200 bg-white p-4">
                <template v-if="selected !== null && sections[selected]">
                    <p class="text-sm font-semibold text-slate-800">
                        {{ blocksCatalog[sections[selected].type]?.label }}
                    </p>
                    <div v-if="mediaTarget(sections[selected])" class="mt-3 rounded-lg border border-dashed border-indigo-200 bg-indigo-50 p-3">
                        <label class="block text-xs font-medium text-indigo-700">Imagen del bloque</label>
                        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-2 block w-full text-xs text-slate-600" :disabled="mediaBusy" @change="uploadMedia" />
                        <p class="mt-1 text-[11px] text-indigo-600">{{ mediaBusy ? 'Subiendo...' : 'Máximo 5 MB' }}</p>
                        <p v-if="mediaError" class="mt-1 text-[11px] text-red-600">{{ mediaError }}</p>
                    </div>
                    <div class="mt-2">
                        <label class="text-xs font-medium text-slate-500">Variante</label>
                        <select
                            :value="sections[selected].variant"
                            @change="changeVariant(selected, $event.target.value)"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                        >
                            <option v-for="(v, key) in blocksCatalog[sections[selected].type]?.variants || {}" :key="key" :value="key">
                                {{ v.label }}
                            </option>
                        </select>
                    </div>

                    <div class="mt-4 space-y-3 text-sm">
                        <template v-for="(value, key) in sections[selected].content" :key="key">
                            <div v-if="typeof value === 'string'">
                                <label class="block text-xs font-medium capitalize text-slate-500">{{ key }}</label>
                                <input
                                    :value="value"
                                    @input="sections[selected].content[key] = $event.target.value"
                                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5"
                                />
                            </div>
                            <div v-else-if="typeof value === 'number'">
                                <label class="block text-xs font-medium capitalize text-slate-500">{{ key }}</label>
                                <input
                                    :value="value"
                                    type="number"
                                    @input="sections[selected].content[key] = Number($event.target.value)"
                                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5"
                                />
                            </div>
                            <div v-else-if="Array.isArray(value)">
                                <label class="block text-xs font-medium capitalize text-slate-500">{{ key }}</label>
                                <div class="mt-1 space-y-2">
                                    <div v-for="(item, ii) in value" :key="ii" class="rounded-lg border border-slate-200 p-2">
                                        <div v-for="(iv, ik) in item" :key="ik" class="mb-1">
                                            <label class="block text-[11px] text-slate-400 capitalize">{{ ik }}</label>
                                            <input
                                                :value="iv"
                                                @input="item[ik] = $event.target.value"
                                                class="w-full rounded-md border border-slate-200 px-2 py-1 text-xs"
                                            />
                                        </div>
                                        <button @click="value.splice(ii, 1)" class="text-xs text-red-500 hover:text-red-700">Quitar</button>
                                    </div>
                                    <button @click="value.push({ title: '', description: '' })" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                        + Añadir
                                    </button>
                                </div>
                            </div>
                            <div v-else-if="value && typeof value === 'object'">
                                <label class="block text-xs font-medium capitalize text-slate-500">{{ key }}</label>
                                <div class="mt-1 space-y-1 rounded-lg border border-slate-200 p-2">
                                    <div v-for="(iv, ik) in value" :key="ik" class="mb-1">
                                        <label class="block text-[11px] text-slate-400 capitalize">{{ ik }}</label>
                                        <textarea v-if="typeof iv === 'string' && iv.length > 100" :value="iv" @input="value[ik] = $event.target.value" rows="3" class="w-full rounded-md border border-slate-200 px-2 py-1 text-xs"></textarea>
                                        <input v-else-if="typeof iv === 'string'" :value="iv" @input="value[ik] = $event.target.value" class="w-full rounded-md border border-slate-200 px-2 py-1 text-xs" />
                                        <input v-else-if="typeof iv === 'number'" :value="iv" type="number" @input="value[ik] = Number($event.target.value)" class="w-full rounded-md border border-slate-200 px-2 py-1 text-xs" />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template v-else>
                    <p class="text-sm font-semibold text-slate-800">Diseño</p>
                    <div class="mt-4 space-y-3 text-sm">
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Color primario</label>
                            <input v-model="theme.primary" type="color" class="mt-1 h-9 w-full rounded-lg border border-slate-300" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Color secundario</label>
                            <input v-model="theme.secondary" type="color" class="mt-1 h-9 w-full rounded-lg border border-slate-300" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Fondo</label>
                            <input v-model="theme.background" type="color" class="mt-1 h-9 w-full rounded-lg border border-slate-300" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Texto</label>
                            <input v-model="theme.text" type="color" class="mt-1 h-9 w-full rounded-lg border border-slate-300" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Texto secundario</label>
                            <input v-model="theme.muted" type="color" class="mt-1 h-9 w-full rounded-lg border border-slate-300" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Tipografía</label>
                            <select v-model="theme.font" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5">
                                <option v-for="f in catalog.fonts" :key="f" :value="f">{{ f }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Esquinas</label>
                            <select v-model="theme.radius" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5">
                                <option v-for="r in catalog.radius_options" :key="r" :value="r">{{ r }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Botones</label>
                            <select v-model="theme.button_style" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5">
                                <option v-for="b in catalog.button_styles" :key="b" :value="b">{{ b }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Animación</label>
                            <select v-model="theme.animation" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5">
                                <option v-for="a in catalog.animations" :key="a" :value="a">{{ a }}</option>
                            </select>
                        </div>
                        <label class="flex items-center gap-2 text-xs text-slate-600">
                            <input v-model="theme.chat_enabled" type="checkbox" class="rounded" />
                            Asistente de chat activo
                        </label>
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Título del chat</label>
                            <input v-model="theme.chat_title" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500">Mensaje de bienvenida</label>
                            <input v-model="theme.chat_welcome" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5" />
                        </div>
                    </div>
                </template>
            </aside>
        </div>
        <p v-if="saveError" class="fixed bottom-5 right-5 z-50 max-w-sm rounded-lg bg-red-600 px-4 py-3 text-sm text-white shadow-lg">
            {{ saveError }}
        </p>

        <div v-if="aiModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="aiModal = false">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6">
                <h3 class="text-lg font-semibold text-slate-800">Crear mi sitio con IA</h3>
                <p class="mt-1 text-sm text-slate-500">Cuéntanos sobre tu negocio y generaremos una propuesta.</p>
                <div class="mt-4 space-y-3 text-sm">
                    <input v-model="aiForm.company_name" placeholder="Nombre de la empresa" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                    <input v-model="aiForm.industry" placeholder="Industria (ej. ingeniería, restaurante...)" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                    <textarea v-model="aiForm.description" placeholder="¿Qué hace tu empresa?" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    <input v-model="aiForm.services" placeholder="Servicios (separados por coma)" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                    <input v-model="aiForm.style" placeholder="Estilo (moderno, premium, minimal...)" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="aiModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600">Cancelar</button>
                    <button @click="generateWithAi" :disabled="aiBusy" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                        {{ aiBusy ? 'Generando...' : 'Generar sitio' }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="refineModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="refineModal = false">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6">
                <h3 class="text-lg font-semibold text-slate-800">Refinar con IA</h3>
                <p class="mt-1 text-sm text-slate-500">Describe qué cambiar: "Hazlo más premium", "pon los productos antes", "estilo Apple"...</p>
                <input v-model="refineText" placeholder="Tu instrucción..." class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" @keyup.enter="refine" />
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="refineModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600">Cancelar</button>
                    <button @click="refine" :disabled="aiBusy || !refineText" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                        {{ aiBusy ? 'Refinando...' : 'Aplicar' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
