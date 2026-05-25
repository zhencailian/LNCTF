<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">👥 队伍管理</h1>

    <!-- 未加入队伍 -->
    <div v-if="!myTeam">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 创建队伍 -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="font-semibold text-gray-900 mb-4">创建队伍</h2>
          <form @submit.prevent="createTeam" class="space-y-3">
            <input v-model="createForm.name" placeholder="队伍名称" required maxlength="32"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
            <textarea v-model="createForm.description" placeholder="队伍简介（可选）" rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500"></textarea>
            <button type="submit" :disabled="creating"
              class="w-full py-2 bg-primary-600 hover:bg-primary-700 disabled:bg-gray-300 text-white rounded-lg text-sm font-medium">
              {{ creating ? '创建中...' : '创建队伍' }}
            </button>
          </form>
          <div v-if="createMsg" class="mt-2 text-sm" :class="createMsgType === 'success' ? 'text-green-600' : 'text-red-600'">
            {{ createMsg }}
          </div>
        </div>

        <!-- 加入队伍 -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="font-semibold text-gray-900 mb-4">加入队伍</h2>
          <form @submit.prevent="joinTeam" class="space-y-3">
            <input v-model="joinForm.code" placeholder="输入邀请码" required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500 font-mono" />
            <button type="submit" :disabled="joining"
              class="w-full py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 text-white rounded-lg text-sm font-medium">
              {{ joining ? '加入中...' : '加入队伍' }}
            </button>
          </form>
          <div v-if="joinMsg" class="mt-2 text-sm" :class="joinMsgType === 'success' ? 'text-green-600' : 'text-red-600'">
            {{ joinMsg }}
          </div>
        </div>
      </div>
    </div>

    <!-- 已加入队伍 -->
    <div v-else>
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-start justify-between mb-6">
          <div>
            <h2 class="text-xl font-bold text-gray-900">{{ myTeam.name }}</h2>
            <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
              <span>👥 {{ myTeam.member_count }}/{{ myTeam.max_members }} 人</span>
              <span>🏆 {{ myTeam.score }} 分</span>
              <span>👑 队长: {{ myTeam.owner_name }}</span>
            </div>
          </div>
          <div class="text-right text-sm">
            <div class="text-gray-400">邀请码</div>
            <div class="font-mono font-bold text-primary-600 bg-primary-50 px-3 py-1 rounded mt-1">{{ myTeam.invite_code }}</div>
          </div>
        </div>

        <!-- 成员列表 -->
        <h3 class="font-semibold text-gray-900 mb-3">成员</h3>
        <div class="space-y-2">
          <div v-for="m in myTeam.members" :key="m.id"
            class="flex items-center justify-between py-2 px-3 rounded-lg bg-gray-50">
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-sm font-bold">
                {{ m.username.charAt(0).toUpperCase() }}
              </span>
              <div>
                <router-link :to="`/profile/${m.id}`" class="font-medium text-gray-900 hover:text-primary-600">{{ m.username }}</router-link>
                <span v-if="m.role === 'owner'" class="ml-2 text-xs px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full">队长</span>
              </div>
            </div>
            <div class="text-sm text-gray-400">{{ m.score }} 分</div>
          </div>
        </div>

        <!-- 操作按钮 -->
        <div class="mt-6 flex gap-3">
          <button v-if="myTeam.is_owner" @click="dismissTeam" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm">
            解散队伍
          </button>
          <button v-if="!myTeam.is_owner" @click="leaveTeam" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm">
            退出队伍
          </button>
        </div>
      </div>
      <div v-if="teamMsg" class="mt-3 text-sm p-3 rounded-lg" :class="teamMsgType === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
        {{ teamMsg }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'

const auth = useAuthStore()

const myTeam = ref(null)

// 创建表单
const createForm = ref({ name: '', description: '' })
const creating = ref(false)
const createMsg = ref('')
const createMsgType = ref('success')

// 加入表单
const joinForm = ref({ code: '' })
const joining = ref(false)
const joinMsg = ref('')
const joinMsgType = ref('success')

// 操作消息
const teamMsg = ref('')
const teamMsgType = ref('success')

async function createTeam() {
  creating.value = true
  createMsg.value = ''
  try {
    await api.post('/teams/create', createForm.value)
    createMsg.value = '队伍创建成功！'
    createMsgType.value = 'success'
    createForm.value = { name: '', description: '' }
    await loadTeam()
  } catch (e) {
    createMsg.value = e.message || '创建失败'
    createMsgType.value = 'error'
  } finally {
    creating.value = false
  }
}

async function joinTeam() {
  joining.value = true
  joinMsg.value = ''
  try {
    await api.post('/teams/join', { invite_code: joinForm.value.code })
    joinMsg.value = '加入队伍成功！'
    joinMsgType.value = 'success'
    joinForm.value = { code: '' }
    await loadTeam()
  } catch (e) {
    joinMsg.value = e.message || '加入失败'
    joinMsgType.value = 'error'
  } finally {
    joining.value = false
  }
}

async function leaveTeam() {
  if (!confirm('确定要退出队伍吗？')) return
  try {
    await api.post('/teams/leave')
    teamMsg.value = '已退出队伍'
    teamMsgType.value = 'success'
    myTeam.value = null
  } catch (e) {
    teamMsg.value = e.message || '退出失败'
    teamMsgType.value = 'error'
  }
}

async function dismissTeam() {
  if (!confirm('确定要解散队伍吗？此操作不可撤销！')) return
  try {
    await api.post('/teams/dismiss')
    teamMsg.value = '队伍已解散'
    teamMsgType.value = 'success'
    myTeam.value = null
  } catch (e) {
    teamMsg.value = e.message || '解散失败'
    teamMsgType.value = 'error'
  }
}

async function loadTeam() {
  try {
    const res = await api.get('/teams/info')
    myTeam.value = res.data
  } catch {
    myTeam.value = null
  }
}

onMounted(loadTeam)
</script>
