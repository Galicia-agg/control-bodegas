<script setup lang="ts">
import { Plane, Ship, Truck } from '@lucide/vue';
import { computed } from 'vue';
import type { TransportMode } from '@/types';

const props = defineProps<{ mode: TransportMode }>();

const config = {
    maritimo: { label: 'Marítimo', icon: Ship },
    aereo: { label: 'Aéreo', icon: Plane },
    terrestre: { label: 'Terrestre', icon: Truck },
} as const;

const item = computed(() => config[props.mode]);
</script>

<template>
    <span class="inline-flex items-center gap-1.5 text-sm">
        <component
            :is="item.icon"
            class="size-4 text-muted-foreground"
            aria-hidden="true"
        />
        {{ item.label }}
    </span>
</template>
