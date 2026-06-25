<script setup lang="ts">
import { TooltipPortal, TooltipContent } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  side?: 'top' | 'right' | 'bottom' | 'left'
  sideOffset?: number
  class?: string
}>()
</script>

<template>
  <TooltipPortal>
    <TooltipContent
      :side="props.side ?? 'top'"
      :side-offset="props.sideOffset ?? 6"
      :class="cn(
        'z-50 rounded-sm bg-text-primary px-2 py-1 text-xs font-medium text-white shadow-sm',
        'animate-in fade-in-0 zoom-in-95',
        props.class,
      )"
    >
      <slot />
    </TooltipContent>
  </TooltipPortal>
</template>
