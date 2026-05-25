<template>
  <aside class="w-64 bg-dark-800 text-white min-h-screen p-4">
    <div class="text-lg font-bold mb-6 flex items-center gap-2">
      <span class="text-amber-400">⚙</span>
      <span>管理后台</span>
    </div>

    <nav class="space-y-1">
      <router-link v-for="item in menuItems" :key="item.path" :to="item.path"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition"
        :class="isActive(item.path) ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-dark-700 hover:text-white'">
        <span>{{ item.icon }}</span>
        <span>{{ item.label }}</span>
      </router-link>
    </nav>

    <div class="mt-8 pt-6 border-t border-dark-700">
      <router-link to="/"
        class="flex items-center gap-2 px-3 py-2 text-sm text-gray-400 hover:text-white transition">
        ← 返回前台
      </router-link>
    </div>
  </aside>
</template>

<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()
const menuItems = [
  { path: '/admin', label: '仪表盘', icon: '📊' },
  { path: '/admin/challenges', label: '题目管理', icon: '📝' },
  { path: '/admin/categories', label: '分类管理', icon: '📁' },
  { path: '/admin/users', label: '用户管理', icon: '👥' },
  { path: '/admin/notices', label: '公告管理', icon: '📢' },
]

function isActive(path) {
  return route.path === path
}
</script>
