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
            <h2 class="text-2xl font-bold md:text-3xl">{{ c.title || 'Galería' }}</h2>
            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <div v-for="(img, i) in c.images || []" :key="i" class="aspect-square overflow-hidden rounded-xl bg-slate-200">
                    <img v-if="img.url" :src="img.url" :alt="img.alt" class="h-full w-full object-cover" />
                </div>
            </div>
        </div>
    </section>
</template>
