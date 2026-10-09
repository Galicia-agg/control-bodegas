<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Printer } from '@lucide/vue';
import { computed } from 'vue';
import MovementTypeBadge from '@/components/MovementTypeBadge.vue';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDateTime, formatNumber } from '@/lib/format';
import { index, show } from '@/routes/movements';
import type { Movement } from '@/types';

const props = defineProps<{ movement: Movement; typeLabel: string }>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Movimientos', href: index() },
        { title: props.movement.folio, href: show(props.movement.id) },
    ],
});

const print = () => window.print();

const total = computed(() =>
    (props.movement.items ?? []).reduce(
        (sum, i) => sum + Math.abs(Number(i.quantity)) * Number(i.unit_cost),
        0,
    ),
);
</script>

<template>
    <Head :title="movement.folio" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-wrap items-center justify-between gap-3 print:hidden"
        >
            <Button variant="ghost" size="sm" as-child>
                <Link :href="index()"><ArrowLeft /> Volver</Link>
            </Button>
            <Button variant="outline" size="sm" @click="print"
                ><Printer /> Imprimir</Button
            >
        </div>

        <article class="overflow-hidden rounded-xl border bg-card shadow-xs">
            <header
                class="flex flex-col gap-4 border-b p-6 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="space-y-2">
                    <MovementTypeBadge :type="movement.type" />
                    <h1 class="font-mono text-2xl font-bold tracking-tight">
                        {{ movement.folio }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ formatDateTime(movement.occurred_at) }}
                    </p>
                </div>
                <dl
                    class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:text-right"
                >
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Registrado por
                        </dt>
                        <dd class="font-medium">{{ movement.user.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Referencia
                        </dt>
                        <dd class="font-medium">
                            {{ movement.reference ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </header>

            <div class="grid gap-4 border-b p-6 sm:grid-cols-3">
                <div class="rounded-lg bg-muted/50 p-4">
                    <p class="text-xs text-muted-foreground">
                        {{
                            movement.destination_warehouse ? 'Origen' : 'Bodega'
                        }}
                    </p>
                    <p class="font-semibold">{{ movement.warehouse.name }}</p>
                    <p class="font-mono text-xs text-muted-foreground">
                        {{ movement.warehouse.code }}
                    </p>
                </div>
                <div
                    v-if="movement.destination_warehouse"
                    class="relative rounded-lg bg-muted/50 p-4"
                >
                    <ArrowRight
                        class="absolute top-1/2 -left-3.5 hidden size-5 -translate-y-1/2 rounded-full bg-card text-info sm:block"
                    />
                    <p class="text-xs text-muted-foreground">Destino</p>
                    <p class="font-semibold">
                        {{ movement.destination_warehouse.name }}
                    </p>
                    <p class="font-mono text-xs text-muted-foreground">
                        {{ movement.destination_warehouse.code }}
                    </p>
                </div>
                <div
                    v-if="movement.supplier"
                    class="rounded-lg bg-muted/50 p-4"
                >
                    <p class="text-xs text-muted-foreground">Proveedor</p>
                    <p class="font-semibold">{{ movement.supplier.name }}</p>
                    <p
                        v-if="movement.supplier.tax_id"
                        class="font-mono text-xs text-muted-foreground"
                    >
                        NIT {{ movement.supplier.tax_id }}
                    </p>
                </div>
                <div
                    v-if="movement.notes"
                    class="rounded-lg bg-muted/50 p-4 sm:col-span-3"
                >
                    <p class="text-xs text-muted-foreground">Observaciones</p>
                    <p class="text-sm whitespace-pre-line">
                        {{ movement.notes }}
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="bg-muted/50 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        <tr>
                            <th scope="col" class="px-6 py-2.5 text-left">
                                Producto
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-right">
                                {{
                                    movement.type === 'ajuste'
                                        ? 'Diferencia'
                                        : 'Cantidad'
                                }}
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-right">
                                Costo unit.
                            </th>
                            <th scope="col" class="px-6 py-2.5 text-right">
                                Subtotal
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in movement.items" :key="item.id">
                            <td class="px-6 py-3">
                                <p class="font-medium">
                                    {{ item.product.name }}
                                </p>
                                <p
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ item.product.sku }}
                                </p>
                            </td>
                            <td
                                class="px-3 py-3 text-right font-semibold tabular"
                            >
                                <span
                                    :class="
                                        movement.type === 'ajuste'
                                            ? Number(item.quantity) < 0
                                                ? 'text-destructive'
                                                : 'text-success'
                                            : ''
                                    "
                                >
                                    {{
                                        movement.type === 'ajuste' &&
                                        Number(item.quantity) > 0
                                            ? '+'
                                            : ''
                                    }}{{ formatNumber(item.quantity) }}
                                </span>
                                <span
                                    class="ml-1 text-xs font-normal text-muted-foreground"
                                    >{{ item.product.unit }}</span
                                >
                            </td>
                            <td
                                class="px-3 py-3 text-right text-muted-foreground tabular"
                            >
                                {{ formatCurrency(item.unit_cost) }}
                            </td>
                            <td class="px-6 py-3 text-right tabular">
                                {{
                                    formatCurrency(
                                        Math.abs(Number(item.quantity)) *
                                            Number(item.unit_cost),
                                    )
                                }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t bg-muted/30">
                            <td
                                colspan="3"
                                class="px-6 py-3 text-right font-medium"
                            >
                                Total
                            </td>
                            <td
                                class="px-6 py-3 text-right text-base font-bold tabular"
                            >
                                {{ formatCurrency(total) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </article>
    </div>
</template>
