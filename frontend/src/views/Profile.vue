<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">👤 个人中心</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- 左侧：个人信息 -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-gray-200 p-6 text-center">
          <div class="w-20 h-20 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-3xl font-bold mx-auto">
            {{ (profile?.username || '?').charAt(0).toUpperCase() }}
          </div>
          <h2 class="text-xl font-bold mt-4">{{ profile?.username }}</h2>
          <p class="text-gray-500 text-sm">{{ profile?.email }}</p>
          <div class="mt-4 flex justify-center gap-4">
            <div>
              <div class="text-2xl font-bold text-amber-600">{{ profile?.score || 0 }}</div>
              <div class="text-xs text-gray-400">总分</div>
            </div>
            <div>
              <div class="text-2xl font-bold text-green-600">{{ profile?.total_solved || 0 }}</div>
              <div class="text-xs text-gray-400">解题</div>
            </div>
          </div>
          <div v-if="profile?.team_name" class="mt-4 text-sm">
            <span class="text-gray-500">队伍: </span>
            <router-link :to="'/teams'" class="text-primary-600 hover:underline">{{ profile.team_name }}</router-link>
          </div>
        </div>

        <!-- 个人资料编辑 -->
        <div v-if="isOwner" class="bg-white rounded-xl border border-gray-200 p-6 mt-4">
          <h3 class="font-semibold text-gray-900 mb-3">编辑资料</h3>
          <form @submit.prevent="updateProfile" class="space-y-3">
            <input v-model="editForm.username" placeholder="用户名"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
            <input v-model="editForm.email" type="email" placeholder="邮箱"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
            <input v-model="editForm.password" type="password" placeholder="新密码（留空不修改）"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
            <button type="submit" class="w-full py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">
              保存修改
            </button>
          </form>
          <div v-if="updateMsg" class="mt-2 text-sm" :class="updateMsgType === 'success' ? 'text-green-600' : 'text-red-600'">
            {{ updateMsg }}
          </div>
        </div>

        <!-- 分类统计 -->
        <div v-if="profile?.category_stats" class="bg-white rounded-xl border border-gray-200 p-6 mt-4">
          <h3 class="font-semibold text-gray-900 mb-3">📊 分类解题</h3>
          <div v-for="cs in profile.category_stats" :key="cs.category" class="flex items-center justify-between py-1.5 text-sm">
            <span class="text-gray-600">{{ cs.category }}</span>
            <span class="font-medium">{{ cs.count }} 题</span>
          </div>
        </div>
      </div>

      <!-- 右侧：解题记录 -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="font-semibold text-gray-900 mb-4">📋 解题记录</h2>

          <div v-if="solves.length === 0" class="text-center py-8 text-gray-400">
            还没有解题记录，去 <router-link to="/challenges" class="text-primary-600">题目列表</router-link> 开始吧！
          </div>

          <div v-for="s in solves" :key="s.challenge_id"
            class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
            <div>
              <router-link :to="`/challenges/${s.challenge_id}`" class="font-medium text-gray-900 hover:text-primary-600">
                {{ s.title }}
              </router-link>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ s.category_icon }} {{ s.category_name }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full font-bold" :class="`diff-${s.difficulty}`">
                  {{ {easy:'简单',medium:'中等',hard:'困难',expert:'专家'}[s.difficulty] }}
                </span>
              </div>
            </div>
            <div class="text-right">
              <div class="font-bold text-amber-600">+{{ s.score_earned }}</div>
              <div class="text-xs text-gray-400">第 {{ s.solve_order }} 个解出</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'

const route = useRoute()
const auth = useAuthStore()

const profile = ref(null)
const solves = ref([])
const isOwner = computed(() => {
  if (!profile.value) return false
  return profile.value.is_owner
})

// 编辑表单
const editForm = ref({ username: '', email: '', password: '' })
const updateMsg = ref('')
const updateMsgType = ref('success')

async function updateProfile() {
  updateMsg.value = ''
  const data = {}
  if (editForm.value.username) data.username = editForm.value.username
  if (editForm.value.email) data.email = editForm.value.email
  if (editForm.value.password) data.password = editForm.value.password

  if (!Object.keys(data).length) return

  try {
    await api.post('/profile/update', data)
    updateMsg.value = '资料更新成功'
    updateMsgType.value = 'success'
    // 刷新
    const me = await auth.fetchMe()
    profile.value = me
  } catch (e) {
    updateMsg.value = e.message || '更新失败'
    updateMsgType.value = 'error'
  }
}

onMounted(async () => {
  try {
    const userId = route.params.id
    const params = userId ? { id: userId } : {}
    const res = await api.get('/profile/index', { params })
    profile.value = res.data

    // 回填编辑表单
    if (profile.value.is_owner) {
      editForm.value.username = profile.value.username || ''
      editForm.value.email = profile.value.email || ''
    }

    // 解题记录
    const solvesRes = await api.get('/profile/solves', { params: { user_id: userId } })
    solves.value = solvesRes.data?.items || []
  } catch (e) {
    console.error('个人中心加载失败:', e)
  }
})
</script>
