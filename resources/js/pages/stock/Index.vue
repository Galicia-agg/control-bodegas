<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Boxes, TriangleAlert } from '@lucide/vue';
import DataCard from '@/components/DataCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFilters } from '@/composables/useFilters';
import { formatCurrency, formatNumber } from '@/lib/format';
import { index } from '@/routes/stock';
import type { Option, Paginated, Stock, WarehouseOption } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Existencias', href: index() }] },
});

const props = defineProps<{
    stocks: Paginated<Stock>;
    warehouses: WarehouseOption[];
    categories: Option[];
    filters: {
        search: string;
        warehouse: number | null;
        category: number | null;
        low: boolean;
    };
}>();

const { filters, reset } = useFilters(index.url(), {
    search: props.filters.search ?? '',
    warehouse: (props.filters.warehouse ?? '') as number | '',
    category: (props.filters.category ?? '') as number | '',
    low: props.filters.low ?? false,
});

const level = (s: Stock) => {
    const qty = Number(s.quantity);
    const min = Number(s.product.min_stock);

    if (qty <= 0)
        return {
            label: 'Agotado',
            class: 'bg-destructive/10 text-destructive',
            bar: 'bg-destructive',
        };
    if (qty <= min)
        return {
            label: 'Bajo',
            class: 'bg-warning/15 text-warning-foreground dark:text-warning',
            bar: 'bg-warning',
        };

    return {
        label: 'Normal',
        class: 'bg-success/12 text-success',
        bar: 'bg-success',
    };
};

const fill = (s: Stock) => {
    const min = Number(s.product.min_stock) || 1;

    return Math.min(100, (Number(s.quantity) / (min * 3)) * 100);
};
</script>

<template>
    <Head title="Existencias" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Existencias"
            description="Cantidad disponible de cada producto por bodega."
        />

        <DataCard>
            <template #toolbar>
                <SearchInput
                    v-model="filters.search"
                    placeholder="Buscar producto o SKU…"
                />
                <NativeSelect
                    v-model="filters.warehouse"
                    class="sm:w-52"
                    aria-label="Filtrar por bodega"
                >
                    <option value="">Todas las bodegas</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">
                        {{ w.name }}
                    </option>
                </NativeSelect>
                <NativeSelect
                    v-model="filters.category"
                    class="sm:w-52"
                    aria-label="Filtrar por categoría"
                >
                    <option value="">Todas las categorías</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </NativeSelect>
                <Label
                    class="flex h-9 items-center gap-2 rounded-md border px-3 text-sm font-normal"
                >
                    <Checkbox v-model="filters.low" />
                    <TriangleAlert class="size-4 text-warning" /> Solo stock
                    bajo
                </Label>
                <Button
                    v-if="
                        filters.search ||
                        filters.warehouse ||
                        filters.category ||
                        filters.low
                    "
                    variant="ghost"
                    size="sm"
                    @click="reset"
                    >Limpiar filtros</Button
                >
            </template>

            <Table v-if="stocks.data.length">
                <TableHeader>
                    <TableRow>
                        <TableHead>Producto</TableHead>
                        <TableHead>Bodega</TableHead>
                        <TableHead>Categoría</TableHead>
                        <TableHead class="text-right">Existencia</TableHead>
                        <TableHead class="w-44">Nivel</TableHead>
                        <TableHead class="text-right">Valor</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="s in stocks.data" :key="s.id">
                        <TableCell>
                            <p class="font-medium">{{ s.product.name }}</p>
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ s.product.sku }}
                            </p>
                        </TableCell>
                        <TableCell>
                            <span
                                class="font-mono text-xs text-muted-foreground"
                                >{{ s.warehouse.code }}</span
                            >
                            <p class="text-sm">{{ s.warehouse.name }}</p>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{
                            s.product.category?.name ?? '—'
                        }}</TableCell>
                        <TableCell class="text-right">
                            <span class="font-semibold tabular">{{
                                formatNumber(s.quantity)
                            }}</span>
                            <span class="ml-1 text-xs text-muted-foreground">{{
                                s.product.unit
                            }}</span>
                            <p class="text-xs text-muted-foreground tabular">
                                mín. {{ formatNumber(s.product.min_stock) }}
                            </p>
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"
                                    aria-hidden="true"
                                >
                                    <div
                                        class="h-full rounded-full"
                                        :class="level(s).bar"
                                        :style="{ width: `${fill(s)}%` }"
                                    />
                                </div>
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="level(s).class"
                                    >{{ level(s).label }}</span
                                >
                            </div>
                        </TableCell>
                        <TableCell class="text-right tabular">{{
                            formatCurrency(
                                Number(s.quantity) * Number(s.product.cost),
                            )
                        }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <EmptyState
                v-else
                :icon="Boxes"
                title="Sin existencias"
                description="No hay inventario que coincida con los filtros."
            />
            <Pagination :paginator="stocks" />
        </DataCard>
    </div>
</template>
