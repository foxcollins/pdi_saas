<script setup>
import ChatWidget from '../Components/ChatWidget.vue';
import BlockRenderer from '../Components/blocks/BlockRenderer.vue';

const props = defineProps({
    site: Object,
    page: Object,
    profile: Object,
    published: Boolean,
});

const theme = props.site?.theme || {};
const sections = props.page?.sections || [];
</script>

<template>
    <div>
        <div v-if="!published" class="bg-amber-100 px-4 py-2 text-center text-xs font-medium text-amber-800">
            Este sitio está en borrador. Cuando lo publiques aparecerá públicamente.
        </div>

        <div :style="{ fontFamily: `'${theme.font}', sans-serif` }">
            <BlockRenderer v-for="(block, i) in sections" :key="block.id || i" :block="block" :theme="theme" />
        </div>

        <footer
            class="py-10 text-center text-sm"
            :style="{ backgroundColor: theme.footer_style === 'dark' ? theme.text : theme.background, color: theme.footer_style === 'dark' ? theme.background : theme.text }"
        >
            <div class="mx-auto max-w-6xl px-6">
                <p class="font-semibold">{{ site.name }}</p>
                <p class="mt-1 opacity-70">© {{ new Date().getFullYear() }} · {{ site.name }}</p>
                <p v-if="profile?.contact?.email" class="mt-1 opacity-70">{{ profile.contact.email }}</p>
            </div>
        </footer>

        <ChatWidget v-if="theme.chat_enabled" :slug="site.slug" :title="site.chat?.title" :welcome="site.chat?.welcome" :primary="theme.primary" />
    </div>
</template>
