<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <div :class="cn('animate-pulse rounded-sm bg-gray-200', props.class)" />
</template>
