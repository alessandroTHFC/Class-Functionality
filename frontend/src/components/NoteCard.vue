<script setup lang="ts">
import { Pencil } from 'lucide-vue-next'
import Avatar from '@/components/ui/Avatar.vue'
import TooltipProvider from '@/components/ui/TooltipProvider.vue'
import Tooltip from '@/components/ui/Tooltip.vue'
import TooltipTrigger from '@/components/ui/TooltipTrigger.vue'
import TooltipContent from '@/components/ui/TooltipContent.vue'
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
  <div class="flex items-center gap-3">
    <!-- Staff teal avatar — sits outside the bordered card -->
    <Avatar
      :initials="getInitials(note.author.name)"
      variant="teal"
      size="sm"
      class="shrink-0"
    />

    <!-- Bordered message bubble -->
    <div class="flex-1 min-w-0 border border-brand-border rounded-sm bg-app-bg p-3">
      <!-- Header row: author + date on the left, edit icon on the right -->
      <div class="flex items-baseline justify-between gap-2">
        <div class="flex items-baseline gap-2">
          <span class="text-sm font-semibold text-text-primary">{{ note.author.name }}</span>
          <span class="text-xs text-text-secondary">{{ formatDate(note.note_date) }}</span>
        </div>

        <!-- Edit placeholder — no functionality yet -->
        <TooltipProvider :delay-duration="200">
          <Tooltip>
            <TooltipTrigger>
              <button type="button" class="text-text-secondary opacity-40 cursor-default">
                <Pencil class="w-3.5 h-3.5" />
              </button>
            </TooltipTrigger>
            <TooltipContent side="top">Edit note</TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </div>

      <!-- Note body -->
      <p class="text-sm text-text-primary mt-1 whitespace-pre-wrap">{{ note.note_text }}</p>
    </div>
  </div>
</template>
