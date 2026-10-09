<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Receipt } from '@lucide/vue';
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
import { index, show } from '@/routes/vouchers';
import type { Paginated, Voucher } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Cobros', href: index() }] },
});

const props = defineProps<{
    vouchers: Paginated<Voucher>;
    statuses: { value: string; label: string }[];
    pendingTotal: number;
    filters: { search: string; status: string | null };
}>();

const { filters } = useFilters(index.url(), {
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});
</script>

<template>
    <Head title="Cobros" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Cobros"
            description="Comprobantes de cobro generados en cada entrega."
        >
            <template #actions>
                <p class="text-right">
                    <span class="block text-xs text-muted-foreground"
                        >Pendiente de cobrar</span
                    >
                    <span class="text-xl font-bold tabular">{{
                        formatCurrency(pendingTotal)
                    }}</span>
                </p>
            </template>
        </PageHeader>

        <DataCard>
            <template #toolbar>
                <SearchInput
                    v-model="filters.search"
                    placeholder="Folio, cliente o ingreso…"
                />
                <NativeSelect
                    v-model="filters.status"
                    class="sm:w-52"
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

            <Table v-if="vouchers.data.length">
                <TableHeader>
                    <TableRow>
                        <TableHead>Comprobante</TableHead>
                        <TableHead>Cliente</TableHead>
                        <TableHead>Ingreso</TableHead>
                        <TableHead>Entrega</TableHead>
                        <TableHead class="text-right">Total</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead>Emitido</TableHead>
                        <TableHead class="w-10"
                            ><span class="sr-only">Ver</span></TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="v in vouchers.data"
                        :key="v.id"
                        class="cursor-pointer"
                        @click="router.visit(show.url(v.id))"
                    >
                        <TableCell class="font-mono text-sm font-semibold">{{
                            v.folio
                        }}</TableCell>
                        <TableCell class="font-medium">{{
                            v.customer.name
                        }}</TableCell>
                        <TableCell class="font-mono text-sm">{{
                            v.receipt.folio
                        }}</TableCell>
                        <TableCell class="font-mono text-sm">{{
                            v.delivery?.folio ?? '—'
                        }}</TableCell>
                        <TableCell class="text-right font-medium tabular">{{
                            formatCurrency(v.total)
                        }}</TableCell>
                        <TableCell><StatusPill :status="v.status" /></TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{
                            formatDateTime(v.issued_at)
                        }}</TableCell>
                        <TableCell>
                            <Link
                                :href="show(v.id)"
                                class="grid size-8 place-items-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"
                                :aria-label="`Ver ${v.folio}`"
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
                :icon="Receipt"
                title="Sin comprobantes"
                description="Los comprobantes aparecen al registrar entregas."
            />
            <Pagination :paginator="vouchers" />
        </DataCard>
    </div>
</template>
