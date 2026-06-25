<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { toast } from "vue-sonner";
import {
  BookOpen,
  Users,
  GraduationCap,
  Pencil,
  Trash2,
  Plus,
} from "lucide-vue-next";
import { useAuthStore } from "@/stores/useAuthStore";
import { useReferenceStore } from "@/stores/useReferenceStore";
import { useClasses } from "@/composables/useClasses";
import AppSidebar from "@/components/AppSidebar.vue";
import Button from "@/components/ui/Button.vue";
import Input from "@/components/ui/Input.vue";
import Card from "@/components/ui/Card.vue";
import CardHeader from "@/components/ui/CardHeader.vue";
import CardContent from "@/components/ui/CardContent.vue";
import CardTitle from "@/components/ui/CardTitle.vue";
import Select from "@/components/ui/Select.vue";
import SelectTrigger from "@/components/ui/SelectTrigger.vue";
import SelectContent from "@/components/ui/SelectContent.vue";
import SelectItem from "@/components/ui/SelectItem.vue";
import SelectValue from "@/components/ui/SelectValue.vue";
import Table from "@/components/ui/Table.vue";
import TableHeader from "@/components/ui/TableHeader.vue";
import TableBody from "@/components/ui/TableBody.vue";
import TableRow from "@/components/ui/TableRow.vue";
import TableHead from "@/components/ui/TableHead.vue";
import TableCell from "@/components/ui/TableCell.vue";
import ClassFormDialog from "@/components/ClassFormDialog.vue";
import Skeleton from "@/components/ui/Skeleton.vue";
import type { ClassListItem } from "@/types";

const authStore = useAuthStore();
const referenceStore = useReferenceStore();

const { canCreate, canEdit, canDelete } = authStore;

const {
  classList,
  meta,
  loading,
  error,
  search,
  yearLevelId,
  userId,
  page,
  fetchClasses,
  deleteClass,
} = useClasses();

// ─── Dialog state ─────────────────────────────────────────────────────────────

const showDialog = ref(false);
const editTarget = ref<ClassListItem | null>(null);

function openCreateDialog() {
  editTarget.value = null;
  showDialog.value = true;
}

function openEditDialog(cls: ClassListItem) {
  editTarget.value = cls;
  showDialog.value = true;
}

function closeDialog() {
  showDialog.value = false;
  editTarget.value = null;
}

// editTarget is read before closeDialog() clears it to determine the toast message.
function onSaved() {
  const wasEditing = !!editTarget.value;
  closeDialog();
  fetchClasses();
  toast.success(
    wasEditing ? "Class updated successfully." : "Class created successfully.",
  );
}

// ─── Delete ───────────────────────────────────────────────────────────────────

function promptDelete(cls: ClassListItem) {
  toast(`Please confirm you want to delete Class "${cls.name}"`, {
    duration: Infinity,
    action: { label: "Yes, delete", onClick: () => handleDelete(cls) },
    cancel: { label: "No" },
  });
}

async function handleDelete(cls: ClassListItem) {
  await deleteClass(cls.id)
    .then(() => {
      fetchClasses();
      toast.success(`"${cls.name}" deleted.`);
    })
    .catch(() => toast.error("Failed to delete class."));
}

// ─── Filters ──────────────────────────────────────────────────────────────────

// Radix Vue Select requires non-empty string values. 'all' is the sentinel for null (no filter).
// These computed wrappers convert between the composable's number|null and Select's string.
const yearLevelSelect = computed({
  get: () => (yearLevelId.value !== null ? String(yearLevelId.value) : "all"),
  set: (val: string) => {
    yearLevelId.value = val === "all" ? null : Number(val);
  },
});

const userSelect = computed({
  get: () => (userId.value !== null ? String(userId.value) : "all"),
  set: (val: string) => {
    userId.value = val === "all" ? null : Number(val);
  },
});

const hasActiveFilters = computed(
  () => !!search.value || !!yearLevelId.value || !!userId.value,
);

function clearFilters() {
  search.value = "";
  yearLevelId.value = null;
  userId.value = null;
  page.value = 1;
  fetchClasses();
}

// Debounce search — waits 300ms after typing stops before re-fetching.
let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    page.value = 1;
    fetchClasses();
  }, 300);
});

// Dropdowns reset to page 1 and re-fetch immediately on change.
watch(yearLevelId, () => {
  page.value = 1;
  fetchClasses();
});
watch(userId, () => {
  page.value = 1;
  fetchClasses();
});
watch(page, () => fetchClasses());

// ─── Mount ────────────────────────────────────────────────────────────────────

onMounted(async () => {
  await Promise.all([referenceStore.load(), fetchClasses()]);
});
</script>

<template>
  <AppSidebar>
    <div class="max-w-7xl mx-auto px-6 py-8">
      <!-- Page header -->
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">
          Class Dashboard
        </h1>
        <Button v-if="canCreate" @click="openCreateDialog">
          <Plus class="w-4 h-4 mr-1.5" />
          New Class
        </Button>
      </div>

      <!-- Stat cards -->
      <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-3">
        <!-- Skeleton state -->
        <template v-if="loading">
          <Card v-for="i in 3" :key="i">
            <CardHeader>
              <Skeleton class="h-4 w-28" />
              <Skeleton class="h-9 w-9" />
            </CardHeader>
            <CardContent>
              <Skeleton class="h-8 w-16" />
            </CardContent>
          </Card>
        </template>

        <!-- Loaded state -->
        <template v-else>
          <Card>
            <CardHeader>
              <CardTitle>Total Classes</CardTitle>
              <div class="p-2 bg-purple-bg rounded-sm">
                <BookOpen class="w-5 h-5 text-purple-text" />
              </div>
            </CardHeader>
            <CardContent>
              <p class="text-2xl font-bold text-text-primary">
                {{ meta?.total ?? "—" }}
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Total Students</CardTitle>
              <div class="p-2 bg-purple-bg rounded-sm">
                <Users class="w-5 h-5 text-purple-text" />
              </div>
            </CardHeader>
            <CardContent>
              <p class="text-2xl font-bold text-text-primary">
                {{ meta?.summary?.total_students ?? "—" }}
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Teachers Assigned</CardTitle>
              <div class="p-2 bg-purple-bg rounded-sm">
                <GraduationCap class="w-5 h-5 text-purple-text" />
              </div>
            </CardHeader>
            <CardContent>
              <p class="text-2xl font-bold text-text-primary">
                {{ meta?.summary?.teachers_assigned ?? "—" }}
              </p>
            </CardContent>
          </Card>
        </template>
      </div>

      <!-- Filter bar — 12-column grid: search(5) | year level(3) | staff(3) | clear(1) -->
      <div class="grid grid-cols-12 items-center gap-7 mb-4">
        <div class="col-span-5">
          <Input v-model="search" placeholder="Search classes…" />
        </div>

        <div class="col-span-3">
          <Select v-model="yearLevelSelect">
            <SelectTrigger>
              <SelectValue placeholder="All year levels" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All year levels</SelectItem>
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

        <div class="col-span-3">
          <Select v-model="userSelect">
            <SelectTrigger>
              <SelectValue placeholder="All staff" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All staff</SelectItem>
              <SelectItem
                v-for="user in referenceStore.users"
                :key="user.id"
                :value="String(user.id)"
              >
                {{ user.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="col-span-1 flex items-center">
          <Button
            :disabled="!hasActiveFilters"
            variant="default"
            class="p-5"
            @click="clearFilters"
          >
            Clear
          </Button>
        </div>
      </div>

      <!-- Classes table -->
      <Card class="overflow-hidden">
        <!-- Loading skeleton -->
        <Table v-if="loading">
          <TableHeader>
            <TableRow class="hover:bg-transparent">
              <TableHead>Name</TableHead>
              <TableHead>Year Level</TableHead>
              <TableHead>Staff</TableHead>
              <TableHead>Students</TableHead>
              <TableHead class="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="i in 5" :key="i">
              <TableCell><Skeleton class="h-4 w-40" /></TableCell>
              <TableCell><Skeleton class="h-4 w-20" /></TableCell>
              <TableCell><Skeleton class="h-4 w-36" /></TableCell>
              <TableCell><Skeleton class="h-4 w-8" /></TableCell>
              <TableCell>
                <div class="flex justify-end gap-1">
                  <Skeleton class="h-8 w-8" />
                  <Skeleton class="h-8 w-8" />
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <!-- Error -->
        <div
          v-else-if="error"
          class="py-16 text-center text-sm text-danger-text"
        >
          {{ error }}
        </div>

        <!-- Empty -->
        <div v-else-if="classList.length === 0" class="py-16 text-center">
          <p class="text-sm text-text-secondary mb-1">No classes found.</p>
          <button
            class="text-sm text-teal hover:underline"
            @click="openCreateDialog"
          >
            Create your first class.
          </button>
        </div>

        <!-- Table -->
        <Table v-else>
          <TableHeader>
            <TableRow class="hover:bg-transparent">
              <TableHead>Name</TableHead>
              <TableHead>Year Level</TableHead>
              <TableHead>Staff</TableHead>
              <TableHead>Students</TableHead>
              <TableHead class="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="cls in classList" :key="cls.id">
              <TableCell>
                <RouterLink
                  :to="{ name: 'class-detail', params: { id: cls.id } }"
                  class="text-sm font-medium text-teal hover:text-teal-hover hover:underline"
                >
                  {{ cls.name }}
                </RouterLink>
              </TableCell>
              <TableCell class="text-sm text-text-primary">
                {{ cls.year_level?.description ?? "—" }}
              </TableCell>
              <TableCell class="text-sm text-text-secondary">
                {{
                  cls.assigned_users.length
                    ? cls.assigned_users.map((u) => u.name).join(", ")
                    : "—"
                }}
              </TableCell>
              <TableCell class="text-sm text-text-primary">
                {{ cls.student_count }}
              </TableCell>
              <TableCell>
                <div class="flex items-center justify-end gap-1">
                  <Button
                    v-if="canEdit"
                    variant="ghost"
                    size="icon"
                    title="Edit class"
                    class="text-purple-text bg-purple-bg"
                    @click="openEditDialog(cls)"
                  >
                    <Pencil class="w-4 h-4" />
                  </Button>
                  <Button
                    v-if="canDelete"
                    variant="ghost"
                    size="icon"
                    title="Delete class"
                    class="text-danger-text bg-danger-bg"
                    @click="promptDelete(cls)"
                  >
                    <Trash2 class="w-4 h-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </Card>

      <!-- Pagination -->
      <div
        v-if="meta && meta.last_page > 1"
        class="flex items-center justify-between mt-4"
      >
        <p class="text-sm text-text-secondary">
          Page {{ meta.current_page }} of {{ meta.last_page }} ·
          {{ meta.total }} classes
        </p>
        <div class="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            :disabled="page <= 1"
            @click="page--"
          >
            Previous
          </Button>
          <Button
            variant="outline"
            size="sm"
            :disabled="page >= (meta?.last_page ?? 1)"
            @click="page++"
          >
            Next
          </Button>
        </div>
      </div>
    </div>

    <!-- Class create/edit dialog -->
    <ClassFormDialog
      :open="showDialog"
      :class-item="editTarget"
      @close="closeDialog"
      @saved="onSaved"
    />
  </AppSidebar>
</template>
