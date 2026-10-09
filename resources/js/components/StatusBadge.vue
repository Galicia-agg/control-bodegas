<script setup lang="ts">
withDefaults(defineProps<{ active: boolean; on?: string; off?: string }>(), {
    on: 'Activo',
    off: 'Inactivo',
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium"
        :class="
            active
                ? 'bg-success/12 text-success'
                : 'bg-muted text-muted-foreground'
        "
    >
        <span
            class="size-1.5 rounded-full"
            :class="active ? 'bg-success' : 'bg-muted-foreground/60'"
            aria-hidden="true"
        />
        {{ active ? on : off }}
    </span>
</template>
