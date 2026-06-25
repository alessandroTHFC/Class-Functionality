<script setup lang="ts">
import { PaginationPrev } from 'radix-vue'
import { ChevronLeft } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <PaginationPrev
    :class="cn(
      'p-1 rounded-sm text-text-secondary transition-colors',
      'hover:text-text-primary hover:bg-app-bg',
      'disabled:opacity-40 disabled:cursor-not-allowed',
      'data-[disabled]:opacity-40 data-[disabled]:cursor-not-allowed',
      props.class,
    )"
  >
    <ChevronLeft class="w-4 h-4" />
  </PaginationPrev>
</template>
