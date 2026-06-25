<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import NoteCard from '@/components/NoteCard.vue'
import type { StudentNote } from '@/types'

const props = defineProps<{
  notes: StudentNote[]
  loading: boolean
}>()

const scrollEl = ref<HTMLElement | null>(null)

// Scroll to the bottom whenever notes update so the newest note is visible.
watch(
  () => props.notes,
  async () => {
    await nextTick()
    if (scrollEl.value) {
      scrollEl.value.scrollTop = scrollEl.value.scrollHeight
    }
  },
  { immediate: true },
)
</script>

<template>
  <!-- Scrollable chat window — flex-1 fills remaining height in the tab panel -->
  <div ref="scrollEl" class="flex-1 overflow-y-auto min-h-0 px-1">
    <!-- Skeleton while notes are loading -->
    <template v-if="loading">
      <div v-for="i in 3" :key="i" class="flex gap-3 py-3">
        <Skeleton class="w-8 h-8 rounded-full shrink-0" />
        <div class="flex-1 space-y-2">
          <Skeleton class="h-3 w-1/3" />
          <Skeleton class="h-4 w-full" />
          <Skeleton class="h-4 w-4/5" />
        </div>
      </div>
    </template>

    <!-- Empty state -->
    <div v-else-if="notes.length === 0" class="py-12 text-center text-sm text-text-secondary">
      No notes yet. Add the first note below.
    </div>

    <!-- Note cards in chronological order (oldest first, newest at bottom) -->
    <div v-else class="flex flex-col gap-2">
      <NoteCard v-for="note in notes" :key="note.id" :note="note" />
    </div>
  </div>
</template>
