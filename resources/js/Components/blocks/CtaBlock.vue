<script setup>
import { computed } from 'vue';

const props = defineProps({
    block: Object,
    theme: Object,
    editable: Boolean,
});

const c = computed(() => props.block?.content || {});
const radius = computed(() => (props.theme.radius === 'full' ? '9999px' : props.theme.radius === 'none' ? '0' : '0.5rem'));
</script>

<template>
    <section class="py-16 md:py-20" :style="{ backgroundColor: theme.primary, color: '#fff', fontFamily: `'${theme.font}', sans-serif` }">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <h2 class="text-2xl font-bold md:text-3xl">{{ c.title || '¿Listo para empezar?' }}</h2>
            <p v-if="c.text" class="mx-auto mt-3 max-w-2xl opacity-90">{{ c.text }}</p>
            <a
                v-if="c.button?.label"
                :href="c.button.url || '#'"
                class="mt-6 inline-block px-6 py-3 text-sm font-semibold"
                :style="{ backgroundColor: '#fff', color: theme.primary, borderRadius: radius }"
            >
                {{ c.button.label }}
            </a>
        </div>
    </section>
</template>
