<script setup lang="ts">
import { SelectTrigger as RadixSelectTrigger, PopoverTrigger } from 'radix-vue'
import { inject, computed } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: string; disabled?: boolean; error?: boolean }>()

const isMultiple = inject<boolean>('selectMultiple', false)

const baseClass =
  'flex h-10 w-full items-center justify-between rounded-sm border bg-card-bg pl-3 pr-3 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50'

const borderClass = computed(() =>
  props.error ? 'border-danger-text focus:ring-danger-text' : 'border-brand-border focus:ring-teal',
)
</script>

<template>
  <!-- Single mode -->
  <RadixSelectTrigger
    v-if="!isMultiple"
    :disabled="disabled"
    :class="cn(baseClass, borderClass, props.class)"
  >
    <slot />
    <ChevronDown class="h-4 w-4 text-text-secondary shrink-0 ml-2" />
  </RadixSelectTrigger>

  <!-- Multiple mode: PopoverTrigger acts as the dropdown toggle. -->
  <PopoverTrigger
    v-else
    :class="cn(baseClass, borderClass, 'cursor-pointer', props.class)"
  >
    <slot />
    <ChevronDown class="h-4 w-4 text-text-secondary shrink-0 ml-2" />
  </PopoverTrigger>
</template>
