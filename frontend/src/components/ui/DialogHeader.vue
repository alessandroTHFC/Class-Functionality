<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <div :class="cn('flex items-center justify-between px-6 py-4 bg-teal rounded-t-sm flex-shrink-0', props.class)">
    <slot />
  </div>
</template>
