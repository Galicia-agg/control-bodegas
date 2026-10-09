<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PackagePlus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { toLocalInput } from '@/lib/format';
import { create, index, store } from '@/routes/receipts';
import type { Option, StorageUnitType, WarehouseOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Ingresos', href: index() },
            { title: 'Nuevo', href: create() },
        ],
    },
});

const props = defineProps<{
    customers: (Option & { tax_id: string | null })[];
    warehouses: WarehouseOption[];
    modes: { value: string; label: string; document: string }[];
    unitTypes: { value: StorageUnitType; label: string }[];
}>();

type UnitRow = {
    type: StorageUnitType;
    parent_index: number | '';
    description: string;
    pieces: string;
    weight_kg: string;
    volume_m3: string;
    location: string;
};

const form = useForm({
    customer_id: '' as number | '',
    warehouse_id: (props.warehouses[0]?.id ?? '') as number | '',
    transport_mode: props.modes[0]?.value ?? 'maritimo',
    transport_document: '',
    duca: '',
    carrier: '',
    origin: '',
    received_at: toLocalInput(),
    notes: '',
    units: [] as UnitRow[],
});

const documentLabel = computed(
    () =>
        props.modes.find((m) => m.value === form.transport_mode)?.document ??
        'Documento de transporte',
);

const typeLabel = (value: string) =>
    props.unitTypes.find((t) => t.value === value)?.label ?? value;

const addUnit = (type: StorageUnitType = 'contenedor') => {
    form.units.push({
        type,
        parent_index: '',
        description: '',
        pieces: '',
        weight_kg: '',
        volume_m3: '',
        location: '',
    });
};

const removeUnit = (index: number) => {
    form.units.splice(index, 1);

    // Re-point parents: drop links to the removed row, shift the later ones.
    for (const unit of form.units) {
        if (unit.parent_index === index) {
            unit.parent_index = '';
        } else if (unit.parent_index !== '' && unit.parent_index > index) {
            unit.parent_index -= 1;
        }
    }
};

const err = (key: string) => (form.errors as Record<string, string>)[key];

const submit = () => {
    form.transform((data) => ({
        ...data,
        units: data.units.map((u) => ({
            ...u,
            parent_index: u.parent_index === '' ? null : u.parent_index,
            pieces: u.pieces || null,
            weight_kg: u.weight_kg || null,
            volume_m3: u.volume_m3 || null,
        })),
    }));
    form.post(store.url());
};
</script>

<template>
    <Head title="Nuevo ingreso" />

    <form
        class="flex flex-1 flex-col gap-6 p-4 md:p-6"
        @submit.prevent="submit"
    >
        <PageHeader
            title="Registrar ingreso"
            description="Recepción de mercadería importada que se almacenará para un cliente."
        />

        <section class="grid gap-4 rounded-xl border bg-card p-5 shadow-xs">
            <h2 class="font-semibold">Datos del ingreso</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <FormField
                    label="Cliente"
                    for="customer_id"
                    :error="form.errors.customer_id"
                    required
                >
                    <NativeSelect id="customer_id" v-model="form.customer_id">
                        <option value="" disabled>Selecciona un cliente</option>
                        <option
                            v-for="c in customers"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </NativeSelect>
                </FormField>
                <FormField
                    label="Bodega"
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
                            {{ w.name }}
                        </option>
                    </NativeSelect>
                </FormField>
                <FormField
                    label="Fecha y hora de recepción"
                    for="received_at"
                    :error="form.errors.received_at"
                >
                    <Input
                        id="received_at"
                        v-model="form.received_at"
                        type="datetime-local"
                    />
                </FormField>
                <FormField
                    label="Tipo de transporte"
                    for="transport_mode"
                    :error="form.errors.transport_mode"
                    required
                >
                    <NativeSelect
                        id="transport_mode"
                        v-model="form.transport_mode"
                    >
                        <option
                            v-for="m in modes"
                            :key="m.value"
                            :value="m.value"
                        >
                            {{ m.label }}
                        </option>
                    </NativeSelect>
                </FormField>
                <FormField
                    :label="documentLabel"
                    for="transport_document"
                    :error="form.errors.transport_document"
                >
                    <Input
                        id="transport_document"
                        v-model="form.transport_document"
                        class="font-mono"
                    />
                </FormField>
                <FormField
                    label="DUCA"
                    for="duca"
                    :error="form.errors.duca"
                    hint="Declaración Única Centroamericana."
                >
                    <Input id="duca" v-model="form.duca" class="font-mono" />
                </FormField>
                <FormField
                    label="Naviera / transportista"
                    for="carrier"
                    :error="form.errors.carrier"
                >
                    <Input id="carrier" v-model="form.carrier" />
                </FormField>
                <FormField
                    label="Origen"
                    for="origin"
                    :error="form.errors.origin"
                >
                    <Input id="origin" v-model="form.origin" />
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
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Unidades de almacenaje</h2>
                    <p class="text-sm text-muted-foreground">
                        Contenedores, palets, cajas o bultos. Una unidad puede
                        ir dentro de otra anterior de la lista.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="t in unitTypes"
                        :key="t.value"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addUnit(t.value)"
                    >
                        <PackagePlus /> {{ t.label }}
                    </Button>
                </div>
            </div>

            <p v-if="form.errors.units" class="text-sm text-destructive">
                {{ form.errors.units }}
            </p>

            <EmptyState
                v-if="!form.units.length"
                :icon="PackagePlus"
                title="Aún no hay unidades"
                description="Agrega al menos una unidad con los botones de arriba."
            />

            <ol v-else class="grid gap-3">
                <li
                    v-for="(unit, i) in form.units"
                    :key="i"
                    class="grid gap-3 rounded-lg border bg-muted/30 p-4"
                >
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold">
                            <span class="font-mono text-muted-foreground"
                                >#{{ i + 1 }}</span
                            >
                            {{ typeLabel(unit.type) }}
                        </p>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                            :aria-label="`Quitar unidad ${i + 1}`"
                            @click="removeUnit(i)"
                        >
                            <Trash2 />
                        </Button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <FormField
                            label="Tipo"
                            :for="`unit-type-${i}`"
                            :error="err(`units.${i}.type`)"
                        >
                            <NativeSelect
                                :id="`unit-type-${i}`"
                                v-model="unit.type"
                            >
                                <option
                                    v-for="t in unitTypes"
                                    :key="t.value"
                                    :value="t.value"
                                >
                                    {{ t.label }}
                                </option>
                            </NativeSelect>
                        </FormField>
                        <FormField
                            label="Va dentro de"
                            :for="`unit-parent-${i}`"
                            :error="err(`units.${i}.parent_index`)"
                        >
                            <NativeSelect
                                :id="`unit-parent-${i}`"
                                v-model="unit.parent_index"
                            >
                                <option value="">— Nivel principal —</option>
                                <option
                                    v-for="(other, j) in form.units.slice(0, i)"
                                    :key="j"
                                    :value="j"
                                >
                                    #{{ j + 1 }} {{ typeLabel(other.type) }}
                                </option>
                            </NativeSelect>
                        </FormField>
                        <FormField
                            class="sm:col-span-2"
                            label="Descripción"
                            :for="`unit-desc-${i}`"
                            :error="err(`units.${i}.description`)"
                        >
                            <Input
                                :id="`unit-desc-${i}`"
                                v-model="unit.description"
                                placeholder="Qué contiene"
                            />
                        </FormField>
                        <FormField
                            label="Piezas"
                            :for="`unit-pieces-${i}`"
                            :error="err(`units.${i}.pieces`)"
                        >
                            <Input
                                :id="`unit-pieces-${i}`"
                                v-model="unit.pieces"
                                type="number"
                                min="1"
                                step="1"
                                inputmode="numeric"
                            />
                        </FormField>
                        <FormField
                            label="Peso (kg)"
                            :for="`unit-weight-${i}`"
                            :error="err(`units.${i}.weight_kg`)"
                        >
                            <Input
                                :id="`unit-weight-${i}`"
                                v-model="unit.weight_kg"
                                type="number"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                            />
                        </FormField>
                        <FormField
                            label="Volumen (m³)"
                            :for="`unit-volume-${i}`"
                            :error="err(`units.${i}.volume_m3`)"
                        >
                            <Input
                                :id="`unit-volume-${i}`"
                                v-model="unit.volume_m3"
                                type="number"
                                min="0"
                                step="0.001"
                                inputmode="decimal"
                            />
                        </FormField>
                        <FormField
                            label="Ubicación en bodega"
                            :for="`unit-loc-${i}`"
                            :error="err(`units.${i}.location`)"
                        >
                            <Input
                                :id="`unit-loc-${i}`"
                                v-model="unit.location"
                                placeholder="Ej. A-01"
                            />
                        </FormField>
                    </div>
                </li>
            </ol>
        </section>

        <div class="flex justify-end gap-2">
            <Button variant="outline" as-child>
                <Link :href="index()">Cancelar</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" /> Registrar ingreso
            </Button>
        </div>
    </form>
</template>
