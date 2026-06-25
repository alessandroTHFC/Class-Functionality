<script setup lang="ts">
import { PopoverContent, PopoverPortal } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    class?: string
    side?: 'top' | 'right' | 'bottom' | 'left'
    sideOffset?: number
    align?: 'start' | 'center' | 'end'
  }>(),
  {
    side: 'bottom',
    sideOffset: 8,
    align: 'center',
  },
)
</script>

<template>
  <PopoverPortal>
    <PopoverContent
      :side="props.side"
      :side-offset="props.sideOffset"
      :align="props.align"
      :class="cn(
        'z-50 rounded-sm border border-brand-border bg-card-bg shadow-card-hover p-4 outline-none',
        props.class,
      )"
    >
      <slot />
    </PopoverContent>
  </PopoverPortal>
</template>
