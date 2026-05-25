<template>
  <div class="flex">
    <AdminSidebar />
    <div class="flex-1 p-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-6">👥 用户管理</h1>

      <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
        <input v-model="keyword" @keyup.enter="fetchUsers" placeholder="搜索用户名或邮箱..."
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">用户名</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">邮箱</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">角色</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">分数</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">解题</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">状态</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">操作</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-500">#{{ u.id }}</td>
              <td class="px-4 py-3 text-sm font-medium">{{ u.username }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ u.email }}</td>
              <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-0.5 rounded-full" :class="u.role === 'admin' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'">{{ u.role }}</span></td>
              <td class="px-4 py-3 text-center font-bold text-amber-600">{{ u.score }}</td>
              <td class="px-4 py-3 text-center text-sm">{{ u.solved_count }}</td>
              <td class="px-4 py-3 text-center">
                <button @click="toggleActive(u)" :class="u.is_active ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium hover:underline">
                  {{ u.is_active ? '正常' : '已禁用' }}
                </button>
              </td>
              <td class="px-4 py-3 text-right">
                <button @click="toggleAdmin(u)" class="text-primary-600 hover:text-primary-700 text-sm mr-2">{{ u.role === 'admin' ? '取消管理员' : '设为管理员' }}</button>
                <button @click="deleteUser(u.id)" class="text-red-500 hover:text-red-600 text-sm">删除</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import AdminSidebar from '@/components/AdminSidebar.vue'

const users = ref([])
const keyword = ref('')

async function fetchUsers() {
  const params = { per_page: 100 }
  if (keyword.value) params.keyword = keyword.value
  const res = await api.get('/admin/users', { params })
  users.value = res.data?.items || []
}

async function toggleActive(u) {
  try {
    await api.put(`/admin/users?id=${u.id}`, { is_active: u.is_active ? 0 : 1 })
    u.is_active = !u.is_active
  } catch (e) { alert(e.message) }
}

async function toggleAdmin(u) {
  const newRole = u.role === 'admin' ? 'user' : 'admin'
  try {
    await api.put(`/admin/users?id=${u.id}`, { role: newRole })
    u.role = newRole
  } catch (e) { alert(e.message) }
}

async function deleteUser(id) {
  if (!confirm('确定删除该用户吗？')) return
  try {
    await api.delete(`/admin/users?id=${id}`)
    fetchUsers()
  } catch (e) { alert(e.message) }
}

onMounted(fetchUsers)
</script>
