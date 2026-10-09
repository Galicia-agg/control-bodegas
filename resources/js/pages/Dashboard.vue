<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Boxes,
    Building2,
    CalendarClock,
    CircleDollarSign,
    TriangleAlert,
} from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import MovementTypeBadge from '@/components/MovementTypeBadge.vue';
import StatCard from '@/components/StatCard.vue';
import { usePermissions } from '@/composables/usePermissions';
import {
    formatCompactCurrency,
    formatCurrency,
    formatDateTime,
    formatNumber,
} from '@/lib/format';
import { dashboard } from '@/routes';
import {
    index as movementsIndex,
    show as movementShow,
} from '@/routes/movements';
import { index as stockIndex } from '@/routes/stock';
import type { Movement, Stock } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Panel', href: dashboard() }],
    },
});

const props = defineProps<{
    stats: {
        products: number;
        warehouses: number;
        inventoryValue: number;
        lowStock: number;
        movementsToday: number;
    };
    activity: { date: string; entries: number; exits: number }[];
    recentMovements: Movement[];
    lowStock: Stock[];
    byWarehouse: { id: number; code: string; name: string; value: number }[];
}>();

const { can } = usePermissions();
const user = computed(() => usePage().props.auth.user);

const greeting = computed(() => {
    const hour = new Date().getHours();

    return hour < 12
        ? 'Buenos días'
        : hour < 19
          ? 'Buenas tardes'
          : 'Buenas noches';
});

const maxActivity = computed(() =>
    Math.max(1, ...props.activity.flatMap((d) => [d.entries, d.exits])),
);
const totals = computed(() => ({
    entries: props.activity.reduce((sum, d) => sum + d.entries, 0),
    exits: props.activity.reduce((sum, d) => sum + d.exits, 0),
}));
const maxWarehouse = computed(() =>
    Math.max(1, ...props.byWarehouse.map((w) => w.value)),
);

const dayLabel = (date: string) =>
    new Intl.DateTimeFormat('es-GT', { day: '2-digit', month: 'short' }).format(
        new Date(`${date}T12:00:00`),
    );
</script>

<template>
    <Head title="Panel" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-bold tracking-tight">
                {{ greeting }}, {{ user.name.split(' ')[0] }}
            </h1>
            <p class="text-sm text-muted-foreground">
                Resumen del estado de tus bodegas e inventario.
            </p>
        </div>

        <section
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Indicadores"
        >
            <StatCard
                label="Valor del inventario"
                :value="formatCompactCurrency(stats.inventoryValue)"
                :icon="CircleDollarSign"
                :hint="formatCurrency(stats.inventoryValue)"
            />
            <StatCard
                label="Productos activos"
                :value="formatNumber(stats.products)"
                :icon="Boxes"
                tone="info"
                :hint="`En ${stats.warehouses} bodega${stats.warehouses === 1 ? '' : 's'}`"
            />
            <StatCard
                label="Alertas de stock bajo"
                :value="formatNumber(stats.lowStock)"
                :icon="TriangleAlert"
                :tone="stats.lowStock > 0 ? 'warning' : 'success'"
                hint="Existencias en o bajo el mínimo"
            />
            <StatCard
                label="Movimientos hoy"
                :value="formatNumber(stats.movementsToday)"
                :icon="CalendarClock"
                tone="success"
                hint="Entradas, salidas, transferencias y ajustes"
            />
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <div class="rounded-xl border bg-card p-5 shadow-xs xl:col-span-2">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="font-semibold">Actividad de 14 días</h2>
                        <p class="text-sm text-muted-foreground">
                            Unidades que entraron y salieron por día
                        </p>
                    </div>
                    <div class="flex gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-sm bg-chart-1" />
                            Entradas
                            <span class="font-semibold tabular">{{
                                formatNumber(totals.entries)
                            }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-sm bg-chart-2" />
                            Salidas
                            <span class="font-semibold tabular">{{
                                formatNumber(totals.exits)
                            }}</span>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-6 flex h-56 items-end gap-1.5 sm:gap-2"
                    role="img"
                    :aria-label="`Entradas: ${formatNumber(totals.entries)}, salidas: ${formatNumber(totals.exits)} en 14 días`"
                >
                    <div
                        v-for="day in activity"
                        :key="day.date"
                        class="group relative flex h-full flex-1 flex-col justify-end"
                    >
                        <div
                            class="flex h-full items-end justify-center gap-0.5"
                        >
                            <div
                                class="w-full max-w-3 rounded-t-sm bg-chart-1 transition-opacity duration-150 group-hover:opacity-80"
                                :style="{
                                    height: `${(day.entries / maxActivity) * 100}%`,
                                }"
                            />
                            <div
                                class="w-full max-w-3 rounded-t-sm bg-chart-2 transition-opacity duration-150 group-hover:opacity-80"
                                :style="{
                                    height: `${(day.exits / maxActivity) * 100}%`,
                                }"
                            />
                        </div>
                        <div
                            class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden -translate-x-1/2 rounded-md border bg-popover px-2.5 py-1.5 text-xs whitespace-nowrap shadow-md group-hover:block"
                        >
                            <p class="font-semibold">
                                {{ dayLabel(day.date) }}
                            </p>
                            <p class="text-success">
                                Entradas: {{ formatNumber(day.entries) }}
                            </p>
                            <p class="text-destructive">
                                Salidas: {{ formatNumber(day.exits) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-2 flex gap-1.5 sm:gap-2">
                    <span
                        v-for="(day, i) in activity"
                        :key="day.date"
                        class="flex-1 text-center text-[10px] text-muted-foreground tabular"
                        >{{ i % 2 === 0 ? dayLabel(day.date) : '' }}</span
                    >
                </div>
            </div>

            <div class="rounded-xl border bg-card p-5 shadow-xs">
                <h2 class="font-semibold">Valor por bodega</h2>
                <p class="text-sm text-muted-foreground">
                    Costo total de las existencias
                </p>
                <ul v-if="byWarehouse.length" class="mt-5 space-y-4">
                    <li
                        v-for="w in byWarehouse"
                        :key="w.id"
                        class="space-y-1.5"
                    >
                        <div
                            class="flex items-center justify-between gap-2 text-sm"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <Building2
                                    class="size-4 shrink-0 text-muted-foreground"
                                />
                                <span class="truncate font-medium">{{
                                    w.name
                                }}</span>
                            </span>
                            <span class="font-semibold tabular">{{
                                formatCompactCurrency(w.value)
                            }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary transition-[width] duration-500"
                                :style="{
                                    width: `${(w.value / maxWarehouse) * 100}%`,
                                }"
                            />
                        </div>
                    </li>
                </ul>
                <EmptyState
                    v-else
                    :icon="Building2"
                    title="Sin bodegas asignadas"
                    description="Pide a un administrador que te asigne una bodega."
                />
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-5">
            <div
                class="overflow-hidden rounded-xl border bg-card shadow-xs xl:col-span-3"
            >
                <div class="flex items-center justify-between border-b p-4">
                    <h2 class="font-semibold">Movimientos recientes</h2>
                    <Link
                        v-if="can('movements.view')"
                        :href="movementsIndex()"
                        class="inline-flex items-center gap-1 rounded-md text-sm font-medium text-primary hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        Ver todos <ArrowRight class="size-4" />
                    </Link>
                </div>
                <ul v-if="recentMovements.length" class="divide-y">
                    <li v-for="m in recentMovements" :key="m.id">
                        <Link
                            :href="movementShow(m.id)"
                            class="flex items-center gap-4 px-4 py-3 transition-colors duration-150 hover:bg-muted/40 focus-visible:bg-muted/40 focus-visible:outline-none"
                        >
                            <MovementTypeBadge
                                :type="m.type"
                                class="w-32 justify-center"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    <span class="font-mono">{{ m.folio }}</span>
                                    · {{ m.warehouse.name }}
                                    <template v-if="m.destination_warehouse">
                                        → {{ m.destination_warehouse.name }}
                                    </template>
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ m.items_count }} producto(s) ·
                                    {{ m.user.name }}
                                </p>
                            </div>
                            <span
                                class="hidden text-xs whitespace-nowrap text-muted-foreground sm:block"
                                >{{ formatDateTime(m.occurred_at) }}</span
                            >
                        </Link>
                    </li>
                </ul>
                <EmptyState
                    v-else
                    title="Aún no hay movimientos"
                    description="Los movimientos registrados aparecerán aquí."
                />
            </div>

            <div
                class="overflow-hidden rounded-xl border bg-card shadow-xs xl:col-span-2"
            >
                <div class="flex items-center justify-between border-b p-4">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <TriangleAlert class="size-4 text-warning" />
                        Stock bajo
                    </h2>
                    <Link
                        v-if="can('stock.view')"
                        :href="stockIndex({ query: { low: 1 } })"
                        class="inline-flex items-center gap-1 rounded-md text-sm font-medium text-primary hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        Ver alertas <ArrowRight class="size-4" />
                    </Link>
                </div>
                <ul v-if="lowStock.length" class="divide-y">
                    <li
                        v-for="s in lowStock"
                        :key="s.id"
                        class="flex items-center justify-between gap-3 px-4 py-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ s.product.name }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                <span class="font-mono">{{
                                    s.product.sku
                                }}</span>
                                · {{ s.warehouse.name }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p
                                class="text-sm font-bold tabular"
                                :class="
                                    Number(s.quantity) <= 0
                                        ? 'text-destructive'
                                        : 'text-warning-foreground dark:text-warning'
                                "
                            >
                                {{ formatNumber(s.quantity) }}
                            </p>
                            <p class="text-xs text-muted-foreground tabular">
                                mín. {{ formatNumber(s.product.min_stock) }}
                            </p>
                        </div>
                    </li>
                </ul>
                <EmptyState
                    v-else
                    :icon="Boxes"
                    title="Todo en orden"
                    description="Ningún producto está por debajo de su mínimo."
                />
            </div>
        </section>
    </div>
</template>
