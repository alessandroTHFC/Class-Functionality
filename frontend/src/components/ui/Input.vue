<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{
  modelValue?: string
  type?: string
  placeholder?: string
  disabled?: boolean
  class?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <input
    :type="props.type ?? 'text'"
    :value="props.modelValue"
    :placeholder="props.placeholder"
    :disabled="props.disabled"
    :class="
      cn(
        'flex h-10 w-full rounded-sm border border-brand-border bg-card-bg px-3 py-2',
        'text-sm text-text-primary placeholder:text-text-secondary',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal focus-visible:ring-offset-1',
        'disabled:cursor-not-allowed disabled:opacity-50',
        props.class,
      )
    "
    @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
  />
</template>
