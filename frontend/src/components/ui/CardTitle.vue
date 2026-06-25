<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <p :class="cn('text-sm font-medium text-text-secondary', props.class)">
    <slot />
  </p>
</template>
