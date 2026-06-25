import { ref } from 'vue'
import api from '@/lib/axios'
import type { ClassListItem, ClassListMeta, StoreClassPayload, UpdateClassPayload } from '@/types'

export function useClasses() {
  // The current page of classes — replaced entirely on every fetchClasses() call.
  const classList = ref<ClassListItem[]>([])

  // Pagination and tenant-wide summary injected by the backend into the meta block.
  const meta = ref<ClassListMeta | null>(null)

  const loading = ref(false)
  const error = ref<string | null>(null)

  // ─── Filter state ────────────────────────────────────────────────────────────
  // These are watched in ClassDashboard.vue to trigger re-fetches.
  // Changing a filter always resets page back to 1 before calling fetchClasses().

  const search = ref('')
  const yearLevelId = ref<number | null>(null)
  const userId = ref<number | null>(null)
  const page = ref(1)

  // ─── API calls ───────────────────────────────────────────────────────────────

  /**
   * Fetches the class list from GET /api/classes.
   * Builds query params from the current filter state.
   * Completely replaces classList.value — never merges or appends.
   */
  async function fetchClasses(): Promise<void> {
    loading.value = true
    error.value = null

    // Record<string, unknown> — keys are param names (strings), values are mixed types.
    const params: Record<string, unknown> = { page: page.value }
    if (search.value) params.search = search.value
    if (yearLevelId.value) params.year_level_id = yearLevelId.value
    if (userId.value) params.user_id = userId.value

    await api.get<{ data: ClassListItem[]; meta: ClassListMeta }>('/classes', { params })
      .then((response) => {
        if (response && response.data) {
          classList.value = response.data.data
          meta.value = response.data.meta
        }
      })
      .catch(() => {
        error.value = 'Failed to load classes.'
      })
      .finally(() => {
        loading.value = false
      })
  }

  /**
   * Creates a new class via POST /api/classes.
   * Does NOT auto-refresh — the caller (ClassDashboard) calls fetchClasses() explicitly
   * after the dialog emits 'saved', so only one refresh happens per mutation.
   * Throws on error so the form can catch and display validation messages.
   */
  async function createClass(payload: StoreClassPayload): Promise<void> {
    await api.post('/classes', payload)
  }

  /**
   * Updates an existing class via PUT /api/classes/{id}.
   * user_ids and student_ids are synced (not merged) — send the full desired list.
   * Does NOT auto-refresh — the caller handles the refresh.
   * Throws on error so the form can catch and display validation messages.
   */
  async function updateClass(id: number, payload: UpdateClassPayload): Promise<void> {
    await api.put(`/classes/${id}`, payload)
  }

  /**
   * Soft-deletes a class via DELETE /api/classes/{id}.
   * Does NOT auto-refresh — the caller calls fetchClasses() in the .then() chain.
   */
  async function deleteClass(id: number): Promise<void> {
    await api.delete(`/classes/${id}`)
  }

  return {
    classList,
    meta,
    loading,
    error,
    search,
    yearLevelId,
    userId,
    page,
    fetchClasses,
    createClass,
    updateClass,
    deleteClass,
  }
}
