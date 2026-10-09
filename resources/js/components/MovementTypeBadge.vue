<script setup lang="ts">
import {
    ArrowDownToLine,
    ArrowLeftRight,
    ArrowUpFromLine,
    SlidersHorizontal,
} from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { MovementType } from '@/types';

const props = defineProps<{ type: MovementType; class?: string }>();

const config = {
    entrada: {
        label: 'Entrada',
        icon: ArrowDownToLine,
        class: 'bg-success/12 text-success ring-success/25',
    },
    salida: {
        label: 'Salida',
        icon: ArrowUpFromLine,
        class: 'bg-destructive/10 text-destructive ring-destructive/25',
    },
    transferencia: {
        label: 'Transferencia',
        icon: ArrowLeftRight,
        class: 'bg-info/12 text-info ring-info/25',
    },
    ajuste: {
        label: 'Ajuste',
        icon: SlidersHorizontal,
        class: 'bg-warning/15 text-warning-foreground ring-warning/35 dark:text-warning',
    },
} as const;

const item = computed(() => config[props.type]);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
                item.class,
                props.class,
            )
        "
    >
        <component :is="item.icon" class="size-3.5" aria-hidden="true" />
        {{ item.label }}
    </span>
</template>
