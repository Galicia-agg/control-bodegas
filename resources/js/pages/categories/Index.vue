<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Tags, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DataCard from '@/components/DataCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
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
import { destroy, index, store, update } from '@/routes/categories';
import type { Category, Paginated } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Categorías', href: index() }] },
});

const props = defineProps<{
    categories: Paginated<Category>;
    filters: { search: string };
}>();

const { can } = usePermissions();
const { filters } = useFilters(index.url(), {
    search: props.filters.search ?? '',
});

const dialogOpen = ref(false);
const editing = ref<Category | null>(null);
const form = useForm({ name: '', description: '' });

const openForm = (category?: Category) => {
    editing.value = category ?? null;
    form.clearErrors();
    form.defaults({
        name: category?.name ?? '',
        description: category?.description ?? '',
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

const deleting = ref<Category | null>(null);
const confirmDelete = () => {
    if (!deleting.value) return;
    router.delete(destroy.url(deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
};
</script>

<template>
    <Head title="Categorías" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Categorías"
            description="Agrupa los productos para filtrar y reportar con facilidad."
        >
            <template #actions>
                <Button v-if="can('products.manage')" @click="openForm()">
                    <Plus /> Nueva categoría
                </Button>
            </template>
        </PageHeader>

        <DataCard>
            <template #toolbar>
                <SearchInput
                    v-model="filters.search"
                    placeholder="Buscar categoría…"
                />
            </template>
            <Table v-if="categories.data.length">
                <TableHeader>
                    <TableRow>
                        <TableHead>Nombre</TableHead>
                        <TableHead>Descripción</TableHead>
                        <TableHead class="text-right">Productos</TableHead>
                        <TableHead v-if="can('products.manage')" class="w-24"
                            ><span class="sr-only">Acciones</span></TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="c in categories.data" :key="c.id">
                        <TableCell class="font-medium">
                            <span class="flex items-center gap-2">
                                <Tags class="size-4 text-muted-foreground" />
                                {{ c.name }}
                            </span>
                        </TableCell>
                        <TableCell
                            class="max-w-md truncate text-muted-foreground"
                            >{{ c.description ?? '—' }}</TableCell
                        >
                        <TableCell class="text-right tabular">{{
                            c.products_count
                        }}</TableCell>
                        <TableCell
                            v-if="can('products.manage')"
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
                :icon="Tags"
                title="No hay categorías"
                description="Crea categorías para organizar tu catálogo."
            />
            <Pagination :paginator="categories" />
        </DataCard>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Editar categoría' : 'Nueva categoría'
                }}</DialogTitle>
                <DialogDescription
                    >Las categorías ayudan a clasificar los
                    productos.</DialogDescription
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
                <FormField
                    label="Descripción"
                    for="description"
                    :error="form.errors.description"
                >
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                    />
                </FormField>
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
        description="Los productos de esta categoría quedarán sin categoría."
        @update:open="(v) => !v && (deleting = null)"
        @confirm="confirmDelete"
    />
</template>
