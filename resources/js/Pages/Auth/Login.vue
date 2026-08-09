<script setup>
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '', remember: false });

function submit() {
    form.post('/login', { preserveScroll: true });
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <Link href="/" class="mb-8 block text-center font-bold text-white text-xl tracking-tight">
                PDI <span class="text-indigo-400">SAAS</span>
            </Link>
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-8">
                <h1 class="text-lg font-semibold text-white">Ingresar</h1>
                <p class="mt-1 text-sm text-slate-400">Accede al panel de tu empresa.</p>
                <form @submit.prevent="submit" class="mt-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-400">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Contraseña</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none"
                        />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-400">
                        <input v-model="form.remember" type="checkbox" class="rounded border-slate-600 bg-slate-800" />
                        Recordarme
                    </label>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-lg bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                    >
                        Ingresar
                    </button>
                </form>
                <p class="mt-4 text-center text-sm text-slate-400">
                    ¿No tienes cuenta?
                    <Link href="/register" class="text-indigo-400 hover:text-indigo-300">Crea tu sitio</Link>
                </p>
                <p class="mt-3 text-center text-xs text-slate-500">Demo: demo@andina.com / password</p>
            </div>
        </div>
    </div>
</template>
