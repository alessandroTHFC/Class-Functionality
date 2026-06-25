<script setup lang="ts">
import {
  SelectItem as RadixSelectItem,
  SelectItemText,
  SelectItemIndicator,
  ListboxItem,
  ListboxItemIndicator,
} from 'radix-vue'
import { inject } from 'vue'
import { Check } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps<{
  value: string
  class?: string
  disabled?: boolean
}>()

const isMultiple = inject<boolean>('selectMultiple', false)
</script>

<template>
  <!-- Single mode: Radix SelectItem with automatic selected-state indicator. -->
  <RadixSelectItem
    v-if="!isMultiple"
    :value="value"
    :disabled="disabled"
    :class="cn(
      'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm text-text-primary outline-none',
      'focus:bg-app-bg',
      'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
      props.class,
    )"
  >
    <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <SelectItemIndicator>
        <Check class="h-3.5 w-3.5 text-teal" />
      </SelectItemIndicator>
    </span>
    <SelectItemText>
      <slot />
    </SelectItemText>
  </RadixSelectItem>

  <!-- Multiple mode: ListboxItem with a checkmark indicator for selected state. -->
  <ListboxItem
    v-else
    :value="value"
    :disabled="disabled"
    :class="cn(
      'relative flex w-full cursor-pointer select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm text-text-primary outline-none',
      'data-[highlighted]:bg-app-bg data-[state=checked]:bg-app-bg',
      'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
      props.class,
    )"
  >
    <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <ListboxItemIndicator>
        <Check class="h-3.5 w-3.5 text-teal" />
      </ListboxItemIndicator>
    </span>
    <slot />
  </ListboxItem>
</template>
