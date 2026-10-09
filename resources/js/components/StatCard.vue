<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        icon: LucideIcon;
        hint?: string;
        tone?: 'primary' | 'success' | 'warning' | 'info' | 'destructive';
    }>(),
    { tone: 'primary' },
);

const tones = {
    primary: 'bg-primary/10 text-primary',
    success: 'bg-success/12 text-success',
    warning: 'bg-warning/15 text-warning-foreground dark:text-warning',
    info: 'bg-info/12 text-info',
    destructive: 'bg-destructive/10 text-destructive',
};
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-xl border bg-card p-5 shadow-xs transition-shadow duration-200 hover:shadow-md"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 space-y-1">
                <p class="text-sm font-medium text-muted-foreground">
                    {{ label }}
                </p>
                <p
                    class="truncate text-2xl font-bold tracking-tight text-foreground tabular"
                >
                    {{ value }}
                </p>
            </div>
            <div
                :class="
                    cn(
                        'grid size-10 shrink-0 place-items-center rounded-lg',
                        tones[tone],
                    )
                "
            >
                <component :is="icon" class="size-5" aria-hidden="true" />
            </div>
        </div>
        <p v-if="hint" class="mt-3 text-xs text-muted-foreground">
            <slot name="hint">{{ hint }}</slot>
        </p>
    </div>
</template>
