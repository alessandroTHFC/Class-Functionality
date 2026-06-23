<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{
  for?: string
  class?: string
}>()
</script>

<template>
  <label
    :for="props.for"
    :class="cn('block text-sm font-medium text-text-primary', props.class)"
  >
    <slot />
  </label>
</template>
