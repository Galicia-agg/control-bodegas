<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { Input } from '@/components/ui/input';

withDefaults(defineProps<{ placeholder?: string }>(), {
    placeholder: 'Buscar…',
});

const model = defineModel<string>({ default: '' });
</script>

<template>
    <div class="relative w-full sm:max-w-xs">
        <Search
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            aria-hidden="true"
        />
        <Input
            v-model="model"
            type="search"
            :placeholder="placeholder"
            :aria-label="placeholder"
            class="pr-9 pl-9 [&::-webkit-search-cancel-button]:hidden"
        />
        <button
            v-if="model"
            type="button"
            class="absolute top-1/2 right-2 grid size-6 -translate-y-1/2 place-items-center rounded-sm text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            aria-label="Limpiar búsqueda"
            @click="model = ''"
        >
            <X class="size-4" />
        </button>
    </div>
</template>
