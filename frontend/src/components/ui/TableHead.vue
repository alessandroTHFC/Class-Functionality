<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <th :class="cn('px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text-secondary', props.class)">
    <slot />
  </th>
</template>
