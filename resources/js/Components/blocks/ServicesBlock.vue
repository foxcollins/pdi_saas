<script setup>
import { computed } from 'vue';

const props = defineProps({
    block: Object,
    theme: Object,
    editable: Boolean,
});

const c = computed(() => props.block?.content || {});
const radius = computed(() => (props.theme.radius === 'full' ? '1rem' : props.theme.radius === 'none' ? '0' : '0.75rem'));
</script>

<template>
    <section class="py-16 md:py-20" :style="{ backgroundColor: theme.background, color: theme.text, fontFamily: `'${theme.font}', sans-serif` }">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="text-2xl font-bold md:text-3xl">{{ c.title || 'Nuestros servicios' }}</h2>
            <div :class="block?.variant === 'grid' ? 'mt-8 grid gap-5 md:grid-cols-3' : 'mt-8 grid gap-5 md:grid-cols-2'">
                <div
                    v-for="(s, i) in c.items || []"
                    :key="i"
                    class="p-6"
                    :style="{ borderRadius: radius, backgroundColor: theme.background === '#ffffff' || theme.background.includes('#fff') ? '#f8fafc' : '#ffffff0d', border: '1px solid ' + (theme.text + '22') }"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg" :style="{ backgroundColor: theme.primary + '22', color: theme.primary }">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-6-6h12" />
                        </svg>
                    </div>
                    <h3 class="mt-4 font-semibold">{{ s.title }}</h3>
                    <p class="mt-1 text-sm" :style="{ color: theme.muted }">{{ s.description }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
