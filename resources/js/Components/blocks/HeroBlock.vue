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
    <section
        :style="{ backgroundColor: theme.background, color: theme.text, fontFamily: `'${theme.font}', sans-serif` }"
        :class="block?.variant === 'fullscreen' ? 'min-h-[85vh] flex items-center' : 'py-16 md:py-24'"
    >
        <div :class="block?.variant === 'split' ? 'mx-auto grid max-w-6xl items-center gap-10 px-6 md:grid-cols-2' : 'mx-auto max-w-4xl px-6 text-center'">
            <div>
                <p v-if="c.badge" class="mb-4 inline-block rounded-full px-3 py-1 text-xs font-medium" :style="{ backgroundColor: theme.primary + '22', color: theme.primary }">
                    {{ c.badge }}
                </p>
                <h1 class="text-3xl font-bold leading-tight md:text-5xl" :style="{ fontFamily: `'${theme.font}', sans-serif` }">
                    {{ c.title || 'Tu empresa, con presencia digital inteligente' }}
                </h1>
                <p v-if="c.subtitle" class="mt-4 text-lg" :style="{ color: theme.muted }">{{ c.subtitle }}</p>
                <div v-if="c.primary_cta?.label || c.secondary_cta?.label" class="mt-7 flex flex-wrap justify-center gap-3 md:justify-start">
                    <a
                        v-if="c.primary_cta?.label"
                        :href="c.primary_cta.url || '#'"
                        :style="{ backgroundColor: theme.primary, color: '#fff', borderRadius: radius }"
                        class="px-6 py-3 text-sm font-semibold"
                    >{{ c.primary_cta.label }}</a>
                    <a
                        v-if="c.secondary_cta?.label"
                        :href="c.secondary_cta.url || '#'"
                        :style="{ borderColor: theme.primary, color: theme.primary, borderRadius: radius }"
                        class="border px-6 py-3 text-sm font-semibold"
                    >{{ c.secondary_cta.label }}</a>
                </div>
            </div>
            <div v-if="block?.variant === 'split' && c.image" class="overflow-hidden rounded-2xl" :style="{ borderRadius: radius }">
                <img :src="c.image" alt="" class="h-full w-full object-cover" />
            </div>
        </div>
    </section>
</template>
