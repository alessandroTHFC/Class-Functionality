<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { FileText, Pencil, Trash2 } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/useAuthStore'
import { useClassDetail } from '@/composables/useClassDetail'
import AppSidebar from '@/components/AppSidebar.vue'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import StudentListPanel from '@/components/StudentListPanel.vue'
import StudentProfilePanel from '@/components/StudentProfilePanel.vue'
import BulkNoteModal from '@/components/BulkNoteModal.vue'
import ClassFormDialog from '@/components/ClassFormDialog.vue'
import type { ClassListItem } from '@/types'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const classId = computed(() => Number(route.params.id))

const {
  classDetail,
  loadingDetail,
  errorDetail,
  notes,
  loadingNotes,
  fetchClass,
  deleteClass,
  fetchNotes,
} = useClassDetail()

// ─── Role-based visibility ────────────────────────────────────────────────────

const canEdit = computed(() =>
  authStore.hasRole('school-admin', 'coordinator', 'teacher'),
)
const canDelete = computed(() =>
  authStore.hasRole('school-admin', 'coordinator'),
)
const canAddNotes = computed(() =>
  authStore.hasRole('school-admin', 'coordinator', 'teacher', 'teachers-assistant'),
)

// ─── Selected student ─────────────────────────────────────────────────────────

const selectedStudentId = ref<number | null>(null)

const selectedStudent = computed(() =>
  classDetail.value?.students.find((s) => s.id === selectedStudentId.value) ?? null,
)

// Auto-select the first student once the class loads.
watch(classDetail, (detail) => {
  if (detail && detail.students.length > 0 && selectedStudentId.value === null) {
    selectedStudentId.value = detail.students[0].id
  }
})

// Fetch notes whenever the selected student changes.
watch(selectedStudentId, (id) => {
  if (id !== null) fetchNotes(id)
})

function selectStudent(id: number): void {
  selectedStudentId.value = id
}

// ─── Stat helpers ─────────────────────────────────────────────────────────────

// Count of students that have any NCCD level recorded.
const nccdCount = computed(() => {
  const students = classDetail.value?.students ?? []
  return students.filter((s) => s.nccd_level !== null).length
})

const nccdPercent = computed(() => {
  const total = classDetail.value?.students.length ?? 0
  if (total === 0) return 0
  return Math.round((nccdCount.value / total) * 100)
})

// ─── Edit dialog ──────────────────────────────────────────────────────────────

const showEditDialog = ref(false)

// ClassFormDialog expects a ClassListItem shape for edit mode.
// We project classDetail into that shape on the fly.
const editTarget = computed<ClassListItem | null>(() => {
  if (!classDetail.value) return null
  return {
    id: classDetail.value.id,
    name: classDetail.value.name,
    year_level: classDetail.value.year_level,
    created_by: classDetail.value.created_by,
    assigned_users: classDetail.value.assigned_users,
    student_count: classDetail.value.students.length,
  }
})

function onClassSaved(): void {
  showEditDialog.value = false
  // Re-fetch to pick up any name/year-level/staff/student changes.
  fetchClass(classId.value)
  toast.success('Class updated successfully.')
}

// ─── Bulk note modal ──────────────────────────────────────────────────────────

const showBulkNoteModal = ref(false)

// Re-fetch notes for the currently selected student after any note save (single or bulk).
function refreshNotes(): void {
  if (selectedStudentId.value !== null) {
    fetchNotes(selectedStudentId.value)
  }
}

// ─── Delete ───────────────────────────────────────────────────────────────────

const deleting = ref(false)

function promptDelete(): void {
  const name = classDetail.value?.name ?? 'this class'
  toast(`Delete "${name}"? This cannot be undone.`, {
    duration: Infinity,
    action: { label: 'Yes, delete', onClick: handleDelete },
    cancel: { label: 'Cancel' },
  })
}

async function handleDelete(): Promise<void> {
  deleting.value = true
  await deleteClass(classId.value)
    .then(() => {
      toast.success('Class deleted successfully.')
      router.push({ name: 'classes' })
    })
    .catch(() => {
      toast.error('Failed to delete class.')
    })
    .finally(() => {
      deleting.value = false
    })
}

// ─── Mount ────────────────────────────────────────────────────────────────────

onMounted(() => fetchClass(classId.value))
</script>

<template>
  <AppSidebar>
    <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col h-full">
      <!-- Breadcrumb -->
      <nav class="flex items-center gap-1.5 text-sm text-text-secondary mb-4">
        <RouterLink to="/classes" class="hover:text-teal transition-colors">Classes</RouterLink>
        <span>›</span>
        <span class="text-text-primary font-medium">
          {{ loadingDetail ? '…' : (classDetail?.name ?? 'Class') }}
        </span>
      </nav>

      <!-- Page title + actions -->
      <div class="flex items-start justify-between mb-6">
        <div>
          <template v-if="loadingDetail">
            <Skeleton class="h-7 w-56 mb-2" />
            <Skeleton class="h-4 w-40" />
          </template>
          <template v-else>
            <h1 class="text-2xl font-semibold text-text-primary">{{ classDetail?.name }}</h1>
            <p v-if="classDetail?.assigned_users.length" class="text-sm text-teal mt-0.5">
              Assigned Staff: {{ classDetail.assigned_users.map((u) => u.name).join(', ') }}
            </p>
          </template>
        </div>

        <!-- Action buttons (role-gated) -->
        <div class="flex items-center gap-2 shrink-0">
          <Button
            v-if="canAddNotes"
            variant="default"
            :disabled="!classDetail"
            @click="showBulkNoteModal = true"
          >
            <FileText class="w-4 h-4 mr-1.5" />
            Add Multiple Notes
          </Button>

          <Button
            v-if="canEdit"
            variant="outline"
            :disabled="!classDetail"
            @click="showEditDialog = true"
          >
            <Pencil class="w-4 h-4 mr-1.5" />
            Edit Class
          </Button>

          <Button
            v-if="canDelete"
            variant="outline"
            class="text-danger-text border-danger-text hover:bg-danger-bg"
            :disabled="!classDetail || deleting"
            @click="promptDelete"
          >
            <Trash2 class="w-4 h-4 mr-1.5" />
            Delete Class
          </Button>
        </div>
      </div>

      <!-- Error state -->
      <div v-if="errorDetail" class="py-12 text-center text-sm text-danger-text">
        {{ errorDetail }}
      </div>

      <template v-else>
        <!-- Stat row: 3 columns (students | NCCD | class info) -->
        <div class="grid grid-cols-3 gap-4 mb-6">
          <!-- Total students -->
          <Card>
            <CardHeader>
              <CardTitle>Students</CardTitle>
            </CardHeader>
            <CardContent>
              <template v-if="loadingDetail">
                <Skeleton class="h-8 w-12" />
              </template>
              <template v-else>
                <p class="text-2xl font-bold text-text-primary">{{ classDetail?.students.length ?? '—' }}</p>
                <p class="text-xs text-text-secondary mt-0.5">Enrolled in this class</p>
              </template>
            </CardContent>
          </Card>

          <!-- NCCD students -->
          <Card>
            <CardHeader>
              <CardTitle>NCCD Students</CardTitle>
            </CardHeader>
            <CardContent>
              <template v-if="loadingDetail">
                <Skeleton class="h-8 w-12" />
              </template>
              <template v-else>
                <p class="text-2xl font-bold text-text-primary">{{ nccdCount }}</p>
                <p class="text-xs text-text-secondary mt-0.5">{{ nccdPercent }}% of class</p>
              </template>
            </CardContent>
          </Card>

          <!-- Class info block -->
          <Card>
            <CardHeader>
              <CardTitle>Class Info</CardTitle>
            </CardHeader>
            <CardContent>
              <template v-if="loadingDetail">
                <Skeleton class="h-4 w-32 mb-2" />
                <Skeleton class="h-4 w-24" />
              </template>
              <template v-else>
                <p class="text-sm text-text-primary">
                  <span class="text-text-secondary">Year Level: </span>
                  {{ classDetail?.year_level?.description ?? '—' }}
                </p>
                <p class="text-sm text-text-primary mt-1">
                  <span class="text-text-secondary">Last Updated: </span>
                  {{ classDetail?.updated_at ?? '—' }}
                </p>
              </template>
            </CardContent>
          </Card>
        </div>

        <!-- Two-pane layout: student list (40%) | student profile (60%) -->
        <!-- flex-1 + min-h-0 allows the panes to scroll independently without overflow -->
        <div class="grid grid-cols-5 gap-6 flex-1 min-h-0">
          <!-- Student List Panel (40%) -->
          <div class="col-span-2 min-h-0">
            <StudentListPanel
              :students="classDetail?.students ?? []"
              :selected-id="selectedStudentId"
              :loading="loadingDetail"
              @select="selectStudent"
            />
          </div>

          <!-- Student Profile Panel (60%) -->
          <div class="col-span-3 min-h-0">
            <StudentProfilePanel
              :student="selectedStudent"
              :class-id="classId"
              :class-name="classDetail?.name ?? ''"
              :notes="notes"
              :loading-notes="loadingNotes"
              :can-add-notes="canAddNotes"
              @note-added="refreshNotes"
            />
          </div>
        </div>
      </template>
    </div>

    <!-- Edit Class dialog — reuses ClassFormDialog in edit mode -->
    <ClassFormDialog
      v-if="showEditDialog"
      :class-item="editTarget"
      :open="showEditDialog"
      @close="showEditDialog = false"
      @saved="onClassSaved"
    />

    <!-- Bulk Note modal -->
    <BulkNoteModal
      :open="showBulkNoteModal"
      :students="classDetail?.students ?? []"
      :class-id="classId"
      @update:open="showBulkNoteModal = $event"
      @saved="refreshNotes"
    />
  </AppSidebar>
</template>
