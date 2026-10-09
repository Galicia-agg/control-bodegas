<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { PackageOpen } from '@lucide/vue';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        icon?: LucideIcon;
    }>(),
    {
        title: 'Sin resultados',
        description: 'No encontramos registros con los filtros actuales.',
        icon: () => PackageOpen,
    },
);
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center"
    >
        <div
            class="grid size-12 place-items-center rounded-full bg-muted text-muted-foreground ring-8 ring-muted/40"
        >
            <component :is="icon" class="size-6" aria-hidden="true" />
        </div>
        <div class="space-y-1">
            <p class="font-semibold text-foreground">{{ title }}</p>
            <p class="max-w-sm text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <slot />
    </div>
</template>
