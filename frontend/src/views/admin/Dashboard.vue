<template>
  <div class="flex">
    <AdminSidebar />
    <div class="flex-1 p-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-6">📊 管理仪表盘</h1>

      <!-- 统计卡片 -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="text-2xl font-bold text-gray-900">{{ stats?.total_users || 0 }}</div>
          <div class="text-sm text-gray-500">总用户</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="text-2xl font-bold text-primary-600">{{ stats?.active_challenges || 0 }}</div>
          <div class="text-sm text-gray-500">已发布题目</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="text-2xl font-bold text-amber-600">{{ today?.submissions || 0 }}</div>
          <div class="text-sm text-gray-500">今日提交</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="text-2xl font-bold text-green-600">{{ today?.solves || 0 }}</div>
          <div class="text-sm text-gray-500">今日解题</div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 分类统计 -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="font-semibold text-gray-900 mb-4">📂 分类题目统计</h2>
          <div v-for="cat in categoryStats" :key="cat.name" class="flex items-center justify-between py-2 text-sm">
            <span class="text-gray-600">{{ cat.name }}</span>
            <span class="font-medium">{{ cat.count }} 题</span>
          </div>
        </div>

        <!-- 最近提交 -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="font-semibold text-gray-900 mb-4">最近提交</h2>
          <div v-for="s in (recentSubmissions || [])" :key="s.id" class="flex items-center justify-between py-2 text-sm border-b border-gray-100 last:border-0">
            <div>
              <span class="font-medium">{{ s.username }}</span>
              <span class="text-gray-500"> - {{ s.challenge_title }}</span>
            </div>
            <span :class="s.is_correct ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium">
              {{ s.is_correct ? '✅ 正确' : '❌ 错误' }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import AdminSidebar from '@/components/AdminSidebar.vue'

const stats = ref(null)
const today = ref(null)
const categoryStats = ref([])
const recentSubmissions = ref([])

onMounted(async () => {
  try {
    const res = await api.get('/admin/dashboard')
    const d = res.data
    stats.value = d.stats
    today.value = d.today
    categoryStats.value = d.category_stats
    recentSubmissions.value = d.recent_submissions
  } catch (e) {
    console.error('仪表盘加载失败:', e)
  }
})
</script>
