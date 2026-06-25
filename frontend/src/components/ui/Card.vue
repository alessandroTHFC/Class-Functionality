<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <div :class="cn('rounded-sm border border-brand-border bg-card-bg shadow-card', props.class)">
    <slot />
  </div>
</template>
