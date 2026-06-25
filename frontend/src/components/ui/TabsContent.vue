<script setup lang="ts">
import { TabsContent } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  value: string
  class?: string
}>()
</script>

<template>
  <TabsContent
    :value="props.value"
    :class="cn('focus-visible:outline-none', props.class)"
  >
    <slot />
  </TabsContent>
</template>
