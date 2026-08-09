<script setup>
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

const templates = ['minimal-business', 'modern-tech', 'restaurant', 'beauty-clinic', 'realty', 'startup-saas'];

const form = useForm({
    name: '',
    email: '',
    password: '',
    company_name: '',
    industry: '',
    country: '',
    tagline: '',
    template: 'minimal-business',
});

function submit() {
    form.post('/register');
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-lg">
            <Link href="/" class="mb-8 block text-center font-bold text-white text-xl tracking-tight">
                PDI <span class="text-indigo-400">SAAS</span>
            </Link>
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-8">
                <h1 class="text-lg font-semibold text-white">Crea tu presencia digital</h1>
                <p class="mt-1 text-sm text-slate-400">En minutos tu web estará lista con un asistente de IA.</p>

                <form @submit.prevent="submit" class="mt-6 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Tu nombre</label>
                            <input v-model="form.name" type="text" required class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Email</label>
                            <input v-model="form.email" type="email" required class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none" />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-400">{{ form.errors.email }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Empresa</label>
                            <input v-model="form.company_name" type="text" required placeholder="Mi Empresa S.A.C." class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Industria</label>
                            <input v-model="form.industry" type="text" placeholder="Ingeniería, restaurante, salud..." class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Contraseña</label>
                            <input v-model="form.password" type="password" required class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Eslogan (opcional)</label>
                            <input v-model="form.tagline" type="text" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Elige un template inicial</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="t in templates"
                                :key="t"
                                type="button"
                                @click="form.template = t"
                                class="rounded-lg border px-2 py-2 text-xs font-medium transition"
                                :class="form.template === t ? 'border-indigo-500 bg-indigo-500/20 text-indigo-300' : 'border-slate-700 text-slate-400 hover:border-slate-500'"
                            >
                                {{ t.replace('-', ' ') }}
                            </button>
                        </div>
                    </div>

                    <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                        Crear mi sitio
                    </button>
                </form>

                <p class="mt-4 text-center text-sm text-slate-400">
                    ¿Ya tienes cuenta?
                    <Link href="/login" class="text-indigo-400 hover:text-indigo-300">Ingresar</Link>
                </p>
            </div>
        </div>
    </div>
</template>
