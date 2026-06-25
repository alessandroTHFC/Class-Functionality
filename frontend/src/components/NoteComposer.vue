<script setup lang="ts">
import { ref } from 'vue'
import { Send } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import Textarea from '@/components/ui/Textarea.vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import { useClassDetail } from '@/composables/useClassDetail'

const props = defineProps<{
  studentId: number
  classId: number
}>()

const emit = defineEmits<{
  saved: []
}>()

const { saveNote } = useClassDetail()

const noteText = ref('')
const noteDate = ref(new Date().toISOString().slice(0, 10))  // default to today
const saving = ref(false)
const error = ref<string | null>(null)

async function handleSave(): Promise<void> {
  const text = noteText.value.trim()
  if (!text) {
    error.value = 'Note cannot be empty.'
    return
  }

  saving.value = true
  error.value = null

  await saveNote({
    student_ids: [props.studentId],
    class_id: props.classId,
    note_text: text,
    note_date: noteDate.value,
    confidentiality_level: null,
  })
    .then(() => {
      noteText.value = ''
      toast.success('Note saved.')
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
  <div class="border-t border-brand-border pt-3 shrink-0">
    <!-- Date picker row -->
    <div class="flex items-center gap-2 mb-2">
      <label class="text-xs text-text-secondary shrink-0">Note date</label>
      <Input
        v-model="noteDate"
        type="date"
        class="w-40 text-sm"
      />
    </div>

    <!-- Textarea -->
    <Textarea
      v-model="noteText"
      placeholder="Add a note..."
      :rows="3"
      @keydown.enter.ctrl="handleSave"
    />

    <!-- Inline error -->
    <p v-if="error" class="text-xs text-danger-text mt-1">{{ error }}</p>

    <!-- Submit button -->
    <div class="flex justify-end mt-2">
      <Button :disabled="saving" @click="handleSave">
        <Send class="w-4 h-4 mr-1.5" />
        {{ saving ? 'Saving…' : 'Save Note' }}
      </Button>
    </div>
  </div>
</template>
