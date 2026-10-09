<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, Truck } from '@lucide/vue';
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
import { destroy, index, store, update } from '@/routes/carriers';
import type { Carrier } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Transportistas', href: index() }] },
});

defineProps<{
    carriers: Carrier[];
    kinds: { value: string; label: string }[];
}>();

const { can } = usePermissions();

const dialogOpen = ref(false);
const editing = ref<Carrier | null>(null);
const form = useForm({
    name: '',
    kind: 'propio',
    driver_name: '',
    vehicle_plate: '',
    phone: '',
    is_active: true,
});

const openForm = (carrier?: Carrier) => {
    editing.value = carrier ?? null;
    form.clearErrors();
    form.defaults({
        name: carrier?.name ?? '',
        kind: carrier?.kind ?? 'propio',
        driver_name: carrier?.driver_name ?? '',
        vehicle_plate: carrier?.vehicle_plate ?? '',
        phone: carrier?.phone ?? '',
        is_active: carrier?.is_active ?? true,
    });
    form.reset();
    dialogOpen.value = true;
};

const submit = () => {
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

const deleting = ref<Carrier | null>(null);
const confirmDelete = () => {
    if (!deleting.value) return;
    router.delete(destroy.url(deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
};
</script>

<template>
    <Head title="Transportistas" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Transportistas"
            description="Flota propia y transportistas externos para las entregas a domicilio."
        >
            <template #actions>
                <Button v-if="can('carriers.manage')" @click="openForm()">
                    <Plus /> Nuevo transportista
                </Button>
            </template>
        </PageHeader>

        <DataCard>
            <Table v-if="carriers.length">
                <TableHeader>
                    <TableRow>
                        <TableHead>Transportista</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Piloto</TableHead>
                        <TableHead>Placa</TableHead>
                        <TableHead class="text-right">Entregas</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead v-if="can('carriers.manage')" class="w-24"
                            ><span class="sr-only">Acciones</span></TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="c in carriers" :key="c.id">
                        <TableCell class="font-medium">{{ c.name }}</TableCell>
                        <TableCell>{{
                            kinds.find((k) => k.value === c.kind)?.label
                        }}</TableCell>
                        <TableCell>
                            {{ c.driver_name ?? '—' }}
                            <span
                                v-if="c.phone"
                                class="block text-xs text-muted-foreground"
                                >{{ c.phone }}</span
                            >
                        </TableCell>
                        <TableCell class="font-mono text-sm">{{
                            c.vehicle_plate ?? '—'
                        }}</TableCell>
                        <TableCell class="text-right tabular">{{
                            c.deliveries_count
                        }}</TableCell>
                        <TableCell
                            ><StatusBadge :active="c.is_active"
                        /></TableCell>
                        <TableCell
                            v-if="can('carriers.manage')"
                            class="text-right"
                        >
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="`Editar ${c.name}`"
                                    @click="openForm(c)"
                                >
                                    <Pencil />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    :aria-label="`Eliminar ${c.name}`"
                                    @click="deleting = c"
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
                :icon="Truck"
                title="No hay transportistas"
                description="Registra tu flota o transportistas externos para programar entregas a domicilio."
            />
        </DataCard>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Editar transportista' : 'Nuevo transportista'
                }}</DialogTitle>
                <DialogDescription
                    >Vehículo y piloto que realizan las
                    entregas.</DialogDescription
                >
            </DialogHeader>
            <form class="grid gap-4" @submit.prevent="submit">
                <FormField
                    label="Nombre"
                    for="name"
                    :error="form.errors.name"
                    required
                >
                    <Input id="name" v-model="form.name" />
                </FormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="Tipo"
                        for="kind"
                        :error="form.errors.kind"
                        required
                    >
                        <NativeSelect id="kind" v-model="form.kind">
                            <option
                                v-for="k in kinds"
                                :key="k.value"
                                :value="k.value"
                            >
                                {{ k.label }}
                            </option>
                        </NativeSelect>
                    </FormField>
                    <FormField
                        label="Placa"
                        for="vehicle_plate"
                        :error="form.errors.vehicle_plate"
                    >
                        <Input
                            id="vehicle_plate"
                            v-model="form.vehicle_plate"
                            class="font-mono"
                        />
                    </FormField>
                    <FormField
                        label="Piloto"
                        for="driver_name"
                        :error="form.errors.driver_name"
                    >
                        <Input id="driver_name" v-model="form.driver_name" />
                    </FormField>
                    <FormField
                        label="Teléfono"
                        for="phone"
                        :error="form.errors.phone"
                    >
                        <Input id="phone" v-model="form.phone" type="tel" />
                    </FormField>
                </div>
                <Label class="flex items-center gap-3">
                    <Checkbox v-model="form.is_active" /> Transportista activo
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
        :title="`¿Eliminar ${deleting?.name ?? ''}?`"
        description="Solo se pueden eliminar transportistas sin entregas registradas."
        @update:open="(v) => !v && (deleting = null)"
        @confirm="confirmDelete"
    />
</template>
