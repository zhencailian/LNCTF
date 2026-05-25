<template>
  <div class="flex">
    <AdminSidebar />
    <div class="flex-1 p-8">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">📢 公告管理</h1>
        <button @click="startCreate" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">+ 发布公告</button>
      </div>

      <div class="space-y-3">
        <div v-for="n in notices" :key="n.id" class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <span v-if="n.is_pinned" class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-medium">置顶</span>
                <h3 class="font-semibold text-gray-900">{{ n.title }}</h3>
              </div>
              <p class="text-sm text-gray-500 mt-2">{{ n.content }}</p>
              <div class="text-xs text-gray-400 mt-2">{{ n.created_by_name }} · {{ n.created_at }}</div>
            </div>
            <div class="flex gap-2 ml-4">
              <button @click="startEdit(n)" class="text-primary-600 hover:text-primary-700 text-sm">编辑</button>
              <button @click="deleteNotice(n.id)" class="text-red-500 hover:text-red-600 text-sm">删除</button>
            </div>
          </div>
        </div>
        <div v-if="notices.length === 0" class="text-center py-10 text-gray-400">暂无公告</div>
      </div>

      <!-- 表单弹窗 -->
      <div v-if="showForm" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" @click.self="showForm = false">
        <div class="bg-white rounded-xl p-6 w-full max-w-lg">
          <h2 class="text-xl font-bold mb-4">{{ editingId ? '编辑公告' : '发布公告' }}</h2>
          <form @submit.prevent="save" class="space-y-4">
            <input v-model="form.title" placeholder="公告标题" required class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
            <textarea v-model="form.content" placeholder="公告内容" rows="4" required class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500"></textarea>
            <label class="flex items-center gap-2 text-sm"><input v-model="form.is_pinned" type="checkbox" class="rounded" /> 置顶</label>
            <div class="flex justify-end gap-3">
              <button type="button" @click="showForm = false" class="px-4 py-2 border rounded-lg text-sm">取消</button>
              <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">{{ editingId ? '更新' : '发布' }}</button>
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

const notices = ref([])
const showForm = ref(false)
const editingId = ref(null)
const form = ref({ title: '', content: '', is_pinned: false })

async function fetchNotices() {
  const res = await api.get('/admin/notices')
  notices.value = res.data || []
}

function startCreate() { editingId.value = null; form.value = { title: '', content: '', is_pinned: false }; showForm.value = true }
function startEdit(n) { editingId.value = n.id; form.value = { title: n.title, content: n.content, is_pinned: !!n.is_pinned }; showForm.value = true }

async function save() {
  try {
    if (editingId.value) { await api.put(`/admin/notices?id=${editingId.value}`, form.value) }
    else { await api.post('/admin/notices', form.value) }
    showForm.value = false; fetchNotices()
  } catch (e) { alert(e.message) }
}

async function deleteNotice(id) {
  if (!confirm('确定删除？')) return
  try { await api.delete(`/admin/notices?id=${id}`); fetchNotices() }
  catch (e) { alert(e.message) }
}

onMounted(fetchNotices)
</script>
