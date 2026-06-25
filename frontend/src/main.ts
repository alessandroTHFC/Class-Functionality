import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './style.css'
import 'vue-sonner/style.css'
import App from './App.vue'

const app = createApp(App)

// Pinia must be registered before the router because the navigation guard
// in router/index.ts calls useAuthStore(), which requires Pinia to be active.
app.use(createPinia())
app.use(router)

app.mount('#app')
