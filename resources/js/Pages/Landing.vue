<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-white">
        <header class="mx-auto max-w-6xl px-6 py-6 flex items-center justify-between">
            <div class="font-bold text-lg tracking-tight">PDI <span class="text-indigo-400">SAAS</span></div>
            <nav class="flex items-center gap-3">
                <a href="/site/andina-hidraulica" class="text-sm text-slate-300 hover:text-white">Ver demo</a>
                <Link href="/login" class="text-sm text-slate-300 hover:text-white">Ingresar</Link>
                <Link href="/register" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium hover:bg-indigo-500">Crear mi sitio</Link>
            </nav>
        </header>

        <main class="mx-auto max-w-6xl px-6 pb-20">
            <section class="pt-16 md:pt-24 text-center">
                <p class="inline-flex items-center rounded-full border border-indigo-500/40 bg-indigo-500/10 px-4 py-1 text-xs font-medium text-indigo-300">
                    Presencia Digital Inteligente
                </p>
                <h1 class="mx-auto mt-6 max-w-3xl text-4xl md:text-6xl font-bold tracking-tight leading-tight">
                    Tu empresa con una web profesional e
                    <span class="text-indigo-400">IA que conoce tu negocio</span>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-slate-400 text-lg">
                    Crea tu sitio en minutos con templates y bloques, publícalo en tu dominio propio y atiende a tus
                    visitantes 24/7 con un asistente basado en el conocimiento de tu empresa.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <Link href="/register" class="rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold hover:bg-indigo-500">
                        Crear mi sitio gratis
                    </Link>
                    <a href="/site/andina-hidraulica" class="rounded-lg border border-slate-700 px-6 py-3 text-sm font-medium text-slate-200 hover:bg-slate-900">
                        Ver sitio de ejemplo
                    </a>
                </div>
            </section>

            <section class="mt-20 grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                    <h3 class="font-semibold">Templates y bloques</h3>
                    <p class="mt-2 text-sm text-slate-400">Elige un diseño y arrastra bloques: Hero, servicios, productos, equipo, testimonios, FAQ, contacto.</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                    <h3 class="font-semibold">Asistente con IA</h3>
                    <p class="mt-2 text-sm text-slate-400">Responde con tu propia información: documentos, FAQs y textos. Sin inventar.</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                    <h3 class="font-semibold">Tu dominio, tus leads</h3>
                    <p class="mt-2 text-sm text-slate-400">Publica en tu dominio propio y captura cada consulta como lead en tu CRM.</p>
                </div>
            </section>
        </main>
    </div>
</template>
