<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Receipt as ReceiptIcon, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DataCard from '@/components/DataCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermissions } from '@/composables/usePermissions';
import { formatCurrency } from '@/lib/format';
import { destroy, index, store, update } from '@/routes/rates';
import type { Option, Rate, StorageUnitType } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Tarifas', href: index() }] },
});

defineProps<{
    rates: Rate[];
    services: { value: string; label: string; basis: string }[];
    unitTypes: { value: StorageUnitType; label: string }[];
    customers: Option[];
}>();

const { can } = usePermissions();

const serviceMeta = (
    rates: { value: string; label: string; basis: string }[],
    v: string,
) => rates.find((s) => s.value === v);

const dialogOpen = ref(false);
const editing = ref<Rate | null>(null);
const form = useForm({
    service: 'almacenaje',
    unit_type: '' as string,
    customer_id: '' as number | '',
    amount: '',
    free_days: '0',
    is_active: true,
});

const openForm = (rate?: Rate) => {
    editing.value = rate ?? null;
    form.clearErrors();
    form.defaults({
        service: rate?.service ?? 'almacenaje',
        unit_type: rate?.unit_type ?? '',
        customer_id: rate?.customer_id ?? '',
        amount: rate?.amount ?? '',
        free_days: String(rate?.free_days ?? 0),
        is_active: rate?.is_active ?? true,
    });
    form.reset();
    dialogOpen.value = true;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        unit_type: data.unit_type || null,
        customer_id: data.customer_id || null,
    }));
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    };

    if (editing.value) {
        form.put(update.url(editing.value.id), options);
    } else {
        form.post(store.url(), options);
    }
};

const deleting = ref<Rate | null>(null);
const confirmDelete = () => {
    if (!deleting.value) return;
    router.delete(destroy.url(deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
};
</script>

<template>
    <Head title="Tarifas" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Tarifas"
            description="Costo de cada servicio. Una tarifa por cliente o por tipo de unidad tiene prioridad sobre la general."
        >
            <template #actions>
                <Button v-if="can('rates.manage')" @click="openForm()">
                    <Plus /> Nueva tarifa
                </Button>
            </template>
        </PageHeader>

        <DataCard>
            <Table v-if="rates.length">
                <TableHeader>
                    <TableRow>
                        <TableHead>Servicio</TableHead>
                        <TableHead>Aplica a</TableHead>
                        <TableHead>Cliente</TableHead>
                        <TableHead class="text-right">Tarifa</TableHead>
                        <TableHead class="text-right">Días libres</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead v-if="can('rates.manage')" class="w-24"
                            ><span class="sr-only">Acciones</span></TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="r in rates" :key="r.id">
                        <TableCell class="font-medium">{{
                            serviceMeta(services, r.service)?.label
                        }}</TableCell>
                        <TableCell class="capitalize">{{
                            r.unit_type ?? 'Todos'
                        }}</TableCell>
                        <TableCell>{{
                            r.customer?.name ?? 'General'
                        }}</TableCell>
                        <TableCell class="text-right tabular">
                            {{ formatCurrency(r.amount) }}
                            <span class="block text-xs text-muted-foreground">{{
                                serviceMeta(services, r.service)?.basis
                            }}</span>
                        </TableCell>
                        <TableCell class="text-right tabular">{{
                            r.service === 'almacenaje' ? r.free_days : '—'
                        }}</TableCell>
                        <TableCell
                            ><StatusBadge :active="r.is_active"
                        /></TableCell>
                        <TableCell
                            v-if="can('rates.manage')"
                            class="text-right"
                        >
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Editar tarifa"
                                    @click="openForm(r)"
                                >
                                    <Pencil />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    aria-label="Eliminar tarifa"
                                    @click="deleting = r"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <EmptyState
                v-else
                :icon="ReceiptIcon"
                title="No hay tarifas"
                description="Crea al menos una tarifa de almacenaje para calcular los cobros."
            />
        </DataCard>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Editar tarifa' : 'Nueva tarifa'
                }}</DialogTitle>
                <DialogDescription
                    >Define cuánto se cobra por el servicio.</DialogDescription
                >
            </DialogHeader>
            <form class="grid gap-4" @submit.prevent="submit">
                <FormField
                    label="Servicio"
                    for="service"
                    :error="form.errors.service"
                    :hint="serviceMeta(services, form.service)?.basis"
                    required
                >
                    <NativeSelect id="service" v-model="form.service">
                        <option
                            v-for="s in services"
                            :key="s.value"
                            :value="s.value"
                        >
                            {{ s.label }}
                        </option>
                    </NativeSelect>
                </FormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        v-if="form.service !== 'domicilio'"
                        label="Tipo de unidad"
                        for="unit_type"
                        :error="form.errors.unit_type"
                    >
                        <NativeSelect id="unit_type" v-model="form.unit_type">
                            <option value="">Todos los tipos</option>
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
                        label="Cliente"
                        for="customer_id"
                        :error="form.errors.customer_id"
                        hint="Vacío = tarifa general."
                    >
                        <NativeSelect
                            id="customer_id"
                            v-model="form.customer_id"
                        >
                            <option value="">General</option>
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
                        label="Tarifa (Q)"
                        for="amount"
                        :error="form.errors.amount"
                        required
                    >
                        <Input
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                        />
                    </FormField>
                    <FormField
                        v-if="form.service === 'almacenaje'"
                        label="Días libres"
                        for="free_days"
                        :error="form.errors.free_days"
                        hint="Días sin cobro desde el ingreso."
                    >
                        <Input
                            id="free_days"
                            v-model="form.free_days"
                            type="number"
                            min="0"
                            step="1"
                            inputmode="numeric"
                        />
                    </FormField>
                </div>
                <Label class="flex items-center gap-3">
                    <Checkbox v-model="form.is_active" /> Tarifa activa
                </Label>
                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="dialogOpen = false"
                        >Cancelar</Button
                    >
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" /> Guardar
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        :open="!!deleting"
        title="¿Eliminar esta tarifa?"
        description="Los cálculos futuros dejarán de usarla."
        @update:open="(v) => !v && (deleting = null)"
        @confirm="confirmDelete"
    />
</template>
