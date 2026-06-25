<script setup lang="ts">
import {
  SelectContent as RadixSelectContent,
  SelectViewport,
  SelectScrollUpButton,
  SelectScrollDownButton,
  PopoverPortal,
  PopoverContent as RadixPopoverContent,
  ListboxRoot,
  ListboxContent,
} from 'radix-vue'
import { inject, computed } from 'vue'
import type { ComputedRef } from 'vue'
import { ChevronUp, ChevronDown } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: string
  position?: 'item-aligned' | 'popper'
  sideOffset?: number
}>()

const isMultiple = inject<boolean>('selectMultiple', false)
const modelValue = inject<ComputedRef<string | string[] | undefined>>('selectModelValue')
const updateValue = inject<(val: string | string[]) => void>('selectUpdateValue', () => {})

// Ensure ListboxRoot always receives a string array in multiple mode.
const arrayValue = computed<string[]>(() => {
  const v = modelValue?.value
  return Array.isArray(v) ? v : []
})
</script>

<template>
  <!-- Single mode: Radix SelectContent with scroll buttons. -->
  <RadixSelectContent
    v-if="!isMultiple"
    :position="props.position ?? 'popper'"
    :side-offset="props.sideOffset ?? 4"
    :class="cn(
      'relative z-50 min-w-[var(--radix-select-trigger-width)] overflow-hidden rounded-sm border border-brand-border bg-card-bg shadow-card',
      'flex flex-col max-h-[var(--radix-select-content-available-height)]',
      props.class,
    )"
  >
    <SelectScrollUpButton class="flex cursor-default items-center justify-center py-1 text-text-secondary shrink-0">
      <ChevronUp class="h-4 w-4" />
    </SelectScrollUpButton>
    <SelectViewport class="p-1 flex-1 overflow-y-auto">
      <slot />
    </SelectViewport>
    <SelectScrollDownButton class="flex cursor-default items-center justify-center py-1 text-text-secondary shrink-0">
      <ChevronDown class="h-4 w-4" />
    </SelectScrollDownButton>
  </RadixSelectContent>

  <!-- Multiple mode: Popover dropdown wrapping a ListboxRoot for multi-selection. -->
  <PopoverPortal v-else>
    <RadixPopoverContent
      :side-offset="props.sideOffset ?? 4"
      class="z-50 w-[var(--radix-popover-trigger-width)] overflow-hidden rounded-sm border border-brand-border bg-card-bg shadow-card p-0 outline-none"
    >
      <ListboxRoot
        :model-value="arrayValue"
        multiple
        @update:model-value="(val) => updateValue(val as string[])"
      >
        <ListboxContent class="max-h-48 overflow-y-auto py-1">
          <slot />
        </ListboxContent>
      </ListboxRoot>
    </RadixPopoverContent>
  </PopoverPortal>
</template>
