<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { ChevronDown } from "@lucide/vue"
import { cn } from "@/lib/utils"

defineOptions({ inheritAttrs: false })

const props = defineProps<{ class?: HTMLAttributes["class"] }>()
const model = defineModel<string | number | null | undefined>()
</script>

<template>
  <div :class="cn('relative w-full', props.class)">
    <select
      v-model="model"
      v-bind="$attrs"
      data-slot="native-select"
      class="border-input dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 aria-invalid:border-destructive h-9 w-full min-w-0 appearance-none rounded-md border bg-transparent py-1 pr-9 pl-3 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm [&>option]:bg-popover [&>option]:text-popover-foreground"
    >
      <slot />
    </select>
    <ChevronDown class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
  </div>
</template>
