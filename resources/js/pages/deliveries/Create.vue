<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency } from '@/lib/format';
import { show as receiptShow } from '@/routes/receipts';
import { store } from '@/routes/deliveries';
import type { Carrier, Option } from '@/types';

type Unit = {
    id: number;
    parent_id: number | null;
    type: string;
    code: string;
    description: string | null;
    location: string | null;
    estimate: number | null;
};

const props = defineProps<{
    receipt: { id: number; folio: string; customer: Option };
    units: Unit[];
    carriers: Carrier[];
    types: { value: string; label: string }[];
    paymentMethods: { value: string; label: string }[];
    defaultFee: number;
    canCollect: boolean;
}>();

const form = useForm({
    unit_ids: [] as number[],
    type: 'retiro',
    recipient_name: '',
    recipient_document: '',
    notes: '',
    carrier_id: '' as number | '',
    scheduled_at: '',
    destination_address: '',
    destination_phone: '',
    delivery_fee: '',
    paid: false,
    payment_method: props.paymentMethods[0]?.value ?? 'efectivo',
    payment_reference: '',
});

const byParent = computed(() => {
    const map = new Map<number | null, Unit[]>();

    for (const unit of props.units) {
        const parent = props.units.some((u) => u.id === unit.parent_id)
            ? unit.parent_id
            : null;
        map.set(parent, [...(map.get(parent) ?? []), unit]);
    }

    return map;
});

const rows = computed(() => {
    const out: { unit: Unit; depth: number }[] = [];
    const walk = (parent: number | null, depth: number) => {
        for (const unit of byParent.value.get(parent) ?? []) {
            out.push({ unit, depth });
            walk(unit.id, depth + 1);
        }
    };
    walk(null, 0);

    return out;
});

const descendants = (id: number): number[] =>
    (byParent.value.get(id) ?? []).flatMap((c) => [c.id, ...descendants(c.id)]);

// Units that go out: the ticked ones plus everything they contain.
const released = computed(() => {
    const ids = new Set<number>();

    for (const id of form.unit_ids) {
        ids.add(id);
        descendants(id).forEach((d) => ids.add(d));
    }

    return ids;
});

const toggle = (id: number, checked: boolean) => {
    form.unit_ids = checked
        ? [...form.unit_ids, id]
        : form.unit_ids.filter((i) => i !== id);
};

const allIds = computed(() => props.units.map((u) => u.id));
const everythingSelected = computed(
    () => released.value.size === props.units.length,
);

const estimatedUnits = computed(() =>
    props.units
        .filter((u) => released.value.has(u.id))
        .reduce((sum, u) => sum + (u.estimate ?? 0), 0),
);

const home = computed(() => form.type === 'domicilio');
const fee = computed(() =>
    home.value
        ? form.delivery_fee !== ''
            ? Number(form.delivery_fee)
            : props.defaultFee
        : 0,
);
const estimatedTotal = computed(() => estimatedUnits.value + fee.value);

const typeLabels: Record<string, string> = {
    contenedor: 'Contenedor',
    palet: 'Palet',
    caja: 'Caja',
    bulto: 'Bulto',
};

watch(
    () => form.type,
    (type) => {
        if (type !== 'domicilio') form.carrier_id = '';
    },
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        carrier_id: data.carrier_id || null,
        scheduled_at: data.scheduled_at || null,
        delivery_fee: data.delivery_fee === '' ? null : data.delivery_fee,
    }));
    form.post(store.url(props.receipt.id));
};
</script>

<template>
    <Head :title="`Entrega · ${receipt.folio}`" />

    <form
        class="flex flex-1 flex-col gap-6 p-4 md:p-6"
        @submit.prevent="submit"
    >
        <PageHeader
            :title="`Registrar entrega · ${receipt.folio}`"
            :description="`Mercadería de ${receipt.customer.name}. Al guardar se genera el comprobante de cobro.`"
        />

        <section class="grid gap-4 rounded-xl border bg-card p-5 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">¿Qué sale de la bodega?</h2>
                    <p class="text-sm text-muted-foreground">
                        Marca las unidades. Al marcar una que contiene otras,
                        salen todas.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="form.unit_ids = [...allIds]"
                        >Entrega total</Button
                    >
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="form.unit_ids = []"
                        >Limpiar</Button
                    >
                </div>
            </div>
            <p v-if="form.errors.unit_ids" class="text-sm text-destructive">
                {{ form.errors.unit_ids }}
            </p>

            <ul class="divide-y rounded-lg border">
                <li
                    v-for="{ unit, depth } in rows"
                    :key="unit.id"
                    class="flex items-center gap-3 px-4 py-2.5"
                    :style="{ paddingLeft: `${1 + depth * 1.5}rem` }"
                >
                    <Checkbox
                        :id="`unit-${unit.id}`"
                        :model-value="released.has(unit.id)"
                        :disabled="
                            released.has(unit.id) &&
                            !form.unit_ids.includes(unit.id)
                        "
                        @update:model-value="(v) => toggle(unit.id, v === true)"
                    />
                    <Label
                        :for="`unit-${unit.id}`"
                        class="flex flex-1 flex-wrap items-baseline gap-x-3"
                    >
                        <span class="font-medium">{{
                            typeLabels[unit.type]
                        }}</span>
                        <span class="font-mono text-xs text-muted-foreground">{{
                            unit.code
                        }}</span>
                        <span class="text-sm text-muted-foreground">{{
                            unit.description
                        }}</span>
                    </Label>
                    <span
                        v-if="unit.estimate !== null"
                        class="text-sm text-muted-foreground tabular"
                        >≈ {{ formatCurrency(unit.estimate) }}</span
                    >
                </li>
            </ul>
        </section>

        <section class="grid gap-4 rounded-xl border bg-card p-5 shadow-xs">
            <h2 class="font-semibold">Tipo de entrega</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <FormField
                    label="Tipo"
                    for="type"
                    :error="form.errors.type"
                    required
                >
                    <NativeSelect id="type" v-model="form.type">
                        <option
                            v-for="t in types"
                            :key="t.value"
                            :value="t.value"
                        >
                            {{ t.label }}
                        </option>
                    </NativeSelect>
                </FormField>
                <FormField
                    :label="home ? 'Cliente final (recibe)' : 'Quién retira'"
                    for="recipient_name"
                    :error="form.errors.recipient_name"
                    required
                >
                    <Input id="recipient_name" v-model="form.recipient_name" />
                </FormField>
                <FormField
                    label="Documento (DPI)"
                    for="recipient_document"
                    :error="form.errors.recipient_document"
                >
                    <Input
                        id="recipient_document"
                        v-model="form.recipient_document"
                        class="font-mono"
                    />
                </FormField>
            </div>

            <div v-if="home" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <FormField
                    class="sm:col-span-2"
                    label="Dirección de entrega"
                    for="destination_address"
                    :error="form.errors.destination_address"
                    required
                >
                    <Input
                        id="destination_address"
                        v-model="form.destination_address"
                    />
                </FormField>
                <FormField
                    label="Teléfono de contacto"
                    for="destination_phone"
                    :error="form.errors.destination_phone"
                >
                    <Input
                        id="destination_phone"
                        v-model="form.destination_phone"
                        type="tel"
                    />
                </FormField>
                <FormField
                    label="Transportista"
                    for="carrier_id"
                    :error="form.errors.carrier_id"
                >
                    <NativeSelect id="carrier_id" v-model="form.carrier_id">
                        <option value="">Sin asignar</option>
                        <option v-for="c in carriers" :key="c.id" :value="c.id">
                            {{ c.name
                            }}{{
                                c.vehicle_plate ? ` · ${c.vehicle_plate}` : ''
                            }}
                        </option>
                    </NativeSelect>
                </FormField>
                <FormField
                    label="Fecha programada"
                    for="scheduled_at"
                    :error="form.errors.scheduled_at"
                >
                    <Input
                        id="scheduled_at"
                        v-model="form.scheduled_at"
                        type="datetime-local"
                    />
                </FormField>
                <FormField
                    label="Costo de entrega (Q)"
                    for="delivery_fee"
                    :error="form.errors.delivery_fee"
                    :hint="`Vacío = tarifa vigente (${formatCurrency(defaultFee)}).`"
                >
                    <Input
                        id="delivery_fee"
                        v-model="form.delivery_fee"
                        type="number"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                    />
                </FormField>
            </div>

            <FormField
                label="Observaciones"
                for="notes"
                :error="form.errors.notes"
            >
                <Textarea id="notes" v-model="form.notes" rows="2" />
            </FormField>
        </section>

        <section class="grid gap-4 rounded-xl border bg-card p-5 shadow-xs">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Cobro</h2>
                    <p class="text-sm text-muted-foreground">
                        Estimado con los días acumulados hasta hoy. El
                        comprobante definitivo se calcula al guardar.
                    </p>
                </div>
                <p class="text-right">
                    <span class="block text-xs text-muted-foreground"
                        >Total estimado</span
                    >
                    <span class="text-2xl font-bold tabular">{{
                        formatCurrency(estimatedTotal)
                    }}</span>
                    <span
                        v-if="home"
                        class="block text-xs text-muted-foreground"
                        >incluye entrega {{ formatCurrency(fee) }}</span
                    >
                </p>
            </div>

            <template v-if="canCollect">
                <Label class="flex items-center gap-3">
                    <Checkbox v-model="form.paid" />
                    El cliente paga ahora
                </Label>
                <div
                    v-if="form.paid"
                    class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <FormField
                        label="Forma de pago"
                        for="payment_method"
                        :error="form.errors.payment_method"
                    >
                        <NativeSelect
                            id="payment_method"
                            v-model="form.payment_method"
                        >
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
                    >
                        <Input
                            id="payment_reference"
                            v-model="form.payment_reference"
                        />
                    </FormField>
                </div>
            </template>
            <p v-else class="text-sm text-muted-foreground">
                El comprobante quedará pendiente de pago.
            </p>
            <p v-if="form.errors.paid" class="text-sm text-destructive">
                {{ form.errors.paid }}
            </p>
        </section>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <p
                v-if="everythingSelected"
                class="mr-auto text-sm text-muted-foreground"
            >
                Es una entrega total: el ingreso quedará finalizado.
            </p>
            <Button variant="outline" as-child>
                <Link :href="receiptShow(receipt.id)">Cancelar</Link>
            </Button>
            <Button
                type="submit"
                :disabled="form.processing || !form.unit_ids.length"
            >
                <Spinner v-if="form.processing" /> Registrar entrega
            </Button>
        </div>
    </form>
</template>
