<script setup lang="ts">
import { SelectRoot, PopoverRoot } from 'radix-vue'
import { provide, computed } from 'vue'

const props = defineProps<{
  modelValue?: string | string[]
  defaultValue?: string | string[]
  disabled?: boolean
  multiple?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | string[]]
}>()

// Propagate mode, current value, and update handler down to all Select child components.
provide('selectMultiple', props.multiple ?? false)
provide('selectModelValue', computed(() => props.modelValue))
provide('selectUpdateValue', (val: string | string[]) => emit('update:modelValue', val))
</script>

<template>
  <!-- Single mode: SelectRoot handles open/close and single value binding. -->
  <SelectRoot
    v-if="!multiple"
    :model-value="(modelValue as string | undefined)"
    :disabled="disabled"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <slot />
  </SelectRoot>

  <!-- Multiple mode: PopoverRoot handles open/close; ListboxRoot (inside SelectContent) manages multi-selection. -->
  <PopoverRoot v-else>
    <slot />
  </PopoverRoot>
</template>
