<template>
  <div class="flex">
    <AdminSidebar />
    <div class="flex-1 p-8">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">📝 题目管理</h1>
        <button @click="showCreateForm = true" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">
          + 新建题目
        </button>
      </div>

      <!-- 搜索 -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
        <input v-model="keyword" @keyup.enter="fetchChallenges" placeholder="搜索题目..."
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
      </div>

      <!-- 表格 -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">标题</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">分类</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">难度</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">分值</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">解题</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">状态</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">操作</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="ch in challenges" :key="ch.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-500">#{{ ch.id }}</td>
              <td class="px-4 py-3 text-sm font-medium">{{ ch.title }}</td>
              <td class="px-4 py-3 text-sm">{{ ch.category_name }}</td>
              <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full font-bold" :class="`diff-${ch.difficulty}`">{{ ch.difficulty }}</span></td>
              <td class="px-4 py-3 text-center font-bold text-amber-600">{{ ch.score }}</td>
              <td class="px-4 py-3 text-center text-sm">{{ ch.solve_count }}</td>
              <td class="px-4 py-3 text-center"><span :class="ch.is_active ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium">{{ ch.is_active ? '已发布' : '已隐藏' }}</span></td>
              <td class="px-4 py-3 text-right">
                <button @click="editChallenge(ch)" class="text-primary-600 hover:text-primary-700 text-sm mr-2">编辑</button>
                <button @click="deleteChallenge(ch.id)" class="text-red-500 hover:text-red-600 text-sm">删除</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- 新建/编辑弹窗（简版） -->
      <div v-if="showForm" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" @click.self="showForm = false">
        <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <h2 class="text-xl font-bold mb-4">{{ editingId ? '编辑题目' : '新建题目' }}</h2>
          <form @submit.prevent="saveChallenge" class="space-y-4">
            <input v-model="form.title" placeholder="标题" required class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
            <select v-model="form.category_id" required class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
              <option value="">选择分类</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
            <select v-model="form.difficulty" class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
              <option value="easy">简单</option>
              <option value="medium">中等</option>
              <option value="hard">困难</option>
              <option value="expert">专家</option>
            </select>
            <textarea v-model="form.description" placeholder="题目描述（支持 Markdown）" rows="6" required class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500"></textarea>
            <div class="grid grid-cols-2 gap-4">
              <input v-model="form.score" type="number" placeholder="分值" required class="px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
              <input v-model="form.flag" placeholder="Flag" :required="!editingId" class="px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500 font-mono" />
            </div>
            <input v-model="form.flag_hint" placeholder="Flag 格式提示（可选）" class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
            <input v-model="form.attachment_url" placeholder="附件 URL（可选）" class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showForm = false" class="px-4 py-2 border rounded-lg text-sm">取消</button>
              <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">{{ editingId ? '更新' : '创建' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import AdminSidebar from '@/components/AdminSidebar.vue'

const challenges = ref([])
const categories = ref([])
const keyword = ref('')
const showForm = ref(false)
const editingId = ref(null)
const form = ref({ title: '', category_id: '', difficulty: 'easy', description: '', score: 100, flag: '', flag_hint: '', attachment_url: '' })

async function fetchChallenges() {
  try {
    const params = { per_page: 100 }
    if (keyword.value) params.keyword = keyword.value
    const res = await api.get('/admin/challenges', { params })
    challenges.value = res.data?.items || []
  } catch (e) {
    console.error(e)
  }
}

async function fetchCategories() {
  try {
    const res = await api.get('/admin/categories')
    categories.value = res.data || []
  } catch (e) { console.error(e) }
}

function editChallenge(ch) {
  editingId.value = ch.id
  form.value = { title: ch.title, category_id: ch.category_id, difficulty: ch.difficulty, description: ch.description, score: ch.score, flag: '', flag_hint: ch.flag_hint, attachment_url: ch.attachment_url }
  showForm.value = true
}

async function saveChallenge() {
  try {
    if (editingId.value) {
      await api.put(`/admin/challenges?id=${editingId.value}`, form.value)
    } else {
      await api.post('/admin/challenges', form.value)
    }
    showForm.value = false
    editingId.value = null
    form.value = { title: '', category_id: '', difficulty: 'easy', description: '', score: 100, flag: '', flag_hint: '', attachment_url: '' }
    fetchChallenges()
  } catch (e) {
    alert(e.message || '操作失败')
  }
}

async function deleteChallenge(id) {
  if (!confirm('确定删除该题目吗？')) return
  try {
    await api.delete(`/admin/challenges?id=${id}`)
    fetchChallenges()
  } catch (e) {
    alert(e.message || '删除失败')
  }
}

onMounted(() => { fetchChallenges(); fetchCategories() })
</script>
