<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    block: Object,
    theme: Object,
    editable: Boolean,
});

const c = computed(() => props.block?.content || {});
const open = ref(0);
</script>

<template>
    <section class="py-16 md:py-20" :style="{ backgroundColor: theme.background, color: theme.text, fontFamily: `'${theme.font}', sans-serif` }">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="text-center text-2xl font-bold md:text-3xl">{{ c.title || 'Preguntas frecuentes' }}</h2>
            <div class="mt-8 space-y-3">
                <div
                    v-for="(f, i) in c.items || []"
                    :key="i"
                    class="border"
                    :style="{ borderRadius: theme.radius === 'full' ? '0.75rem' : theme.radius === 'none' ? '0' : '0.5rem', borderColor: theme.text + '22' }"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between px-5 py-4 text-left font-medium"
                        @click="open = open === i ? -1 : i"
                    >
                        {{ f.q }}
                        <svg class="h-4 w-4 shrink-0 transition" :class="open === i ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-if="open === i" class="px-5 pb-4 text-sm" :style="{ color: theme.muted }">{{ f.a }}</div>
                </div>
            </div>
        </div>
    </section>
</template>
