<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CalendarClock,
    PackageCheck,
    Package,
    Truck,
    UsersRound,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import ReceiptStatusBadge from '@/components/ReceiptStatusBadge.vue';
import StatCard from '@/components/StatCard.vue';
import TransportModeBadge from '@/components/TransportModeBadge.vue';
import { usePermissions } from '@/composables/usePermissions';
import { formatCurrency, formatDateTime, formatNumber } from '@/lib/format';
import { dashboard } from '@/routes';
import { index as receiptsIndex, show as receiptShow } from '@/routes/receipts';
import type { Receipt } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Panel', href: dashboard() }],
    },
});

const props = defineProps<{
    stats: {
        activeReceipts: number;
        storedUnits: number;
        customers: number;
        receiptsToday: number;
        pendingCollection: number | null;
        activeDeliveries: number;
    };
    activity: { date: string; receipts: number }[];
    recentReceipts: Receipt[];
    byWarehouse: { id: number; code: string; name: string; units: number }[];
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
    Math.max(1, ...props.activity.map((d) => d.receipts)),
);
const totalReceipts = computed(() =>
    props.activity.reduce((sum, d) => sum + d.receipts, 0),
);
const maxWarehouse = computed(() =>
    Math.max(1, ...props.byWarehouse.map((w) => w.units)),
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
                Resumen de la mercadería que almacenas para tus clientes.
            </p>
        </div>

        <section
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Indicadores"
        >
            <StatCard
                label="Ingresos activos"
                :value="formatNumber(stats.activeReceipts)"
                :icon="PackageCheck"
                hint="Con mercadería aún en bodega"
            />
            <StatCard
                label="Unidades almacenadas"
                :value="formatNumber(stats.storedUnits)"
                :icon="Package"
                tone="info"
                hint="Contenedores, palets, cajas y bultos"
            />
            <StatCard
                label="Clientes activos"
                :value="formatNumber(stats.customers)"
                :icon="UsersRound"
                tone="success"
            />
            <StatCard
                label="Ingresos hoy"
                :value="formatNumber(stats.receiptsToday)"
                :icon="CalendarClock"
                tone="warning"
            />
            <StatCard
                v-if="stats.pendingCollection !== null"
                label="Pendiente de cobrar"
                :value="formatCurrency(stats.pendingCollection)"
                :icon="Wallet"
                tone="destructive"
                hint="Comprobantes emitidos sin pago"
            />
            <StatCard
                label="Entregas a domicilio activas"
                :value="formatNumber(stats.activeDeliveries)"
                :icon="Truck"
                tone="info"
                hint="Programadas o en ruta"
            />
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <div class="rounded-xl border bg-card p-5 shadow-xs xl:col-span-2">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="font-semibold">Ingresos de 14 días</h2>
                        <p class="text-sm text-muted-foreground">
                            Recepciones registradas por día
                        </p>
                    </div>
                    <p class="text-sm">
                        Total
                        <span class="font-semibold tabular">{{
                            formatNumber(totalReceipts)
                        }}</span>
                    </p>
                </div>

                <div
                    class="mt-6 flex h-56 items-end gap-1.5 sm:gap-2"
                    role="img"
                    :aria-label="`${formatNumber(totalReceipts)} ingresos en 14 días`"
                >
                    <div
                        v-for="day in activity"
                        :key="day.date"
                        class="group relative flex h-full flex-1 flex-col justify-end"
                    >
                        <div class="flex h-full items-end justify-center">
                            <div
                                class="w-full max-w-5 rounded-t-sm bg-chart-1 transition-opacity duration-150 group-hover:opacity-80"
                                :style="{
                                    height: `${(day.receipts / maxActivity) * 100}%`,
                                }"
                            />
                        </div>
                        <div
                            class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden -translate-x-1/2 rounded-md border bg-popover px-2.5 py-1.5 text-xs whitespace-nowrap shadow-md group-hover:block"
                        >
                            <p class="font-semibold">
                                {{ dayLabel(day.date) }}
                            </p>
                            <p>Ingresos: {{ formatNumber(day.receipts) }}</p>
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
                <h2 class="font-semibold">Unidades por bodega</h2>
                <p class="text-sm text-muted-foreground">
                    Mercadería actualmente almacenada
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
                                formatNumber(w.units)
                            }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary transition-[width] duration-500"
                                :style="{
                                    width: `${(w.units / maxWarehouse) * 100}%`,
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

        <section>
            <div class="overflow-hidden rounded-xl border bg-card shadow-xs">
                <div class="flex items-center justify-between border-b p-4">
                    <h2 class="font-semibold">Ingresos recientes</h2>
                    <Link
                        v-if="can('receipts.view')"
                        :href="receiptsIndex()"
                        class="inline-flex items-center gap-1 rounded-md text-sm font-medium text-primary hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        Ver todos <ArrowRight class="size-4" />
                    </Link>
                </div>
                <ul v-if="recentReceipts.length" class="divide-y">
                    <li v-for="r in recentReceipts" :key="r.id">
                        <Link
                            :href="receiptShow(r.id)"
                            class="flex items-center gap-4 px-4 py-3 transition-colors duration-150 hover:bg-muted/40 focus-visible:bg-muted/40 focus-visible:outline-none"
                        >
                            <ReceiptStatusBadge
                                :status="r.status"
                                class="w-32 justify-center"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    <span class="font-mono">{{ r.folio }}</span>
                                    · {{ r.customer.name }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ r.warehouse.name }} ·
                                    {{ r.units_count }} unidad(es)
                                </p>
                            </div>
                            <TransportModeBadge
                                :mode="r.transport_mode"
                                class="hidden sm:inline-flex"
                            />
                            <span
                                class="hidden text-xs whitespace-nowrap text-muted-foreground sm:block"
                                >{{ formatDateTime(r.received_at) }}</span
                            >
                        </Link>
                    </li>
                </ul>
                <EmptyState
                    v-else
                    title="Aún no hay ingresos"
                    description="Los ingresos registrados aparecerán aquí."
                />
            </div>
        </section>
    </div>
</template>
