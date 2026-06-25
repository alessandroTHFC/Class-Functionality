<script setup lang="ts">
import { TooltipProvider } from 'radix-vue'

const props = defineProps<{
  delayDuration?: number
}>()
</script>

<template>
  <TooltipProvider :delay-duration="props.delayDuration ?? 300">
    <slot />
  </TooltipProvider>
</template>
