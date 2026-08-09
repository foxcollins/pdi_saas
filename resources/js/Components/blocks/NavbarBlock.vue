<script setup>
const props = defineProps({
    block: Object,
    theme: Object,
    editable: Boolean,
});
</script>

<template>
    <header
        :style="{ backgroundColor: theme.background, color: theme.text, fontFamily: `'${theme.font}', sans-serif`, borderColor: theme.text + '22' }"
        :class="theme.header_style === 'sticky' ? 'sticky top-0 z-20' : ''"
        class="border-b"
    >
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <span class="font-bold tracking-tight">{{ block?.content?.logo || 'LOGO' }}</span>
            <div class="hidden items-center gap-6 text-sm md:flex">
                <a v-for="l in block?.content?.links || []" :key="l.label" :href="l.url" class="hover:opacity-70">
                    {{ l.label }}
                </a>
            </div>
            <a
                v-if="block?.content?.cta?.label"
                :href="block.content.cta.url || '#'"
                :style="{ backgroundColor: theme.primary, color: '#fff', borderRadius: theme.radius === 'full' ? '9999px' : theme.radius === 'none' ? '0' : '0.5rem' }"
                class="px-4 py-2 text-sm font-medium"
            >
                {{ block.content.cta.label }}
            </a>
        </nav>
    </header>
</template>
