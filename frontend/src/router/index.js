import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/Register.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/challenges',
    name: 'Challenges',
    component: () => import('@/views/Challenges.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/challenges/:id',
    name: 'ChallengeDetail',
    component: () => import('@/views/ChallengeDetail.vue'),
    meta: { requiresAuth: true },
    props: true,
  },
  {
    path: '/leaderboard',
    name: 'Leaderboard',
    component: () => import('@/views/Leaderboard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/teams',
    name: 'Teams',
    component: () => import('@/views/Teams.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/views/Profile.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/profile/:id',
    name: 'UserProfile',
    component: () => import('@/views/Profile.vue'),
    meta: { requiresAuth: true },
    props: true,
  },
  {
    path: '/writeups',
    name: 'Writeups',
    component: () => import('@/views/Writeups.vue'),
    meta: { requiresAuth: true },
  },
  // ---- Admin ----
  {
    path: '/admin',
    name: 'AdminDashboard',
    component: () => import('@/views/admin/Dashboard.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/admin/challenges',
    name: 'AdminChallenges',
    component: () => import('@/views/admin/Challenges.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/admin/users',
    name: 'AdminUsers',
    component: () => import('@/views/admin/Users.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/admin/categories',
    name: 'AdminCategories',
    component: () => import('@/views/admin/Categories.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/admin/notices',
    name: 'AdminNotices',
    component: () => import('@/views/admin/Notices.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 路由守卫
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const userStr = localStorage.getItem('user')

  console.log(`[Route] ${from.path} → ${to.path}, token: ${token ? token.substring(0, 20) + '...' : 'null'}`)

  if (to.meta.requiresAuth && !token) {
    console.log('[Route] 需要登录，重定向到 /login')
    next('/login')
    return
  }

  if (to.meta.guest && token) {
    console.log('[Route] 已登录，从 /login 重定向到 /')
    next('/')
    return
  }

  if (to.meta.requiresAdmin) {
    if (!userStr) {
      next('/login')
      return
    }
    try {
      const user = JSON.parse(userStr)
      if (user.role !== 'admin') {
        next('/')
        return
      }
    } catch {
      next('/login')
      return
    }
  }

  next()
})

export default router
