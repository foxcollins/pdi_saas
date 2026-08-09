<script setup>
import { computed, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    block: Object,
    theme: Object,
    editable: Boolean,
});

const page = usePage();
const c = computed(() => props.block?.content || {});
const radius = computed(() => (props.theme.radius === 'full' ? '0.75rem' : props.theme.radius === 'none' ? '0' : '0.5rem'));

const form = reactive({ name: '', email: '', phone: '', subject: '', message: '' });
const sent = ref(false);
const sending = ref(false);
const error = ref('');

async function submit() {
    sending.value = true;
    error.value = '';
    try {
        const res = await fetch(`/api/contact/${page.props.site?.slug || ''}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
            body: JSON.stringify(form),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Error');
        sent.value = true;
    } catch (e) {
        error.value = e.message;
    } finally {
        sending.value = false;
    }
}
</script>

<template>
    <section class="py-16 md:py-20" :style="{ backgroundColor: theme.background, color: theme.text, fontFamily: `'${theme.font}', sans-serif` }">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="text-2xl font-bold md:text-3xl">{{ c.title || 'Contacto' }}</h2>
            <p v-if="c.subtitle" class="mt-2" :style="{ color: theme.muted }">{{ c.subtitle }}</p>

            <div :class="block?.variant === 'split' ? 'mt-8 grid gap-10 md:grid-cols-2' : 'mt-8 mx-auto max-w-xl'">
                <div v-if="block?.variant === 'split'" class="space-y-3 text-sm">
                    <p v-if="c.phone"><strong>Teléfono:</strong> {{ c.phone }}</p>
                    <p v-if="c.email"><strong>Email:</strong> {{ c.email }}</p>
                    <p v-if="c.address"><strong>Dirección:</strong> {{ c.address }}</p>
                    <p v-if="c.whatsapp"><strong>WhatsApp:</strong> {{ c.whatsapp }}</p>
                    <div v-if="c.schedule?.length" class="pt-2">
                        <p class="font-semibold">Horarios</p>
                        <p v-for="s in c.schedule" :key="s.days">{{ s.days }} · {{ s.hours }}</p>
                    </div>
                </div>

                <form v-if="c.show_form && !sent" @submit.prevent="submit" class="space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input v-model="form.name" type="text" placeholder="Nombre" class="w-full rounded-lg border px-3 py-2 text-sm" :style="{ borderColor: theme.text + '33' }" />
                        <input v-model="form.phone" type="text" placeholder="Teléfono" class="w-full rounded-lg border px-3 py-2 text-sm" :style="{ borderColor: theme.text + '33' }" />
                    </div>
                    <input v-model="form.email" type="email" placeholder="Email" class="w-full rounded-lg border px-3 py-2 text-sm" :style="{ borderColor: theme.text + '33' }" />
                    <input v-model="form.subject" type="text" placeholder="Asunto" class="w-full rounded-lg border px-3 py-2 text-sm" :style="{ borderColor: theme.text + '33' }" />
                    <textarea v-model="form.message" required placeholder="Tu mensaje" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm" :style="{ borderColor: theme.text + '33' }"></textarea>
                    <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
                    <button
                        type="submit"
                        :disabled="sending"
                        class="px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                        :style="{ backgroundColor: theme.primary, borderRadius: radius }"
                    >
                        {{ sending ? 'Enviando...' : 'Enviar mensaje' }}
                    </button>
                </form>
                <div v-else-if="sent" class="rounded-lg border border-green-300 bg-green-50 p-6 text-sm text-green-800">
                    ¡Gracias! Tu mensaje fue enviado. Te contactaremos pronto.
                </div>
            </div>
        </div>
    </section>
</template>
