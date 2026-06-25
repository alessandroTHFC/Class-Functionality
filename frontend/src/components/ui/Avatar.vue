<script setup lang="ts">
import { AvatarRoot, AvatarFallback } from 'radix-vue'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const rootVariants = cva('relative inline-flex shrink-0 rounded-full overflow-hidden', {
  variants: {
    size: {
      sm: 'w-8 h-8',
      md: 'w-10 h-10',
      lg: 'w-12 h-12',
    },
  },
  defaultVariants: { size: 'md' },
})

const fallbackVariants = cva('flex h-full w-full items-center justify-center rounded-full font-semibold', {
  variants: {
    variant: {
      teal:   'bg-teal text-white',
      purple: 'bg-purple-bg text-purple-text',
    },
    size: {
      sm: 'text-xs',
      md: 'text-sm',
      lg: 'text-base',
    },
  },
  defaultVariants: { variant: 'teal', size: 'md' },
})

type RootVariants = VariantProps<typeof rootVariants>
type FallbackVariants = VariantProps<typeof fallbackVariants>

const props = defineProps<{
  initials: string
  variant?: FallbackVariants['variant']
  size?: RootVariants['size']
  class?: string
}>()
</script>

<template>
  <AvatarRoot :class="cn(rootVariants({ size }), props.class)">
    <AvatarFallback :delay-ms="0" :class="fallbackVariants({ variant, size })">
      {{ initials }}
    </AvatarFallback>
  </AvatarRoot>
</template>
