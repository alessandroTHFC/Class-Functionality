<script setup lang="ts">
import { TabsTrigger } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  value: string
  class?: string
}>()
</script>

<template>
  <TabsTrigger
    :value="props.value"
    :class="cn(
      'inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-text-secondary rounded-sm transition-colors',
      'data-[state=active]:bg-card-bg data-[state=active]:text-text-primary data-[state=active]:shadow-sm',
      'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal',
      props.class,
    )"
  >
    <slot />
  </TabsTrigger>
</template>
