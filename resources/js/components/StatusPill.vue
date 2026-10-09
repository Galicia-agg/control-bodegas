<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    status: string;
    class?: string;
}>();

const config: Record<string, { label: string; class: string }> = {
    programada: {
        label: 'Programada',
        class: 'bg-info/12 text-info ring-info/25',
    },
    en_ruta: {
        label: 'En ruta',
        class: 'bg-warning/15 text-warning-foreground ring-warning/35 dark:text-warning',
    },
    entregada: {
        label: 'Entregada',
        class: 'bg-success/12 text-success ring-success/25',
    },
    cancelada: {
        label: 'Cancelada',
        class: 'bg-muted text-muted-foreground ring-border',
    },
    pendiente: {
        label: 'Pendiente de pago',
        class: 'bg-warning/15 text-warning-foreground ring-warning/35 dark:text-warning',
    },
    pagado: {
        label: 'Pagado',
        class: 'bg-success/12 text-success ring-success/25',
    },
    anulado: {
        label: 'Anulado',
        class: 'bg-muted text-muted-foreground ring-border',
    },
};

const item = computed(
    () =>
        config[props.status] ?? {
            label: props.status,
            class: 'bg-muted text-muted-foreground ring-border',
        },
);
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
