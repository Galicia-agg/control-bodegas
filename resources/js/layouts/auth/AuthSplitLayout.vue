<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ArrowLeftRight, Boxes, ShieldCheck } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;

defineProps<{
    title?: string;
    description?: string;
}>();

const features = [
    {
        icon: Boxes,
        title: 'Mercadería de tus clientes, localizada',
        text: 'Contenedores, palets y cajas con su ubicación en bodega.',
    },
    {
        icon: ArrowLeftRight,
        title: 'Trazabilidad completa',
        text: 'Cada ingreso con folio, documento de transporte y DUCA.',
    },
    {
        icon: ShieldCheck,
        title: 'Acceso por roles',
        text: 'Cada usuario ve y opera solo lo que le corresponde.',
    },
];
</script>

<template>
    <div class="grid min-h-dvh lg:grid-cols-[1.1fr_1fr]">
        <aside
            class="relative hidden overflow-hidden bg-sidebar p-10 text-sidebar-foreground lg:flex lg:flex-col"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-grid text-white/[0.04]"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute -top-32 -right-32 size-[28rem] rounded-full bg-primary/30 blur-3xl"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute -bottom-40 -left-24 size-[26rem] rounded-full bg-brand/20 blur-3xl"
                aria-hidden="true"
            />

            <div
                class="relative flex items-center gap-3 text-lg font-bold text-white"
            >
                <span
                    class="grid size-10 place-items-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground shadow-lg"
                >
                    <AppLogoIcon class="size-6" />
                </span>
                {{ name }}
            </div>

            <div class="relative my-auto max-w-lg space-y-10">
                <div class="space-y-4">
                    <p
                        class="text-sm font-semibold tracking-widest text-sidebar-primary uppercase"
                    >
                        Control de inventario
                    </p>
                    <h2
                        class="text-4xl leading-tight font-bold text-balance text-white"
                    >
                        Almacenaje y entregas, claros para tus clientes.
                    </h2>
                    <p class="text-base text-sidebar-foreground/80">
                        Registra ingresos de importación, controla lo almacenado
                        y cobra con transparencia.
                    </p>
                </div>
                <ul class="space-y-5">
                    <li v-for="f in features" :key="f.title" class="flex gap-4">
                        <span
                            class="grid size-10 shrink-0 place-items-center rounded-lg bg-white/10 text-sidebar-primary ring-1 ring-white/10"
                        >
                            <component
                                :is="f.icon"
                                class="size-5"
                                aria-hidden="true"
                            />
                        </span>
                        <span>
                            <span class="block font-semibold text-white">{{
                                f.title
                            }}</span>
                            <span
                                class="block text-sm text-sidebar-foreground/70"
                                >{{ f.text }}</span
                            >
                        </span>
                    </li>
                </ul>
            </div>

            <p class="relative text-xs text-sidebar-foreground/50">
                © {{ new Date().getFullYear() }} {{ name }}
            </p>
        </aside>

        <main
            class="flex items-center justify-center bg-background px-6 py-12 sm:px-10"
        >
            <div class="w-full max-w-sm space-y-8">
                <div class="flex items-center gap-3 lg:hidden">
                    <span
                        class="grid size-10 place-items-center rounded-xl bg-sidebar text-sidebar-primary"
                    >
                        <AppLogoIcon class="size-6" />
                    </span>
                    <span class="text-lg font-bold">{{ name }}</span>
                </div>
                <div class="space-y-2">
                    <h1 v-if="title" class="text-2xl font-bold tracking-tight">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </main>
    </div>
</template>
