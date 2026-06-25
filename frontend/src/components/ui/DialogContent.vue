<script setup lang="ts">
import { DialogPortal, DialogOverlay, DialogContent } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string }>()
</script>

<template>
  <DialogPortal>
    <DialogOverlay class="fixed inset-0 z-50 bg-black/50" />
    <DialogContent
      :class="cn(
        'fixed left-1/2 top-1/2 z-50 -translate-x-1/2 -translate-y-1/2',
        'w-full max-w-lg bg-card-bg rounded-sm border border-brand-border shadow-card',
        'focus:outline-none',
        props.class,
      )"
    >
      <slot />
    </DialogContent>
  </DialogPortal>
</template>
