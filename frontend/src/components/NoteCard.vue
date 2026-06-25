<script setup lang="ts">
import Avatar from '@/components/ui/Avatar.vue'
import { getInitials } from '@/lib/utils'
import type { StudentNote } from '@/types'

const props = defineProps<{ note: StudentNote }>()

// Format an ISO date string as "D Mon YYYY" (e.g. "2 Jun 2025").
function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-AU', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}
</script>

<template>
  <div class="flex gap-3 py-3">
    <!-- Staff teal avatar -->
    <Avatar
      :initials="getInitials(note.author.name)"
      variant="teal"
      size="sm"
      class="shrink-0 mt-0.5"
    />

    <div class="flex-1 min-w-0">
      <!-- Author name + date on the same line -->
      <div class="flex items-baseline gap-2">
        <span class="text-sm font-semibold text-text-primary">{{ note.author.name }}</span>
        <span class="text-xs text-text-secondary">{{ formatDate(note.note_date) }}</span>
      </div>

      <!-- Note body -->
      <p class="text-sm text-text-primary mt-1 whitespace-pre-wrap">{{ note.note_text }}</p>
    </div>
  </div>
</template>
