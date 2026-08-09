<script setup>
import { computed } from 'vue';
import registry from './registry';

const props = defineProps({
    block: { type: Object, required: true },
    theme: { type: Object, required: true },
    editable: { type: Boolean, default: false },
    selected: { type: Boolean, default: false },
});

const component = computed(() => registry[props.block.type]);
</script>

<template>
    <div class="relative" :class="selected ? 'ring-2 ring-indigo-500 ring-offset-2 ring-offset-slate-100 rounded-lg' : ''">
        <component
            :is="component"
            v-if="component"
            :block="block"
            :theme="theme"
            :editable="editable"
        />
        <div v-else class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-400">
            Bloque "{{ block.type }}" sin componente registrado.
        </div>
    </div>
</template>
