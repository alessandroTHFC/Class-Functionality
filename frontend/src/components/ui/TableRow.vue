<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <tr :class="cn('border-b border-brand-border transition-colors hover:bg-app-bg', props.class)">
    <slot />
  </tr>
</template>
