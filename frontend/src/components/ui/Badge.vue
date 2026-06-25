<script setup lang="ts">
import { type VariantProps, cva } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const badgeVariants = cva(
  'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
  {
    variants: {
      variant: {
        default:   'bg-teal-light text-teal',
        secondary: 'bg-app-bg text-text-secondary border border-brand-border',
        outline:   'border border-brand-border text-text-primary',
        purple:    'bg-purple-bg text-purple-text',
        success:   'bg-success-bg text-success-text',
        warning:   'bg-warning-bg text-warning-text',
        danger:    'bg-danger-bg text-danger-text',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
)

type BadgeVariants = VariantProps<typeof badgeVariants>

const props = defineProps<{
  variant?: BadgeVariants['variant']
  class?: string
}>()
</script>

<template>
  <span :class="cn(badgeVariants({ variant }), props.class)">
    <slot />
  </span>
</template>
