<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        confirmLabel?: string;
        processing?: boolean;
    }>(),
    {
        title: '¿Confirmar eliminación?',
        description: 'Esta acción no se puede deshacer.',
        confirmLabel: 'Eliminar',
        processing: false,
    },
);

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ confirm: [] }>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="flex-row items-start gap-4 space-y-0">
                <div
                    class="grid size-10 shrink-0 place-items-center rounded-full bg-destructive/10 text-destructive"
                >
                    <TriangleAlert class="size-5" aria-hidden="true" />
                </div>
                <div class="space-y-1.5 text-left">
                    <DialogTitle>{{ title }}</DialogTitle>
                    <DialogDescription>{{ description }}</DialogDescription>
                </div>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="open = false"
                    >Cancelar</Button
                >
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    <Spinner v-if="processing" />
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
