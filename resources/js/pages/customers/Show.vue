<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import ReceiptStatusBadge from '@/components/ReceiptStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate, formatNumber } from '@/lib/format';
import { index, show } from '@/routes/customers';
import { show as receiptShow } from '@/routes/receipts';
import type { Customer, ReceiptStatus } from '@/types';

type Row = {
    id: number;
    folio: string;
    status: ReceiptStatus;
    received_at: string;
    warehouse: string;
    units_count: number;
    billed: number | null;
    paid: number | null;
    pending: number | null;
    accruing: number | null;
};

const props = defineProps<{
    customer: Customer;
    receipts: Row[];
    canSeeMoney: boolean;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Clientes', href: index() },
        { title: props.customer.name, href: show(props.customer.id) },
    ],
});

const sum = (key: 'billed' | 'paid' | 'pending' | 'accruing') =>
    props.receipts.reduce((total, r) => total + Number(r[key] ?? 0), 0);
</script>

<template>
    <Head :title="customer.name" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center justify-between print:hidden">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="index()"><ArrowLeft /> Volver</Link>
            </Button>
        </div>

        <header class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight">
                {{ customer.name }}
            </h1>
            <p class="text-sm text-muted-foreground">
                <span v-if="customer.tax_id" class="font-mono"
                    >NIT {{ customer.tax_id }}</span
                >
                <template v-if="customer.contact_name">
                    · {{ customer.contact_name }}
                </template>
                <template v-if="customer.phone">
                    · {{ customer.phone }}
                </template>
            </p>
        </header>

        <section
            v-if="canSeeMoney"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Resumen de cuenta"
        >
            <div class="rounded-xl border bg-card p-5 shadow-xs">
                <p class="text-sm text-muted-foreground">Facturado</p>
                <p class="text-2xl font-bold tabular">
                    {{ formatCurrency(sum('billed')) }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-5 shadow-xs">
                <p class="text-sm text-muted-foreground">Cobrado</p>
                <p class="text-2xl font-bold text-success tabular">
                    {{ formatCurrency(sum('paid')) }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-5 shadow-xs">
                <p class="text-sm text-muted-foreground">Pendiente de pago</p>
                <p class="text-2xl font-bold tabular">
                    {{ formatCurrency(sum('pending')) }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-5 shadow-xs">
                <p class="text-sm text-muted-foreground">
                    Almacenaje acumulado sin cobrar
                </p>
                <p class="text-2xl font-bold tabular">
                    {{ formatCurrency(sum('accruing')) }}
                </p>
                <p class="text-xs text-muted-foreground">
                    De la mercadería aún en bodega, a hoy.
                </p>
            </div>
        </section>

        <div class="overflow-hidden rounded-xl border bg-card shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="bg-muted/50 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-6 py-2.5 text-left">Ingreso</th>
                            <th class="px-3 py-2.5 text-left">Estado</th>
                            <th class="px-3 py-2.5 text-left">Bodega</th>
                            <th class="px-3 py-2.5 text-right">Unidades</th>
                            <template v-if="canSeeMoney">
                                <th class="px-3 py-2.5 text-right">
                                    Facturado
                                </th>
                                <th class="px-3 py-2.5 text-right">Cobrado</th>
                                <th class="px-3 py-2.5 text-right">
                                    Pendiente
                                </th>
                                <th class="px-6 py-2.5 text-right">
                                    Acumulando
                                </th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="r in receipts" :key="r.id">
                            <td class="px-6 py-3">
                                <Link
                                    :href="receiptShow(r.id)"
                                    class="font-mono font-semibold hover:underline"
                                    >{{ r.folio }}</Link
                                >
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{ formatDate(r.received_at) }}</span
                                >
                            </td>
                            <td class="px-3 py-3">
                                <ReceiptStatusBadge :status="r.status" />
                            </td>
                            <td class="px-3 py-3">{{ r.warehouse }}</td>
                            <td class="px-3 py-3 text-right tabular">
                                {{ formatNumber(r.units_count) }}
                            </td>
                            <template v-if="canSeeMoney">
                                <td class="px-3 py-3 text-right tabular">
                                    {{ formatCurrency(r.billed) }}
                                </td>
                                <td class="px-3 py-3 text-right tabular">
                                    {{ formatCurrency(r.paid) }}
                                </td>
                                <td class="px-3 py-3 text-right tabular">
                                    {{ formatCurrency(r.pending) }}
                                </td>
                                <td class="px-6 py-3 text-right tabular">
                                    {{ formatCurrency(r.accruing) }}
                                </td>
                            </template>
                        </tr>
                        <tr v-if="!receipts.length">
                            <td
                                colspan="8"
                                class="px-6 py-8 text-center text-muted-foreground"
                            >
                                Este cliente aún no tiene ingresos.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
