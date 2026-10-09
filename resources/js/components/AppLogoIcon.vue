<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        fill="none"
        :class="className"
        v-bind="$attrs"
        aria-hidden="true"
    >
        <path
            d="M3 13.2 16 5l13 8.2V27a1 1 0 0 1-1 1h-2.5V15.5h-19V28H4a1 1 0 0 1-1-1V13.2Z"
            fill="currentColor"
        />
        <rect x="9" y="22" width="6" height="6" rx="0.75" fill="currentColor" />
        <rect
            x="17"
            y="22"
            width="6"
            height="6"
            rx="0.75"
            fill="currentColor"
        />
        <rect
            x="13"
            y="17"
            width="6"
            height="4.2"
            rx="0.75"
            fill="currentColor"
            opacity="0.7"
        />
    </svg>
</template>
