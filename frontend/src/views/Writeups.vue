<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">📝 WriteUp</h1>
    </div>

    <div v-if="loading" class="text-center py-16">
      <div class="spinner inline-block w-8 h-8 border-3 border-primary-500 border-t-transparent rounded-full"></div>
      <p class="text-gray-400 mt-3">加载中...</p>
    </div>

    <div v-else-if="writeups.length === 0" class="text-center py-16 text-gray-400">
      <p class="text-4xl mb-3">📝</p>
      <p>暂无公开的 WriteUp</p>
      <p class="text-sm mt-2">解题后可以提交你的 WriteUp</p>
    </div>

    <div v-else class="space-y-4">
      <div v-for="w in writeups" :key="w.id"
        class="bg-white rounded-xl border border-gray-200 p-5 card-hover">
        <div class="flex items-start justify-between">
          <div>
            <router-link :to="`/challenges/${w.challenge_id}`" class="font-semibold text-gray-900 hover:text-primary-600">
              {{ w.challenge_title }}
            </router-link>
            <div class="flex items-center gap-2 mt-1 text-sm text-gray-500">
              <span>✍️ {{ w.author_name }}</span>
              <span>{{ w.created_at }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const writeups = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await api.get('/writeups/list', { params: { per_page: 50 } })
    writeups.value = res.data?.items || []
  } catch (e) {
    console.error('WriteUp 加载失败:', e)
  } finally {
    loading.value = false
  }
})
</script>
