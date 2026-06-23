import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    // @ resolves to src/ — matches the alias in tsconfig.app.json so that
    // both TypeScript and Vite resolve @/components/... identically.
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})
