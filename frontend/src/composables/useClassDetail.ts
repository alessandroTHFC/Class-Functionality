import { ref } from 'vue'
import api from '@/lib/axios'
import type { ClassDetail, StudentNote, StoreNotePayload } from '@/types'

export function useClassDetail() {
  const classDetail = ref<ClassDetail | null>(null)
  const loadingDetail = ref(false)
  const errorDetail = ref<string | null>(null)

  const notes = ref<StudentNote[]>([])
  const loadingNotes = ref(false)

  // ─── Class ───────────────────────────────────────────────────────────────────

  /**
   * Fetches the full class detail from GET /api/classes/{id}.
   * Replaces classDetail.value — never merges.
   */
  async function fetchClass(id: number): Promise<void> {
    loadingDetail.value = true
    errorDetail.value = null

    await api.get<{ data: ClassDetail }>(`/classes/${id}`)
      .then((response) => {
        if (response && response.data) {
          classDetail.value = response.data.data
        }
      })
      .catch(() => {
        errorDetail.value = 'Failed to load class.'
      })
      .finally(() => {
        loadingDetail.value = false
      })
  }

  /**
   * Soft-deletes a class via DELETE /api/classes/{id}.
   * Throws on error so the caller can show feedback.
   */
  async function deleteClass(id: number): Promise<void> {
    await api.delete(`/classes/${id}`)
  }

  // ─── Notes ───────────────────────────────────────────────────────────────────

  /**
   * Fetches all notes for a student via GET /api/students/{studentId}/notes.
   * Notes are ordered by note_date ascending (oldest first) per the API contract.
   * Replaces notes.value — never merges.
   */
  async function fetchNotes(studentId: number): Promise<void> {
    loadingNotes.value = true

    await api.get<{ data: StudentNote[] }>(`/students/${studentId}/notes`)
      .then((response) => {
        if (response && response.data) {
          notes.value = response.data.data
        }
      })
      .catch(() => {
        notes.value = []
      })
      .finally(() => {
        loadingNotes.value = false
      })
  }

  /**
   * Saves a note via POST /api/notes.
   * Throws on error so the caller (NoteComposer, BulkNoteModal) can show feedback.
   */
  async function saveNote(payload: StoreNotePayload): Promise<void> {
    await api.post('/notes', payload)
  }

  return {
    classDetail,
    loadingDetail,
    errorDetail,
    notes,
    loadingNotes,
    fetchClass,
    deleteClass,
    fetchNotes,
    saveNote,
  }
}
