<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <div :class="cn('flex items-center justify-end gap-2 px-6 py-4 border-t border-brand-border', props.class)">
    <slot />
  </div>
</template>
