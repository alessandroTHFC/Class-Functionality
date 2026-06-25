<script setup lang="ts">
import { TabsList } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <TabsList
    :class="cn(
      'inline-flex items-center rounded-sm bg-app-bg border border-brand-border p-0.5',
      props.class,
    )"
  >
    <slot />
  </TabsList>
</template>
