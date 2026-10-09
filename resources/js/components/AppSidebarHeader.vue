<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Monitor, Moon, Plus, Sun } from '@lucide/vue';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import { usePermissions } from '@/composables/usePermissions';
import { create as createMovement } from '@/routes/movements';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { canAny } = usePermissions();
const { appearance, updateAppearance } = useAppearance();

const canMove = computed(() =>
    canAny(
        'movements.entry',
        'movements.exit',
        'movements.transfer',
        'movements.adjust',
    ),
);

const next = { light: 'dark', dark: 'system', system: 'light' } as const;
const icons = { light: Sun, dark: Moon, system: Monitor };
const labels = {
    light: 'Tema claro',
    dark: 'Tema oscuro',
    system: 'Tema del sistema',
};
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-border/70 px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" aria-label="Mostrar u ocultar menú" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-2">
            <Button
                variant="ghost"
                size="icon"
                :aria-label="labels[appearance]"
                :title="labels[appearance]"
                @click="updateAppearance(next[appearance])"
            >
                <component :is="icons[appearance]" class="size-4" />
            </Button>
            <Button v-if="canMove" size="sm" as-child>
                <Link :href="createMovement()">
                    <Plus class="size-4" />
                    <span class="hidden sm:inline">Nuevo movimiento</span>
                </Link>
            </Button>
        </div>
    </header>
</template>
