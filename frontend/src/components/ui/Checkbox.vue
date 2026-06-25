<script setup lang="ts">
import { CheckboxRoot, CheckboxIndicator } from 'radix-vue'
import { Check } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps<{
  checked?: boolean | 'indeterminate'
  disabled?: boolean
  class?: string
}>()

const emit = defineEmits<{
  'update:checked': [value: boolean]
}>()
</script>

<template>
  <CheckboxRoot
    :checked="props.checked"
    :disabled="props.disabled"
    :class="cn(
      'h-4 w-4 shrink-0 rounded-sm border border-brand-border bg-card-bg transition-colors',
      'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal focus-visible:ring-offset-1',
      'data-[state=checked]:bg-teal data-[state=checked]:border-teal data-[state=checked]:text-white',
      'disabled:cursor-not-allowed disabled:opacity-50',
      props.class,
    )"
    @update:checked="emit('update:checked', $event)"
  >
    <CheckboxIndicator class="flex items-center justify-center text-current">
      <Check class="w-3 h-3" />
    </CheckboxIndicator>
  </CheckboxRoot>
</template>
