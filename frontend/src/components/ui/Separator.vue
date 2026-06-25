<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{
  orientation?: 'horizontal' | 'vertical'
  class?: string
}>()
</script>

<template>
  <div
    :class="cn(
      'shrink-0 bg-brand-border',
      props.orientation === 'vertical' ? 'w-px h-full' : 'h-px w-full',
      props.class,
    )"
  />
</template>
