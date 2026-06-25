<script setup lang="ts">
import { ref, computed } from 'vue'
import { Search } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import Input from '@/components/ui/Input.vue'
import ScrollArea from '@/components/ui/ScrollArea.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import StudentListItem from '@/components/StudentListItem.vue'
import type { StudentDetail } from '@/types'

const props = defineProps<{
  students: StudentDetail[]
  selectedId: number | null
  loading: boolean
}>()

const emit = defineEmits<{
  select: [id: number]
}>()

const search = ref('')

// Filter students client-side — all enrolled students are loaded with the class detail response.
const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return props.students
  return props.students.filter((s) => s.full_name.toLowerCase().includes(q))
})
</script>

<template>
  <Card class="flex flex-col h-full overflow-hidden">
    <!-- Search input -->
    <div class="p-4 border-b border-brand-border shrink-0">
      <div class="relative">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-secondary pointer-events-none" />
        <Input v-model="search" placeholder="Search students..." class="pl-9" />
      </div>
    </div>

    <!-- Skeleton state — shown while the class detail is loading -->
    <template v-if="loading">
      <div class="flex flex-col flex-1">
        <div v-for="i in 6" :key="i" class="flex items-start gap-3 px-4 py-3">
          <Skeleton class="w-8 h-8 rounded-full shrink-0" />
          <div class="flex-1 space-y-1.5">
            <Skeleton class="h-4 w-3/4" />
            <Skeleton class="h-3 w-1/2" />
          </div>
        </div>
      </div>
    </template>

    <!-- Scrollable student list -->
    <ScrollArea v-else class="flex-1">
      <div v-if="filtered.length === 0" class="py-12 text-center text-sm text-text-secondary">
        No students found.
      </div>
      <StudentListItem
        v-for="student in filtered"
        :key="student.id"
        :student="student"
        :selected="student.id === selectedId"
        @select="emit('select', $event)"
      />
    </ScrollArea>

    <!-- Enrolled count footer -->
    <div class="px-4 py-2 border-t border-brand-border shrink-0">
      <p class="text-xs text-text-secondary">{{ props.students.length }} students enrolled</p>
    </div>
  </Card>
</template>
