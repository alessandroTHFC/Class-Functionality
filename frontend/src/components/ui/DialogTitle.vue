<script setup lang="ts">
import { DialogTitle } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <DialogTitle :class="cn('text-base font-semibold text-white', props.class)">
    <slot />
  </DialogTitle>
</template>
