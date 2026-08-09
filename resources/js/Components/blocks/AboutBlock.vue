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
            <h2 class="text-2xl font-bold md:text-3xl">{{ c.title || 'Sobre nosotros' }}</h2>
            <div :class="block?.variant === 'split' && c.image ? 'mt-8 grid items-center gap-10 md:grid-cols-2' : 'mt-6 max-w-3xl'">
                <p v-if="c.text" class="text-lg" :style="{ color: theme.muted }">{{ c.text }}</p>
                <img v-if="block?.variant === 'split' && c.image" :src="c.image" alt="" class="rounded-2xl object-cover" />
            </div>
        </div>
    </section>
</template>
