<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <thead :class="cn('bg-app-bg border-b border-brand-border', props.class)">
    <slot />
  </thead>
</template>
