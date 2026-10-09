<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

const props = defineProps<{ paginator: Paginated<unknown> }>();

const pages = computed(() => props.paginator.links.slice(1, -1));
const prev = computed(() => props.paginator.links[0]);
const next = computed(
    () => props.paginator.links[props.paginator.links.length - 1],
);

const base =
    'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm font-medium transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';
</script>

<template>
    <nav
        v-if="paginator.total > 0"
        class="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 sm:flex-row"
        aria-label="Paginación"
    >
        <p class="text-sm text-muted-foreground">
            Mostrando
            <span class="font-medium text-foreground tabular">{{
                paginator.from
            }}</span>
            –
            <span class="font-medium text-foreground tabular">{{
                paginator.to
            }}</span>
            de
            <span class="font-medium text-foreground tabular">{{
                paginator.total
            }}</span>
        </p>
        <div v-if="paginator.last_page > 1" class="flex items-center gap-1">
            <component
                :is="prev?.url ? Link : 'span'"
                :href="prev?.url ?? undefined"
                preserve-scroll
                :class="
                    cn(
                        base,
                        prev?.url
                            ? 'hover:bg-accent'
                            : 'pointer-events-none opacity-40',
                    )
                "
                aria-label="Página anterior"
            >
                <ChevronLeft class="size-4" />
            </component>
            <template v-for="link in pages" :key="link.label">
                <span
                    v-if="!link.url"
                    :class="cn(base, 'text-muted-foreground')"
                    >…</span
                >
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    :aria-current="link.active ? 'page' : undefined"
                    :class="
                        cn(
                            base,
                            'hidden tabular sm:inline-flex',
                            link.active
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'hover:bg-accent',
                        )
                    "
                    >{{ link.label }}</Link
                >
            </template>
            <component
                :is="next?.url ? Link : 'span'"
                :href="next?.url ?? undefined"
                preserve-scroll
                :class="
                    cn(
                        base,
                        next?.url
                            ? 'hover:bg-accent'
                            : 'pointer-events-none opacity-40',
                    )
                "
                aria-label="Página siguiente"
            >
                <ChevronRight class="size-4" />
            </component>
        </div>
    </nav>
</template>
