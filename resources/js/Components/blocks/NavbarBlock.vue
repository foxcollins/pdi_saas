<script setup>
import { computed } from 'vue';

const props = defineProps({
    block: Object,
    theme: Object,
    editable: Boolean,
});

const logo = computed(() => props.block?.content?.logo || '');
const isLogoImage = computed(() => /^(https?:\/\/|\/)/i.test(logo.value));
</script>

<template>
    <header
        :style="{ backgroundColor: theme.background, color: theme.text, fontFamily: `'${theme.font}', sans-serif`, borderColor: theme.text + '22' }"
        :class="theme.header_style === 'sticky' && !editable ? 'sticky top-0 z-20' : ''"
        class="border-b"
    >
        <nav class="mx-auto flex min-w-0 max-w-6xl items-center gap-4 px-6 py-4">
            <div class="min-w-0 shrink-0">
                <img v-if="isLogoImage" :src="logo" alt="Logo" class="block max-h-9 max-w-32 object-contain" />
                <span v-else class="block max-w-32 truncate font-bold tracking-tight">{{ logo || 'LOGO' }}</span>
            </div>
            <div class="hidden min-w-0 flex-1 items-center justify-end gap-6 overflow-hidden text-sm md:flex">
                <a v-for="l in block?.content?.links || []" :key="l.label" :href="l.url" class="hover:opacity-70">
                    {{ l.label }}
                </a>
            </div>
            <a
                v-if="block?.content?.cta?.label"
                :href="block.content.cta.url || '#'"
                :style="{ backgroundColor: theme.primary, color: '#fff', borderRadius: theme.radius === 'full' ? '9999px' : theme.radius === 'none' ? '0' : '0.5rem' }"
                class="shrink-0 whitespace-nowrap px-4 py-2 text-sm font-medium"
            >
                {{ block.content.cta.label }}
            </a>
        </nav>
    </header>
</template>
