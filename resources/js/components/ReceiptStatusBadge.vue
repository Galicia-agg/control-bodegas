<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { ReceiptStatus } from '@/types';

const props = defineProps<{ status: ReceiptStatus; class?: string }>();

const config = {
    activo: {
        label: 'Almacenado',
        class: 'bg-info/12 text-info ring-info/25',
    },
    parcial: {
        label: 'Entrega parcial',
        class: 'bg-warning/15 text-warning-foreground ring-warning/35 dark:text-warning',
    },
    finalizado: {
        label: 'Finalizado',
        class: 'bg-success/12 text-success ring-success/25',
    },
} as const;

const item = computed(() => config[props.status]);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
                item.class,
                props.class,
            )
        "
    >
        {{ item.label }}
    </span>
</template>
