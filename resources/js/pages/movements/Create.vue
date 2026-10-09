<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowDownToLine,
    ArrowLeftRight,
    ArrowUpFromLine,
    PackagePlus,
    SlidersHorizontal,
    Trash2,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import FormField from '@/components/FormField.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProductPicker from '@/components/ProductPicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency, formatNumber, toLocalInput } from '@/lib/format';
import { cn } from '@/lib/utils';
import { create, index, store } from '@/routes/movements';
import type { MovementType, Option, WarehouseOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Movimientos', href: index() },
            { title: 'Nuevo', href: create() },
        ],
    },
});

type PickerProduct = {
    id: number;
    sku: string;
    name: string;
    unit: string;
    cost: string;
};

const props = defineProps<{
    types: { value: MovementType; label: string }[];
    initialType: MovementType;
    warehouses: WarehouseOption[];
    destinations: WarehouseOption[];
    suppliers: Option[];
    products: PickerProduct[];
    stock?: Record<string, Record<string, string>>;
}>();

const typeMeta: Record<
    MovementType,
    { icon: typeof ArrowDownToLine; hint: string; tone: string }
> = {
    entrada: {
        icon: ArrowDownToLine,
        hint: 'Recepción de mercadería',
        tone: 'text-success bg-success/12',
    },
    salida: {
        icon: ArrowUpFromLine,
        hint: 'Despacho o consumo',
        tone: 'text-destructive bg-destructive/10',
    },
    transferencia: {
        icon: ArrowLeftRight,
        hint: 'Entre dos bodegas',
        tone: 'text-info bg-info/12',
    },
    ajuste: {
        icon: SlidersHorizontal,
        hint: 'Conteo físico',
        tone: 'text-warning-foreground bg-warning/15 dark:text-warning',
    },
};

const form = useForm({
    type: props.initialType,
    warehouse_id: (props.warehouses[0]?.id ?? '') as number | '',
    destination_warehouse_id: '' as number | '',
    supplier_id: '' as number | '',
    reference: '',
    occurred_at: toLocalInput(),
    notes: '',
    items: [] as { product_id: number; quantity: string; unit_cost: string }[],
});

const productMap = computed(
    () => new Map(props.products.map((p) => [p.id, p])),
);
const available = (productId: number) =>
    props.stock?.[String(form.warehouse_id)]?.[String(productId)] ?? '0';

const isEntry = computed(() => form.type === 'entrada');
const isTransfer = computed(() => form.type === 'transferencia');
const isAdjustment = computed(() => form.type === 'ajuste');
const checksStock = computed(() => form.type === 'salida' || isTransfer.value);

watch(
    () => form.warehouse_id,
    (id) => {
        if (form.destination_warehouse_id === id)
            form.destination_warehouse_id = '';
    },
);

const addItem = (product: PickerProduct) => {
    form.items.push({
        product_id: product.id,
        quantity: isAdjustment.value ? available(product.id) : '1',
        unit_cost: product.cost,
    });
};

const removeItem = (i: number) => form.items.splice(i, 1);

const overStock = (item: { product_id: number; quantity: string }) =>
    checksStock.value &&
    props.stock !== undefined &&
    Number(item.quantity) > Number(available(item.product_id));

const total = computed(() =>
    form.items.reduce(
        (sum, i) => sum + Number(i.quantity || 0) * Number(i.unit_cost || 0),
        0,
    ),
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        destination_warehouse_id: isTransfer.value
            ? data.destination_warehouse_id || null
            : null,
        supplier_id: isEntry.value ? data.supplier_id || null : null,
        items: data.items.map((i) => ({
            product_id: i.product_id,
            quantity: i.quantity,
            unit_cost: isEntry.value ? i.unit_cost : null,
        })),
    }));
    form.post(store.url());
};
</script>

<template>
    <Head title="Nuevo movimiento" />

    <form
        class="flex flex-1 flex-col gap-6 p-4 md:p-6"
        @submit.prevent="submit"
    >
        <PageHeader
            title="Registrar movimiento"
            description="El inventario se actualiza al guardar; los movimientos no se pueden editar."
        />

        <fieldset>
            <legend class="sr-only">Tipo de movimiento</legend>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <label
                    v-for="t in types"
                    :key="t.value"
                    :class="
                        cn(
                            'flex cursor-pointer items-center gap-3 rounded-xl border bg-card p-4 shadow-xs transition-all duration-150 hover:border-primary/40 has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50',
                            form.type === t.value &&
                                'border-primary ring-1 ring-primary',
                        )
                    "
                >
                    <input
                        v-model="form.type"
                        type="radio"
                        name="type"
                        :value="t.value"
                        class="sr-only"
                    />
                    <span
                        :class="
                            cn(
                                'grid size-10 shrink-0 place-items-center rounded-lg',
                                typeMeta[t.value].tone,
                            )
                        "
                    >
                        <component
                            :is="typeMeta[t.value].icon"
                            class="size-5"
                        />
                    </span>
                    <span>
                        <span class="block font-semibold">{{ t.label }}</span>
                        <span class="block text-xs text-muted-foreground">{{
                            typeMeta[t.value].hint
                        }}</span>
                    </span>
                </label>
            </div>
            <InputError :message="form.errors.type" />
        </fieldset>

        <div class="grid gap-6 xl:grid-cols-3">
            <section
                class="space-y-4 rounded-xl border bg-card p-5 shadow-xs"
                aria-labelledby="general-title"
            >
                <h2 id="general-title" class="font-semibold">
                    Datos generales
                </h2>
                <FormField
                    :label="isTransfer ? 'Bodega origen' : 'Bodega'"
                    for="warehouse_id"
                    :error="form.errors.warehouse_id"
                    required
                >
                    <NativeSelect id="warehouse_id" v-model="form.warehouse_id">
                        <option
                            v-for="w in warehouses"
                            :key="w.id"
                            :value="w.id"
                        >
                            {{ w.code }} · {{ w.name }}
                        </option>
                    </NativeSelect>
                </FormField>
                <FormField
                    v-if="isTransfer"
                    label="Bodega destino"
                    for="destination_warehouse_id"
                    :error="form.errors.destination_warehouse_id"
                    required
                >
                    <NativeSelect
                        id="destination_warehouse_id"
                        v-model="form.destination_warehouse_id"
                    >
                        <option value="" disabled>Selecciona destino</option>
                        <option
                            v-for="w in destinations.filter(
                                (d) => d.id !== form.warehouse_id,
                            )"
                            :key="w.id"
                            :value="w.id"
                        >
                            {{ w.code }} · {{ w.name }}
                        </option>
                    </NativeSelect>
                </FormField>
                <FormField
                    v-if="isEntry"
                    label="Proveedor"
                    for="supplier_id"
                    :error="form.errors.supplier_id"
                >
                    <NativeSelect id="supplier_id" v-model="form.supplier_id">
                        <option value="">Sin proveedor</option>
                        <option
                            v-for="s in suppliers"
                            :key="s.id"
                            :value="s.id"
                        >
                            {{ s.name }}
                        </option>
                    </NativeSelect>
                </FormField>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                    <FormField
                        label="Referencia"
                        for="reference"
                        :error="form.errors.reference"
                        hint="Factura, orden de trabajo, etc."
                    >
                        <Input id="reference" v-model="form.reference" />
                    </FormField>
                    <FormField
                        label="Fecha y hora"
                        for="occurred_at"
                        :error="form.errors.occurred_at"
                        required
                    >
                        <Input
                            id="occurred_at"
                            v-model="form.occurred_at"
                            type="datetime-local"
                        />
                    </FormField>
                </div>
                <FormField
                    label="Observaciones"
                    for="notes"
                    :error="form.errors.notes"
                >
                    <Textarea id="notes" v-model="form.notes" rows="3" />
                </FormField>
            </section>

            <section
                class="flex flex-col rounded-xl border bg-card shadow-xs xl:col-span-2"
                aria-labelledby="items-title"
            >
                <div class="space-y-3 border-b p-5">
                    <div class="flex items-center justify-between">
                        <h2 id="items-title" class="font-semibold">
                            Productos
                        </h2>
                        <span class="text-sm text-muted-foreground"
                            >{{ form.items.length }} línea(s)</span
                        >
                    </div>
                    <ProductPicker
                        :products="products"
                        :exclude="form.items.map((i) => i.product_id)"
                        @select="addItem"
                    />
                    <InputError :message="form.errors.items" />
                </div>

                <div v-if="form.items.length" class="flex-1 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="bg-muted/50 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <tr>
                                <th scope="col" class="px-4 py-2.5 text-left">
                                    Producto
                                </th>
                                <th scope="col" class="px-3 py-2.5 text-right">
                                    Disponible
                                </th>
                                <th
                                    scope="col"
                                    class="w-36 px-3 py-2.5 text-right"
                                >
                                    {{
                                        isAdjustment
                                            ? 'Conteo físico'
                                            : 'Cantidad'
                                    }}
                                </th>
                                <th
                                    v-if="isAdjustment"
                                    scope="col"
                                    class="px-3 py-2.5 text-right"
                                >
                                    Diferencia
                                </th>
                                <th
                                    v-if="isEntry"
                                    scope="col"
                                    class="w-36 px-3 py-2.5 text-right"
                                >
                                    Costo unit.
                                </th>
                                <th scope="col" class="w-12 px-3 py-2.5">
                                    <span class="sr-only">Quitar</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="(item, i) in form.items"
                                :key="item.product_id"
                                class="align-top"
                            >
                                <td class="px-4 py-3">
                                    <p class="font-medium">
                                        {{
                                            productMap.get(item.product_id)
                                                ?.name
                                        }}
                                    </p>
                                    <p
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{
                                            productMap.get(item.product_id)?.sku
                                        }}
                                        ·
                                        {{
                                            productMap.get(item.product_id)
                                                ?.unit
                                        }}
                                    </p>
                                </td>
                                <td
                                    class="px-3 py-3 text-right text-muted-foreground tabular"
                                >
                                    <span v-if="stock">{{
                                        formatNumber(available(item.product_id))
                                    }}</span>
                                    <span
                                        v-else
                                        class="inline-block h-4 w-10 animate-pulse rounded bg-muted"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model="item.quantity"
                                        type="number"
                                        step="0.01"
                                        :min="isAdjustment ? 0 : 0.01"
                                        inputmode="decimal"
                                        :aria-label="`Cantidad de ${productMap.get(item.product_id)?.name}`"
                                        :aria-invalid="
                                            overStock(item) ||
                                            !!form.errors[`items.${i}.quantity`]
                                        "
                                        class="text-right tabular"
                                    />
                                    <p
                                        v-if="overStock(item)"
                                        class="mt-1 text-xs text-destructive"
                                    >
                                        Excede lo disponible
                                    </p>
                                    <InputError
                                        :message="
                                            form.errors[
                                                `items.${i}.quantity`
                                            ] ??
                                            form.errors[`items.${i}.product_id`]
                                        "
                                    />
                                </td>
                                <td
                                    v-if="isAdjustment"
                                    class="px-3 py-3 text-right font-semibold tabular"
                                >
                                    <span
                                        :class="
                                            Number(item.quantity) -
                                                Number(
                                                    available(item.product_id),
                                                ) <
                                            0
                                                ? 'text-destructive'
                                                : 'text-success'
                                        "
                                    >
                                        {{
                                            Number(item.quantity) -
                                                Number(
                                                    available(item.product_id),
                                                ) >
                                            0
                                                ? '+'
                                                : ''
                                        }}{{
                                            formatNumber(
                                                Number(item.quantity) -
                                                    Number(
                                                        available(
                                                            item.product_id,
                                                        ),
                                                    ),
                                            )
                                        }}
                                    </span>
                                </td>
                                <td v-if="isEntry" class="px-3 py-2">
                                    <Input
                                        v-model="item.unit_cost"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        inputmode="decimal"
                                        :aria-label="`Costo de ${productMap.get(item.product_id)?.name}`"
                                        class="text-right tabular"
                                    />
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                        :aria-label="`Quitar ${productMap.get(item.product_id)?.name}`"
                                        @click="removeItem(i)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState
                    v-else
                    class="flex-1"
                    :icon="PackagePlus"
                    title="Agrega productos"
                    description="Busca por nombre o SKU en el campo de arriba."
                />

                <div
                    class="flex flex-col gap-3 border-t p-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p v-if="isEntry" class="text-sm text-muted-foreground">
                        Total de la entrada:
                        <span
                            class="text-base font-bold text-foreground tabular"
                            >{{ formatCurrency(total) }}</span
                        >
                    </p>
                    <span v-else />
                    <div class="flex gap-2">
                        <Button type="button" variant="outline" as-child>
                            <Link :href="index()">Cancelar</Link>
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing || !form.items.length"
                        >
                            <Spinner v-if="form.processing" />
                            Guardar movimiento
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    </form>
</template>
