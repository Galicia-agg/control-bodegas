<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Package, Pencil, Plus, Trash2 } from '@lucide/vue';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useFilters } from '@/composables/useFilters';
import { usePermissions } from '@/composables/usePermissions';
import { formatCurrency, formatNumber } from '@/lib/format';
import { destroy, index, store, update } from '@/routes/products';
import type { Option, Paginated, Product } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Productos', href: index() }] },
});

const props = defineProps<{
    products: Paginated<Product>;
    categories: Option[];
    units: string[];
    filters: { search: string; category: number | null };
}>();

const { can } = usePermissions();
const { filters } = useFilters(index.url(), {
    search: props.filters.search ?? '',
    category: (props.filters.category ?? '') as number | '',
});

const dialogOpen = ref(false);
const editing = ref<Product | null>(null);
const form = useForm({
    sku: '',
    name: '',
    description: '',
    category_id: '' as number | '',
    unit: 'unidad',
    cost: '0',
    min_stock: '0',
    is_active: true,
});

const openForm = (product?: Product) => {
    editing.value = product ?? null;
    form.clearErrors();
    form.defaults({
        sku: product?.sku ?? '',
        name: product?.name ?? '',
        description: product?.description ?? '',
        category_id: product?.category_id ?? '',
        unit: product?.unit ?? 'unidad',
        cost: product?.cost ?? '0',
        min_stock: product?.min_stock ?? '0',
        is_active: product?.is_active ?? true,
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
        category_id: data.category_id || null,
    }));

    if (editing.value) {
        form.put(update.url(editing.value.id), options);
    } else {
        form.post(store.url(), options);
    }
};

const deleting = ref<Product | null>(null);
const confirmDelete = () => {
    if (!deleting.value) return;
    router.delete(destroy.url(deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
};

const isLow = (p: Product) => Number(p.total_stock ?? 0) <= Number(p.min_stock);
</script>

<template>
    <Head title="Productos" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Productos"
            description="Catálogo maestro de artículos que manejan tus bodegas."
        >
            <template #actions>
                <Button v-if="can('products.manage')" @click="openForm()">
                    <Plus /> Nuevo producto
                </Button>
            </template>
        </PageHeader>

        <DataCard>
            <template #toolbar>
                <SearchInput
                    v-model="filters.search"
                    placeholder="Buscar por nombre o SKU…"
                />
                <NativeSelect
                    v-model="filters.category"
                    class="sm:w-56"
                    aria-label="Filtrar por categoría"
                >
                    <option value="">Todas las categorías</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </NativeSelect>
            </template>
            <Table v-if="products.data.length">
                <TableHeader>
                    <TableRow>
                        <TableHead>Producto</TableHead>
                        <TableHead>Categoría</TableHead>
                        <TableHead>Unidad</TableHead>
                        <TableHead class="text-right">Costo</TableHead>
                        <TableHead class="text-right">Mínimo</TableHead>
                        <TableHead class="text-right"
                            >Existencia total</TableHead
                        >
                        <TableHead>Estado</TableHead>
                        <TableHead v-if="can('products.manage')" class="w-24"
                            ><span class="sr-only">Acciones</span></TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="p in products.data" :key="p.id">
                        <TableCell>
                            <p class="font-medium">{{ p.name }}</p>
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ p.sku }}
                            </p>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{
                            p.category?.name ?? '—'
                        }}</TableCell>
                        <TableCell class="capitalize">{{ p.unit }}</TableCell>
                        <TableCell class="text-right tabular">{{
                            formatCurrency(p.cost)
                        }}</TableCell>
                        <TableCell
                            class="text-right text-muted-foreground tabular"
                            >{{ formatNumber(p.min_stock) }}</TableCell
                        >
                        <TableCell class="text-right">
                            <span
                                class="font-semibold tabular"
                                :class="
                                    isLow(p)
                                        ? 'text-warning-foreground dark:text-warning'
                                        : ''
                                "
                                >{{ formatNumber(p.total_stock) }}</span
                            >
                        </TableCell>
                        <TableCell
                            ><StatusBadge :active="p.is_active"
                        /></TableCell>
                        <TableCell
                            v-if="can('products.manage')"
                            class="text-right"
                        >
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="`Editar ${p.name}`"
                                    @click="openForm(p)"
                                >
                                    <Pencil />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    :aria-label="`Eliminar ${p.name}`"
                                    @click="deleting = p"
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
                :icon="Package"
                title="No hay productos"
                description="Ajusta los filtros o registra un producto nuevo."
            />
            <Pagination :paginator="products" />
        </DataCard>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Editar producto' : 'Nuevo producto'
                }}</DialogTitle>
                <DialogDescription
                    >El stock se modifica únicamente con
                    movimientos.</DialogDescription
                >
            </DialogHeader>
            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-3">
                    <FormField
                        label="SKU"
                        for="sku"
                        :error="form.errors.sku"
                        required
                    >
                        <Input
                            id="sku"
                            v-model="form.sku"
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
                            <Input id="name" v-model="form.name" />
                        </FormField>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="Categoría"
                        for="category_id"
                        :error="form.errors.category_id"
                    >
                        <NativeSelect
                            id="category_id"
                            v-model="form.category_id"
                        >
                            <option value="">Sin categoría</option>
                            <option
                                v-for="c in categories"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </NativeSelect>
                    </FormField>
                    <FormField
                        label="Unidad de medida"
                        for="unit"
                        :error="form.errors.unit"
                        required
                    >
                        <NativeSelect
                            id="unit"
                            v-model="form.unit"
                            class="capitalize"
                        >
                            <option v-for="u in units" :key="u" :value="u">
                                {{ u }}
                            </option>
                        </NativeSelect>
                    </FormField>
                    <FormField
                        label="Costo unitario (Q)"
                        for="cost"
                        :error="form.errors.cost"
                        required
                    >
                        <Input
                            id="cost"
                            v-model="form.cost"
                            type="number"
                            step="0.01"
                            min="0"
                            inputmode="decimal"
                            class="tabular"
                        />
                    </FormField>
                    <FormField
                        label="Stock mínimo"
                        for="min_stock"
                        :error="form.errors.min_stock"
                        hint="Genera alerta al llegar a este valor."
                        required
                    >
                        <Input
                            id="min_stock"
                            v-model="form.min_stock"
                            type="number"
                            step="0.01"
                            min="0"
                            inputmode="decimal"
                            class="tabular"
                        />
                    </FormField>
                </div>
                <FormField
                    label="Descripción"
                    for="description"
                    :error="form.errors.description"
                >
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="2"
                    />
                </FormField>
                <Label class="flex items-center gap-3">
                    <Checkbox v-model="form.is_active" /> Producto activo
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
        description="Solo se pueden eliminar productos sin existencias ni movimientos."
        @update:open="(v) => !v && (deleting = null)"
        @confirm="confirmDelete"
    />
</template>
