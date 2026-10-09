<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DataCard from '@/components/DataCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import FormField from '@/components/FormField.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import SearchInput from '@/components/SearchInput.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import { useFilters } from '@/composables/useFilters';
import { useInitials } from '@/composables/useInitials';
import { destroy, index, store, update } from '@/routes/users';
import type { ManagedUser, Paginated, WarehouseOption } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Usuarios', href: index() }] },
});

const props = defineProps<{
    users: Paginated<ManagedUser>;
    roles: string[];
    warehouses: WarehouseOption[];
    filters: { search: string; role: string | null };
}>();

const { getInitials } = useInitials();
const currentUserId = computed(() => usePage().props.auth.user.id);
const { filters } = useFilters(index.url(), {
    search: props.filters.search ?? '',
    role: props.filters.role ?? '',
});

const roleTone: Record<string, string> = {
    Administrador: 'bg-primary/10 text-primary ring-primary/25',
    Supervisor: 'bg-info/12 text-info ring-info/25',
    Bodeguero: 'bg-success/12 text-success ring-success/25',
    Auditor:
        'bg-warning/15 text-warning-foreground ring-warning/35 dark:text-warning',
};

const dialogOpen = ref(false);
const editing = ref<ManagedUser | null>(null);
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'Bodeguero',
    warehouses: [] as number[],
    is_active: true,
});

const openForm = (user?: ManagedUser) => {
    editing.value = user ?? null;
    form.clearErrors();
    form.defaults({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
        role: user?.roles[0]?.name ?? 'Bodeguero',
        warehouses: user?.warehouses.map((w) => w.id) ?? [],
        is_active: user?.is_active ?? true,
    });
    form.reset();
    dialogOpen.value = true;
};

const toggleWarehouse = (id: number, checked: boolean | 'indeterminate') => {
    form.warehouses =
        checked === true
            ? [...form.warehouses, id]
            : form.warehouses.filter((w) => w !== id);
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

const deleting = ref<ManagedUser | null>(null);
const confirmDelete = () => {
    if (!deleting.value) return;
    router.delete(destroy.url(deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
};
</script>

<template>
    <Head title="Usuarios" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Usuarios"
            description="Cuentas con acceso al sistema, su rol y las bodegas que operan."
        >
            <template #actions>
                <Button @click="openForm()"><Plus /> Nuevo usuario</Button>
            </template>
        </PageHeader>

        <DataCard>
            <template #toolbar>
                <SearchInput
                    v-model="filters.search"
                    placeholder="Buscar por nombre o correo…"
                />
                <NativeSelect
                    v-model="filters.role"
                    class="sm:w-48"
                    aria-label="Filtrar por rol"
                >
                    <option value="">Todos los roles</option>
                    <option v-for="r in roles" :key="r" :value="r">
                        {{ r }}
                    </option>
                </NativeSelect>
            </template>
            <Table v-if="users.data.length">
                <TableHeader>
                    <TableRow>
                        <TableHead>Usuario</TableHead>
                        <TableHead>Rol</TableHead>
                        <TableHead>Bodegas asignadas</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead class="w-24"
                            ><span class="sr-only">Acciones</span></TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="u in users.data" :key="u.id">
                        <TableCell>
                            <div class="flex items-center gap-3">
                                <Avatar class="size-9 rounded-lg">
                                    <AvatarFallback
                                        class="rounded-lg bg-primary/10 font-semibold text-primary"
                                        >{{
                                            getInitials(u.name)
                                        }}</AvatarFallback
                                    >
                                </Avatar>
                                <div>
                                    <p class="font-medium">
                                        {{ u.name }}
                                        <span
                                            v-if="u.id === currentUserId"
                                            class="ml-1 text-xs text-muted-foreground"
                                            >(tú)</span
                                        >
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ u.email }}
                                    </p>
                                </div>
                            </div>
                        </TableCell>
                        <TableCell>
                            <span
                                v-for="r in u.roles"
                                :key="r.id"
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset"
                                :class="
                                    roleTone[r.name] ??
                                    'bg-muted text-foreground ring-border'
                                "
                                >{{ r.name }}</span
                            >
                        </TableCell>
                        <TableCell class="max-w-xs whitespace-normal">
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="w in u.warehouses"
                                    :key="w.id"
                                    class="rounded-md border bg-background px-1.5 py-0.5 font-mono text-xs"
                                    :title="w.name"
                                    >{{ w.code }}</span
                                >
                                <span
                                    v-if="!u.warehouses.length"
                                    class="text-xs text-muted-foreground"
                                    >—</span
                                >
                            </div>
                        </TableCell>
                        <TableCell
                            ><StatusBadge :active="u.is_active"
                        /></TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="`Editar ${u.name}`"
                                    @click="openForm(u)"
                                >
                                    <Pencil />
                                </Button>
                                <Button
                                    v-if="u.id !== currentUserId"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    :aria-label="`Eliminar ${u.name}`"
                                    @click="deleting = u"
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
                :icon="Users"
                title="Sin usuarios"
                description="No hay usuarios con los filtros actuales."
            />
            <Pagination :paginator="users" />
        </DataCard>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Editar usuario' : 'Nuevo usuario'
                }}</DialogTitle>
                <DialogDescription
                    >Define el rol y las bodegas en las que puede
                    operar.</DialogDescription
                >
            </DialogHeader>
            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="Nombre completo"
                        for="name"
                        :error="form.errors.name"
                        required
                    >
                        <Input
                            id="name"
                            v-model="form.name"
                            autocomplete="off"
                        />
                    </FormField>
                    <FormField
                        label="Correo electrónico"
                        for="email"
                        :error="form.errors.email"
                        required
                    >
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="off"
                        />
                    </FormField>
                    <FormField
                        label="Contraseña"
                        for="password"
                        :error="form.errors.password"
                        :hint="
                            editing
                                ? 'Déjala vacía para conservar la actual.'
                                : undefined
                        "
                        :required="!editing"
                    >
                        <PasswordInput
                            id="password"
                            v-model="form.password"
                            autocomplete="new-password"
                        />
                    </FormField>
                    <FormField
                        label="Confirmar contraseña"
                        for="password_confirmation"
                        :required="!editing"
                    >
                        <PasswordInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            autocomplete="new-password"
                        />
                    </FormField>
                </div>
                <FormField
                    label="Rol"
                    for="role"
                    :error="form.errors.role"
                    required
                >
                    <NativeSelect id="role" v-model="form.role">
                        <option v-for="r in roles" :key="r" :value="r">
                            {{ r }}
                        </option>
                    </NativeSelect>
                </FormField>
                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">
                        Bodegas asignadas
                    </legend>
                    <p class="-mt-1 mb-1 text-xs text-muted-foreground">
                        Roles con “acceso a todas las bodegas” ignoran esta
                        lista.
                    </p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <Label
                            v-for="w in warehouses"
                            :key="w.id"
                            class="flex items-center gap-3 rounded-md border p-2.5 font-normal transition-colors hover:bg-muted/50 has-data-[state=checked]:border-primary/50 has-data-[state=checked]:bg-primary/5"
                        >
                            <Checkbox
                                :model-value="form.warehouses.includes(w.id)"
                                @update:model-value="
                                    (v) => toggleWarehouse(w.id, v)
                                "
                            />
                            <span class="truncate">{{ w.name }}</span>
                            <span
                                class="ml-auto font-mono text-xs text-muted-foreground"
                                >{{ w.code }}</span
                            >
                        </Label>
                    </div>
                    <InputError :message="form.errors.warehouses" />
                </fieldset>
                <Label class="flex items-center gap-3">
                    <Checkbox v-model="form.is_active" /> Usuario activo
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
        :title="`¿Eliminar a ${deleting?.name ?? ''}?`"
        description="Si el usuario tiene movimientos registrados se desactivará en lugar de eliminarse."
        @update:open="(v) => !v && (deleting = null)"
        @confirm="confirmDelete"
    />
</template>
