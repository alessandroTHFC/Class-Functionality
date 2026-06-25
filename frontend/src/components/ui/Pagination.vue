<script setup lang="ts">
import { PaginationRoot } from 'radix-vue'

const props = defineProps<{
  page?: number
  total?: number
  itemsPerPage?: number
}>()

const emit = defineEmits<{
  'update:page': [value: number]
}>()
</script>

<template>
  <PaginationRoot
    :page="props.page"
    :total="props.total ?? 0"
    :items-per-page="props.itemsPerPage ?? 10"
    @update:page="emit('update:page', $event)"
  >
    <slot />
  </PaginationRoot>
</template>
