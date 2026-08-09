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
            <h2 class="text-center text-2xl font-bold md:text-3xl">{{ c.title || 'Testimonios' }}</h2>
            <div class="mt-8 grid gap-5 md:grid-cols-3">
                <figure
                    v-for="(t, i) in c.items || []"
                    :key="i"
                    class="p-6"
                    :style="{ borderRadius: radius, backgroundColor: theme.background === '#ffffff' || theme.background.includes('#fff') ? '#f8fafc' : '#ffffff0d', border: '1px solid ' + (theme.text + '22') }"
                >
                    <svg class="h-6 w-6" fill="currentColor" :style="{ color: theme.primary }" viewBox="0 0 24 24">
                        <path d="M6 7h4v4H6V7zm8 0h4v4h-4V7zM6 13h4v4H6v-4zm8 0h4v4h-4v-4z" />
                    </svg>
                    <blockquote class="mt-3 text-sm">{{ t.quote }}</blockquote>
                    <figcaption class="mt-4">
                        <p class="font-semibold text-sm">{{ t.author }}</p>
                        <p class="text-xs" :style="{ color: theme.muted }">{{ t.role }}</p>
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>
</template>
