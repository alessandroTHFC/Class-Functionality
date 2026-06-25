<script setup lang="ts">
import Avatar from "@/components/ui/Avatar.vue";
import Badge from "@/components/ui/Badge.vue";
import Card from "@/components/ui/Card.vue";
import Tabs from "@/components/ui/Tabs.vue";
import TabsList from "@/components/ui/TabsList.vue";
import TabsTrigger from "@/components/ui/TabsTrigger.vue";
import TabsContent from "@/components/ui/TabsContent.vue";
import NotesList from "@/components/NotesList.vue";
import NoteComposer from "@/components/NoteComposer.vue";
import StrategiesView from "@/components/StrategiesView.vue";
import { getInitials } from "@/lib/utils";
import type { StudentDetail, StudentNote } from "@/types";

const props = defineProps<{
  student: StudentDetail | null;
  classId: number;
  className: string;
  notes: StudentNote[];
  loadingNotes: boolean;
  canAddNotes: boolean;
}>();

const emit = defineEmits<{
  noteAdded: [];
}>();

// NCCD badge variant — maps level to the closest colour token available.
const nccdBadgeVariant: Record<
  string,
  "default" | "warning" | "danger" | "secondary"
> = {
  QDTP: "secondary",
  Supplementary: "default",
  Substantial: "warning",
  Extensive: "danger",
};

function nccdVariant(
  level: string | null,
): "default" | "warning" | "danger" | "secondary" {
  return level ? (nccdBadgeVariant[level] ?? "secondary") : "secondary";
}

// Format an ISO date string as "D Mon YYYY" (e.g. "2 Jun 2025").
function formatDate(iso: string | null): string {
  if (!iso) return "—";
  return new Date(iso).toLocaleDateString("en-AU", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}
</script>

<template>
  <Card class="flex flex-col h-full overflow-hidden">
    <!-- Empty state — no student selected -->
    <div
      v-if="!student"
      class="flex-1 flex items-center justify-center text-sm text-text-secondary"
    >
      Select a student to view their profile.
    </div>

    <template v-else>
      <!-- Student header -->
      <div
        class="flex items-center gap-4 p-6 border-b border-brand-border shrink-0 bg-app-bg rounded-t-sm"
      >
        <!-- Large purple avatar — custom size overrides the lg preset -->
        <Avatar
          :initials="getInitials(student.full_name)"
          variant="purple"
          size="xl"
          class="shrink-0"
        />

        <!-- Left: name + NCCD badges -->
        <div class="flex-1 min-w-0">
          <h2 class="text-lg font-semibold text-text-primary">
            {{ student.full_name }}
          </h2>

          <div class="flex flex-wrap gap-1.5 mt-1.5">
            <Badge
              v-if="student.nccd_level"
              :variant="nccdVariant(student.nccd_level)"
            >
              {{ student.nccd_level }}
            </Badge>
            <Badge v-if="student.nccd_category" variant="purple">
              {{ student.nccd_category }}
            </Badge>
            <Badge v-if="!student.nccd_level" variant="secondary">Not Recorded</Badge>
          </div>
        </div>

        <!-- Right: metadata column -->
        <div class="shrink-0 flex flex-col gap-1 text-xs text-text-secondary text-right">
          <span><span class="font-medium text-text-primary">DOB:</span> {{ formatDate(student.date_of_birth) }}</span>
          <span><span class="font-medium text-text-primary">Year Level:</span> {{ student.year_level?.description ?? '—' }}</span>
          <span v-if="student.primary_disability"><span class="font-medium text-text-primary">Disability:</span> {{ student.primary_disability }}</span>
        </div>
      </div>

      <!-- Tabs: Notes / Strategies -->
      <Tabs
        default-value="notes"
        class="flex flex-col flex-1 overflow-hidden p-4"
      >
        <TabsList class="shrink-0">
          <TabsTrigger value="notes">Notes</TabsTrigger>
          <TabsTrigger value="strategies">Strategies</TabsTrigger>
        </TabsList>

        <!-- Notes tab — flex-col lives on the inner div, not TabsContent, so Radix's hidden attribute isn't overridden -->
        <TabsContent value="notes" class="flex-1 min-h-0 mt-3">
          <div class="flex flex-col h-full overflow-hidden">
            <NotesList :notes="notes" :loading="loadingNotes" />
            <NoteComposer
              v-if="canAddNotes"
              :student-id="student.id"
              :class-id="classId"
              @saved="emit('noteAdded')"
            />
          </div>
        </TabsContent>

        <!-- Strategies tab — no display-setting classes on TabsContent itself -->
        <TabsContent value="strategies" class="flex-1 overflow-y-auto mt-3">
          <StrategiesView />
        </TabsContent>
      </Tabs>
    </template>
  </Card>
</template>
