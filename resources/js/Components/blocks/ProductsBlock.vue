<script setup>
import { computed } from 'vue';

const props = defineProps({
    block: Object,
    theme: Object,
    editable: Boolean,
});

const c = computed(() => props.block?.content || {});
const radius = computed(() => (props.theme.radius === 'full' ? '1.5rem' : props.theme.radius === 'none' ? '0' : '1rem'));
</script>

<template>
    <section class="py-16 md:py-20" :style="{ backgroundColor: theme.background, color: theme.text, fontFamily: `'${theme.font}', sans-serif` }">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="text-2xl font-bold md:text-3xl">{{ c.title || 'Nuestros productos' }}</h2>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <div v-for="(p, i) in c.items || []" :key="i" class="overflow-hidden" :style="{ borderRadius: radius, border: '1px solid ' + (theme.text + '22') }">
                    <div v-if="p.image" class="aspect-video bg-slate-100">
                        <img :src="p.image" :alt="p.title" class="h-full w-full object-cover" />
                    </div>
                    <div v-else class="aspect-video flex items-center justify-center" :style="{ backgroundColor: theme.primary + '1a', color: theme.primary }">
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-3 0-5 2-5 4s2 4 5 4 5-2 5-4-2-4-5-4z" />
                        </svg>
                    </div>
                    <div class="p-5">
                        <h3 class="font-semibold">{{ p.title }}</h3>
                        <p class="mt-1 text-sm" :style="{ color: theme.muted }">{{ p.description }}</p>
                        <p v-if="p.price" class="mt-3 text-sm font-bold" :style="{ color: theme.primary }">{{ p.price }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
