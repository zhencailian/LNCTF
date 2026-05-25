<template>
  <div v-if="loading" class="text-center py-16">
    <div class="spinner inline-block w-8 h-8 border-3 border-primary-500 border-t-transparent rounded-full"></div>
    <p class="text-gray-400 mt-3">加载中...</p>
  </div>

  <div v-else-if="challenge" class="max-w-4xl mx-auto">
    <!-- 返回 -->
    <router-link to="/challenges" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-primary-600 mb-4">
      ← 返回题目列表
    </router-link>

    <!-- 标题区 -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
      <div class="flex items-start justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ challenge.title }}</h1>
          <div class="flex items-center gap-3 mt-2 text-sm">
            <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
              {{ challenge.category_icon }} {{ challenge.category_name }}
            </span>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="`diff-${challenge.difficulty}`">
              {{ difficultyLabel }}
            </span>
            <span class="text-gray-400">基础分: {{ challenge.base_score }}</span>
            <span v-if="challenge.dynamic_score !== challenge.base_score" class="text-amber-500 font-medium">
              当前分: {{ challenge.dynamic_score }}
            </span>
            <span class="text-gray-400">🔥 {{ challenge.solve_count }} 人解出</span>
          </div>
        </div>
        <div v-if="solved" class="px-4 py-2 bg-green-50 text-green-700 border border-green-200 rounded-lg text-sm font-medium">
          ✅ 已解题
        </div>
      </div>

      <!-- 一血信息 -->
      <div v-if="challenge.first_blood" class="mt-3 text-xs text-amber-600">
        🏆 一血: {{ challenge.first_blood }}
      </div>

      <!-- Flag 格式提示 -->
      <div v-if="challenge.flag_hint" class="mt-3 text-xs text-gray-400">
        Flag 格式: {{ challenge.flag_hint }}
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- 左侧：题目描述 -->
      <div class="lg:col-span-2 space-y-6">
        <!-- 题目描述 -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="font-semibold text-gray-900 mb-3">📄 题目描述</h2>
          <div class="markdown-body text-gray-700" v-html="renderedDescription"></div>
        </div>

        <!-- 附件 -->
        <div v-if="challenge.attachment_url" class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="font-semibold text-gray-900 mb-3">📎 附件</h2>
          <a :href="challenge.attachment_url" target="_blank" download
            class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 font-medium">
            <span>⬇</span>
            <span>下载附件</span>
          </a>
        </div>

        <!-- Docker 在线环境 -->
        <div v-if="challenge.is_dockerized" class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="font-semibold text-gray-900 mb-3">🔗 在线环境</h2>
          <div class="p-3 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-500 mb-2">容器状态: 开发中</p>
            <button disabled class="px-4 py-2 bg-gray-200 text-gray-400 rounded-lg text-sm">启动容器（即将上线）</button>
          </div>
        </div>

        <!-- 标签 -->
        <div v-if="challenge.tags && challenge.tags.length" class="flex flex-wrap gap-2">
          <span v-for="tag in challenge.tags" :key="tag"
            class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">
            #{{ tag }}
          </span>
        </div>
      </div>

      <!-- 右侧：Flag 提交 -->
      <div>
        <FlagSubmit :challenge-id="challenge.id" :solved="solved" @solved="onSolved" />
      </div>
    </div>
  </div>

  <div v-else class="text-center py-16 text-gray-400">
    <p class="text-4xl mb-3">🔍</p>
    <p>题目不存在或未发布</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useChallengeStore } from '@/stores/challenges'
import FlagSubmit from '@/components/FlagSubmit.vue'
import { marked } from 'marked'

const route = useRoute()
const store = useChallengeStore()

const challenge = ref(null)
const loading = ref(true)
const solved = ref(false)

const difficultyLabel = computed(() => {
  const map = { easy: '简单', medium: '中等', hard: '困难', expert: '专家' }
  return map[challenge.value?.difficulty] || challenge.value?.difficulty
})

const renderedDescription = computed(() => {
  if (!challenge.value?.description) return ''
  return marked(challenge.value.description, { breaks: true })
})

function onSolved(data) {
  solved.value = true
  challenge.value.solved = true
  challenge.value.solve_count++
  // 更新动态分值（展示用，精确值下次刷新时从后端获取）
  if (challenge.value.base_score && challenge.value.solve_count > 0) {
    const factor = Math.pow(0.8, challenge.value.solve_count / 5)
    challenge.value.dynamic_score = Math.max(1, Math.round(challenge.value.base_score * factor))
  }
}

onMounted(async () => {
  try {
    const id = route.params.id
    const data = await store.fetchChallengeDetail(id)
    challenge.value = data
    solved.value = data.solved
  } catch (e) {
    console.error('题目加载失败:', e)
  } finally {
    loading.value = false
  }
})
</script>
