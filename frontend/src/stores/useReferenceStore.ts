import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/lib/axios'
import type { YearLevel, UserSummary } from '@/types'

export const useReferenceStore = defineStore('reference', () => {
  // These are seeded/rarely-changing lookups shared by the filter bar and form dialog.
  const yearLevels = ref<YearLevel[]>([])
  const users = ref<UserSummary[]>([])

  // Once loaded we skip the API calls on subsequent navigations to the dashboard.
  const loaded = ref(false)

  /**
   * Fetches year levels from GET /api/year_levels and stores the result.
   * Response shape: { data: YearLevel[] }
   */
  async function loadYearLevels(): Promise<void> {
    await api.get<{ data: YearLevel[] }>('/year_levels')
      .then((response) => {
        if (response && response.data) {
          yearLevels.value = response.data.data
        }
      })
  }

  /**
   * Fetches staff users from GET /api/users and stores the result.
   * Response shape: { data: UserSummary[] }
   */
  async function loadUsers(): Promise<void> {
    await api.get<{ data: UserSummary[] }>('/users')
      .then((response) => {
        if (response && response.data) {
          users.value = response.data.data
        }
      })
  }

  /**
   * Fires both requests in parallel on first dashboard mount.
   * Safe to call on every mount — exits immediately after the first successful load.
   */
  async function load(): Promise<void> {
    if (loaded.value) return

    await Promise.all([loadYearLevels(), loadUsers()])
    loaded.value = true
  }

  return { yearLevels, users, loaded, load }
})
