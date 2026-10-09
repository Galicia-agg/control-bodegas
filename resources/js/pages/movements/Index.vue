<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeftRight, ArrowRight, Plus } from '@lucide/vue';
import DataCard from '@/components/DataCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import MovementTypeBadge from '@/components/MovementTypeBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Button } from '@/components/ui/button';
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
import { usePermissions } from '@/composables/usePermissions';
import { formatDateTime, formatNumber } from '@/lib/format';
import { create, index, show } from '@/routes/movements';
import type { Movement, Paginated, WarehouseOption } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Movimientos', href: index() }] },
});

const props = defineProps<{
    movements: Paginated<Movement>;
    types: { value: string; label: string }[];
    warehouses: WarehouseOption[];
    filters: { search: string; type: string | null; warehouse: number | null };
}>();

const { canAny } = usePermissions();
const { filters } = useFilters(index.url(), {
    search: props.filters.search ?? '',
    type: props.filters.type ?? '',
    warehouse: (props.filters.warehouse ?? '') as number | '',
});
</script>

<template>
    <Head title="Movimientos" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Movimientos"
            description="Bitácora de entradas, salidas, transferencias y ajustes."
        >
            <template #actions>
                <Button
                    v-if="
                        canAny(
                            'movements.entry',
                            'movements.exit',
                            'movements.transfer',
                            'movements.adjust',
                        )
                    "
                    as-child
                >
                    <Link :href="create()"><Plus /> Registrar movimiento</Link>
                </Button>
            </template>
        </PageHeader>

        <DataCard>
            <template #toolbar>
                <SearchInput
                    v-model="filters.search"
                    placeholder="Folio o referencia…"
                />
                <NativeSelect
                    v-model="filters.type"
                    class="sm:w-48"
                    aria-label="Filtrar por tipo"
                >
                    <option value="">Todos los tipos</option>
                    <option v-for="t in types" :key="t.value" :value="t.value">
                        {{ t.label }}
                    </option>
                </NativeSelect>
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
            </template>

            <Table v-if="movements.data.length">
                <TableHeader>
                    <TableRow>
                        <TableHead>Folio</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Bodega</TableHead>
                        <TableHead>Referencia</TableHead>
                        <TableHead class="text-right">Productos</TableHead>
                        <TableHead>Usuario</TableHead>
                        <TableHead>Fecha</TableHead>
                        <TableHead class="w-10"
                            ><span class="sr-only">Ver</span></TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="m in movements.data"
                        :key="m.id"
                        class="cursor-pointer"
                        @click="router.visit(show.url(m.id))"
                    >
                        <TableCell class="font-mono text-sm font-semibold">{{
                            m.folio
                        }}</TableCell>
                        <TableCell
                            ><MovementTypeBadge :type="m.type"
                        /></TableCell>
                        <TableCell>
                            <span class="text-sm">{{ m.warehouse.name }}</span>
                            <span
                                v-if="m.destination_warehouse"
                                class="flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <ArrowRight class="size-3" />
                                {{ m.destination_warehouse.name }}
                            </span>
                            <span
                                v-else-if="m.supplier"
                                class="block text-xs text-muted-foreground"
                                >{{ m.supplier.name }}</span
                            >
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{
                            m.reference ?? '—'
                        }}</TableCell>
                        <TableCell class="text-right tabular">
                            {{ m.items_count }}
                            <span class="block text-xs text-muted-foreground"
                                >{{ formatNumber(m.total_quantity) }} u.</span
                            >
                        </TableCell>
                        <TableCell class="text-sm">{{ m.user.name }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{
                            formatDateTime(m.occurred_at)
                        }}</TableCell>
                        <TableCell>
                            <Link
                                :href="show(m.id)"
                                class="grid size-8 place-items-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"
                                :aria-label="`Ver ${m.folio}`"
                                @click.stop
                            >
                                <ArrowRight class="size-4" />
                            </Link>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <EmptyState
                v-else
                :icon="ArrowLeftRight"
                title="Sin movimientos"
                description="No hay movimientos con los filtros seleccionados."
            />
            <Pagination :paginator="movements" />
        </DataCard>
    </div>
</template>
