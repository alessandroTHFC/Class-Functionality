import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/useAuthStore'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/pages/LoginPage.vue'),
      // Redirect authenticated users away from the login page.
      meta: { requiresGuest: true },
    },
    {
      path: '/classes',
      name: 'classes',
      component: () => import('@/pages/ClassDashboard.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/classes/:id',
      name: 'class-detail',
      component: () => import('@/pages/ClassDetailPage.vue'),
      meta: { requiresAuth: true },
    },
    // Redirect the root path based on auth state.
    {
      path: '/',
      redirect: '/classes',
    },
  ],
})

// Navigation guard — runs before every route change.
// requiresAuth routes redirect unauthenticated users to /login.
// requiresGuest routes redirect authenticated users to /classes.
router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.requiresGuest && auth.isAuthenticated) {
    return { name: 'classes' }
  }
})

export default router
