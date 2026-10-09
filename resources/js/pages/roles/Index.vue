<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    Lock,
    Pencil,
    Plus,
    ShieldCheck,
    Trash2,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import FormField from '@/components/FormField.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
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
import { destroy, index, store, update } from '@/routes/roles';
import type { PermissionGroups, Role } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Roles y permisos', href: index() }] },
});

const props = defineProps<{
    roles: Role[];
    groups: PermissionGroups;
}>();

const allPermissions = computed(() =>
    Object.values(props.groups).flatMap((g) => Object.keys(g.permissions)),
);

const dialogOpen = ref(false);
const editing = ref<Role | null>(null);
const form = useForm({ name: '', permissions: [] as string[] });

const openForm = (role?: Role) => {
    editing.value = role ?? null;
    form.clearErrors();
    form.defaults({
        name: role?.name ?? '',
        permissions: [...(role?.permissions ?? [])],
    });
    form.reset();
    dialogOpen.value = true;
};

const toggle = (permission: string, checked: boolean | 'indeterminate') => {
    form.permissions =
        checked === true
            ? [...form.permissions, permission]
            : form.permissions.filter((p) => p !== permission);
};

const groupState = (perms: string[]): boolean | 'indeterminate' => {
    const selected = perms.filter((p) => form.permissions.includes(p)).length;

    return selected === 0
        ? false
        : selected === perms.length
          ? true
          : 'indeterminate';
};

const toggleGroup = (perms: string[], checked: boolean | 'indeterminate') => {
    const rest = form.permissions.filter((p) => !perms.includes(p));
    form.permissions = checked === true ? [...rest, ...perms] : rest;
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

const deleting = ref<Role | null>(null);
const confirmDelete = () => {
    if (!deleting.value) return;
    router.delete(destroy.url(deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
};

const has = (role: Role, permission: string) =>
    role.locked || role.permissions.includes(permission);
</script>

<template>
    <Head title="Roles y permisos" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Roles y permisos"
            description="Controla qué puede ver y hacer cada perfil de usuario."
        >
            <template #actions>
                <Button @click="openForm()"><Plus /> Nuevo rol</Button>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="role in roles"
                :key="role.id"
                class="flex flex-col rounded-xl border bg-card p-5 shadow-xs"
            >
                <div class="flex items-start justify-between gap-2">
                    <div
                        class="grid size-10 place-items-center rounded-lg bg-primary/10 text-primary"
                    >
                        <ShieldCheck class="size-5" />
                    </div>
                    <span
                        v-if="role.locked"
                        class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                        ><Lock class="size-3" /> Protegido</span
                    >
                </div>
                <h3 class="mt-3 font-semibold">{{ role.name }}</h3>
                <p
                    class="mt-1 flex items-center gap-3 text-sm text-muted-foreground"
                >
                    <span class="inline-flex items-center gap-1"
                        ><Users class="size-3.5" /> {{ role.users_count }}</span
                    >
                    <span>{{
                        role.locked
                            ? 'Todos los permisos'
                            : `${role.permissions.length} de ${allPermissions.length} permisos`
                    }}</span>
                </p>
                <div v-if="!role.locked" class="mt-4 flex gap-1 border-t pt-3">
                    <Button variant="ghost" size="sm" @click="openForm(role)"
                        ><Pencil /> Editar</Button
                    >
                    <Button
                        v-if="role.users_count === 0"
                        variant="ghost"
                        size="sm"
                        class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                        @click="deleting = role"
                        ><Trash2 /> Eliminar</Button
                    >
                </div>
            </article>
        </section>

        <section
            class="overflow-hidden rounded-xl border bg-card shadow-xs"
            aria-labelledby="matrix-title"
        >
            <div class="border-b p-4">
                <h2 id="matrix-title" class="font-semibold">
                    Matriz de permisos
                </h2>
                <p class="text-sm text-muted-foreground">
                    Vista general de lo que cada rol puede hacer.
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th
                                scope="col"
                                class="px-4 py-2.5 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Permiso
                            </th>
                            <th
                                v-for="role in roles"
                                :key="role.id"
                                scope="col"
                                class="px-3 py-2.5 text-center text-xs font-semibold whitespace-nowrap"
                            >
                                {{ role.name }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(group, key) in groups" :key="key">
                            <tr class="border-t bg-muted/20">
                                <th
                                    scope="rowgroup"
                                    :colspan="roles.length + 1"
                                    class="px-4 py-2 text-left text-xs font-semibold tracking-wide text-primary uppercase"
                                >
                                    {{ group.label }}
                                </th>
                            </tr>
                            <tr
                                v-for="(label, permission) in group.permissions"
                                :key="permission"
                                class="border-t transition-colors hover:bg-muted/30"
                            >
                                <th
                                    scope="row"
                                    class="px-4 py-2.5 text-left font-normal"
                                >
                                    {{ label }}
                                </th>
                                <td
                                    v-for="role in roles"
                                    :key="role.id"
                                    class="px-3 py-2.5 text-center"
                                >
                                    <Check
                                        v-if="has(role, permission)"
                                        class="mx-auto size-4 text-success"
                                        aria-label="Sí"
                                    />
                                    <span
                                        v-else
                                        class="text-muted-foreground/50"
                                        aria-label="No"
                                        >—</span
                                    >
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? `Editar rol: ${editing.name}` : 'Nuevo rol'
                }}</DialogTitle>
                <DialogDescription
                    >Selecciona los permisos que tendrá este
                    rol.</DialogDescription
                >
            </DialogHeader>
            <form class="grid gap-5" @submit.prevent="submit">
                <FormField
                    label="Nombre del rol"
                    for="name"
                    :error="form.errors.name"
                    required
                >
                    <Input id="name" v-model="form.name" />
                </FormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <fieldset
                        v-for="(group, key) in groups"
                        :key="key"
                        class="rounded-lg border p-4"
                    >
                        <legend class="px-1">
                            <Label
                                class="flex items-center gap-2 text-sm font-semibold"
                            >
                                <Checkbox
                                    :model-value="
                                        groupState(
                                            Object.keys(group.permissions),
                                        )
                                    "
                                    @update:model-value="
                                        (v) =>
                                            toggleGroup(
                                                Object.keys(group.permissions),
                                                v,
                                            )
                                    "
                                />
                                {{ group.label }}
                            </Label>
                        </legend>
                        <div class="mt-2 grid gap-2.5">
                            <Label
                                v-for="(label, permission) in group.permissions"
                                :key="permission"
                                class="flex items-start gap-2.5 font-normal"
                            >
                                <Checkbox
                                    class="mt-0.5"
                                    :model-value="
                                        form.permissions.includes(permission)
                                    "
                                    @update:model-value="
                                        (v) => toggle(permission, v)
                                    "
                                />
                                <span class="leading-snug">{{ label }}</span>
                            </Label>
                        </div>
                    </fieldset>
                </div>
                <InputError :message="form.errors.permissions" />
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
        :title="`¿Eliminar el rol ${deleting?.name ?? ''}?`"
        @update:open="(v) => !v && (deleting = null)"
        @confirm="confirmDelete"
    />
</template>
