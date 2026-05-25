<template>
  <nav class="bg-dark-800 text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex items-center justify-between h-16">
        <!-- Logo -->
        <router-link to="/" class="flex items-center gap-2 text-xl font-bold hover:text-primary-400 transition">
          <span class="text-primary-500">⚔</span>
          <span>LNCTF</span>
        </router-link>

        <!-- 导航链接 -->
        <div class="hidden md:flex items-center gap-1" v-if="auth.isLoggedIn">
          <router-link to="/challenges" class="nav-link" active-class="nav-link-active">
            🏴 题目
          </router-link>
          <router-link to="/leaderboard" class="nav-link" active-class="nav-link-active">
            🏆 排行榜
          </router-link>
          <router-link to="/teams" class="nav-link" active-class="nav-link-active">
            👥 队伍
          </router-link>
          <router-link to="/writeups" class="nav-link" active-class="nav-link-active">
            📝 WriteUp
          </router-link>
          <router-link v-if="auth.isAdmin" to="/admin" class="nav-link nav-link-admin" active-class="nav-link-active">
            ⚙ 管理
          </router-link>
        </div>

        <!-- 用户信息 -->
        <div class="flex items-center gap-3">
          <template v-if="auth.isLoggedIn">
            <router-link to="/profile" class="flex items-center gap-2 text-sm hover:text-primary-400 transition">
              <span class="w-7 h-7 rounded-full bg-primary-600 flex items-center justify-center text-xs font-bold">
                {{ auth.username.charAt(0).toUpperCase() }}
              </span>
              <span class="hidden sm:inline">{{ auth.username }}</span>
            </router-link>
            <button @click="handleLogout" class="text-sm text-gray-400 hover:text-red-400 transition">
              退出
            </button>
          </template>
          <template v-else>
            <router-link to="/login" class="text-sm text-gray-300 hover:text-white transition">登录</router-link>
            <router-link to="/register" class="text-sm bg-primary-600 hover:bg-primary-700 px-4 py-1.5 rounded-lg transition">
              注册
            </router-link>
          </template>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
.nav-link {
  @apply px-3 py-2 rounded-lg text-sm text-gray-300 hover:text-white hover:bg-dark-700 transition;
}
.nav-link-active {
  @apply text-white bg-primary-600;
}
.nav-link-admin {
  @apply border border-amber-500/30 text-amber-400 hover:bg-amber-500/10;
}
</style>
