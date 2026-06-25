<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { toast } from 'vue-sonner'
import { X } from 'lucide-vue-next'
import Dialog from '@/components/ui/Dialog.vue'
import DialogContent from '@/components/ui/DialogContent.vue'
import DialogHeader from '@/components/ui/DialogHeader.vue'
import DialogTitle from '@/components/ui/DialogTitle.vue'
import DialogFooter from '@/components/ui/DialogFooter.vue'
import DialogClose from '@/components/ui/DialogClose.vue'
import Checkbox from '@/components/ui/Checkbox.vue'
import Textarea from '@/components/ui/Textarea.vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import ScrollArea from '@/components/ui/ScrollArea.vue'
import { useClassDetail } from '@/composables/useClassDetail'
import type { StudentDetail } from '@/types'

const props = defineProps<{
  open: boolean
  students: StudentDetail[]
  classId: number
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  saved: []
}>()

const { saveNote } = useClassDetail()

const selectedIds = ref<number[]>([])
const noteText = ref('')
const noteDate = ref(new Date().toISOString().slice(0, 10))
const saving = ref(false)
const error = ref<string | null>(null)

// Reset form state each time the dialog opens.
watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      selectedIds.value = []
      noteText.value = ''
      noteDate.value = new Date().toISOString().slice(0, 10)
      error.value = null
    }
  },
)

// Whether all students are currently selected.
const allSelected = computed(() => selectedIds.value.length === props.students.length)

function toggleAll(): void {
  selectedIds.value = allSelected.value ? [] : props.students.map((s) => s.id)
}

function toggleStudent(id: number): void {
  const idx = selectedIds.value.indexOf(id)
  if (idx === -1) {
    selectedIds.value = [...selectedIds.value, id]
  } else {
    selectedIds.value = selectedIds.value.filter((i) => i !== id)
  }
}

async function handleSave(): Promise<void> {
  if (selectedIds.value.length === 0) {
    error.value = 'Select at least one student.'
    return
  }
  if (!noteText.value.trim()) {
    error.value = 'Note cannot be empty.'
    return
  }

  saving.value = true
  error.value = null

  await saveNote({
    student_ids: selectedIds.value,
    class_id: props.classId,
    note_text: noteText.value.trim(),
    note_date: noteDate.value,
    confidentiality_level: null,
  })
    .then(() => {
      toast.success(`Note saved for ${selectedIds.value.length} student(s).`)
      emit('update:open', false)
      emit('saved')
    })
    .catch(() => {
      error.value = 'Failed to save note. Please try again.'
    })
    .finally(() => {
      saving.value = false
    })
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-lg flex flex-col max-h-[80vh]">
      <!-- Header -->
      <DialogHeader>
        <DialogTitle>Add Note for Multiple Students</DialogTitle>
        <DialogClose>
          <button class="text-white/70 hover:text-white transition-colors" aria-label="Close">
            <X class="w-5 h-5" />
          </button>
        </DialogClose>
      </DialogHeader>

      <!-- Body -->
      <div class="flex flex-col gap-4 px-6 py-4 flex-1 overflow-hidden">
        <!-- Select All toggle -->
        <div class="flex items-center gap-2">
          <Checkbox
            :checked="allSelected"
            @update:checked="toggleAll"
          />
          <span class="text-sm font-medium text-text-primary cursor-pointer select-none" @click="toggleAll">
            Select All ({{ selectedIds.length }} / {{ students.length }} selected)
          </span>
        </div>

        <!-- Student list -->
        <ScrollArea class="border border-brand-border rounded-sm h-48">
          <div
            v-for="student in students"
            :key="student.id"
            class="flex items-center gap-3 px-4 py-2.5 hover:bg-app-bg cursor-pointer"
            @click="toggleStudent(student.id)"
          >
            <Checkbox
              :checked="selectedIds.includes(student.id)"
              @update:checked="toggleStudent(student.id)"
            />
            <span class="text-sm text-text-primary">{{ student.full_name }}</span>
            <span v-if="student.nccd_level" class="ml-auto text-xs text-text-secondary">
              {{ student.nccd_level }}
            </span>
          </div>
        </ScrollArea>

        <!-- Note date -->
        <div class="flex items-center gap-3">
          <label class="text-sm text-text-secondary shrink-0 w-20">Note date</label>
          <Input v-model="noteDate" type="date" class="flex-1 text-sm" />
        </div>

        <!-- Note text -->
        <Textarea
          v-model="noteText"
          placeholder="Write the note here..."
          :rows="4"
        />

        <!-- Inline error -->
        <p v-if="error" class="text-xs text-danger-text -mt-2">{{ error }}</p>
      </div>

      <!-- Footer -->
      <DialogFooter>
        <DialogClose>
          <Button variant="outline">Cancel</Button>
        </DialogClose>
        <Button :disabled="saving" @click="handleSave">
          {{ saving ? 'Saving…' : `Save Note for ${selectedIds.length} Student(s)` }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
