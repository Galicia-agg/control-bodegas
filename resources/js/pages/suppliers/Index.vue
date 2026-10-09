<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Mail, Pencil, Phone, Plus, Trash2, Truck } from '@lucide/vue';
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
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFilters } from '@/composables/useFilters';
import { usePermissions } from '@/composables/usePermissions';
import { destroy, index, store, update } from '@/routes/suppliers';
import type { Paginated, Supplier } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Proveedores', href: index() }] },
});

const props = defineProps<{
    suppliers: Paginated<Supplier>;
    filters: { search: string };
}>();

const { can } = usePermissions();
const { filters } = useFilters(index.url(), {
    search: props.filters.search ?? '',
});

const dialogOpen = ref(false);
const editing = ref<Supplier | null>(null);
const form = useForm({
    name: '',
    tax_id: '',
    contact_name: '',
    email: '',
    phone: '',
    is_active: true,
});

const openForm = (supplier?: Supplier) => {
    editing.value = supplier ?? null;
    form.clearErrors();
    form.defaults({
        name: supplier?.name ?? '',
        tax_id: supplier?.tax_id ?? '',
        contact_name: supplier?.contact_name ?? '',
        email: supplier?.email ?? '',
        phone: supplier?.phone ?? '',
        is_active: supplier?.is_active ?? true,
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

const deleting = ref<Supplier | null>(null);
const confirmDelete = () => {
    if (!deleting.value) return;
    router.delete(destroy.url(deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
};
</script>

<template>
    <Head title="Proveedores" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Proveedores"
            description="Empresas que abastecen tus bodegas."
        >
            <template #actions>
                <Button v-if="can('suppliers.manage')" @click="openForm()">
                    <Plus /> Nuevo proveedor
                </Button>
            </template>
        </PageHeader>

        <DataCard>
            <template #toolbar>
                <SearchInput
                    v-model="filters.search"
                    placeholder="Buscar por nombre, NIT o contacto…"
                />
            </template>
            <Table v-if="suppliers.data.length">
                <TableHeader>
                    <TableRow>
                        <TableHead>Proveedor</TableHead>
                        <TableHead>NIT</TableHead>
                        <TableHead>Contacto</TableHead>
                        <TableHead class="text-right">Entradas</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead v-if="can('suppliers.manage')" class="w-24"
                            ><span class="sr-only">Acciones</span></TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="s in suppliers.data" :key="s.id">
                        <TableCell>
                            <div class="flex items-center gap-3">
                                <div
                                    class="grid size-9 place-items-center rounded-lg bg-info/12 text-info"
                                >
                                    <Truck class="size-4" />
                                </div>
                                <span class="font-medium">{{ s.name }}</span>
                            </div>
                        </TableCell>
                        <TableCell class="font-mono text-sm">{{
                            s.tax_id ?? '—'
                        }}</TableCell>
                        <TableCell>
                            <p class="text-sm">{{ s.contact_name ?? '—' }}</p>
                            <div
                                class="flex gap-3 text-xs text-muted-foreground"
                            >
                                <a
                                    v-if="s.email"
                                    :href="`mailto:${s.email}`"
                                    class="inline-flex items-center gap-1 hover:text-primary"
                                >
                                    <Mail class="size-3" />{{ s.email }}
                                </a>
                                <span
                                    v-if="s.phone"
                                    class="inline-flex items-center gap-1"
                                    ><Phone class="size-3" />{{ s.phone }}</span
                                >
                            </div>
                        </TableCell>
                        <TableCell class="text-right tabular">{{
                            s.movements_count
                        }}</TableCell>
                        <TableCell
                            ><StatusBadge :active="s.is_active"
                        /></TableCell>
                        <TableCell
                            v-if="can('suppliers.manage')"
                            class="text-right"
                        >
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="`Editar ${s.name}`"
                                    @click="openForm(s)"
                                >
                                    <Pencil />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    :aria-label="`Eliminar ${s.name}`"
                                    @click="deleting = s"
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
                title="No hay proveedores"
                description="Registra proveedores para asociarlos a las entradas."
            />
            <Pagination :paginator="suppliers" />
        </DataCard>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Editar proveedor' : 'Nuevo proveedor'
                }}</DialogTitle>
                <DialogDescription
                    >Información fiscal y de contacto.</DialogDescription
                >
            </DialogHeader>
            <form class="grid gap-4" @submit.prevent="submit">
                <FormField
                    label="Razón social"
                    for="name"
                    :error="form.errors.name"
                    required
                >
                    <Input id="name" v-model="form.name" />
                </FormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="NIT"
                        for="tax_id"
                        :error="form.errors.tax_id"
                    >
                        <Input
                            id="tax_id"
                            v-model="form.tax_id"
                            class="font-mono"
                        />
                    </FormField>
                    <FormField
                        label="Contacto"
                        for="contact_name"
                        :error="form.errors.contact_name"
                    >
                        <Input id="contact_name" v-model="form.contact_name" />
                    </FormField>
                    <FormField
                        label="Correo"
                        for="email"
                        :error="form.errors.email"
                    >
                        <Input id="email" v-model="form.email" type="email" />
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
                    <Checkbox v-model="form.is_active" /> Proveedor activo
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
        description="Las entradas existentes conservarán el registro sin proveedor."
        @update:open="(v) => !v && (deleting = null)"
        @confirm="confirmDelete"
    />
</template>
