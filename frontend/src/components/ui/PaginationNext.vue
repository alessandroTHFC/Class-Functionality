<script setup lang="ts">
import { PaginationNext } from 'radix-vue'
import { ChevronRight } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <PaginationNext
    :class="cn(
      'p-1 rounded-sm text-text-secondary transition-colors',
      'hover:text-text-primary hover:bg-app-bg',
      'disabled:opacity-40 disabled:cursor-not-allowed',
      'data-[disabled]:opacity-40 data-[disabled]:cursor-not-allowed',
      props.class,
    )"
  >
    <ChevronRight class="w-4 h-4" />
  </PaginationNext>
</template>
