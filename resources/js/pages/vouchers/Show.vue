<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Banknote, Printer } from '@lucide/vue';
import { computed } from 'vue';
import FormField from '@/components/FormField.vue';
import StatusPill from '@/components/StatusPill.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { usePermissions } from '@/composables/usePermissions';
import { formatCurrency, formatDateTime, formatNumber } from '@/lib/format';
import { pay as payRoute } from '@/routes/vouchers';
import { index, show } from '@/routes/vouchers';
import { show as receiptShow } from '@/routes/receipts';
import { show as deliveryShow } from '@/routes/deliveries';
import type { Voucher } from '@/types';

const props = defineProps<{
    voucher: Voucher;
    paymentMethods: { value: string; label: string }[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Cobros', href: index() },
        { title: props.voucher.folio, href: show(props.voucher.id) },
    ],
});

const { can } = usePermissions();
const print = () => window.print();

const form = useForm({
    payment_method: props.paymentMethods[0]?.value ?? 'efectivo',
    payment_reference: '',
});

const pay = () =>
    form.post(payRoute.url(props.voucher.id), { preserveScroll: true });

const methodLabel = computed(
    () =>
        props.paymentMethods.find(
            (m) => m.value === props.voucher.payment_method,
        )?.label ?? '—',
);

const concepts: Record<string, string> = {
    almacenaje: 'Almacenaje',
    manejo: 'Manejo',
    domicilio: 'Domicilio',
};

const subtotals = computed(() => {
    const totals: Record<string, number> = {};

    for (const line of props.voucher.lines ?? []) {
        totals[line.concept] =
            (totals[line.concept] ?? 0) + Number(line.amount);
    }

    return Object.entries(totals);
});
</script>

<template>
    <Head :title="voucher.folio" />

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
                    <StatusPill :status="voucher.status" />
                    <h1 class="font-mono text-2xl font-bold tracking-tight">
                        {{ voucher.folio }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Comprobante de cobro · emitido el
                        {{ formatDateTime(voucher.issued_at) }}
                    </p>
                </div>
                <dl
                    class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:text-right"
                >
                    <div>
                        <dt class="text-xs text-muted-foreground">Ingreso</dt>
                        <dd class="font-mono font-medium">
                            <Link
                                :href="receiptShow(voucher.receipt.id)"
                                class="hover:underline"
                                >{{ voucher.receipt.folio }}</Link
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">Entrega</dt>
                        <dd class="font-mono font-medium">
                            <Link
                                v-if="voucher.delivery"
                                :href="deliveryShow(voucher.delivery.id)"
                                class="hover:underline"
                                >{{ voucher.delivery.folio }}</Link
                            >
                            <template v-else>—</template>
                        </dd>
                    </div>
                </dl>
            </header>

            <div class="grid gap-4 border-b p-6 sm:grid-cols-2">
                <div class="rounded-lg bg-muted/50 p-4">
                    <p class="text-xs text-muted-foreground">Cliente</p>
                    <p class="font-semibold">{{ voucher.customer.name }}</p>
                    <p
                        v-if="voucher.customer.tax_id"
                        class="font-mono text-xs text-muted-foreground"
                    >
                        NIT {{ voucher.customer.tax_id }}
                    </p>
                    <p
                        v-if="voucher.customer.address"
                        class="text-xs text-muted-foreground"
                    >
                        {{ voucher.customer.address }}
                    </p>
                </div>
                <div class="rounded-lg bg-muted/50 p-4">
                    <p class="text-xs text-muted-foreground">Pago</p>
                    <template v-if="voucher.status === 'pagado'">
                        <p class="font-semibold">
                            {{ methodLabel }} ·
                            {{ formatDateTime(voucher.paid_at) }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Referencia: {{ voucher.payment_reference ?? '—' }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Recibió: {{ voucher.collector?.name ?? '—' }}
                        </p>
                    </template>
                    <p v-else class="text-sm">
                        {{
                            voucher.status === 'anulado'
                                ? 'Comprobante anulado.'
                                : 'Aún no se ha registrado el pago.'
                        }}
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
                                Concepto
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-right">
                                Cant.
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-right">
                                Precio
                            </th>
                            <th scope="col" class="px-6 py-2.5 text-right">
                                Importe
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="line in voucher.lines" :key="line.id">
                            <td class="px-6 py-3">
                                <p class="font-medium">
                                    {{ line.description }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ concepts[line.concept] }}
                                </p>
                            </td>
                            <td class="px-3 py-3 text-right tabular">
                                {{ formatNumber(line.quantity) }}
                            </td>
                            <td class="px-3 py-3 text-right tabular">
                                {{ formatCurrency(line.unit_price) }}
                            </td>
                            <td class="px-6 py-3 text-right tabular">
                                {{ formatCurrency(line.amount) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr
                            v-for="[concept, amount] in subtotals"
                            :key="concept"
                            class="border-t text-muted-foreground"
                        >
                            <td colspan="3" class="px-6 py-2 text-right">
                                Subtotal {{ concepts[concept].toLowerCase() }}
                            </td>
                            <td class="px-6 py-2 text-right tabular">
                                {{ formatCurrency(amount) }}
                            </td>
                        </tr>
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
                                {{ formatCurrency(voucher.total) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </article>

        <form
            v-if="voucher.status === 'pendiente' && can('vouchers.collect')"
            class="grid gap-4 rounded-xl border bg-card p-5 shadow-xs sm:grid-cols-3 print:hidden"
            @submit.prevent="pay"
        >
            <FormField
                label="Forma de pago"
                for="payment_method"
                :error="form.errors.payment_method"
            >
                <NativeSelect id="payment_method" v-model="form.payment_method">
                    <option
                        v-for="m in paymentMethods"
                        :key="m.value"
                        :value="m.value"
                    >
                        {{ m.label }}
                    </option>
                </NativeSelect>
            </FormField>
            <FormField
                label="Referencia"
                for="payment_reference"
                :error="form.errors.payment_reference"
                hint="Número de boleta, cheque o transferencia."
            >
                <Input
                    id="payment_reference"
                    v-model="form.payment_reference"
                />
            </FormField>
            <div class="flex items-end">
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full"
                >
                    <Spinner v-if="form.processing" /><Banknote v-else />
                    Registrar pago de {{ formatCurrency(voucher.total) }}
                </Button>
            </div>
        </form>
    </div>
</template>
