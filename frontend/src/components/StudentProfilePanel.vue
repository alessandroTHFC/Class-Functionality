<script setup lang="ts">
import Avatar from '@/components/ui/Avatar.vue'
import Badge from '@/components/ui/Badge.vue'
import Card from '@/components/ui/Card.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import Tabs from '@/components/ui/Tabs.vue'
import TabsList from '@/components/ui/TabsList.vue'
import TabsTrigger from '@/components/ui/TabsTrigger.vue'
import TabsContent from '@/components/ui/TabsContent.vue'
import NotesList from '@/components/NotesList.vue'
import NoteComposer from '@/components/NoteComposer.vue'
import StrategiesView from '@/components/StrategiesView.vue'
import { getInitials } from '@/lib/utils'
import type { StudentDetail, StudentNote } from '@/types'

const props = defineProps<{
  student: StudentDetail | null
  classId: number
  className: string
  notes: StudentNote[]
  loadingNotes: boolean
  canAddNotes: boolean
}>()

const emit = defineEmits<{
  noteAdded: []
}>()

// NCCD badge variant — maps level to the closest colour token available.
const nccdBadgeVariant: Record<string, 'default' | 'warning' | 'danger' | 'secondary'> = {
  QDTP:          'secondary',
  Supplementary: 'default',
  Substantial:   'warning',
  Extensive:     'danger',
}

function nccdVariant(level: string | null): 'default' | 'warning' | 'danger' | 'secondary' {
  return level ? (nccdBadgeVariant[level] ?? 'secondary') : 'secondary'
}

// Format an ISO date string as "D Mon YYYY" (e.g. "2 Jun 2025").
function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-AU', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}
</script>

<template>
  <Card class="flex flex-col h-full overflow-hidden">
    <!-- Empty state — no student selected -->
    <div v-if="!student" class="flex-1 flex items-center justify-center text-sm text-text-secondary">
      Select a student to view their profile.
    </div>

    <template v-else>
      <!-- Student header -->
      <div class="flex items-start gap-4 p-6 border-b border-brand-border shrink-0">
        <!-- Large purple avatar -->
        <Avatar :initials="getInitials(student.full_name)" variant="purple" size="lg" class="shrink-0" />

        <div class="flex-1 min-w-0">
          <!-- Name -->
          <h2 class="text-lg font-semibold text-text-primary">{{ student.full_name }}</h2>

          <!-- NCCD badges -->
          <div class="flex flex-wrap gap-1.5 mt-1.5">
            <Badge v-if="student.nccd_level" :variant="nccdVariant(student.nccd_level)">
              {{ student.nccd_level }}
            </Badge>
            <Badge v-if="student.nccd_category" variant="purple">
              {{ student.nccd_category }}
            </Badge>
            <Badge v-if="!student.nccd_level" variant="secondary">Not Recorded</Badge>
          </div>

          <!-- Metadata row: DOB, Year Level, Diagnosis -->
          <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-2 text-xs text-text-secondary">
            <span><span class="font-medium text-text-primary">DOB:</span> {{ formatDate(student.date_of_birth) }}</span>
            <span><span class="font-medium text-text-primary">Year Level:</span> {{ student.year_level?.description ?? '—' }}</span>
            <span v-if="student.primary_disability">
              <span class="font-medium text-text-primary">Disability:</span> {{ student.primary_disability }}
            </span>
          </div>
        </div>
      </div>

      <!-- Tabs: Notes / Strategies -->
      <Tabs default-value="notes" class="flex flex-col flex-1 overflow-hidden p-4">
        <TabsList class="shrink-0">
          <TabsTrigger value="notes">Notes</TabsTrigger>
          <TabsTrigger value="strategies">Strategies</TabsTrigger>
        </TabsList>

        <!-- Notes tab — chat window layout -->
        <TabsContent value="notes" class="flex flex-col flex-1 overflow-hidden mt-3 min-h-0">
          <NotesList :notes="notes" :loading="loadingNotes" />
          <NoteComposer
            v-if="canAddNotes"
            :student-id="student.id"
            :class-id="classId"
            @saved="emit('noteAdded')"
          />
        </TabsContent>

        <!-- Strategies tab — placeholder -->
        <TabsContent value="strategies" class="flex-1 overflow-y-auto">
          <StrategiesView />
        </TabsContent>
      </Tabs>
    </template>
  </Card>
</template>
