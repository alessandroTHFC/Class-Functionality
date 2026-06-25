<script setup lang="ts">
// Thin wrapper around vue-sonner's Toaster component.
// Mounted once in App.vue so toasts are available on every page.
// Import toast() from 'vue-sonner' anywhere in the app to fire a notification.
import { Toaster } from 'vue-sonner'
</script>

<template>
  <!-- position: top-right keeps toasts visible above dialog backdrops
       richColors: maps success/error/warning to semantic colours
       toastOptions: overrides the default font to match the app body font -->
  <Toaster
    position="top-right"
    :rich-colors="true"
    :toast-options="{
      style: { fontFamily: 'Inter, sans-serif' },
    }"
  />
</template>
