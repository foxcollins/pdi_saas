import { defineAsyncComponent } from 'vue';

const registry = {
    navbar: defineAsyncComponent(() => import('./NavbarBlock.vue')),
    hero: defineAsyncComponent(() => import('./HeroBlock.vue')),
    services: defineAsyncComponent(() => import('./ServicesBlock.vue')),
    products: defineAsyncComponent(() => import('./ProductsBlock.vue')),
    about: defineAsyncComponent(() => import('./AboutBlock.vue')),
    team: defineAsyncComponent(() => import('./TeamBlock.vue')),
    testimonials: defineAsyncComponent(() => import('./TestimonialsBlock.vue')),
    gallery: defineAsyncComponent(() => import('./GalleryBlock.vue')),
    stats: defineAsyncComponent(() => import('./StatsBlock.vue')),
    faq: defineAsyncComponent(() => import('./FaqBlock.vue')),
    cta: defineAsyncComponent(() => import('./CtaBlock.vue')),
    contact: defineAsyncComponent(() => import('./ContactBlock.vue')),
    map: defineAsyncComponent(() => import('./MapBlock.vue')),
};

export default registry;
