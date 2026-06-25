<script setup lang="ts">
import { SelectValue as RadixSelectValue } from 'radix-vue'
import { inject } from 'vue'
import type { ComputedRef } from 'vue'

const props = defineProps<{ placeholder?: string }>()

const isMultiple = inject<boolean>('selectMultiple', false)
// In multiple mode the parent passes a computed display label via :placeholder.
const modelValue = inject<ComputedRef<string | string[] | undefined>>('selectModelValue')
</script>

<template>
  <!-- Single mode: Radix SelectValue auto-renders the selected option's text. -->
  <RadixSelectValue v-if="!isMultiple" :placeholder="placeholder" />

  <!-- Multiple mode: render the placeholder prop which the parent computes as the current label. -->
  <span
    v-else
    :class="Array.isArray(modelValue) && (modelValue as string[]).length > 0 ? 'text-text-primary' : 'text-text-secondary'"
  >
    {{ placeholder }}
  </span>
</template>
