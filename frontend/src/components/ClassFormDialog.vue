<script setup lang="ts">
// Modal dialog for creating and editing a class.
// Two-column layout per the frontend design spec:
//   Left  — class name, teacher select, year level select, selected student badges
//   Right — filterable paginated student table with Plus / Check icons
// Teleported to <body> so it renders above the sidebar regardless of DOM position.
import { ref, reactive, computed, watch } from "vue";
import { toast } from "vue-sonner";
import { X, Plus, Check } from "lucide-vue-next";
import api from "@/lib/axios";
import { useReferenceStore } from "@/stores/useReferenceStore";
import { useClasses } from "@/composables/useClasses";
import Button from "@/components/ui/Button.vue";
import Input from "@/components/ui/Input.vue";
import Label from "@/components/ui/Label.vue";
import Select from "@/components/ui/Select.vue";
import SelectTrigger from "@/components/ui/SelectTrigger.vue";
import SelectValue from "@/components/ui/SelectValue.vue";
import SelectContent from "@/components/ui/SelectContent.vue";
import SelectItem from "@/components/ui/SelectItem.vue";
import Table from "@/components/ui/Table.vue";
import TableHeader from "@/components/ui/TableHeader.vue";
import TableBody from "@/components/ui/TableBody.vue";
import TableRow from "@/components/ui/TableRow.vue";
import TableHead from "@/components/ui/TableHead.vue";
import TableCell from "@/components/ui/TableCell.vue";
import Pagination from "@/components/ui/Pagination.vue";
import PaginationPrevious from "@/components/ui/PaginationPrevious.vue";
import PaginationNext from "@/components/ui/PaginationNext.vue";
import Badge from "@/components/ui/Badge.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import type { ClassListItem, ClassDetail, StudentListItem } from "@/types";

const props = defineProps<{
  // Controls visibility — toggled by the parent page.
  open: boolean;
  // null = create mode. Populated ClassListItem = edit mode.
  classItem: ClassListItem | null;
}>();

const emit = defineEmits<{
  // User cancelled or dismissed without saving.
  close: [];
  // API call succeeded — parent calls fetchClasses() and shows a success toast.
  saved: [];
}>();

const referenceStore = useReferenceStore();
const { createClass, updateClass } = useClasses();

// True when a classItem is provided — drives labels, API method, and pre-population.
const isEditing = computed(() => !!props.classItem);

// Staff multi-select: form stores number[] but Select needs string[].
const staffSelect = computed({
  get: (): string[] => form.userIds.map(String),
  set: (val: string | string[]) => {
    form.userIds = (Array.isArray(val) ? val : val ? [val] : []).map(Number);
  },
});

// Trigger display label for the staff multi-select dropdown.
const staffLabel = computed(() => {
  const count = form.userIds.length;
  if (count === 0) return "No staff assigned";
  if (count === 1) {
    return referenceStore.users.find((u) => u.id === form.userIds[0])?.name ?? "1 staff member";
  }
  return `${count} staff members`;
});

// Year level select: form stores number | null but Select needs string.
const yearLevelSelect = computed({
  get: () => (form.yearLevelId !== null ? String(form.yearLevelId) : "none"),
  set: (val: string | string[]) => {
    const v = Array.isArray(val) ? val[0] : val;
    form.yearLevelId = !v || v === "none" ? null : Number(v);
  },
});

// Student picker year level filter — same null ↔ "all" sentinel pattern.
const studentYearLevelSelect = computed({
  get: () => (studentYearLevelFilter.value !== null ? String(studentYearLevelFilter.value) : "all"),
  set: (val: string | string[]) => {
    const v = Array.isArray(val) ? val[0] : val;
    studentYearLevelFilter.value = !v || v === "all" ? null : Number(v);
  },
});

// ─── Form state ───────────────────────────────────────────────────────────────

const form = reactive({
  name: "",
  yearLevelId: null as number | null,
  // Array of selected staff user IDs (multi-select checkboxes).
  userIds: [] as number[],
  // Array of enrolled student IDs — managed by the right-column student picker.
  studentIds: [] as number[],
});

// 422 validation errors from the API — keyed by Laravel field name.
const errors = ref<Record<string, string[]>>({});

// ─── Student list state ───────────────────────────────────────────────────────

// All students for the tenant — loaded once on dialog open; never re-fetched on filter change.
const students = ref<StudentListItem[]>([]);
const studentSearch = ref("");
const studentYearLevelFilter = ref<number | null>(null);
const loadingStudents = ref(false);

// Number of student rows shown per page in the right-column table.
const STUDENTS_PER_PAGE = 10;

// Current page of the student picker table. Resets to 1 whenever filters change.
const studentPage = ref(1);

// Applies name and year level filters client-side — no extra API calls.
// Returns the full list in alphabetical order when both filters are empty.
// The backend already orders by family_name then given_name; the sort here
// preserves that order after filtering changes the slice of the array.
const filteredStudents = computed(() =>
  students.value
    .filter((s) => {
      const matchesName =
        !studentSearch.value ||
        s.full_name.toLowerCase().includes(studentSearch.value.toLowerCase());
      const matchesYearLevel =
        !studentYearLevelFilter.value ||
        s.year_level?.id === studentYearLevelFilter.value;
      return matchesName && matchesYearLevel;
    })
    .sort((a, b) => a.full_name.localeCompare(b.full_name)),
);

// Slices filteredStudents to show only the current page.
const paginatedStudents = computed(() => {
  const start = (studentPage.value - 1) * STUDENTS_PER_PAGE;
  return filteredStudents.value.slice(start, start + STUDENTS_PER_PAGE);
});

// Total number of pages for the current filtered set.
const studentTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredStudents.value.length / STUDENTS_PER_PAGE)),
);

// Reset to page 1 whenever either filter changes so the user always sees results.
watch([studentSearch, studentYearLevelFilter], () => {
  studentPage.value = 1;
});

// Clear field-level errors as soon as the user corrects each mandatory field.
watch(() => form.name, () => { delete errors.value.name });
watch(() => form.userIds, () => { delete errors.value.user_ids }, { deep: true });
watch(() => form.studentIds, () => { delete errors.value.student_ids }, { deep: true });

// Looks up a student name by ID — used to render selected student badges.
function getStudentName(id: number): string {
  return students.value.find((s) => s.id === id)?.full_name ?? `Student ${id}`;
}

// ─── Loading and saving state ─────────────────────────────────────────────────

// True while fetching the full class detail in edit mode.
const loadingDetail = ref(false);

// True while the save API call is in flight.
const saving = ref(false);

// ─── Data loading ─────────────────────────────────────────────────────────────

function resetForm() {
  form.name = "";
  form.yearLevelId = null;
  form.userIds = [];
  form.studentIds = [];
  studentSearch.value = "";
  studentYearLevelFilter.value = null;
  studentPage.value = 1;
  errors.value = {};
}

/**
 * Loads all students for the tenant on dialog open.
 * per_page=100 covers the seeded dataset (~30 students per tenant).
 * The full list is loaded once; all filtering and pagination is handled client-side.
 */
async function loadStudents(): Promise<void> {
  loadingStudents.value = true;
  await api
    .get<{ data: StudentListItem[] }>("/students", {
      params: { per_page: 100 },
    })
    .then((response) => {
      if (response && response.data) {
        students.value = response.data.data;
      }
    })
    .finally(() => {
      loadingStudents.value = false;
    });
}

/**
 * Fetches the full class detail in edit mode to get enrolled student IDs.
 * ClassListItem only exposes student_count — ClassDetail.students[] has the IDs.
 */
async function loadClassDetail(): Promise<void> {
  if (!props.classItem) return;
  loadingDetail.value = true;
  await api
    .get<{ data: ClassDetail }>(`/classes/${props.classItem.id}`)
    .then((response) => {
      if (response && response.data) {
        const detail = response.data.data;
        form.name = detail.name;
        form.yearLevelId = detail.year_level?.id ?? null;
        form.userIds = detail.assigned_users.map((u) => u.id);
        form.studentIds = detail.students.map((s) => s.id);
      }
    })
    .finally(() => {
      loadingDetail.value = false;
    });
}

// Triggers whenever the dialog opens.
// Resets form state, loads the student list, and loads class detail in edit mode.
watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return;
    resetForm();
    await Promise.all([
      loadStudents(),
      isEditing.value ? loadClassDetail() : Promise.resolve(),
    ]);
  },
);

// ─── Student picker ───────────────────────────────────────────────────────────

// Adds a student to the selection — no-op if already enrolled.
function addStudent(studentId: number): void {
  if (!form.studentIds.includes(studentId)) {
    form.studentIds.push(studentId);
  }
}

// Removes a student from the selection — triggered by the badge × button.
function removeStudent(studentId: number): void {
  form.studentIds = form.studentIds.filter((id) => id !== studentId);
}

// ─── Save ─────────────────────────────────────────────────────────────────────

// Runs frontend validation before the API call.
// Populates errors ref with field-level messages and fires a warning toast if invalid.
// Returns true when all mandatory fields pass.
function validate(): boolean {
  const fieldErrors: Record<string, string[]> = {};

  if (!form.name.trim()) {
    fieldErrors.name = ["Class name is required."];
  }
  if (form.userIds.length === 0) {
    fieldErrors.user_ids = ["At least one staff member must be assigned."];
  }
  if (form.studentIds.length === 0) {
    fieldErrors.student_ids = ["At least one student must be enrolled."];
  }

  if (Object.keys(fieldErrors).length > 0) {
    errors.value = fieldErrors;
    toast.warning("Please fill in all mandatory fields before saving.");
    return false;
  }

  return true;
}

/**
 * Calls createClass or updateClass depending on mode.
 * Runs frontend validation first — aborts with a warning toast if invalid.
 * On 422: populates errors ref — displayed inline beneath each field.
 * On success: emits 'saved' — parent handles list refresh and success toast.
 */
async function handleSave(): Promise<void> {
  errors.value = {};

  if (!validate()) return;

  saving.value = true;

  const payload = {
    name: form.name,
    year_level_id: form.yearLevelId,
    user_ids: form.userIds,
    student_ids: form.studentIds,
  };

  const action = isEditing.value
    ? updateClass(props.classItem!.id, payload)
    : createClass(payload);

  await action
    .then(() => emit("saved"))
    .catch((e: any) => {
      if (e.response?.status === 422) {
        errors.value = e.response.data.errors ?? {};
      }
    })
    .finally(() => {
      saving.value = false;
    });
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-start justify-center p-6 pt-16"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="emit('close')" />

        <!-- Dialog card — wide enough for the 2-column layout -->
        <div
          class="relative bg-card-bg rounded-sm border border-brand-border shadow-card w-[95vw] max-w-6xl flex flex-col max-h-[88vh]"
        >
          <!-- Header -->
          <div
            class="flex items-center justify-between px-6 py-4 bg-teal rounded-t-sm flex-shrink-0"
          >
            <h2 class="text-base font-semibold text-white">
              {{ isEditing ? "Edit Class" : "New Class" }}
            </h2>
            <button
              class="p-1 rounded-sm text-white/70 hover:text-white hover:bg-white/10 transition-colors"
              @click="emit('close')"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Loading detail skeleton (edit mode only) -->
          <div v-if="loadingDetail" class="grid grid-cols-12 flex-1 overflow-hidden">
            <!-- Left column skeleton -->
            <div class="col-span-5 flex flex-col gap-5 px-6 py-5 border-r border-brand-border bg-app-bg">
              <div class="space-y-1.5">
                <Skeleton class="h-3.5 w-20" />
                <Skeleton class="h-10 w-full" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                  <Skeleton class="h-3.5 w-24" />
                  <Skeleton class="h-10 w-full" />
                </div>
                <div class="space-y-1.5">
                  <Skeleton class="h-3.5 w-20" />
                  <Skeleton class="h-10 w-full" />
                </div>
              </div>
              <div class="space-y-1.5">
                <Skeleton class="h-3.5 w-32" />
                <div class="flex flex-wrap gap-1.5">
                  <Skeleton class="h-6 w-20 rounded-full" />
                  <Skeleton class="h-6 w-24 rounded-full" />
                  <Skeleton class="h-6 w-16 rounded-full" />
                </div>
              </div>
            </div>
            <!-- Right column skeleton -->
            <div class="col-span-7 flex flex-col px-6 py-5">
              <Skeleton class="h-3.5 w-24 mb-2" />
              <div class="grid grid-cols-5 gap-2 mb-3">
                <Skeleton class="h-10 col-span-3" />
                <Skeleton class="h-10 col-span-2" />
              </div>
              <div class="flex flex-col gap-2">
                <Skeleton v-for="i in 6" :key="i" class="h-12 w-full" />
              </div>
            </div>
          </div>

          <!-- Two-column form body — 12-col grid: left(5) | right(7) -->
          <div v-else class="grid grid-cols-12 flex-1 overflow-hidden">
            <!-- ── Left column — class details ─────────────────────────── -->
            <div
              class="col-span-5 flex flex-col gap-5 px-6 py-5 border-r border-brand-border overflow-y-auto bg-app-bg"
            >
              <!-- Class name -->
              <div class="space-y-1.5">
                <Label for="class-name">Class name</Label>
                <Input
                  id="class-name"
                  v-model="form.name"
                  placeholder="e.g. Year 9 Science"
                  :error="!!errors.name"
                />
                <p v-if="errors.name" class="text-xs text-danger-text">
                  {{ errors.name[0] }}
                </p>
              </div>

              <!-- Assign Staff + Year Level — side by side -->
              <div class="grid grid-cols-2 gap-3">
                <!-- Assign staff multi-select -->
                <div class="space-y-1.5">
                  <Label>Assign staff</Label>
                  <Select v-model="staffSelect" multiple>
                    <SelectTrigger :error="!!errors.user_ids">
                      <SelectValue :placeholder="staffLabel" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="user in referenceStore.users"
                        :key="user.id"
                        :value="String(user.id)"
                      >
                        {{ user.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="errors.user_ids" class="text-xs text-danger-text">
                    {{ errors.user_ids[0] }}
                  </p>
                </div>

                <!-- Year level single select -->
                <div class="space-y-1.5">
                  <Label>Year level</Label>
                  <Select v-model="yearLevelSelect">
                    <SelectTrigger>
                      <SelectValue placeholder="No year level" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">No year level</SelectItem>
                      <SelectItem
                        v-for="yl in referenceStore.yearLevels"
                        :key="yl.id"
                        :value="String(yl.id)"
                      >
                        {{ yl.description }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <!-- Selected student badges -->
              <div class="space-y-1.5">
                <Label>Enrolled students</Label>
                <div class="flex flex-wrap gap-1.5 min-h-[32px]">
                  <Badge
                    v-for="studentId in form.studentIds"
                    :key="studentId"
                    variant="purple"
                  >
                    {{ getStudentName(studentId) }}
                    <button
                      class="hover:text-teal-active ml-0.5"
                      @click="removeStudent(studentId)"
                    >
                      <X class="w-3 h-3" />
                    </button>
                  </Badge>
                  <span
                    v-if="form.studentIds.length === 0"
                    class="text-xs text-text-secondary italic"
                  >
                    No students selected.
                  </span>
                </div>
                <p v-if="errors.student_ids" class="text-xs text-danger-text">
                  {{ errors.student_ids[0] }}
                </p>
              </div>
            </div>

            <!-- ── Right column — student picker ───────────────────────── -->
            <!-- overflow-hidden constrains flex-1 inside to the grid cell height -->
            <div class="col-span-7 flex flex-col px-6 py-5 min-w-0 overflow-hidden">
              <Label class="mb-2">Add students</Label>

              <!-- Filter row — 5-col grid: search (3) + year level (2) -->
              <div class="grid grid-cols-5 gap-2 mb-3">
                <Input
                  v-model="studentSearch"
                  placeholder="Search by name…"
                  class="col-span-3"
                />
                <div class="col-span-2">
                  <Select v-model="studentYearLevelSelect">
                    <SelectTrigger>
                      <SelectValue placeholder="All years" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All years</SelectItem>
                      <SelectItem
                        v-for="yl in referenceStore.yearLevels"
                        :key="yl.id"
                        :value="String(yl.id)"
                      >
                        {{ yl.description }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <!-- Student table — no outer border; overflow-hidden bounds the flex-1 table area -->
              <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Loading skeleton -->
                <Table v-if="loadingStudents">
                  <TableHeader>
                    <TableRow>
                      <TableHead>Name</TableHead>
                      <TableHead>Year Level</TableHead>
                      <TableHead class="text-right">Enrol</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="i in 6" :key="i">
                      <TableCell><Skeleton class="h-4 w-36" /></TableCell>
                      <TableCell><Skeleton class="h-4 w-20" /></TableCell>
                      <TableCell>
                        <div class="flex justify-end">
                          <Skeleton class="h-8 w-8" />
                        </div>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>

                <template v-else>
                  <!-- Scrollable table area -->
                  <div class="flex-1 overflow-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Name</TableHead>
                          <TableHead>Year Level</TableHead>
                          <TableHead class="text-right">Enrol</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        <TableRow
                          v-for="student in paginatedStudents"
                          :key="student.id"
                        >
                          <TableCell class="text-text-primary font-medium">
                            {{ student.full_name }}
                          </TableCell>
                          <TableCell class="text-text-secondary">
                            {{ student.year_level?.description ?? "—" }}
                          </TableCell>
                          <TableCell class="text-right">
                            <!-- Plus icon — not yet enrolled -->
                            <button
                              v-if="!form.studentIds.includes(student.id)"
                              class="p-1 rounded-sm text-teal hover:text-teal-hover hover:bg-teal-light transition-colors"
                              title="Add student"
                              @click="addStudent(student.id)"
                            >
                              <Plus class="w-4 h-4" />
                            </button>
                            <!-- Check icon — already enrolled (non-interactive) -->
                            <span
                              v-else
                              class="p-1 inline-flex text-text-secondary cursor-default"
                            >
                              <Check class="w-4 h-4" />
                            </span>
                          </TableCell>
                        </TableRow>

                        <!-- No results -->
                        <TableRow v-if="paginatedStudents.length === 0">
                          <TableCell
                            colspan="3"
                            class="py-6 text-sm text-text-secondary text-center"
                          >
                            No students match the current filters.
                          </TableCell>
                        </TableRow>
                      </TableBody>
                    </Table>
                  </div>

                  <!-- Pagination — pinned to bottom via flex-shrink-0 -->
                  <div
                    class="flex items-center justify-between px-1 py-2.5 border-t border-brand-border flex-shrink-0"
                  >
                    <p class="text-xs text-text-secondary">
                      Showing
                      {{
                        filteredStudents.length === 0
                          ? 0
                          : (studentPage - 1) * STUDENTS_PER_PAGE + 1
                      }}–{{
                        Math.min(
                          studentPage * STUDENTS_PER_PAGE,
                          filteredStudents.length,
                        )
                      }}
                      of {{ filteredStudents.length }} students
                    </p>
                    <Pagination
                      v-model:page="studentPage"
                      :total="filteredStudents.length"
                      :items-per-page="STUDENTS_PER_PAGE"
                    >
                      <div class="flex items-center gap-1">
                        <PaginationPrevious />
                        <span class="text-xs text-text-secondary px-1">
                          {{ studentPage }} / {{ studentTotalPages }}
                        </span>
                        <PaginationNext />
                      </div>
                    </Pagination>
                  </div>
                </template>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div
            class="flex items-center justify-end gap-2 px-6 py-4 border-t border-brand-border flex-shrink-0"
          >
            <Button variant="outline" :disabled="saving" @click="emit('close')">
              Cancel
            </Button>
            <Button :disabled="saving || loadingDetail" @click="handleSave">
              {{
                saving ? "Saving…" : isEditing ? "Save changes" : "Create class"
              }}
            </Button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.15s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
