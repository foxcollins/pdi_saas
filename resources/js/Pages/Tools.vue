<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../Components/AppLayout.vue';

const props = defineProps({
    tools: Array,
    enabled: Array,
    products: Array,
    quotes: Array,
});

const flash = usePage().props.flash || {};
const success = computed(() => flash.success || '');

const enabledTools = ref([...(props.enabled || [])]);

function toggleTool(name) {
    if (enabledTools.value.includes(name)) {
        enabledTools.value = enabledTools.value.filter((t) => t !== name);
    } else {
        enabledTools.value.push(name);
    }
}

function saveTools() {
    router.put('/app/tools', { tools: enabledTools.value }, { preserveScroll: true });
}

const permissionLabel = { read: 'Lectura', internal: 'Escritura interna', external: 'Externa', destructive: 'Destructiva' };

const newProduct = ref({ title: '', description: '', price: '', currency: 'USD', unit: '', category: '' });

function addProduct() {
    router.post('/app/tools/products', newProduct.value, {
        preserveScroll: true,
        onSuccess: () => {
            newProduct.value = { title: '', description: '', price: '', currency: 'USD', unit: '', category: '' };
        },
    });
}

function toggleProduct(product) {
    router.put(`/app/tools/products/${product.id}`, {
        ...product,
        price: product.price ?? 0,
        is_active: !product.is_active,
    }, { preserveScroll: true });
}

function deleteProduct(product) {
    if (!confirm(`¿Eliminar el producto "${product.title}"?`)) return;
    router.delete(`/app/tools/products/${product.id}`, { preserveScroll: true });
}

function setQuoteStatus(quote, status) {
    router.patch(`/app/tools/quotes/${quote.id}`, { status }, { preserveScroll: true });
}

const money = (v, c) => `${c ?? 'USD'} ${Number(v).toLocaleString('en', { minimumFractionDigits: 2 })}`;
</script>

<template>
    <AppLayout title="Tools">
        <h1 class="text-xl font-bold text-slate-900">Herramientas del agente</h1>
        <p class="mt-1 text-sm text-slate-500">Activa las herramientas que tu asistente puede usar para cotizar, registrar leads y crear tareas.</p>

        <div v-if="success" class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ success }}
        </div>

        <div class="mt-5 rounded-xl border border-slate-200 bg-white p-6">
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                <label
                    v-for="tool in tools"
                    :key="tool.name"
                    class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition"
                    :class="enabledTools.includes(tool.name) ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200 hover:border-slate-300'"
                >
                    <input type="checkbox" :checked="enabledTools.includes(tool.name)" @change="toggleTool(tool.name)" class="mt-1 h-4 w-4 accent-indigo-600" />
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ tool.name }}</p>
                        <p class="text-xs text-slate-500">{{ tool.description }}</p>
                        <span class="mt-1 inline-block rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">
                            {{ permissionLabel[tool.permission] || tool.permission }}
                        </span>
                    </div>
                </label>
            </div>
            <button
                @click="saveTools"
                class="mt-4 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            >
                Guardar herramientas
            </button>
        </div>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-800">Catálogo de productos</h2>
            <p class="mt-1 text-xs text-slate-500">Usado por las tools <code>catalog_lookup</code>, <code>quote_calculator</code> y <code>create_quote</code>.</p>

            <div class="mt-4 flex flex-wrap items-end gap-2">
                <input v-model="newProduct.title" placeholder="Título del producto" class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none" />
                <input v-model="newProduct.price" type="number" step="0.01" min="0" placeholder="Precio" class="w-28 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none" />
                <input v-model="newProduct.currency" placeholder="USD" maxlength="3" class="w-20 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none" />
                <input v-model="newProduct.category" placeholder="Categoría" class="w-36 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none" />
                <button @click="addProduct" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                    Añadir
                </button>
            </div>

            <div class="mt-4 divide-y divide-slate-100">
                <div v-for="product in products" :key="product.id" class="flex items-center justify-between gap-3 py-3">
                    <div>
                        <p class="text-sm font-medium text-slate-800">{{ product.title }}</p>
                        <p class="text-xs text-slate-500">{{ product.category || 'Sin categoría' }} · {{ money(product.price, product.currency) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px]" :class="product.is_active ? 'text-green-600' : 'text-slate-400'">
                            {{ product.is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                        <button @click="toggleProduct(product)" class="rounded border border-slate-200 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">
                            {{ product.is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                        <button @click="deleteProduct(product)" class="rounded border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50">
                            Eliminar
                        </button>
                    </div>
                </div>
                <p v-if="products.length === 0" class="py-4 text-sm text-slate-400">Aún no hay productos en el catálogo.</p>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-800">Cotizaciones generadas</h2>
            <div class="mt-4 divide-y divide-slate-100">
                <div v-for="quote in quotes" :key="quote.id" class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div>
                        <p class="text-sm font-medium text-slate-800">{{ quote.number }}</p>
                        <p class="text-xs text-slate-500">
                            {{ quote.contact?.name || 'Sin cliente' }} · {{ money(quote.total, quote.currency) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ quote.status }}</span>
                        <button @click="setQuoteStatus(quote, 'sent')" class="rounded border border-slate-200 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Enviada</button>
                        <button @click="setQuoteStatus(quote, 'accepted')" class="rounded border border-green-200 px-2 py-1 text-xs text-green-700 hover:bg-green-50">Aceptar</button>
                        <button @click="setQuoteStatus(quote, 'rejected')" class="rounded border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50">Rechazar</button>
                    </div>
                </div>
                <p v-if="quotes.length === 0" class="py-4 text-sm text-slate-400">Aún no se generaron cotizaciones.</p>
            </div>
        </div>
    </AppLayout>
</template>
