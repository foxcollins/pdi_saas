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
            <h2 class="text-2xl font-bold md:text-3xl">{{ c.title || 'Ubicación' }}</h2>
            <div class="mt-6 overflow-hidden rounded-2xl border" :style="{ borderColor: theme.text + '22' }">
                <iframe
                    v-if="c.embed_url"
                    :src="c.embed_url"
                    class="h-96 w-full"
                    loading="lazy"
                    style="border: 0"
                ></iframe>
                <div v-else class="flex h-64 items-center justify-center text-sm" :style="{ color: theme.muted, backgroundColor: theme.text + '0d' }">
                    Agrega una URL de mapa (Google Maps embed)
                </div>
            </div>
        </div>
    </section>
</template>
