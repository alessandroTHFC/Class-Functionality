<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    variant?: 'default' | 'destructive' | 'outline' | 'ghost'
    size?: 'default' | 'sm' | 'lg'
    class?: string
    disabled?: boolean
    type?: 'button' | 'submit' | 'reset'
  }>(),
  {
    variant: 'default',
    size: 'default',
    type: 'button',
  },
)

const variants: Record<string, string> = {
  default:     'bg-teal text-white hover:bg-teal-hover',
  destructive: 'bg-danger-text text-white hover:opacity-90',
  outline:     'border border-brand-border bg-card-bg text-text-primary hover:bg-app-bg',
  ghost:       'text-text-primary hover:bg-app-bg',
}

const sizes: Record<string, string> = {
  default: 'h-10 px-4 py-2 text-sm',
  sm:      'h-8 px-3 text-xs',
  lg:      'h-11 px-8 text-sm',
}
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    :class="
      cn(
        'inline-flex items-center justify-center rounded-sm font-medium transition-colors',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal focus-visible:ring-offset-2',
        'disabled:pointer-events-none disabled:opacity-50',
        variants[variant ?? 'default'],
        sizes[size ?? 'default'],
        props.class,
      )
    "
  >
    <slot />
  </button>
</template>
