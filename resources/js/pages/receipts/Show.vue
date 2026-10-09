<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { ArrowLeft, Printer } from '@lucide/vue';
import { computed } from 'vue';
import ReceiptStatusBadge from '@/components/ReceiptStatusBadge.vue';
import TransportModeBadge from '@/components/TransportModeBadge.vue';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDateTime, formatNumber } from '@/lib/format';
import { index, show } from '@/routes/receipts';
import type { Accrual, Receipt, StorageUnit } from '@/types';

const props = defineProps<{
    receipt: Receipt;
    modeLabel: string;
    documentLabel: string;
    statusLabel: string;
    accrual: Accrual | null;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Ingresos', href: index() },
        { title: props.receipt.folio, href: show(props.receipt.id) },
    ],
});

const print = () => window.print();

const typeLabels: Record<string, string> = {
    contenedor: 'Contenedor',
    palet: 'Palet',
    caja: 'Caja',
    bulto: 'Bulto',
};

// Depth-first order so children are listed under their parent.
const rows = computed(() => {
    const units = props.receipt.units ?? [];
    const byParent = new Map<number | null, StorageUnit[]>();

    for (const unit of units) {
        const list = byParent.get(unit.parent_id) ?? [];
        list.push(unit);
        byParent.set(unit.parent_id, list);
    }

    const out: { unit: StorageUnit; depth: number }[] = [];
    const walk = (parent: number | null, depth: number) => {
        for (const unit of byParent.get(parent) ?? []) {
            out.push({ unit, depth });
            walk(unit.id, depth + 1);
        }
    };
    walk(null, 0);

    return out;
});

const totals = computed(() => {
    const roots = rows.value.filter((r) => r.depth === 0).map((r) => r.unit);

    return {
        units: rows.value.length,
        weight: roots.reduce((sum, u) => sum + Number(u.weight_kg ?? 0), 0),
        volume: roots.reduce((sum, u) => sum + Number(u.volume_m3 ?? 0), 0),
    };
});
</script>

<template>
    <Head :title="receipt.folio" />

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
                    <ReceiptStatusBadge :status="receipt.status" />
                    <h1 class="font-mono text-2xl font-bold tracking-tight">
                        {{ receipt.folio }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Recibido el {{ formatDateTime(receipt.received_at) }}
                    </p>
                </div>
                <dl
                    class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:text-right"
                >
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Registrado por
                        </dt>
                        <dd class="font-medium">{{ receipt.user.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">Bodega</dt>
                        <dd class="font-medium">
                            {{ receipt.warehouse.name }}
                        </dd>
                    </div>
                </dl>
            </header>

            <div class="grid gap-4 border-b p-6 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg bg-muted/50 p-4">
                    <p class="text-xs text-muted-foreground">Cliente</p>
                    <p class="font-semibold">{{ receipt.customer.name }}</p>
                    <p
                        v-if="receipt.customer.tax_id"
                        class="font-mono text-xs text-muted-foreground"
                    >
                        NIT {{ receipt.customer.tax_id }}
                    </p>
                    <p
                        v-if="receipt.customer.contact_name"
                        class="text-xs text-muted-foreground"
                    >
                        {{ receipt.customer.contact_name }}
                        <template v-if="receipt.customer.phone">
                            · {{ receipt.customer.phone }}
                        </template>
                    </p>
                </div>
                <div class="rounded-lg bg-muted/50 p-4">
                    <p class="text-xs text-muted-foreground">Transporte</p>
                    <TransportModeBadge
                        :mode="receipt.transport_mode"
                        class="font-semibold"
                    />
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ documentLabel }}:
                        <span class="font-mono">{{
                            receipt.transport_document ?? '—'
                        }}</span>
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Transportista: {{ receipt.carrier ?? '—' }}
                    </p>
                </div>
                <div class="rounded-lg bg-muted/50 p-4">
                    <p class="text-xs text-muted-foreground">
                        Documentación aduanal
                    </p>
                    <p class="text-sm">
                        DUCA:
                        <span class="font-mono font-medium">{{
                            receipt.duca ?? '—'
                        }}</span>
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Origen: {{ receipt.origin ?? '—' }}
                    </p>
                </div>
                <div
                    v-if="receipt.notes"
                    class="rounded-lg bg-muted/50 p-4 sm:col-span-2 xl:col-span-3"
                >
                    <p class="text-xs text-muted-foreground">Observaciones</p>
                    <p class="text-sm whitespace-pre-line">
                        {{ receipt.notes }}
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
                                Unidad
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-left">
                                Descripción
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-right">
                                Piezas
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-right">
                                Peso (kg)
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-right">
                                Vol. (m³)
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-left">
                                Ubicación
                            </th>
                            <th scope="col" class="px-6 py-2.5 text-left">
                                Estado
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="{ unit, depth } in rows" :key="unit.id">
                            <td
                                class="px-6 py-3"
                                :style="{
                                    paddingLeft: `${1.5 + depth * 1.5}rem`,
                                }"
                            >
                                <p class="font-medium">
                                    {{ typeLabels[unit.type] }}
                                </p>
                                <p
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ unit.code }}
                                </p>
                            </td>
                            <td class="px-3 py-3">
                                {{ unit.description ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-right tabular">
                                {{
                                    unit.pieces
                                        ? formatNumber(unit.pieces)
                                        : '—'
                                }}
                            </td>
                            <td class="px-3 py-3 text-right tabular">
                                {{
                                    unit.weight_kg
                                        ? formatNumber(unit.weight_kg)
                                        : '—'
                                }}
                            </td>
                            <td class="px-3 py-3 text-right tabular">
                                {{
                                    unit.volume_m3
                                        ? formatNumber(unit.volume_m3)
                                        : '—'
                                }}
                            </td>
                            <td class="px-3 py-3">
                                {{ unit.location ?? '—' }}
                            </td>
                            <td class="px-6 py-3 capitalize">
                                {{ unit.status }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t bg-muted/30 font-medium">
                            <td class="px-6 py-3" colspan="3">
                                {{ totals.units }} unidad(es)
                            </td>
                            <td class="px-3 py-3 text-right tabular">
                                {{ formatNumber(totals.weight) }}
                            </td>
                            <td class="px-3 py-3 text-right tabular">
                                {{ formatNumber(totals.volume) }}
                            </td>
                            <td colspan="2" />
                        </tr>
                    </tfoot>
                </table>
                <p class="px-6 pb-4 text-xs text-muted-foreground">
                    Los totales de peso y volumen suman solo las unidades de
                    primer nivel para no contar dos veces el contenido.
                </p>
            </div>
        </article>

        <section
            v-if="accrual"
            class="overflow-hidden rounded-xl border bg-card shadow-xs"
        >
            <div
                class="flex flex-wrap items-start justify-between gap-3 border-b p-6"
            >
                <div>
                    <h2 class="font-semibold">Almacenaje acumulado a hoy</h2>
                    <p class="text-sm text-muted-foreground">
                        Se cobra por unidad y día, desde la fecha de recepción
                        (el primer día cuenta). Los contenedores o palets con
                        contenido se cobran a través de lo que contienen.
                    </p>
                </div>
                <p class="text-right">
                    <span class="block text-xs text-muted-foreground"
                        >Total</span
                    >
                    <span class="text-2xl font-bold tabular">{{
                        formatCurrency(accrual.total)
                    }}</span>
                </p>
            </div>
            <p
                v-if="accrual.missing_rates"
                class="border-b bg-warning/15 px-6 py-3 text-sm"
            >
                {{ accrual.missing_rates }} unidad(es) no tienen tarifa de
                almacenaje y no se están cobrando. Revisa la sección de Tarifas.
            </p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="bg-muted/50 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        <tr>
                            <th scope="col" class="px-6 py-2.5 text-left">
                                Unidad
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-right">
                                Días
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-right">
                                Libres
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-right">
                                Cobrables
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-right">
                                Tarifa/día
                            </th>
                            <th scope="col" class="px-6 py-2.5 text-right">
                                Importe
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="line in accrual.lines" :key="line.unit_id">
                            <td class="px-6 py-3">
                                <p class="font-medium">
                                    {{ typeLabels[line.type] }}
                                </p>
                                <p
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ line.code }}
                                </p>
                            </td>
                            <td class="px-3 py-3 text-right tabular">
                                {{ line.days }}
                            </td>
                            <td class="px-3 py-3 text-right tabular">
                                {{ line.free_days }}
                            </td>
                            <td class="px-3 py-3 text-right tabular">
                                {{ line.billable_days }}
                            </td>
                            <td class="px-3 py-3 text-right tabular">
                                {{
                                    line.rate === null
                                        ? 'Sin tarifa'
                                        : formatCurrency(line.rate)
                                }}
                            </td>
                            <td
                                class="px-6 py-3 text-right font-medium tabular"
                            >
                                {{ formatCurrency(line.amount) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
