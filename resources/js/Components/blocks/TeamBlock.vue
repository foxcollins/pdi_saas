<script setup>
import { computed } from 'vue';

const props = defineProps({
    block: Object,
    theme: Object,
    editable: Boolean,
});

const c = computed(() => props.block?.content || {});
</script>

<template>
    <section class="py-16 md:py-20" :style="{ backgroundColor: theme.background, color: theme.text, fontFamily: `'${theme.font}', sans-serif` }">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="text-2xl font-bold md:text-3xl">{{ c.title || 'Nuestro equipo' }}</h2>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <div v-for="(m, i) in c.items || []" :key="i" class="text-center">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full text-2xl font-bold" :style="{ backgroundColor: theme.primary + '22', color: theme.primary }">
                        {{ (m.name || '?')[0] }}
                    </div>
                    <h3 class="mt-3 font-semibold">{{ m.name }}</h3>
                    <p class="text-sm" :style="{ color: theme.muted }">{{ m.role }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
