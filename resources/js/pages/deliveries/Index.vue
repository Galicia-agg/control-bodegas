<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, PackageOpen } from '@lucide/vue';
import DataCard from '@/components/DataCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import StatusPill from '@/components/StatusPill.vue';
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
import { formatCurrency, formatDateTime } from '@/lib/format';
import { index, show } from '@/routes/deliveries';
import type { Delivery, Paginated } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Entregas', href: index() }] },
});

const props = defineProps<{
    deliveries: Paginated<Delivery>;
    statuses: { value: string; label: string }[];
    types: { value: string; label: string }[];
    filters: { search: string; status: string | null; type: string | null };
}>();

const { filters } = useFilters(index.url(), {
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    type: props.filters.type ?? '',
});
</script>

<template>
    <Head title="Entregas" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Entregas"
            description="Retiros en bodega y entregas a domicilio, parciales o totales. Se registran desde el detalle de un ingreso."
        />

        <DataCard>
            <template #toolbar>
                <SearchInput
                    v-model="filters.search"
                    placeholder="Folio, cliente o quien recibe…"
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
                    v-model="filters.status"
                    class="sm:w-44"
                    aria-label="Filtrar por estado"
                >
                    <option value="">Todos los estados</option>
                    <option
                        v-for="s in statuses"
                        :key="s.value"
                        :value="s.value"
                    >
                        {{ s.label }}
                    </option>
                </NativeSelect>
            </template>

            <Table v-if="deliveries.data.length">
                <TableHeader>
                    <TableRow>
                        <TableHead>Entrega</TableHead>
                        <TableHead>Cliente / ingreso</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Recibe</TableHead>
                        <TableHead class="text-right">Unidades</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead>Cobro</TableHead>
                        <TableHead>Fecha</TableHead>
                        <TableHead class="w-10"
                            ><span class="sr-only">Ver</span></TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="d in deliveries.data"
                        :key="d.id"
                        class="cursor-pointer"
                        @click="router.visit(show.url(d.id))"
                    >
                        <TableCell class="font-mono text-sm font-semibold">{{
                            d.folio
                        }}</TableCell>
                        <TableCell>
                            <span class="font-medium">{{
                                d.receipt.customer.name
                            }}</span>
                            <span
                                class="block font-mono text-xs text-muted-foreground"
                                >{{ d.receipt.folio }}</span
                            >
                        </TableCell>
                        <TableCell class="text-sm">{{
                            d.type === 'retiro' ? 'Retiro' : 'Domicilio'
                        }}</TableCell>
                        <TableCell class="text-sm">{{
                            d.recipient_name
                        }}</TableCell>
                        <TableCell class="text-right tabular">{{
                            d.units_count
                        }}</TableCell>
                        <TableCell><StatusPill :status="d.status" /></TableCell>
                        <TableCell>
                            <template v-if="d.voucher">
                                <span class="tabular">{{
                                    formatCurrency(d.voucher.total)
                                }}</span>
                                <StatusPill
                                    :status="d.voucher.status"
                                    class="ml-2"
                                />
                            </template>
                            <template v-else>—</template>
                        </TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{
                            formatDateTime(d.released_at)
                        }}</TableCell>
                        <TableCell>
                            <Link
                                :href="show(d.id)"
                                class="grid size-8 place-items-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"
                                :aria-label="`Ver ${d.folio}`"
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
                :icon="PackageOpen"
                title="Sin entregas"
                description="Aún no hay entregas con los filtros seleccionados."
            />
            <Pagination :paginator="deliveries" />
        </DataCard>
    </div>
</template>
