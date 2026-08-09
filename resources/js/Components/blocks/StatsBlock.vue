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
    <section class="py-14 md:py-16" :style="{ backgroundColor: theme.background, color: theme.text, fontFamily: `'${theme.font}', sans-serif` }">
        <div class="mx-auto grid max-w-6xl grid-cols-2 gap-6 px-6 md:grid-cols-4">
            <div v-for="(s, i) in c.items || []" :key="i" class="text-center">
                <p class="text-3xl font-bold md:text-4xl" :style="{ color: theme.primary }">{{ s.value }}</p>
                <p class="mt-1 text-sm" :style="{ color: theme.muted }">{{ s.label }}</p>
            </div>
        </div>
    </section>
</template>
