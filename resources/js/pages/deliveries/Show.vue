<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Ban, Printer, Truck } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import FormField from '@/components/FormField.vue';
import StatusPill from '@/components/StatusPill.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/composables/usePermissions';
import { formatCurrency, formatDateTime } from '@/lib/format';
import { advance, cancel, index, show } from '@/routes/deliveries';
import { show as receiptShow } from '@/routes/receipts';
import { show as voucherShow } from '@/routes/vouchers';
import type { Delivery } from '@/types';

const props = defineProps<{ delivery: Delivery }>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Entregas', href: index() },
        { title: props.delivery.folio, href: show(props.delivery.id) },
    ],
});

const { can } = usePermissions();
const print = () => window.print();

const typeLabels: Record<string, string> = {
    contenedor: 'Contenedor',
    palet: 'Palet',
    caja: 'Caja',
    bulto: 'Bulto',
};

const route = useForm({ status: 'en_ruta' });
const startRoute = () =>
    route.post(advance.url(props.delivery.id), { preserveScroll: true });

const proof = useForm({
    status: 'entregada',
    recipient_name: props.delivery.recipient_name,
    proof_note: '',
});
const complete = () =>
    proof.post(advance.url(props.delivery.id), { preserveScroll: true });

const cancelling = ref(false);
const cancelForm = useForm({});
const confirmCancel = () =>
    cancelForm.post(cancel.url(props.delivery.id), {
        preserveScroll: true,
        onFinish: () => (cancelling.value = false),
    });

const canManage = () => can('deliveries.create');
const statusError = computed(
    () => (usePage().props.errors as Record<string, string>)?.status,
);
</script>

<template>
    <Head :title="delivery.folio" />

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
                    <StatusPill :status="delivery.status" />
                    <h1 class="font-mono text-2xl font-bold tracking-tight">
                        {{ delivery.folio }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{
                            delivery.type === 'retiro'
                                ? 'Retiro en bodega'
                                : 'Entrega a domicilio'
                        }}
                        · {{ formatDateTime(delivery.released_at) }}
                    </p>
                </div>
                <dl
                    class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:text-right"
                >
                    <div>
                        <dt class="text-xs text-muted-foreground">Ingreso</dt>
                        <dd class="font-mono font-medium">
                            <Link
                                :href="receiptShow(delivery.receipt.id)"
                                class="hover:underline"
                                >{{ delivery.receipt.folio }}</Link
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Comprobante
                        </dt>
                        <dd class="font-medium">
                            <template v-if="delivery.voucher">
                                <Link
                                    :href="voucherShow(delivery.voucher.id)"
                                    class="font-mono hover:underline"
                                    >{{ delivery.voucher.folio }}</Link
                                >
                                · {{ formatCurrency(delivery.voucher.total) }}
                            </template>
                            <template v-else>—</template>
                        </dd>
                    </div>
                </dl>
            </header>

            <div class="grid gap-4 border-b p-6 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg bg-muted/50 p-4">
                    <p class="text-xs text-muted-foreground">Cliente</p>
                    <p class="font-semibold">
                        {{ delivery.receipt.customer.name }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Registró: {{ delivery.user?.name }}
                    </p>
                </div>
                <div class="rounded-lg bg-muted/50 p-4">
                    <p class="text-xs text-muted-foreground">
                        {{ delivery.type === 'retiro' ? 'Retira' : 'Recibe' }}
                    </p>
                    <p class="font-semibold">{{ delivery.recipient_name }}</p>
                    <p
                        v-if="delivery.recipient_document"
                        class="font-mono text-xs text-muted-foreground"
                    >
                        {{ delivery.recipient_document }}
                    </p>
                </div>
                <div
                    v-if="delivery.type === 'domicilio'"
                    class="rounded-lg bg-muted/50 p-4"
                >
                    <p class="text-xs text-muted-foreground">Destino</p>
                    <p class="font-semibold">
                        {{ delivery.destination_address }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ delivery.destination_phone ?? '' }}
                        <template v-if="delivery.scheduled_at">
                            · Programada
                            {{ formatDateTime(delivery.scheduled_at) }}
                        </template>
                    </p>
                </div>
                <div
                    v-if="delivery.type === 'domicilio'"
                    class="rounded-lg bg-muted/50 p-4"
                >
                    <p class="text-xs text-muted-foreground">Transportista</p>
                    <p class="font-semibold">
                        {{ delivery.carrier?.name ?? 'Sin asignar' }}
                    </p>
                    <p
                        v-if="delivery.carrier"
                        class="text-xs text-muted-foreground"
                    >
                        {{ delivery.carrier.driver_name ?? '' }}
                        <template v-if="delivery.carrier.vehicle_plate">
                            · {{ delivery.carrier.vehicle_plate }}
                        </template>
                    </p>
                </div>
                <div
                    v-if="delivery.completed_at"
                    class="rounded-lg bg-muted/50 p-4"
                >
                    <p class="text-xs text-muted-foreground">Entregada</p>
                    <p class="font-semibold">
                        {{ formatDateTime(delivery.completed_at) }}
                    </p>
                    <p
                        v-if="delivery.proof_note"
                        class="text-xs text-muted-foreground"
                    >
                        {{ delivery.proof_note }}
                    </p>
                </div>
                <div
                    v-if="delivery.notes"
                    class="rounded-lg bg-muted/50 p-4 sm:col-span-2 xl:col-span-3"
                >
                    <p class="text-xs text-muted-foreground">Observaciones</p>
                    <p class="text-sm whitespace-pre-line">
                        {{ delivery.notes }}
                    </p>
                </div>
            </div>

            <ul class="divide-y">
                <li
                    v-for="u in delivery.units"
                    :key="u.id"
                    class="flex flex-wrap items-baseline gap-x-3 px-6 py-3"
                >
                    <span class="font-medium">{{ typeLabels[u.type] }}</span>
                    <span class="font-mono text-xs text-muted-foreground">{{
                        u.code
                    }}</span>
                    <span class="text-sm text-muted-foreground">{{
                        u.description
                    }}</span>
                </li>
            </ul>
        </article>

        <section
            v-if="
                canManage() &&
                delivery.type === 'domicilio' &&
                (delivery.status === 'programada' ||
                    delivery.status === 'en_ruta')
            "
            class="grid gap-4 rounded-xl border bg-card p-5 shadow-xs print:hidden"
        >
            <h2 class="font-semibold">Seguimiento</h2>

            <div
                v-if="delivery.status === 'programada'"
                class="flex flex-wrap gap-2"
            >
                <Button :disabled="route.processing" @click="startRoute">
                    <Truck /> Marcar en ruta
                </Button>
            </div>

            <form
                v-else
                class="grid gap-4 sm:grid-cols-2"
                @submit.prevent="complete"
            >
                <FormField
                    label="Recibió"
                    for="proof_recipient"
                    :error="proof.errors.recipient_name"
                >
                    <Input
                        id="proof_recipient"
                        v-model="proof.recipient_name"
                    />
                </FormField>
                <FormField
                    label="Constancia de entrega"
                    for="proof_note"
                    :error="proof.errors.proof_note"
                    hint="Quién firmó, observaciones, número de hoja de ruta…"
                >
                    <Textarea
                        id="proof_note"
                        v-model="proof.proof_note"
                        rows="2"
                    />
                </FormField>
                <div class="sm:col-span-2">
                    <Button type="submit" :disabled="proof.processing">
                        Confirmar entrega
                    </Button>
                </div>
            </form>

            <div class="border-t pt-4">
                <Button
                    variant="ghost"
                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                    @click="cancelling = true"
                >
                    <Ban /> Cancelar entrega
                </Button>
                <p v-if="statusError" class="mt-2 text-sm text-destructive">
                    {{ statusError }}
                </p>
            </div>
        </section>
    </div>

    <ConfirmDialog
        :open="cancelling"
        title="¿Cancelar esta entrega?"
        description="Las unidades vuelven a bodega y el comprobante se anula. No se puede si el comprobante ya fue cobrado."
        @update:open="(v) => !v && (cancelling = false)"
        @confirm="confirmCancel"
    />
</template>
