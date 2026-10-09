<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Building2,
    MapPin,
    Pencil,
    Phone,
    Plus,
    Trash2,
    UserRound,
} from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DataCard from '@/components/DataCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import SearchInput from '@/components/SearchInput.vue';
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
import { useFilters } from '@/composables/useFilters';
import { usePermissions } from '@/composables/usePermissions';
import { formatNumber } from '@/lib/format';
import { destroy, index, store, update } from '@/routes/warehouses';
import type { Option, Paginated, Warehouse } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Bodegas', href: index() }] },
});

const props = defineProps<{
    warehouses: Paginated<Warehouse>;
    managers: Option[];
    filters: { search: string };
}>();

const { can } = usePermissions();
const { filters } = useFilters(index.url(), {
    search: props.filters.search ?? '',
});

const dialogOpen = ref(false);
const editing = ref<Warehouse | null>(null);
const form = useForm({
    code: '',
    name: '',
    address: '',
    phone: '',
    manager_id: '' as number | '',
    is_active: true,
});

const openForm = (warehouse?: Warehouse) => {
    editing.value = warehouse ?? null;
    form.clearErrors();
    form.defaults({
        code: warehouse?.code ?? '',
        name: warehouse?.name ?? '',
        address: warehouse?.address ?? '',
        phone: warehouse?.phone ?? '',
        manager_id: warehouse?.manager_id ?? '',
        is_active: warehouse?.is_active ?? true,
    });
    form.reset();
    dialogOpen.value = true;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    };
    form.transform((data) => ({
        ...data,
        manager_id: data.manager_id || null,
    }));

    if (editing.value) {
        form.put(update.url(editing.value.id), options);
    } else {
        form.post(store.url(), options);
    }
};

const deleting = ref<Warehouse | null>(null);
const deleteProcessing = ref(false);
const confirmDelete = () => {
    if (!deleting.value) return;
    router.delete(destroy.url(deleting.value.id), {
        preserveScroll: true,
        onStart: () => (deleteProcessing.value = true),
        onFinish: () => {
            deleteProcessing.value = false;
            deleting.value = null;
        },
    });
};
</script>

<template>
    <Head title="Bodegas" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Bodegas"
            description="Ubicaciones físicas donde se almacena el inventario."
        >
            <template #actions>
                <Button v-if="can('warehouses.manage')" @click="openForm()">
                    <Plus /> Nueva bodega
                </Button>
            </template>
        </PageHeader>

        <DataCard>
            <template #toolbar>
                <SearchInput
                    v-model="filters.search"
                    placeholder="Buscar por nombre o código…"
                />
            </template>

            <div
                v-if="warehouses.data.length"
                class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="w in warehouses.data"
                    :key="w.id"
                    class="group flex flex-col rounded-xl border bg-background p-5 transition-all duration-200 hover:border-primary/40 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="grid size-10 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary"
                            >
                                <Building2 class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate font-semibold">
                                    {{ w.name }}
                                </h3>
                                <p
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ w.code }}
                                </p>
                            </div>
                        </div>
                        <StatusBadge
                            :active="w.is_active"
                            on="Activa"
                            off="Inactiva"
                        />
                    </div>

                    <dl class="mt-4 space-y-2 text-sm text-muted-foreground">
                        <div v-if="w.address" class="flex items-center gap-2">
                            <MapPin class="size-4 shrink-0" />
                            <dd class="truncate">{{ w.address }}</dd>
                        </div>
                        <div v-if="w.phone" class="flex items-center gap-2">
                            <Phone class="size-4 shrink-0" />
                            <dd>{{ w.phone }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <UserRound class="size-4 shrink-0" />
                            <dd>{{ w.manager?.name ?? 'Sin encargado' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 grid grid-cols-2 gap-3 border-t pt-4">
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Ingresos activos
                            </p>
                            <p class="font-semibold tabular">
                                {{ formatNumber(w.active_receipts_count) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Unidades almacenadas
                            </p>
                            <p class="font-semibold tabular">
                                {{ formatNumber(w.stored_units_count) }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="can('warehouses.manage')"
                        class="mt-4 flex justify-end gap-1"
                    >
                        <Button variant="ghost" size="sm" @click="openForm(w)">
                            <Pencil /> Editar
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                            @click="deleting = w"
                        >
                            <Trash2 /> Eliminar
                        </Button>
                    </div>
                </article>
            </div>
            <EmptyState
                v-else
                :icon="Building2"
                title="No hay bodegas"
                :description="
                    filters.search
                        ? 'Prueba con otro término de búsqueda.'
                        : 'Registra tu primera bodega para empezar.'
                "
            />
            <Pagination :paginator="warehouses" />
        </DataCard>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Editar bodega' : 'Nueva bodega'
                }}</DialogTitle>
                <DialogDescription
                    >Datos generales de la ubicación.</DialogDescription
                >
            </DialogHeader>
            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-3">
                    <FormField
                        label="Código"
                        for="code"
                        :error="form.errors.code"
                        required
                    >
                        <Input
                            id="code"
                            v-model="form.code"
                            placeholder="BOD-01"
                            class="font-mono uppercase"
                        />
                    </FormField>
                    <div class="sm:col-span-2">
                        <FormField
                            label="Nombre"
                            for="name"
                            :error="form.errors.name"
                            required
                        >
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Bodega Central"
                            />
                        </FormField>
                    </div>
                </div>
                <FormField
                    label="Dirección"
                    for="address"
                    :error="form.errors.address"
                >
                    <Input id="address" v-model="form.address" />
                </FormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="Teléfono"
                        for="phone"
                        :error="form.errors.phone"
                    >
                        <Input id="phone" v-model="form.phone" type="tel" />
                    </FormField>
                    <FormField
                        label="Encargado"
                        for="manager_id"
                        :error="form.errors.manager_id"
                    >
                        <NativeSelect id="manager_id" v-model="form.manager_id">
                            <option value="">Sin encargado</option>
                            <option
                                v-for="m in managers"
                                :key="m.id"
                                :value="m.id"
                            >
                                {{ m.name }}
                            </option>
                        </NativeSelect>
                    </FormField>
                </div>
                <Label class="flex items-center gap-3">
                    <Checkbox v-model="form.is_active" />
                    Bodega activa
                </Label>
                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="dialogOpen = false"
                        >Cancelar</Button
                    >
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        Guardar
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        :open="!!deleting"
        :processing="deleteProcessing"
        :title="`¿Eliminar ${deleting?.name ?? ''}?`"
        description="Solo puedes eliminar bodegas sin movimientos registrados."
        @update:open="(v) => !v && (deleting = null)"
        @confirm="confirmDelete"
    />
</template>
