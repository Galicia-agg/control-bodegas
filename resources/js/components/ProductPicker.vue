<script
    setup
    lang="ts"
    generic="T extends { id: number; sku: string; name: string }"
>
import { Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

const props = defineProps<{
    products: T[];
    exclude?: number[];
}>();
const emit = defineEmits<{ select: [product: T] }>();

const query = ref('');
const open = ref(false);
const active = ref(0);

const results = computed(() => {
    const q = query.value.trim().toLowerCase();
    const excluded = new Set(props.exclude ?? []);

    return props.products
        .filter((p) => !excluded.has(p.id))
        .filter(
            (p) =>
                !q ||
                p.name.toLowerCase().includes(q) ||
                p.sku.toLowerCase().includes(q),
        )
        .slice(0, 8);
});

const choose = (product?: T) => {
    if (!product) return;
    emit('select', product);
    query.value = '';
    active.value = 0;
};

const onKeydown = (e: KeyboardEvent) => {
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        open.value = true;
        active.value = Math.min(active.value + 1, results.value.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        active.value = Math.max(active.value - 1, 0);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        choose(results.value[active.value]);
    } else if (e.key === 'Escape') {
        open.value = false;
    }
};
</script>

<template>
    <div class="relative">
        <Search
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            aria-hidden="true"
        />
        <Input
            v-model="query"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="open"
            aria-controls="product-picker-list"
            aria-label="Agregar producto"
            placeholder="Escribe el nombre o SKU para agregar un producto…"
            class="h-11 pl-9"
            @focus="open = true"
            @blur="open = false"
            @input="((open = true), (active = 0))"
            @keydown="onKeydown"
        />
        <ul
            v-show="open && results.length"
            id="product-picker-list"
            role="listbox"
            class="absolute z-30 mt-1 max-h-72 w-full overflow-auto rounded-lg border bg-popover p-1 shadow-lg"
        >
            <li
                v-for="(p, i) in results"
                :key="p.id"
                role="option"
                :aria-selected="i === active"
                :class="
                    cn(
                        'flex cursor-pointer items-center justify-between gap-3 rounded-md px-3 py-2 text-sm',
                        i === active ? 'bg-accent text-accent-foreground' : '',
                    )
                "
                @mouseenter="active = i"
                @mousedown.prevent="choose(p)"
            >
                <span class="truncate font-medium">{{ p.name }}</span>
                <span
                    class="shrink-0 font-mono text-xs text-muted-foreground"
                    >{{ p.sku }}</span
                >
            </li>
        </ul>
    </div>
</template>
