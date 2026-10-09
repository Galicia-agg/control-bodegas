<template>
    <div class="overflow-hidden rounded-xl border bg-card shadow-xs">
        <div
            v-if="$slots.toolbar"
            class="flex flex-col gap-3 border-b p-4 sm:flex-row sm:flex-wrap sm:items-center"
        >
            <slot name="toolbar" />
        </div>
        <slot />
    </div>
</template>
