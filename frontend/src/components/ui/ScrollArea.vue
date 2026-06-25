<script setup lang="ts">
import {
  ScrollAreaRoot,
  ScrollAreaViewport,
  ScrollAreaScrollbar,
  ScrollAreaThumb,
  ScrollAreaCorner,
} from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  orientation?: 'vertical' | 'horizontal'
  class?: string
}>()
</script>

<template>
  <ScrollAreaRoot :class="cn('relative overflow-hidden', props.class)">
    <ScrollAreaViewport class="w-full h-full rounded-inherit">
      <slot />
    </ScrollAreaViewport>
    <ScrollAreaScrollbar
      :orientation="props.orientation ?? 'vertical'"
      class="flex select-none touch-none p-0.5 bg-transparent transition-colors w-2.5"
    >
      <ScrollAreaThumb class="flex-1 bg-brand-border rounded-full relative" />
    </ScrollAreaScrollbar>
    <ScrollAreaCorner />
  </ScrollAreaRoot>
</template>
