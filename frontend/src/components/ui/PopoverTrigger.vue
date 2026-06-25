<script setup lang="ts">
import { PopoverTrigger } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <PopoverTrigger :class="cn('cursor-pointer outline-none', props.class)">
    <slot />
  </PopoverTrigger>
</template>
