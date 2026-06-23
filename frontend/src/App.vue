<script setup lang="ts">
// RouterView renders whichever page component the current route maps to.
// Sonner is mounted here so the toast renderer is available on every page.
import Sonner from '@/components/ui/Sonner.vue'
</script>

<template>
  <RouterView />
  <Sonner />
</template>
