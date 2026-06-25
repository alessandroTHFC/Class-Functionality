<script setup lang="ts">
import Avatar from '@/components/ui/Avatar.vue'
import { getInitials } from '@/lib/utils'
import type { StudentDetail } from '@/types'

const props = defineProps<{
  student: StudentDetail
  selected: boolean
}>()

const emit = defineEmits<{
  select: [id: number]
}>()

// NCCD level text colour — matches the colour spec in frontend-design.md.
const nccdColour: Record<string, string> = {
  QDTP:          'text-text-secondary',
  Supplementary: 'text-teal',
  Substantial:   'text-warning-text',
  Extensive:     'text-danger-text',
}

function nccdClass(level: string | null): string {
  return level ? (nccdColour[level] ?? 'text-text-secondary') : 'text-text-secondary'
}
</script>

<template>
  <button
    type="button"
    class="w-full text-left flex items-start gap-3 px-4 py-3 transition-colors hover:bg-app-bg focus-visible:outline-none border-l-[3px]"
    :class="selected
      ? 'bg-[#F5FBFB] border-l-teal'
      : 'border-l-transparent'"
    @click="emit('select', student.id)"
  >
    <!-- Purple student avatar -->
    <Avatar :initials="getInitials(student.full_name)" variant="purple" size="sm" class="shrink-0 mt-0.5" />

    <div class="flex-1 min-w-0">
      <p class="text-sm font-semibold text-text-primary truncate">{{ student.full_name }}</p>

      <!-- NCCD level (colour-coded per design spec) -->
      <p class="text-xs mt-0.5" :class="nccdClass(student.nccd_level)">
        {{ student.nccd_level ?? 'Not Recorded' }}
      </p>

      <!-- Primary disability label (secondary text, only when present) -->
      <p v-if="student.primary_disability" class="text-xs text-text-secondary mt-0.5 truncate">
        {{ student.primary_disability }}
      </p>
    </div>
  </button>
</template>
