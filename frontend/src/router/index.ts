import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/auth/RegisterView.vue'),
      meta: { guest: true },
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/dashboard/DashboardView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/groups',
      name: 'groups',
      component: () => import('@/views/groups/GroupsListView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/groups/new',
      name: 'groups-create',
      component: () => import('@/views/groups/GroupCreateView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/groups/:id',
      name: 'groups-detail',
      component: () => import('@/views/groups/GroupDetailView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/friends',
      name: 'friends',
      component: () => import('@/views/friends/FriendsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/expenses',
      name: 'expenses',
      component: () => import('@/views/expenses/ExpensesListView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/expenses/new',
      name: 'expenses-create',
      component: () => import('@/views/expenses/ExpenseCreateView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/expenses/:id',
      name: 'expenses-detail',
      component: () => import('@/views/expenses/ExpenseDetailView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/settlements',
      name: 'settlements',
      component: () => import('@/views/settlements/SettlementsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/analytics',
      name: 'analytics',
      component: () => import('@/views/analytics/AnalyticsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/activity',
      name: 'activity',
      component: () => import('@/views/activity/ActivityView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/settings',
      name: 'settings',
      component: () => import('@/views/settings/SettingsView.vue'),
      meta: { requiresAuth: true },
    },
  ],
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if (to.meta.guest && authStore.isAuthenticated) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
